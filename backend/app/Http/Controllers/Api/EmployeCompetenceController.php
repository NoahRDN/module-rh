<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\EmployeCompetence;
use App\Models\Competence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeCompetenceController extends Controller
{
    /**
     * Récupérer les compétences d'un employé
     */
    public function index($employeId)
    {
        try {
            $employe = Employe::with(['competences.categorie'])->findOrFail($employeId);
            
            $competences = $employe->competences->map(function($competence) {
                return [
                    'id' => $competence->id,
                    'code' => $competence->code,
                    'nom' => $competence->nom,
                    'categorie' => $competence->categorie,
                    'niveau' => $competence->pivot->niveau,
                    'date_evaluation' => $competence->pivot->date_evaluation,
                    'commentaire' => $competence->pivot->commentaire,
                ];
            });

            return response()->json($competences);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération compétences employé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ajouter ou mettre à jour une compétence pour un employé
     */
    public function store(Request $request, $employeId)
    {
        try {
            $employe = Employe::findOrFail($employeId);

            $validated = $request->validate([
                'competence_id' => 'required|exists:competences,id',
                'niveau' => 'required|integer|min:1|max:5',
                'date_evaluation' => 'nullable|date',
                'commentaire' => 'nullable|string',
            ]);

            $employe->competences()->syncWithoutDetaching([
                $validated['competence_id'] => [
                    'niveau' => $validated['niveau'],
                    'date_evaluation' => $validated['date_evaluation'] ?? now(),
                    'commentaire' => $validated['commentaire'] ?? null,
                    'evalue_par' => $request->user()?->id,
                ]
            ]);

            return response()->json(['message' => 'Compétence ajoutée/mise à jour'], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout compétence employé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mise à jour en masse des compétences d'un employé
     */
    public function bulkUpdate(Request $request, $employeId)
    {
        try {
            $employe = Employe::findOrFail($employeId);

            $validated = $request->validate([
                'competences' => 'required|array',
                'competences.*.competence_id' => 'required|exists:competences,id',
                'competences.*.niveau' => 'required|integer|min:1|max:5',
                'competences.*.commentaire' => 'nullable|string',
            ]);

            $syncData = [];
            foreach ($validated['competences'] as $comp) {
                $syncData[$comp['competence_id']] = [
                    'niveau' => $comp['niveau'],
                    'date_evaluation' => now(),
                    'commentaire' => $comp['commentaire'] ?? null,
                    'evalue_par' => $request->user()?->id,
                ];
            }

            $employe->competences()->sync($syncData);

            return response()->json(['message' => 'Compétences mises à jour']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour compétences employé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer une compétence d'un employé
     */
    public function destroy($employeId, $competenceId)
    {
        try {
            $employe = Employe::findOrFail($employeId);
            $employe->competences()->detach($competenceId);

            return response()->json(['message' => 'Compétence retirée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression compétence employé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Radar des compétences d'un employé par catégorie
     */
    public function radar($employeId)
    {
        try {
            $employe = Employe::with(['competences.categorie'])->findOrFail($employeId);
            
            $radar = $employe->competences
                ->groupBy('categorie.nom')
                ->map(function($competences, $categorie) {
                    $moyenne = $competences->avg('pivot.niveau');
                    return [
                        'categorie' => $categorie,
                        'moyenne' => round($moyenne, 2),
                        'count' => $competences->count(),
                    ];
                })
                ->values();

            return response()->json($radar);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur radar compétences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
