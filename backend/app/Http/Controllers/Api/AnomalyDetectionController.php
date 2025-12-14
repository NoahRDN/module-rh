<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnomalyDetectionController extends Controller
{
    private AnomalyDetectionService $anomalyService;

    public function __construct(AnomalyDetectionService $anomalyService)
    {
        $this->anomalyService = $anomalyService;
    }

    /**
     * Détecter toutes les anomalies
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'employe_id' => 'nullable|exists:employes,id',
            'type' => 'nullable|string|in:pointage,paie,conges,contrats,heures',
        ]);

        $options = [
            'date_debut' => $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->subMonth(),
            'date_fin' => $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now(),
            'employe_id' => $request->employe_id,
        ];

        $result = $this->anomalyService->detecterToutesAnomalies($options);

        // Filtrer par type si demandé
        if ($request->type) {
            $result['anomalies'] = [
                $request->type => $result['anomalies'][$request->type] ?? [],
            ];
        }

        return response()->json($result);
    }

    /**
     * Anomalies de pointage uniquement
     */
    public function pointage(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'employe_id' => 'nullable|exists:employes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->subMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now();

        $anomalies = $this->anomalyService->detecterAnomaliesPointage(
            $dateDebut, 
            $dateFin, 
            $request->employe_id
        );

        return response()->json([
            'type' => 'pointage',
            'anomalies' => $anomalies,
            'count' => count($anomalies),
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Anomalies de paie uniquement
     */
    public function paie(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'employe_id' => 'nullable|exists:employes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->subMonths(3);
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now();

        $anomalies = $this->anomalyService->detecterAnomaliesPaie(
            $dateDebut, 
            $dateFin, 
            $request->employe_id
        );

        return response()->json([
            'type' => 'paie',
            'anomalies' => $anomalies,
            'count' => count($anomalies),
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Anomalies de congés uniquement
     */
    public function conges(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'employe_id' => 'nullable|exists:employes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->subMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now();

        $anomalies = $this->anomalyService->detecterAnomaliesConges(
            $dateDebut, 
            $dateFin, 
            $request->employe_id
        );

        return response()->json([
            'type' => 'conges',
            'anomalies' => $anomalies,
            'count' => count($anomalies),
        ]);
    }

    /**
     * Anomalies de contrats uniquement
     */
    public function contrats(Request $request): JsonResponse
    {
        $request->validate([
            'employe_id' => 'nullable|exists:employes,id',
        ]);

        $anomalies = $this->anomalyService->detecterAnomaliesContrats($request->employe_id);

        return response()->json([
            'type' => 'contrats',
            'anomalies' => $anomalies,
            'count' => count($anomalies),
        ]);
    }

    /**
     * Anomalies d'heures de travail uniquement
     */
    public function heures(Request $request): JsonResponse
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'employe_id' => 'nullable|exists:employes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->subMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now();

        $anomalies = $this->anomalyService->detecterAnomaliesHeures(
            $dateDebut, 
            $dateFin, 
            $request->employe_id
        );

        return response()->json([
            'type' => 'heures',
            'anomalies' => $anomalies,
            'count' => count($anomalies),
        ]);
    }

    /**
     * Statistiques d'anomalies
     */
    public function statistiques(Request $request): JsonResponse
    {
        $request->validate([
            'periode' => 'nullable|string|in:semaine,mois,trimestre,annee',
        ]);

        $periode = $request->periode ?? 'mois';
        $stats = $this->anomalyService->getStatistiquesAnomalies($periode);

        return response()->json([
            'periode' => $periode,
            'statistiques' => $stats,
        ]);
    }

    /**
     * Alertes critiques
     */
    public function alertesCritiques(): JsonResponse
    {
        $alertes = $this->anomalyService->getAlertesCritiques();

        return response()->json([
            'alertes' => $alertes,
            'count' => count($alertes),
        ]);
    }

    /**
     * Dashboard des anomalies
     */
    public function dashboard(): JsonResponse
    {
        $dernierMois = $this->anomalyService->detecterToutesAnomalies([
            'date_debut' => Carbon::now()->subMonth(),
            'date_fin' => Carbon::now(),
        ]);

        $alertesCritiques = $this->anomalyService->getAlertesCritiques();

        return response()->json([
            'statistiques' => $dernierMois['statistiques'],
            'alertes_critiques' => array_slice($alertesCritiques, 0, 10),
            'periode' => $dernierMois['periode'],
            'resume' => [
                'total_anomalies' => $dernierMois['statistiques']['total'],
                'critiques' => $dernierMois['statistiques']['par_gravite']['critique'],
                'hautes' => $dernierMois['statistiques']['par_gravite']['haute'],
            ],
        ]);
    }
}
