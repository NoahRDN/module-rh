<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competence;
use App\Models\NiveauCompetence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompetenceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Competence::with('categorie')
                ->withCount(['employes', 'postes']);

            if ($request->has('actif')) {
                $query->where('actif', $request->boolean('actif'));
            }

            if ($request->has('categorie_id')) {
                $query->where('categorie_id', $request->categorie_id);
            }

            $competences = $query->ordered()->get();

            return response()->json($competences);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération compétences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:competences,code',
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'categorie_id' => 'required|exists:categorie_competences,id',
                'actif' => 'nullable|boolean',
                'ordre' => 'nullable|integer',
            ]);

            $competence = Competence::create($validated);
            $competence->load('categorie');

            return response()->json($competence, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            $competence = Competence::with(['categorie', 'formations'])->findOrFail($id);
            return response()->json($competence);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $competence = Competence::findOrFail($id);

            $validated = $request->validate([
                'code' => 'sometimes|string|unique:competences,code,' . $id,
                'nom' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'categorie_id' => 'sometimes|exists:categorie_competences,id',
                'actif' => 'nullable|boolean',
                'ordre' => 'nullable|integer',
            ]);

            $competence->update($validated);
            $competence->load('categorie');

            return response()->json($competence);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $competence = Competence::findOrFail($id);
            $competence->delete();

            return response()->json(['message' => 'Compétence supprimée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Récupérer les niveaux de compétence
     */
    public function niveaux()
    {
        try {
            $niveaux = NiveauCompetence::ordered()->get();
            return response()->json($niveaux);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération niveaux compétence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Récupérer la cartographie complète des compétences
     */
    public function cartographie()
    {
        try {
            $totalEmployes = \App\Models\Employe::count();
            $employesAvecCompetences = \DB::table('employe_competences')
                ->distinct('employe_id')
                ->count('employe_id');
            $tauxCouverture = $totalEmployes > 0
                ? round(($employesAvecCompetences / $totalEmployes) * 100, 1)
                : 0;

            $categories = \App\Models\CategorieCompetence::actif()
                ->ordered()
                ->with(['competences' => function($q) {
                    $q->actif()->ordered()->withCount(['employes', 'postes']);
                }])
                ->get();

            return response()->json([
                'categories' => $categories,
                'employes_avec_competences' => $employesAvecCompetences,
                'taux_couverture' => $tauxCouverture,
                'total_employes' => $totalEmployes,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération cartographie', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
