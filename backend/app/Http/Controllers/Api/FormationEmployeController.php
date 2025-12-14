<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormationEmploye;
use App\Models\Employe;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormationEmployeController extends Controller
{
    /**
     * Liste des inscriptions aux formations
     */
    public function index(Request $request)
    {
        try {
            $query = FormationEmploye::with(['employe', 'formation']);

            if ($request->has('employe_id')) {
                $query->where('employe_id', $request->employe_id);
            }

            if ($request->has('formation_id')) {
                $query->where('formation_id', $request->formation_id);
            }

            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            $inscriptions = $query->orderBy('created_at', 'desc')->get();

            return response()->json($inscriptions);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération inscriptions formations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Inscrire un employé à une formation
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employe_id' => 'required|exists:employes,id',
                'formation_id' => 'required|exists:formations,id',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date|after_or_equal:date_debut',
                'commentaire' => 'nullable|string',
            ]);

            // Vérifier si l'employé n'est pas déjà inscrit à cette formation
            $existing = FormationEmploye::where('employe_id', $validated['employe_id'])
                ->where('formation_id', $validated['formation_id'])
                ->whereIn('statut', ['planifiee', 'en_cours'])
                ->first();

            if ($existing) {
                return response()->json(['message' => 'L\'employé est déjà inscrit à cette formation'], 422);
            }

            $inscription = FormationEmploye::create([
                'employe_id' => $validated['employe_id'],
                'formation_id' => $validated['formation_id'],
                'statut' => 'planifiee',
                'date_debut' => $validated['date_debut'] ?? null,
                'date_fin' => $validated['date_fin'] ?? null,
                'commentaire' => $validated['commentaire'] ?? null,
                'demande_par' => $request->user()?->id,
            ]);

            $inscription->load(['employe', 'formation']);

            return response()->json($inscription, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur inscription formation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'une inscription
     */
    public function show($id)
    {
        try {
            $inscription = FormationEmploye::with(['employe', 'formation.competences'])
                ->findOrFail($id);
            return response()->json($inscription);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Inscription non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération inscription', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour une inscription (statut, dates, notes)
     */
    public function update(Request $request, $id)
    {
        try {
            $inscription = FormationEmploye::findOrFail($id);

            $validated = $request->validate([
                'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date',
                'note' => 'nullable|numeric|min:0|max:20',
                'commentaire' => 'nullable|string',
                'certificat_obtenu' => 'nullable|boolean',
            ]);

            // Si la formation est terminée, mettre à jour les compétences de l'employé
            if (isset($validated['statut']) && $validated['statut'] === 'terminee' && $inscription->statut !== 'terminee') {
                $validated['valide_par'] = $request->user()?->id;
                $this->updateEmployeCompetences($inscription);
            }

            $inscription->update($validated);
            $inscription->load(['employe', 'formation']);

            return response()->json($inscription);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Inscription non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour inscription', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Annuler une inscription
     */
    public function destroy($id)
    {
        try {
            $inscription = FormationEmploye::findOrFail($id);
            
            if ($inscription->statut === 'terminee') {
                return response()->json(['message' => 'Impossible de supprimer une formation terminée'], 422);
            }

            $inscription->delete();

            return response()->json(['message' => 'Inscription supprimée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Inscription non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression inscription', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour les compétences de l'employé après une formation terminée
     */
    private function updateEmployeCompetences(FormationEmploye $inscription): void
    {
        $employe = $inscription->employe;
        $formation = $inscription->formation;

        foreach ($formation->competences as $competence) {
            $niveauApport = $competence->pivot->niveau_apport;
            
            // Récupérer le niveau actuel de l'employé
            $competenceActuelle = $employe->competences()->where('competence_id', $competence->id)->first();
            $niveauActuel = $competenceActuelle ? $competenceActuelle->pivot->niveau : 0;

            // Mettre à jour seulement si le niveau apporté est supérieur
            if ($niveauApport > $niveauActuel) {
                $employe->competences()->syncWithoutDetaching([
                    $competence->id => [
                        'niveau' => $niveauApport,
                        'date_evaluation' => now(),
                        'commentaire' => "Niveau atteint après formation: {$formation->titre}",
                    ]
                ]);
            }
        }
    }

    /**
     * Historique des formations d'un employé
     */
    public function historiqueEmploye($employeId)
    {
        try {
            $inscriptions = FormationEmploye::with('formation.competences')
                ->where('employe_id', $employeId)
                ->orderBy('date_debut', 'desc')
                ->get();

            return response()->json($inscriptions);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération historique formations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
