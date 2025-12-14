<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Poste;
use App\Services\CompetenceMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MatchingController extends Controller
{
    protected CompetenceMatchingService $matchingService;

    public function __construct(CompetenceMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Calculer la compatibilité entre un employé et un poste
     */
    public function compatibilite(Request $request)
    {
        try {
            $request->validate([
                'employe_id' => 'required|exists:employes,id',
                'poste_id' => 'required|exists:postes,id',
            ]);

            $employe = Employe::with('competences')->findOrFail($request->employe_id);
            $poste = Poste::with('competences')->findOrFail($request->poste_id);

            $result = $this->matchingService->calculerCompatibilite($employe, $poste);

            return response()->json([
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                ],
                'poste' => [
                    'id' => $poste->id,
                    'nom' => $poste->nom,
                ],
                'compatibilite' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur calcul compatibilité', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Trouver les meilleurs candidats pour un poste
     */
    public function candidatsPourPoste($posteId, Request $request)
    {
        try {
            $poste = Poste::with('competences')->findOrFail($posteId);
            $limit = $request->get('limit', 10);

            $candidats = $this->matchingService->trouverCandidats($poste, $limit);

            return response()->json([
                'poste' => [
                    'id' => $poste->id,
                    'nom' => $poste->nom,
                    'departement' => $poste->departement?->nom,
                ],
                'candidats' => $candidats,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Poste non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur recherche candidats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Trouver les postes compatibles pour un employé
     */
    public function postesCompatibles($employeId, Request $request)
    {
        try {
            $employe = Employe::with('competences')->findOrFail($employeId);
            $limit = $request->get('limit', 10);

            $postes = $this->matchingService->trouverPostesCompatibles($employe, $limit);

            return response()->json([
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                ],
                'postes_compatibles' => $postes,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Employé non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur recherche postes compatibles', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Suggérer des formations pour un employé
     */
    public function suggestionsFormations($employeId, Request $request)
    {
        try {
            $employe = Employe::with(['competences', 'poste.competences'])->findOrFail($employeId);
            
            $poste = null;
            if ($request->has('poste_id')) {
                $poste = Poste::with('competences')->findOrFail($request->poste_id);
            }

            $suggestions = $this->matchingService->suggererFormations($employe, $poste);

            return response()->json([
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                ],
                'poste_cible' => $poste ? [
                    'id' => $poste->id,
                    'nom' => $poste->nom,
                ] : [
                    'id' => $employe->poste?->id,
                    'nom' => $employe->poste?->nom,
                    'note' => 'Poste actuel de l\'employé',
                ],
                'suggestions' => $suggestions,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Ressource non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suggestions formations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Analyse globale des compétences de l'entreprise
     */
    public function analyseGlobale()
    {
        try {
            $analyse = $this->matchingService->analyseGlobale();
            return response()->json($analyse);
        } catch (\Throwable $e) {
            Log::error('Erreur analyse globale', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
