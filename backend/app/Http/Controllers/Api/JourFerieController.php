<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JourFerie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JourFerieController extends Controller
{
    public function index()
    {
        try {
            $feries = JourFerie::orderBy('date')->get();
            return response()->json(['data' => $feries]);
        } catch (\Throwable $e) {
            Log::error('Erreur liste jours fériés', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'date' => 'required|date',
            'recurrent' => 'boolean',
        ]);

        try {
            $ferie = JourFerie::create([
                'nom' => $data['nom'],
                'date' => $data['date'],
                'recurrent' => $data['recurrent'] ?? false,
            ]);

            return response()->json(['data' => $ferie], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur création jour férié', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'date' => 'required|date',
            'recurrent' => 'boolean',
        ]);

        try {
            $ferie = JourFerie::findOrFail($id);
            $ferie->update([
                'nom' => $data['nom'],
                'date' => $data['date'],
                'recurrent' => $data['recurrent'] ?? false,
            ]);

            return response()->json(['data' => $ferie]);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour jour férié', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            JourFerie::findOrFail($id)->delete();
            return response()->json(['message' => 'Jour férié supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression jour férié', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
