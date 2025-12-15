<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Services\TurnoverPredictionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TurnoverPredictionController extends Controller
{
    private TurnoverPredictionService $turnoverService;

    public function __construct(TurnoverPredictionService $turnoverService)
    {
        $this->turnoverService = $turnoverService;
    }

    /**
     * Analyse complète de tous les employés
     */
    public function index(): JsonResponse
    {
        $analyse = $this->turnoverService->analyserTousEmployes();

        return response()->json($analyse);
    }

    /**
     * Analyse d'un employé spécifique
     */
    public function show($employeId): JsonResponse
    {
        $employe = Employe::with(['poste', 'departement', 'contrats'])->findOrFail($employeId);
        $analyse = $this->turnoverService->calculerRisqueEmploye($employe);

        return response()->json($analyse);
    }

    /**
     * Top des employés à risque
     */
    public function topRisques(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $topRisques = $this->turnoverService->getTopRisques($limit);

        return response()->json([
            'top_risques' => $topRisques,
        ]);
    }

    /**
     * Statistiques globales
     */
    public function statistiques(): JsonResponse
    {
        $analyse = $this->turnoverService->analyserTousEmployes();

        return response()->json([
            'statistiques' => $analyse['statistiques'],
        ]);
    }

    /**
     * Alertes critiques
     */
    public function alertes(): JsonResponse
    {
        $analyse = $this->turnoverService->analyserTousEmployes();

        return response()->json([
            'alertes' => $analyse['alertes'],
            'count' => count($analyse['alertes']),
        ]);
    }

    /**
     * Analyse par département
     */
    public function parDepartement(): JsonResponse
    {
        $analyse = $this->turnoverService->analyserTousEmployes();
        
        // On réinjecte le nom du département (clé du tableau d'origine) pour l'affichage front
        $parDepartement = collect($analyse['statistiques']['par_departement'])
            ->map(fn($data, $nom) => array_merge(['nom' => $nom], $data))
            ->sortByDesc('score_moyen')
            ->values()
            ->toArray();

        return response()->json([
            'departements' => $parDepartement,
        ]);
    }

    /**
     * Tendances de turnover (données pour graphiques)
     */
    public function tendances(): JsonResponse
    {
        $analyse = $this->turnoverService->analyserTousEmployes();
        
        // Répartition par niveau de risque
        $repartition = $analyse['statistiques']['par_niveau'];
        
        // Répartition par département
        $parDepartement = $analyse['statistiques']['par_departement'];

        return response()->json([
            'repartition_risques' => $repartition,
            'par_departement' => $parDepartement,
            'taux_risque_eleve' => $analyse['statistiques']['taux_risque_eleve'],
            'total_employes' => $analyse['statistiques']['total_employes'],
        ]);
    }
}
