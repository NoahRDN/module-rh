<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Formation::with('competences.categorie')
                ->withCount('inscriptions');

            if ($request->has('actif')) {
                $query->where('actif', $request->boolean('actif'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('niveau')) {
                $query->where('niveau', $request->niveau);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('titre', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('organisme', 'LIKE', "%{$search}%");
                });
            }

            $formations = $query->get();

            return response()->json($formations);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération formations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:formations,code',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'duree_heures' => 'nullable|integer|min:1',
                'type' => 'nullable|in:presentiel,distanciel,hybride',
                'niveau' => 'nullable|in:debutant,intermediaire,avance',
                'cout' => 'nullable|numeric|min:0',
                'organisme' => 'nullable|string|max:255',
                'actif' => 'nullable|boolean',
                'competences' => 'nullable|array',
                'competences.*.competence_id' => 'required_with:competences|exists:competences,id',
                'competences.*.niveau_apport' => 'required_with:competences|integer|min:1|max:5',
            ]);

            $formation = Formation::create($validated);

            // Lier les compétences
            if (!empty($validated['competences'])) {
                $syncData = [];
                foreach ($validated['competences'] as $comp) {
                    $syncData[$comp['competence_id']] = ['niveau_apport' => $comp['niveau_apport']];
                }
                $formation->competences()->sync($syncData);
            }

            $formation->load('competences.categorie');

            return response()->json($formation, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création formation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            $formation = Formation::with(['competences.categorie', 'inscriptions.employe'])
                ->findOrFail($id);
            return response()->json($formation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération formation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $formation = Formation::findOrFail($id);

            $validated = $request->validate([
                'code' => 'sometimes|string|unique:formations,code,' . $id,
                'titre' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'duree_heures' => 'nullable|integer|min:1',
                'type' => 'nullable|in:presentiel,distanciel,hybride',
                'niveau' => 'nullable|in:debutant,intermediaire,avance',
                'cout' => 'nullable|numeric|min:0',
                'organisme' => 'nullable|string|max:255',
                'actif' => 'nullable|boolean',
                'competences' => 'nullable|array',
                'competences.*.competence_id' => 'required_with:competences|exists:competences,id',
                'competences.*.niveau_apport' => 'required_with:competences|integer|min:1|max:5',
            ]);

            $formation->update($validated);

            // Mettre à jour les compétences si fournies
            if (isset($validated['competences'])) {
                $syncData = [];
                foreach ($validated['competences'] as $comp) {
                    $syncData[$comp['competence_id']] = ['niveau_apport' => $comp['niveau_apport']];
                }
                $formation->competences()->sync($syncData);
            }

            $formation->load('competences.categorie');

            return response()->json($formation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour formation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $formation = Formation::findOrFail($id);
            $formation->delete();

            return response()->json(['message' => 'Formation supprimée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression formation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
