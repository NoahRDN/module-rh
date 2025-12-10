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
            // Vérifier si déjà crédité pour ce mois
            $existe = AcquisConge::where('employe_id', $emp->id)
                ->where('type_conge_id', $type->id)
                ->whereDate('acquis_le', $finMois->toDateString())
                ->exists();
            if ($existe) {
                continue;
            }

            // Créditer le 2,5 standard (peut être remplacé par règle plus tard)
            AcquisConge::create([
                'employe_id' => $emp->id,
                'type_conge_id' => $type->id,
                'jours_acquis' => 2.5,
                'acquis_le' => $finMois->toDateString(),
                'expire_le' => $finMois->copy()->addYears(3)->toDateString(),
            ]);
        }
    }

    /**
     * Créditer un employé mois par mois, depuis le début de son contrat actif jusqu'au mois courant.
     */
    public function accrueMensuelPourEmploye(Employe $employe, ?Carbon $date = null): void
    {
        $dateRef = $date ?: Carbon::now();

        // Contrat en cours sur la date de référence
        $contratActif = $employe->contrats()
            ->where('statut', 'en_cours')
            ->whereDate('date_debut', '<=', $dateRef->toDateString())
            ->where(function ($q) use ($dateRef) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateRef->toDateString());
            })
            ->orderBy('date_debut')
            ->first();

        if (!$contratActif) {
            return;
        }

        $type = TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        // On crédite uniquement les mois ENTIEREMENT écoulés : on s'arrête au dernier jour du mois précédent
        $moisCursor = Carbon::parse($contratActif->date_debut)->startOfMonth();
        $fin = $dateRef->copy()->subMonthNoOverflow()->endOfMonth();

        if ($moisCursor->gt($fin)) {
            return;
        }

        while ($moisCursor->lte($fin)) {
            $acquisDate = $moisCursor->copy()->endOfMonth();
            $deja = AcquisConge::where('employe_id', $employe->id)
                ->where('type_conge_id', $type->id)
                ->whereDate('acquis_le', $acquisDate->toDateString())
                ->exists();
            if (!$deja) {
                $jours = $this->tauxAcquisition($employe, $type);
                if ($jours > 0) {
                    AcquisConge::create([
                        'employe_id' => $employe->id,
                        'type_conge_id' => $type->id,
                        'jours_acquis' => $jours,
                        'acquis_le' => $acquisDate->toDateString(),
                        'expire_le' => $acquisDate->copy()->addYears(3)->toDateString(),
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
                $acquis = AcquisConge::create([
                    'employe_id'    => $demande->employe_id,
                    'type_conge_id' => $type->id,
                    'jours_acquis'  => $joursDemandes,
                    'acquis_le'     => now()->toDateString(),
                    'expire_le'     => $demande->date_fin ?? now()->addYears(1)->toDateString(),
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
                ->whereDate('expire_le', '>=', now())
                ->orderBy('expire_le')
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

    protected function calculJoursDemandes(DemandeConge $demande): float
    {
        $debut = Carbon::parse($demande->date_debut);
        $fin = Carbon::parse($demande->date_fin);
        $jours = $debut->diffInDays($fin) + 1;
        return (float) $jours;
    }

    /**
     * Solde entre deux dates (simple) basé sur acquis/conso.
     */
    public function soldeEntre(int $employeId, int $typeCongeId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $acquisQuery = AcquisConge::where('employe_id', $employeId)
            ->where('type_conge_id', $typeCongeId);

        // Par défaut, on ne compte que les acquis non expirés
        $dateLimite = $to ? $to->copy()->endOfDay() : now();
        $acquisQuery->whereDate('expire_le', '>=', $dateLimite->toDateString());

        // Disponible à partir du 1er du mois suivant l'acquisition
        $dateDispoLimite = $to ? $to->copy()->endOfDay() : now();
        $acquisQuery->whereRaw("(date_trunc('month', acquis_le) + interval '1 month') <= ?", [$dateDispoLimite->toDateString()]);

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
}
