<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\Departement;
use App\Models\User;
use App\Models\Pointage;
use App\Models\Evaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service pour le portail Manager.
 */
class ManagerService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Récupère le département géré par un manager
     */
    public function getDepartementGere(User $manager): ?Departement
    {
        if (!$manager->employe_id) {
            return null;
        }

        return Departement::where('manager_id', $manager->employe_id)->first();
    }

    /**
     * Récupère les employés de l'équipe du manager
     */
    public function getEquipe(User $manager)
    {
        $dept = $this->getDepartementGere($manager);
        if (!$dept) {
            return collect();
        }

        return Employe::where('departement_id', $dept->id)
            ->where('id', '!=', $manager->employe_id)
            ->with(['poste', 'user'])
            ->get();
    }

    /**
     * Récupère les demandes de congés de l'équipe en attente de validation manager
     */
    public function getDemandesCongesEquipe(User $manager, ?string $statut = null)
    {
        $equipeIds = $this->getEquipe($manager)->pluck('id');

        $query = DemandeConge::whereIn('employe_id', $equipeIds)
            ->with(['employe', 'typeConge'])
            ->orderBy('created_at', 'desc');

        if ($statut) {
            $query->where('statut', $statut);
        } else {
            // Par défaut, on récupère les demandes en attente de validation manager
            $query->where('statut', 'en_attente');
        }

        return $query->get();
    }

    /**
     * Récupère les demandes RH de l'équipe en attente de validation manager
     */
    public function getDemandesRHEquipe(User $manager, ?string $statut = null)
    {
        $equipeIds = $this->getEquipe($manager)->pluck('id');

        $query = Demande::whereIn('employe_id', $equipeIds)
            ->with(['employe', 'typeDemande'])
            ->orderBy('created_at', 'desc');

        if ($statut) {
            $query->where('statut', $statut);
        } else {
            $query->where('statut', 'soumise');
        }

        return $query->get();
    }

    /**
     * Valide une demande de congé (niveau manager)
     */
    public function validerDemandeConge(User $manager, int $demandeId, ?string $commentaire = null): DemandeConge
    {
        $demande = DemandeConge::findOrFail($demandeId);

        // Vérifie que l'employé fait partie de l'équipe
        if (!$manager->isManagerOf($demande->employe)) {
            throw new \Exception("Vous n'êtes pas autorisé à valider cette demande");
        }

        $demande->update([
            'statut' => 'manager_valide',
            'manager_id' => $manager->id,
            'date_validation_manager' => now(),
            'commentaire_manager' => $commentaire,
        ]);

        $this->auditService->logApproval(
            $manager,
            DemandeConge::class,
            $demandeId,
            "Validation manager de la demande de congé #{$demandeId}",
            ['commentaire' => $commentaire]
        );

        return $demande->fresh();
    }

    /**
     * Rejette une demande de congé (niveau manager)
     */
    public function rejeterDemandeConge(User $manager, int $demandeId, ?string $commentaire = null): DemandeConge
    {
        $demande = DemandeConge::findOrFail($demandeId);

        if (!$manager->isManagerOf($demande->employe)) {
            throw new \Exception("Vous n'êtes pas autorisé à rejeter cette demande");
        }

        $demande->update([
            'statut' => 'rejete',
            'manager_id' => $manager->id,
            'date_validation_manager' => now(),
            'commentaire_manager' => $commentaire,
        ]);

        $this->auditService->logRejection(
            $manager,
            DemandeConge::class,
            $demandeId,
            "Rejet manager de la demande de congé #{$demandeId}",
            ['commentaire' => $commentaire]
        );

        return $demande->fresh();
    }

    /**
     * Valide une demande RH (niveau manager)
     */
    public function validerDemandeRH(User $manager, int $demandeId, ?string $commentaire = null): Demande
    {
        $demande = Demande::findOrFail($demandeId);

        if (!$manager->isManagerOf($demande->employe)) {
            throw new \Exception("Vous n'êtes pas autorisé à valider cette demande");
        }

        $demande->update([
            'statut' => 'manager_valide',
            'manager_id' => $manager->id,
            'date_validation_manager' => now(),
            'commentaire_manager' => $commentaire,
        ]);

        $this->auditService->logApproval(
            $manager,
            Demande::class,
            $demandeId,
            "Validation manager de la demande RH #{$demandeId}"
        );

        return $demande->fresh();
    }

    /**
     * Récupère les statistiques d'absences de l'équipe
     */
    public function getStatistiquesAbsences(User $manager, ?string $mois = null): array
    {
        $equipeIds = $this->getEquipe($manager)->pluck('id');
        $date = $mois ? Carbon::parse($mois) : now();

        $congesValidés = DemandeConge::whereIn('employe_id', $equipeIds)
            ->where('statut', 'rh_valide')
            ->whereMonth('date_debut', $date->month)
            ->whereYear('date_debut', $date->year)
            ->get();

        return [
            'total_jours_absences' => $congesValidés->sum('jours_demandes'),
            'nombre_absences' => $congesValidés->count(),
            'par_type' => $congesValidés->groupBy('type_conge_id')
                ->map(fn($groupe) => [
                    'count' => $groupe->count(),
                    'jours' => $groupe->sum('jours_demandes'),
                ])
                ->toArray(),
            'par_employe' => $congesValidés->groupBy('employe_id')
                ->map(fn($groupe) => $groupe->sum('jours_demandes'))
                ->toArray(),
        ];
    }

    /**
     * Récupère les statistiques de performance de l'équipe
     */
    public function getStatistiquesPerformance(User $manager): array
    {
        $equipeIds = $this->getEquipe($manager)->pluck('id');
        $annee = now()->year;

        $evaluations = Evaluation::whereIn('employe_id', $equipeIds)
            ->whereYear('date_evaluation', $annee)
            ->get();

        return [
            'nombre_evaluations' => $evaluations->count(),
            'note_moyenne' => round($evaluations->avg('note_globale'), 2),
            'repartition_notes' => [
                'excellent' => $evaluations->where('note_globale', '>=', 4)->count(),
                'bon' => $evaluations->whereBetween('note_globale', [3, 4])->count(),
                'moyen' => $evaluations->whereBetween('note_globale', [2, 3])->count(),
                'insuffisant' => $evaluations->where('note_globale', '<', 2)->count(),
            ],
            'evaluations_recentes' => $evaluations->sortByDesc('date_evaluation')->take(5),
        ];
    }

    /**
     * Récupère le tableau de bord manager
     */
    public function getDashboard(User $manager): array
    {
        $equipe = $this->getEquipe($manager);
        $dept = $this->getDepartementGere($manager);

        return [
            'departement' => $dept ? [
                'id' => $dept->id,
                'nom' => $dept->nom,
            ] : null,
            'equipe' => [
                'total' => $equipe->count(),
                'employes' => $equipe->map(fn($e) => [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'prenom' => $e->prenom,
                    'poste' => $e->poste?->nom,
                    'photo' => $e->photo,
                ]),
            ],
            'demandes_en_attente' => [
                'conges' => $this->getDemandesCongesEquipe($manager, 'en_attente')->count(),
                'rh' => $this->getDemandesRHEquipe($manager, 'soumise')->count(),
            ],
            'absences_mois' => $this->getStatistiquesAbsences($manager),
            'performance' => $this->getStatistiquesPerformance($manager),
        ];
    }

    /**
     * Récupère le calendrier des absences de l'équipe
     */
    public function getCalendrierAbsences(User $manager, string $debut, string $fin): array
    {
        $equipeIds = $this->getEquipe($manager)->pluck('id');

        $conges = DemandeConge::whereIn('employe_id', $equipeIds)
            ->whereIn('statut', ['manager_valide', 'rh_valide'])
            ->where(function ($q) use ($debut, $fin) {
                $q->whereBetween('date_debut', [$debut, $fin])
                    ->orWhereBetween('date_fin', [$debut, $fin])
                    ->orWhere(function ($q2) use ($debut, $fin) {
                        $q2->where('date_debut', '<=', $debut)
                            ->where('date_fin', '>=', $fin);
                    });
            })
            ->with(['employe', 'typeConge'])
            ->get();

        return $conges->map(fn($c) => [
            'id' => $c->id,
            'employe_id' => $c->employe_id,
            'employe_nom' => $c->employe->nom . ' ' . $c->employe->prenom,
            'type' => $c->typeConge?->libelle ?? 'Congé',
            'date_debut' => $c->date_debut->format('Y-m-d'),
            'date_fin' => $c->date_fin->format('Y-m-d'),
            'jours' => $c->jours_demandes,
            'statut' => $c->statut,
        ])->toArray();
    }
}
