<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour le portail Manager.
 */
class ManagerController extends Controller
{
    public function __construct(private ManagerService $managerService)
    {
    }

    /**
     * Tableau de bord du manager
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isManagerOfDepartement() && !$user->isAdmin() && !$user->isRH()) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas responsable d\'un département',
                ], 403);
            }

            $dashboard = $this->managerService->getDashboard($user);

            return response()->json($dashboard);
        } catch (\Throwable $e) {
            Log::error('Erreur dashboard manager', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Liste des employés de l'équipe
     */
    public function equipe(Request $request)
    {
        try {
            $user = $request->user();
            $equipe = $this->managerService->getEquipe($user);

            return response()->json([
                'data' => $equipe,
                'total' => $equipe->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération équipe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Liste des demandes de congés de l'équipe
     */
    public function demandesConges(Request $request)
    {
        try {
            $user = $request->user();
            $statut = $request->query('statut');

            $demandes = $this->managerService->getDemandesCongesEquipe($user, $statut);

            return response()->json([
                'data' => $demandes,
                'total' => $demandes->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demandes congés équipe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Liste des demandes RH de l'équipe
     */
    public function demandesRH(Request $request)
    {
        try {
            $user = $request->user();
            $statut = $request->query('statut');

            $demandes = $this->managerService->getDemandesRHEquipe($user, $statut);

            return response()->json([
                'data' => $demandes,
                'total' => $demandes->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demandes RH équipe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Valide une demande de congé
     */
    public function validerDemandeConge(Request $request, $id)
    {
        try {
            $user = $request->user();
            $commentaire = $request->input('commentaire');

            $demande = $this->managerService->validerDemandeConge($user, $id, $commentaire);

            return response()->json([
                'message' => 'Demande validée avec succès',
                'demande' => $demande,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            Log::error('Erreur validation demande congé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Rejette une demande de congé
     */
    public function rejeterDemandeConge(Request $request, $id)
    {
        try {
            $user = $request->user();
            $commentaire = $request->input('commentaire');

            if (!$commentaire) {
                return response()->json([
                    'message' => 'Un commentaire est requis pour le rejet',
                ], 422);
            }

            $demande = $this->managerService->rejeterDemandeConge($user, $id, $commentaire);

            return response()->json([
                'message' => 'Demande rejetée',
                'demande' => $demande,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            Log::error('Erreur rejet demande congé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Valide une demande RH
     */
    public function validerDemandeRH(Request $request, $id)
    {
        try {
            $user = $request->user();
            $commentaire = $request->input('commentaire');

            $demande = $this->managerService->validerDemandeRH($user, $id, $commentaire);

            return response()->json([
                'message' => 'Demande validée avec succès',
                'demande' => $demande,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            Log::error('Erreur validation demande RH', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Statistiques d'absences de l'équipe
     */
    public function statistiquesAbsences(Request $request)
    {
        try {
            $user = $request->user();
            $mois = $request->query('mois');

            $stats = $this->managerService->getStatistiquesAbsences($user, $mois);

            return response()->json($stats);
        } catch (\Throwable $e) {
            Log::error('Erreur stats absences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Statistiques de performance de l'équipe
     */
    public function statistiquesPerformance(Request $request)
    {
        try {
            $user = $request->user();
            $stats = $this->managerService->getStatistiquesPerformance($user);

            return response()->json($stats);
        } catch (\Throwable $e) {
            Log::error('Erreur stats performance', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Calendrier des absences de l'équipe
     */
    public function calendrierAbsences(Request $request)
    {
        try {
            $user = $request->user();
            $debut = $request->query('debut', now()->startOfMonth()->format('Y-m-d'));
            $fin = $request->query('fin', now()->endOfMonth()->format('Y-m-d'));

            $calendrier = $this->managerService->getCalendrierAbsences($user, $debut, $fin);

            return response()->json(['data' => $calendrier]);
        } catch (\Throwable $e) {
            Log::error('Erreur calendrier absences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
