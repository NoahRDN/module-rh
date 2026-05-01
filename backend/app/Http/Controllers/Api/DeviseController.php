<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Models\EntrepriseSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class DeviseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('active')) {
            $active = $request->boolean('active');
            $activeKey = $active ? '1' : '0';

            return response()->json(Cache::remember("settings:devises:active:{$activeKey}", now()->addMinutes(30), function () use ($active) {
                return Devise::query()
                    ->where('active', $active)
                    ->orderByDesc('active')
                    ->orderBy('code')
                    ->get();
            }));
        }

        return response()->json(Cache::remember('settings:devises:all', now()->addMinutes(30), function () {
            return Devise::query()->orderByDesc('active')->orderBy('code')->get();
        }));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:devises,code'],
            'libelle' => ['required', 'string', 'max:100'],
            'symbole' => ['nullable', 'string', 'max:16'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        $devise = Devise::create([
            'code' => $data['code'],
            'libelle' => trim($data['libelle']),
            'symbole' => isset($data['symbole']) ? trim((string) $data['symbole']) : null,
            'active' => (bool) ($data['active'] ?? true),
        ]);
        $this->clearCurrencyCache();

        return response()->json($devise, 201);
    }

    public function show(int $id)
    {
        return response()->json(Devise::findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $devise = Devise::findOrFail($id);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('devises', 'code')->ignore($devise->id),
            ],
            'libelle' => ['required', 'string', 'max:100'],
            'symbole' => ['nullable', 'string', 'max:16'],
            'active' => ['required', 'boolean'],
        ]);

        $newCode = strtoupper(trim($data['code']));
        $oldCode = $devise->code;

        $devise->update([
            'code' => $newCode,
            'libelle' => trim($data['libelle']),
            'symbole' => isset($data['symbole']) ? trim((string) $data['symbole']) : null,
            'active' => (bool) $data['active'],
        ]);

        if ($oldCode !== $newCode) {
            EntrepriseSetting::query()
                ->where('devise', $oldCode)
                ->update(['devise' => $newCode]);
        }
        $this->clearCurrencyCache();

        return response()->json($devise->fresh());
    }

    public function destroy(int $id)
    {
        $devise = Devise::findOrFail($id);

        if (EntrepriseSetting::query()->where('devise', $devise->code)->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer la devise {$devise->code} car elle est utilisée par les paramètres entreprise.",
            ], 422);
        }

        $devise->delete();
        $this->clearCurrencyCache();

        return response()->json(['message' => 'Devise supprimée']);
    }

    private function clearCurrencyCache(): void
    {
        Cache::forget('settings:devises:all');
        Cache::forget('settings:devises:active');
        Cache::forget('settings:devises:active:1');
        Cache::forget('settings:devises:active:0');
        Cache::forget('settings:entreprise');
    }
}
