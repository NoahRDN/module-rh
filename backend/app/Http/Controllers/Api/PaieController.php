<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paie;
use App\Models\PaieDetail;
use App\Models\PaieParametre;
use App\Models\PaiePrime;
use App\Models\Pointage;
use App\Models\Contrat;
use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaieController extends Controller
{
    public function genererPaie(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'mois'       => 'required|date_format:Y-m',
        ]);

        try {
            $employe = Employe::findOrFail($request->employe_id);
            $param   = PaieParametre::firstOrFail();
            $mois    = $request->mois;

            [$heuresTrav, $heuresSup, $details, $absences, $retardsTotal] = $this->calculerHeuresMois($employe->id, $mois);

            $salaireBase = $this->recupererSalaireBase($employe->id);
            $montantHs   = $heuresSup * ($salaireBase / 173.33) * $param->hs_taux;

            $brut = $salaireBase
                + $param->prime_transport
                + $param->prime_presence
                + $montantHs;

            $cnaps = $brut * ($param->cnaps / 100);
            $ostie = $brut * ($param->ostie / 100);
            $irsa  = max(0, ($brut - $param->irsa_base) * ($param->irsa_taux / 100));
            $retenues = $cnaps + $ostie + $irsa;
            $net = $brut - $retenues;

            $paie = Paie::create([
                'employe_id'            => $employe->id,
                'mois'                  => $mois,
                'salaire_base'          => $salaireBase,
                'heures_travaillees'    => $heuresTrav,
                'heures_supplementaires'=> $heuresSup,
                'montant_hs'            => $montantHs,
                'prime_transport'       => $param->prime_transport,
                'prime_presence'        => $param->prime_presence,
                'retenue_cnaps'         => $cnaps,
                'retenue_ostie'         => $ostie,
                'retenue_irsa'          => $irsa,
                'total_brut'            => $brut,
                'total_retenues'        => $retenues,
                'net_a_payer'           => $net,
            ]);

            foreach ($details as $d) {
                PaieDetail::create(array_merge(['paie_id' => $paie->id], $d));
            }

            if ($param->prime_transport > 0) {
                PaiePrime::create([
                    'paie_id' => $paie->id,
                    'libelle' => 'Prime transport',
                    'montant' => $param->prime_transport,
                ]);
            }
            if ($param->prime_presence > 0) {
                PaiePrime::create([
                    'paie_id' => $paie->id,
                    'libelle' => 'Prime présence',
                    'montant' => $param->prime_presence,
                ]);
            }

            return response()->json([
                'message' => 'Paie générée',
                'paie'    => $paie,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur génération paie', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function recupererSalaireBase(int $employeId): float
    {
        $contrat = Contrat::where('employe_id', $employeId)
            ->orderByDesc('date_debut')
            ->first();
        return $contrat?->salaire_base ?? 0;
    }

    protected function calculerHeuresMois(int $employeId, string $mois): array
    {
        $start = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $pointages = Pointage::forEmploye($employeId)
            ->between($start->toDateString(), $end->toDateString())
            ->orderBy('pointe_a')
            ->get()
            ->groupBy(fn($p) => $p->pointe_a->toDateString());

        $totalHeures = 0;
        $totalHs = 0;
        $totalRetards = 0;
        $absences = 0;
        $details = [];

        foreach ($pointages as $jour => $liste) {
            $resume = $this->calculerJournee($liste, $jour);
            $totalHeures += $resume['heures_travaillees'];
            $totalHs += $resume['heures_supplementaires'];
            $totalRetards += $resume['retard_minutes'];
            if ($resume['absent']) {
                $absences++;
            }

            $details[] = [
                'jour' => $jour,
                'heures_travaillees' => $resume['heures_travaillees'],
                'heures_supplementaires' => $resume['heures_supplementaires'],
                'retard_minutes' => $resume['retard_minutes'],
                'absent' => $resume['absent'],
            ];
        }

        return [$totalHeures, $totalHs, $details, $absences, $totalRetards];
    }

    protected function calculerJournee($pointages, $jour)
    {
        if ($pointages->isEmpty()) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'absent' => true,
            ];
        }

        $date = Carbon::parse($jour);
        $premiereEntree = $pointages->firstWhere('type', 'entree');
        $derniereSortie = $pointages->where('type', 'sortie')->last();

        if (!$premiereEntree || !$derniereSortie) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'absent' => true,
            ];
        }

        $debut = $premiereEntree->pointe_a;
        $fin   = $derniereSortie->pointe_a;

        $minutesBrut = $debut->diffInMinutes($fin);
        $pauses = $this->calculerDureePauses($pointages);
        $minutesTravail = max(0, $minutesBrut - $pauses);

        $minutesNormales = 8 * 60;

        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresSupp = max(0, round(($minutesTravail - $minutesNormales) / 60, 2));

        $heureTheorique = (clone $date)->setTime(8, 0, 0);
        $retardMinutes = 0;
        if ($debut->greaterThan($heureTheorique)) {
            $retardMinutes = $heureTheorique->diffInMinutes($debut);
        }

        return [
            'heures_travaillees'      => $heuresTravaillees,
            'heures_supplementaires'  => $heuresSupp,
            'retard_minutes'          => $retardMinutes,
            'absent'                  => false,
        ];
    }

    protected function calculerDureePauses($pointages)
    {
        $pauses = $pointages->filter(fn($p) =>
            in_array($p->type, ['pause_debut', 'pause_fin'])
        )->sortBy('pointe_a')->values();

        $total = 0;
        for ($i = 0; $i < $pauses->count(); $i += 2) {
            $debut = $pauses[$i] ?? null;
            $fin   = $pauses[$i + 1] ?? null;

            if ($debut && $fin) {
                $total += $debut->pointe_a->diffInMinutes($fin->pointe_a);
            }
        }
        return $total;
    }
}
