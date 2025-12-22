<?php

namespace App\Services;

use App\Models\AcquisConge;
use App\Models\ConsommationConge;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\RegleConge;
use App\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CongeService
{
    public const CODE_CONGE_PAYE = 'PAYE';

    /**
     * Créditer les congés payés pour un mois donné (par défaut mois précédent).
     * Règle standard : 2,5 jours par mois entamé, si contrat en cours sur le mois.
     */
    public function accrueMois(?Carbon $mois = null): void
    {
        // Par défaut, on crédite le mois ENTIER précédant la date courante
        $mois = $mois ?: now()->subMonthNoOverflow()->startOfMonth();
        $debutMois = $mois->copy()->startOfMonth();
        $finMois   = $mois->copy()->endOfMonth();

        $type = TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        $employes = Employe::whereHas('contrats', function ($q) use ($finMois) {
            $q->whereDate('date_debut', '<=', $finMois->toDateString())
              ->where(function ($w) use ($finMois) {
                  $w->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $finMois->toDateString());
              });
        })->get();

        foreach ($employes as $emp) {
            $contrat = $this->contratActifLe($emp, $finMois);
            if (!$contrat) {
                continue;
            }

            // Vérifier si déjà crédité pour ce mois
            $existe = AcquisConge::where('employe_id', $emp->id)
                ->where('type_conge_id', $type->id)
                ->whereDate('acquis_le', $finMois->toDateString())
                ->exists();
            if ($existe) {
                continue;
            }

            // Créditer le 2,5 standard (peut être remplacé par règle plus tard)
            [$acquisFirst, $expireFirst] = $this->determineFenetre($emp->id, $type->id, $finMois);
            $expireFinal = Carbon::parse($expireFirst);
            AcquisConge::create([
                'employe_id' => $emp->id,
                'type_conge_id' => $type->id,
                'contrat_type' => $contrat->type_contrat ?? null,
                'contrat_fin' => $contrat->date_fin,
                'jours_acquis' => 2.5,
                'acquis_le' => $finMois->toDateString(),
                'expire_le' => $expireFinal->toDateString(),
                'acquis_first' => $acquisFirst,
                'expire_first' => $expireFinal->toDateString(),
            ]);
        }
    }

    /**
     * Créditer un employé mois par mois, depuis le début de son premier contrat
     * jusqu'au mois courant, sans jamais remettre les compteurs à zéro lors d'un passage
     * CDD -> CDI (ou autre type de contrat). On crédite chaque mois où AU MOINS
     * un contrat est actif.
     */
    public function accrueMensuelPourEmploye(Employe $employe, ?Carbon $date = null): void
    {
        $dateRef = $date ?: Carbon::now();

        // Premier contrat de l'employé (quel que soit le type ou le statut)
        $premierContrat = $employe->contrats()->orderBy('date_debut')->first();
        if (!$premierContrat) {
            return;
        }

        $type = TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        // On crédite uniquement les mois ENTIEREMENT écoulés : on s'arrête au dernier jour du mois précédent.
        // Le curseur part du premier mois du premier contrat.
        $moisCursor = Carbon::parse($premierContrat->date_debut)->startOfMonth();
        $fin = $dateRef->copy()->subMonthNoOverflow()->endOfMonth();

        if ($moisCursor->gt($fin)) {
            return;
        }

        while ($moisCursor->lte($fin)) {
            $acquisDate = $moisCursor->copy()->endOfMonth();

            // Vérifier qu'au moins un contrat est actif sur le mois (pas de trou dans la relation de travail)
            $contratSurMois = $this->contratActifLe($employe, $acquisDate, $moisCursor);
            if (!$contratSurMois) {
                $moisCursor->addMonth();
                continue;
            }

            $deja = AcquisConge::where('employe_id', $employe->id)
                ->where('type_conge_id', $type->id)
                ->whereDate('acquis_le', $acquisDate->toDateString())
                ->exists();
            if (!$deja) {
                $jours = $this->tauxAcquisition($employe, $type);
                if ($jours > 0) {
                    [$acquisFirst, $expireFirst] = $this->determineFenetre($employe->id, $type->id, $acquisDate);
                    $expireFinal = Carbon::parse($expireFirst);
                    AcquisConge::create([
                        'employe_id' => $employe->id,
                        'type_conge_id' => $type->id,
                        'contrat_type' => $contratSurMois->type_contrat ?? null,
                        'contrat_fin' => $contratSurMois->date_fin,
                        'jours_acquis' => $jours,
                        'acquis_le' => $acquisDate->toDateString(),
                        'expire_le' => $expireFinal->toDateString(),
                        'acquis_first' => $acquisFirst,
                        'expire_first' => $expireFinal->toDateString(),
                    ]);
                }
            }
            $moisCursor->addMonth();
        }
    }

    protected function tauxAcquisition(Employe $employe, TypeConge $type): float
    {
        $contratActif = method_exists($employe, 'contrats')
            ? $employe->contrats()->where('statut', 'en_cours')->orderByDesc('date_debut')->first()
            : null;
        // Ancienneté arrondie à l'année inférieure pour matcher la règle entière
        $anciennete = $employe->date_embauche
            ? (int) floor(Carbon::parse($employe->date_embauche)->floatDiffInYears(now()))
            : 0;
        $regle = RegleConge::where('type_conge_id', $type->id)
            ->where('anciennete_min', '<=', $anciennete)
            ->when($contratActif && $contratActif->type_contrat, function ($q) use ($contratActif) {
                $q->where(function ($sub) use ($contratActif) {
                    $sub->whereNull('contrat_type')->orWhere('contrat_type', $contratActif->type_contrat);
                });
            })
            ->orderByDesc('anciennete_min')
            ->first();

        $jours = $regle?->jours_acquis_par_mois ?? 2.5;
        if ($regle && $regle->temps_partiel_ratio) {
            $jours = $jours * (float) $regle->temps_partiel_ratio;
        }
        return (float) $jours;
    }

    public function consommerDemande(DemandeConge $demande): void
    {
        $type = $demande->type_conge_id ? $demande->typeConge : TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        $joursDemandes = $demande->jours_demandes ?: $this->calculJoursDemandes($demande);
        if ($joursDemandes <= 0) {
            return;
        }

        // Cas non PAYE : on crée un acquis ponctuel correspondant à la durée autorisée
        if ($type->code !== self::CODE_CONGE_PAYE) {
            DB::transaction(function () use ($demande, $type, $joursDemandes) {
                $acquisDate = Carbon::parse($demande->date_debut);
                // [$acquisFirst, $expireFirst] = $this->determineFenetre($demande->employe_id, $type->id, $acquisDate);
                $contrat = $this->contratActifLe($demande->employe, $acquisDate);
                $expireFinal = Carbon::parse($demande->date_fin);
                $acquis = AcquisConge::create([
                    'employe_id'    => $demande->employe_id,
                    'type_conge_id' => $type->id,
                    'contrat_type'  => $contrat->type_contrat ?? null,
                    'contrat_fin'   => $contrat->date_fin ?? null,
                    'jours_acquis'  => $joursDemandes,
                    'acquis_le'     => $acquisDate->toDateString(),
                    'expire_le'     => ($demande->date_fin && Carbon::parse($demande->date_fin)->lt($expireFinal))
                        ? Carbon::parse($demande->date_fin)->toDateString()
                        : $expireFinal->toDateString(),
                    'acquis_first'  => $acquisDate,
                    'expire_first'  => $expireFinal->toDateString(),
                ]);

                ConsommationConge::create([
                    'demande_conge_id' => $demande->id,
                    'acquis_conge_id'  => $acquis->id,
                    'jours_utilises'   => $joursDemandes,
                ]);
            });
            return;
        }

        // Cas PAYE : consommation FIFO sur les acquis existants
        DB::transaction(function () use ($demande, $type, $joursDemandes) {
            $restant = $joursDemandes;
            $acquisList = AcquisConge::where('employe_id', $demande->employe_id)
                ->where('type_conge_id', $type->id)
                ->whereDate('expire_le', '>=', Carbon::parse($demande->date_fin))
                ->orderBy('acquis_first')
                ->orderBy('acquis_le')
                ->lockForUpdate()
                ->get();

            foreach ($acquisList as $acquis) {
                if ($restant <= 0) {
                    break;
                }
                $consomme = ConsommationConge::where('acquis_conge_id', $acquis->id)->sum('jours_utilises');
                $dispo = max(0, (float) $acquis->jours_acquis - (float) $consomme);
                if ($dispo <= 0) {
                    continue;
                }
                $aPrendre = min($dispo, $restant);
                ConsommationConge::create([
                    'demande_conge_id' => $demande->id,
                    'acquis_conge_id' => $acquis->id,
                    'jours_utilises' => $aPrendre,
                ]);
            $restant -= $aPrendre;
        }
        });
    }

    /**
     * Consommer automatiquement des jours d'absence (retards/absences) sur le solde PAYE.
     * Crée une demande synthétique "Régularisation paie {mois}" pour tracer la consommation.
     */
    public function consommerAbsenceAuto(int $employeId, float $jours, Carbon $dateRef, string $moisLabel = ''): void
    {
        if ($jours <= 0) {
            return;
        }
        $type = TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        $motif = $moisLabel ? "Régularisation paie {$moisLabel}" : 'Régularisation paie';

        // Nettoyer une éventuelle régularisation précédente sur le même mois
        $ancienne = DemandeConge::where('employe_id', $employeId)
            ->where('statut', 'rh_valide')
            ->where('motif', 'like', $motif . '%')
            ->first();
        if ($ancienne) {
            ConsommationConge::where('demande_conge_id', $ancienne->id)->delete();
            $ancienne->delete();
        }

        $dateFin = (clone $dateRef)->addDays(max(0, (int) ceil($jours) - 1));

        $demande = DemandeConge::create([
            'employe_id'     => $employeId,
            'type_conge_id'  => $type->id,
            'jours_demandes' => $jours,
            'date_debut'     => $dateRef->toDateString(),
            'date_fin'       => $dateFin->toDateString(),
            'statut'         => 'rh_valide',
            'motif'          => $motif,
        ]);

        $this->consommerDemande($demande);
    }

    protected function calculJoursDemandes(DemandeConge $demande): float
    {
        $debut = Carbon::parse($demande->date_debut);
        $fin = Carbon::parse($demande->date_fin);
        $jours = $debut->diffInDays($fin) + 1;
        return (float) $jours;
    }

    /**
     * Retourne le contrat actif à une date donnée.
     */
    protected function contratActifLe(Employe $employe, Carbon $date, ?Carbon $debutMois = null)
    {
        $debut = $debutMois ?: $date;
        return $employe->contrats()
            ->whereDate('date_debut', '<=', $date->toDateString())
            ->where(function ($q) use ($debut) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $debut->toDateString());
            })
            ->orderBy('date_debut')
            ->first();
    }

    /**
     * Solde entre deux dates (simple) basé sur acquis/conso.
     */
    public function soldeEntre(int $employeId, int $typeCongeId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $acquisQuery = AcquisConge::where('employe_id', $employeId)
            ->where('type_conge_id', $typeCongeId);

        // Par défaut, on ne compte que les acquis non expirés (fenêtre de 3 ans)
        $dateLimite = $to ? $to->copy()->endOfDay() : now();
        $acquisQuery->whereDate('expire_first', '>=', $dateLimite->toDateString());

        if ($from) {
            $acquisQuery->whereDate('acquis_le', '>=', $from);
        }
        if ($to) {
            $acquisQuery->whereDate('acquis_le', '<=', $to);
        }

        $totalAcquis = (float) $acquisQuery->sum('jours_acquis');
        $acquisIds = $acquisQuery->pluck('id');

        $totalConso = (float) ConsommationConge::whereIn('acquis_conge_id', $acquisIds)->sum('jours_utilises');

        return $totalAcquis - $totalConso;
    }

    /**
     * Résumé (acquis, utilisé, solde) sur une période.
     */
    public function resumeEntre(int $employeId, int $typeCongeId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $acquisQuery = AcquisConge::where('employe_id', $employeId)
            ->where('type_conge_id', $typeCongeId);

        $dateFin = $to ? $to->copy()->endOfDay() : now();

        $acquisQuery->whereDate('expire_first', '>=', $dateFin->toDateString());

        if ($from) {
            $acquisQuery->whereDate('acquis_le', '>=', $from);
        }
        if ($to) {
            $acquisQuery->whereDate('acquis_le', '<=', $to);
        }

        $acquisIds = $acquisQuery->pluck('id');
        $totalAcquis = (float) AcquisConge::whereIn('id', $acquisIds)->sum('jours_acquis');
        $totalConso = (float) ConsommationConge::whereIn('acquis_conge_id', $acquisIds)->sum('jours_utilises');

        return [
            'acquis' => $totalAcquis,
            'utilise' => $totalConso,
            'solde' => $totalAcquis - $totalConso,
        ];
    }

    /**
     * Détermine la fenêtre (acquis_first / expire_first) à utiliser pour un nouvel acquis.
     * Si une fenêtre en cours existe et couvre la date d'acquisition, on la réutilise.
     * Sinon, on démarre une nouvelle fenêtre de 3 ans à partir de la date d'acquisition.
     */
    private function determineFenetre(int $employeId, int $typeCongeId, Carbon $acquisDate): array
    {
        // Rechercher une fenêtre encore valide couvrant la date d'acquisition
        $fenetre = AcquisConge::where('employe_id', $employeId)
            ->where('type_conge_id', $typeCongeId)
            ->whereDate('expire_first', '>=', $acquisDate->toDateString())
            ->orderByDesc('acquis_first')
            ->first();

        if ($fenetre && $fenetre->acquis_first && $fenetre->expire_first) {
            return [$fenetre->acquis_first->toDateString(), $fenetre->expire_first->toDateString()];
        }

        // Sinon, on crée une nouvelle fenêtre de 3 ans à partir de la fin de mois acquise
        $acquisFirst = $acquisDate->copy()->endOfMonth();
        // addYearsNoOverflow pour éviter le passage au 1er mars sur les années bissextiles, puis on reprend la fin du mois
        $expireFirst = $acquisFirst->copy()->addYearsNoOverflow(3)->endOfMonth();
        return [$acquisFirst->toDateString(), $expireFirst->toDateString()];
    }
}
