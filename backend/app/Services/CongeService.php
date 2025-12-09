<?php

namespace App\Services;

use App\Models\AcquisConge;
use App\Models\ConsommationConge;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\RegleConge;
use App\Models\SoldeConge;
use App\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CongeService
{
    public const CODE_CONGE_PAYE = 'PAYE';

    public function accrueMensuelPourEmploye(Employe $employe, ?Carbon $date = null): void
    {
        $date = $date ?: Carbon::now();
        $type = TypeConge::where('code', self::CODE_CONGE_PAYE)->first();
        if (!$type) {
            return;
        }

        $jours = $this->tauxAcquisition($employe, $type);
        if ($jours <= 0) {
            return;
        }

        $acquis = AcquisConge::create([
            'employe_id' => $employe->id,
            'type_conge_id' => $type->id,
            'jours_acquis' => $jours,
            'acquis_le' => $date->copy()->endOfMonth()->toDateString(),
            'expire_le' => $date->copy()->endOfMonth()->addYears(3)->toDateString(),
        ]);

        $solde = SoldeConge::firstOrCreate(
            ['employe_id' => $employe->id, 'type_conge_id' => $type->id],
            ['solde_actuel' => 0, 'solde_annuel' => 0, 'type_id' => null]
        );
        $solde->increment('solde_actuel', $jours);
        $solde->increment('solde_annuel', $jours);
        $solde->expire_le = $acquis->expire_le;
        $solde->save();
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
        if (!$type || !$type->utilise_solde) {
            return;
        }

        $joursDemandes = $this->calculJoursDemandes($demande);
        if ($joursDemandes <= 0) {
            return;
        }

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
                $dispo = max(0, (float) $acquis->jours_acquis - (float) $acquis->jours_utilises);
                if ($dispo <= 0) {
                    continue;
                }
                $aPrendre = min($dispo, $restant);
                $acquis->increment('jours_utilises', $aPrendre);
                ConsommationConge::create([
                    'demande_conge_id' => $demande->id,
                    'acquis_conge_id' => $acquis->id,
                    'jours_utilises' => $aPrendre,
                ]);
                $restant -= $aPrendre;
            }

            $solde = SoldeConge::firstOrCreate(
                ['employe_id' => $demande->employe_id, 'type_conge_id' => $type->id],
                ['solde_actuel' => 0, 'solde_annuel' => 0, 'type_id' => null]
            );
            $solde->decrement('solde_actuel', max(0, $joursDemandes - $restant));
        });
    }

    protected function calculJoursDemandes(DemandeConge $demande): float
    {
        $debut = Carbon::parse($demande->date_debut);
        $fin = Carbon::parse($demande->date_fin);
        $jours = $debut->diffInDays($fin) + 1;
        return (float) $jours;
    }
}
