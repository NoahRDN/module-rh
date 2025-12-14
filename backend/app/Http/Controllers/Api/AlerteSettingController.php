<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteSetting;
use Illuminate\Http\Request;

class AlerteSettingController extends Controller
{
    /**
     * Liste tous les paramètres d'alertes
     */
    public function index()
    {
        $settings = AlerteSetting::orderBy('libelle')->get();
        return response()->json(['data' => $settings]);
    }

    /**
     * Affiche un paramètre d'alerte
     */
    public function show($id)
    {
        $setting = AlerteSetting::findOrFail($id);
        return response()->json(['data' => $setting]);
    }

    /**
     * Met à jour un paramètre d'alerte
     */
    public function update(Request $request, $id)
    {
        $setting = AlerteSetting::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'actif' => 'sometimes|boolean',
            'seuil_jours' => 'nullable|integer|min:1',
            'seuil_nombre' => 'nullable|integer|min:1',
            'periode_jours' => 'nullable|integer|min:1',
            'niveau' => 'sometimes|in:info,warning,danger',
        ]);

        $setting->update($validated);

        return response()->json([
            'message' => 'Paramètre d\'alerte mis à jour',
            'data' => $setting->fresh(),
        ]);
    }

    /**
     * Récupère un paramètre par son code
     */
    public function getByCode($code)
    {
        $setting = AlerteSetting::where('code', $code)->firstOrFail();
        return response()->json(['data' => $setting]);
    }
}
