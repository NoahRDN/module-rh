<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieCompetence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategorieCompetenceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CategorieCompetence::query();

            if ($request->has('actif')) {
                $query->where('actif', $request->boolean('actif'));
            }

            $categories = $query->ordered()
                ->withCount('competences')
                ->get();

            return response()->json($categories);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération catégories compétences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:categorie_competences,code',
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'couleur' => 'nullable|string|max:7',
                'actif' => 'nullable|boolean',
                'ordre' => 'nullable|integer',
            ]);

            $categorie = CategorieCompetence::create($validated);

            return response()->json($categorie, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création catégorie compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            $categorie = CategorieCompetence::with('competences')->findOrFail($id);
            return response()->json($categorie);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération catégorie compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $categorie = CategorieCompetence::findOrFail($id);

            $validated = $request->validate([
                'code' => 'sometimes|string|unique:categorie_competences,code,' . $id,
                'nom' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'couleur' => 'nullable|string|max:7',
                'actif' => 'nullable|boolean',
                'ordre' => 'nullable|integer',
            ]);

            $categorie->update($validated);

            return response()->json($categorie);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour catégorie compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $categorie = CategorieCompetence::findOrFail($id);
            $categorie->delete();

            return response()->json(['message' => 'Catégorie supprimée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression catégorie compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
