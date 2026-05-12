<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntrepriseSetting;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class EntrepriseSettingController extends Controller
{
    public function show()
    {
        return response()->json(Cache::remember('settings:entreprise', now()->addMinutes(30), function () {
            return $this->settingPayload($this->setting());
        }));
    }

    public function update(Request $request, SupabaseStorageService $storage)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'devise' => ['required', 'string', 'max:10', Rule::exists('devises', 'code')->where('active', true)],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', Rule::in(['1', 'true', true, 1])],
        ]);

        $setting = $this->setting();
        $setting->nom = $data['nom'];
        $setting->devise = strtoupper($data['devise']);

        if ($request->boolean('remove_logo') && $setting->logo_path) {
            $storage->delete($setting->logo_path);
            $setting->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                $storage->delete($setting->logo_path);
            }

            $setting->logo_path = $storage->upload($request->file('logo'), 'entreprise');
        }

        $setting->save();
        Cache::forget('settings:entreprise');

        return response()->json($this->settingPayload($setting->fresh()));
    }

    private function setting(): EntrepriseSetting
    {
        $setting = EntrepriseSetting::firstOrCreate(
            [],
            [
                'nom' => config('app.name', 'Module RH'),
                'devise' => 'MGA',
            ]
        );

        if (!$setting->devise) {
            $setting->devise = 'MGA';
            $setting->save();
        }

        return $setting;
    }

    private function settingPayload(EntrepriseSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'nom' => $setting->nom,
            'devise' => $setting->devise,
            'logo_path' => $setting->logo_path,
            'logo_url' => $setting->logo_url,
            'created_at' => $setting->created_at,
            'updated_at' => $setting->updated_at,
        ];
    }
}
