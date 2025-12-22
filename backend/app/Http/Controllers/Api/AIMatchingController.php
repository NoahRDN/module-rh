<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Poste;
use App\Services\AIMatchingService;
use App\Services\CompetenceMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIMatchingController extends Controller
{
    private AIMatchingService $aiMatchingService;
    private CompetenceMatchingService $matchingService;

    public function __construct(
        AIMatchingService $aiMatchingService,
        CompetenceMatchingService $matchingService
    ) {
        $this->aiMatchingService = $aiMatchingService;
        $this->matchingService = $matchingService;
    }

    /**
     * Analyse IA d'un profil pour un poste
     */
    public function analyserProfil(Request $request): JsonResponse
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'poste_id' => 'required|exists:postes,id',
        ]);

        $employe = Employe::with(['competences', 'formations', 'poste', 'departement'])->findOrFail($request->employe_id);
        $poste = Poste::with(['competences', 'departement'])->findOrFail($request->poste_id);

        $analyse = $this->aiMatchingService->analyserProfilPoste($employe, $poste);

        return response()->json($analyse);
    }

    /**
     * Analyser un CV pour un poste
     */
    public function analyserCV(Request $request): JsonResponse
    {
        $request->validate([
            'cv_texte' => 'required|string|min:100|max:10000',
            'poste_id' => 'required|exists:postes,id',
        ]);

        $poste = Poste::with('competences')->findOrFail($request->poste_id);
        $result = $this->aiMatchingService->analyserCV($request->cv_texte, $poste);

        return response()->json($result);
    }

    /**
     * Suggestions de formations IA
     */
    public function suggestionsFormations(Request $request, $employeId): JsonResponse
    {
        $request->validate([
            'poste_cible_id' => 'nullable|exists:postes,id',
        ]);

        $employe = Employe::with(['competences', 'formations'])->findOrFail($employeId);
        $posteCible = $request->poste_cible_id 
            ? Poste::with('competences')->find($request->poste_cible_id) 
            : null;

        $suggestions = $this->aiMatchingService->suggererFormationsIA($employe, $posteCible);

        return response()->json($suggestions);
    }

    /**
     * Générer un plan de carrière personnalisé
     */
    public function planCarriere($employeId): JsonResponse
    {
        $employe = Employe::with([
            'competences', 
            'formations', 
            'poste', 
            'departement',
            'historiquePostes.poste'
        ])->findOrFail($employeId);

        $plan = $this->aiMatchingService->genererPlanCarriere($employe);

        return response()->json($plan);
    }

    /**
     * Trouver les meilleurs candidats pour un poste avec analyse IA
     */
    public function candidatsPostAI(Request $request, $posteId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'avec_ia' => 'nullable|boolean',
        ]);

        $poste = Poste::with(['competences', 'departement'])->findOrFail($posteId);
        $limit = $request->limit ?? 10;
        $avecIA = $request->boolean('avec_ia', false);

        // Matching classique d'abord
        $candidatsClassiques = $this->matchingService->trouverCandidats($poste, $limit);

        if (!$avecIA) {
            return response()->json([
                'poste' => [
                    'id' => $poste->id,
                    'titre' => $poste->nom,
                ],
                'candidats' => $candidatsClassiques,
                'analyse_ia' => false,
            ]);
        }

        // Enrichir avec l'analyse IA pour les top candidats
        $candidatsEnrichis = $candidatsClassiques->take(5)->map(function ($candidat) use ($poste) {
            $employe = Employe::with(['competences', 'formations'])->find($candidat['employe']['id']);
            if ($employe) {
                $analyseIA = $this->aiMatchingService->analyserProfilPoste($employe, $poste);
                $candidat['analyse_ia'] = $analyseIA['analyse_ia'] ?? null;
                $candidat['score_combine'] = $analyseIA['score_combine'] ?? $candidat['compatibilite']['score_global'];
            }
            return $candidat;
        });

        // Combiner avec les candidats non analysés par l'IA
        $autresCandidats = $candidatsClassiques->skip(5)->values();

        return response()->json([
            'poste' => [
                'id' => $poste->id,
                'titre' => $poste->nom,
            ],
            'candidats_ia' => $candidatsEnrichis,
            'autres_candidats' => $autresCandidats,
            'analyse_ia' => true,
        ]);
    }

    /**
     * Trouver les postes compatibles pour un employé avec analyse IA
     */
    public function postesCompatiblesAI(Request $request, $employeId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'avec_ia' => 'nullable|boolean',
        ]);

        $employe = Employe::with(['competences', 'formations', 'poste', 'departement'])->findOrFail($employeId);
        $limit = $request->limit ?? 10;
        $avecIA = $request->boolean('avec_ia', false);

        $postesCompatibles = $this->matchingService->trouverPostesCompatibles($employe, $limit);

        if (!$avecIA) {
            return response()->json([
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom . ' ' . $employe->prenom,
                ],
                'postes' => $postesCompatibles,
                'analyse_ia' => false,
            ]);
        }

        // Enrichir avec l'analyse IA pour les top postes
        $postesEnrichis = $postesCompatibles->take(5)->map(function ($posteData) use ($employe) {
            $poste = Poste::with('competences')->find($posteData['poste']['id']);
            if ($poste) {
                $analyseIA = $this->aiMatchingService->analyserProfilPoste($employe, $poste);
                $posteData['analyse_ia'] = $analyseIA['analyse_ia'] ?? null;
                $posteData['score_combine'] = $analyseIA['score_combine'] ?? $posteData['compatibilite']['score_global'];
            }
            return $posteData;
        });

        $autresPostes = $postesCompatibles->skip(5)->values();

        return response()->json([
            'employe' => [
                'id' => $employe->id,
                'nom' => $employe->nom . ' ' . $employe->prenom,
            ],
            'postes_ia' => $postesEnrichis,
            'autres_postes' => $autresPostes,
            'analyse_ia' => true,
        ]);
    }
}
