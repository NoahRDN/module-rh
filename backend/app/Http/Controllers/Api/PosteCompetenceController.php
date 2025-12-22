<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poste;
use App\Models\PosteCompetence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosteCompetenceController extends Controller
{
    /**
     * Récupérer les compétences requises pour un poste
     */
    public function index($posteId)
    {
        try {
            $poste = Poste::with(['competences.categorie'])->findOrFail($posteId);
            
            $competences = $poste->competences->map(function($competence) {
                return [
                    'id' => $competence->id,
                    'code' => $competence->code,
                    'nom' => $competence->nom,
                    'categorie' => $competence->categorie,
                    'niveau_requis' => $competence->pivot->niveau_requis,
                    'obligatoire' => $competence->pivot->obligatoire,
                    'poids' => $competence->pivot->poids,
                ];
            });

            return response()->json($competences);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Poste non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération compétences poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ajouter ou mettre à jour une compétence requise pour un poste
     */
    public function store(Request $request, $posteId)
    {
        try {
            $poste = Poste::findOrFail($posteId);

            $validated = $request->validate([
                'competence_id' => 'required|exists:competences,id',
                'niveau_requis' => 'required|integer|min:1|max:5',
                'obligatoire' => 'nullable|boolean',
                'poids' => 'nullable|integer|min:1|max:10',
            ]);

            $poste->competences()->syncWithoutDetaching([
                $validated['competence_id'] => [
                    'niveau_requis' => $validated['niveau_requis'],
                    'obligatoire' => $validated['obligatoire'] ?? true,
                    'poids' => $validated['poids'] ?? 1,
                ]
            ]);

            return response()->json(['message' => 'Compétence requise ajoutée/mise à jour'], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Poste non trouvé'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout compétence poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mise à jour en masse des compétences requises pour un poste
     */
    public function bulkUpdate(Request $request, $posteId)
    {
        try {
            $poste = Poste::findOrFail($posteId);

            $validated = $request->validate([
                'competences' => 'required|array',
                'competences.*.competence_id' => 'required|exists:competences,id',
                'competences.*.niveau_requis' => 'required|integer|min:1|max:5',
                'competences.*.obligatoire' => 'nullable|boolean',
                'competences.*.poids' => 'nullable|integer|min:1|max:10',
            ]);

            $syncData = [];
            foreach ($validated['competences'] as $comp) {
                $syncData[$comp['competence_id']] = [
                    'niveau_requis' => $comp['niveau_requis'],
                    'obligatoire' => $comp['obligatoire'] ?? true,
                    'poids' => $comp['poids'] ?? 1,
                ];
            }

            $poste->competences()->sync($syncData);

            return response()->json(['message' => 'Compétences requises mises à jour']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Poste non trouvé'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour compétences poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer une compétence requise d'un poste
     */
    public function destroy($posteId, $competenceId)
    {
        try {
            $poste = Poste::findOrFail($posteId);
            $poste->competences()->detach($competenceId);

            return response()->json(['message' => 'Compétence requise retirée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Poste non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression compétence poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
