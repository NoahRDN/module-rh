<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntrepriseSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EntrepriseSettingController extends Controller
{
    public function show()
    {
        return response()->json($this->setting());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', Rule::in(['1', 'true', true, 1])],
        ]);

        $setting = $this->setting();
        $setting->nom = $data['nom'];

        if ($request->boolean('remove_logo') && $setting->logo_path) {
            Storage::disk('public')->delete($setting->logo_path);
            $setting->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $setting->logo_path = $request->file('logo')->store('entreprise', 'public');
        }

        $setting->save();

        return response()->json($setting->fresh());
    }

    private function setting(): EntrepriseSetting
    {
        return EntrepriseSetting::firstOrCreate(
            [],
            ['nom' => config('app.name', 'Module RH')]
        );
    }
}
