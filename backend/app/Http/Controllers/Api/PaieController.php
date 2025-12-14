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
use DateInterval;
use DatePeriod;
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

            $salaireBase = $this->recupererSalaireBase($employe->id);
            $tauxHoraire = $salaireBase > 0 ? $salaireBase / 173.33 : 0;

            [$heuresTrav, $details, $absences, $retardsTotal] = $this->calculerHeuresMois($employe->id, $mois);
            [$heuresSup, $montantHs] = $this->calculerHsHebdo($details, $tauxHoraire);

            $deductionRetards = ($retardsTotal / 60) * $tauxHoraire;
            $deductionAbsences = $absences * ($salaireBase / 30);

            $brut = $salaireBase
                + $param->prime_transport
                + $param->prime_presence
                + $montantHs;

            $cnaps = $brut * ($param->cnaps / 100);
            $ostie = $brut * ($param->ostie / 100);
            $irsa  = max(0, ($brut - $param->irsa_base) * ($param->irsa_taux / 100));
            $retenues = $cnaps + $ostie + $irsa + $deductionRetards + $deductionAbsences;
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
        $totalRetards = 0;
        $absences = 0;
        $details = [];

        $period = new DatePeriod($start, new DateInterval('P1D'), $end->copy()->addDay());

        foreach ($period as $day) {
            $jour = $day->format('Y-m-d');
            $liste = $pointages[$jour] ?? collect();
            $resume = $this->calculerJournee($liste, $jour);
            $totalHeures += $resume['heures_travaillees'];
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

        return [$totalHeures, $details, $absences, $totalRetards];
    }

    protected function calculerJournee($pointages, $jour)
    {
        $dayCode = Carbon::parse($jour)->format('D'); // Mon, Tue...
        $dayCode = strtolower(substr($dayCode, 0, 3)); // mon, tue, wed...
        $settings = $this->loadWorktimeSettings();
        $workingDays = $settings['working_days'] ?? ['mon','tue','wed','thu','fri'];
        $saturdayMode = $settings['saturday_mode'] ?? 'normal'; // normal|hs
        $isSunday = $dayCode === 'sun';
        $isSaturday = $dayCode === 'sat';
        $isWorking = in_array($dayCode, $workingDays) || ($isSaturday && $saturdayMode === 'normal');

        if ($pointages->isEmpty()) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                // absent seulement si c'est un jour travaillé
                'absent' => $isWorking,
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

        $settings = $this->loadWorktimeSettings();
        $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
        $minutesNormales = $hoursPerDay * 60;

        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresSupp = max(0, round(($minutesTravail - $minutesNormales) / 60, 2));

        $settings = $this->loadWorktimeSettings();
        $startHour = (int) ($settings['start_hour'] ?? 8);
        $startMinute = (int) ($settings['start_minute'] ?? 0);
        $heureTheorique = (clone $date)->setTime($startHour, $startMinute, 0);
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

    /**
     * Calcul des HS hebdomadaires en fonction de la config worktime.
     */
    protected function calculerHsHebdo(array $details, float $tauxHoraire): array
    {
        $config = $this->loadWorktimeSettings();
        $threshold = (float) ($config['weekly_threshold'] ?? 40);
        $mult = $config['multipliers'] ?? [];
        $weekdays = $config['working_days'] ?? ['mon','tue','wed','thu','fri'];
        $saturdayMode = $config['saturday_mode'] ?? 'normal';

        $weeks = [];
        foreach ($details as $d) {
            $date = Carbon::parse($d['jour']);
            $weekKey = $date->isoWeekYear() . '-' . $date->isoWeek();
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = ['weekday_hours' => 0, 'saturday_hours' => 0, 'sunday_hours' => 0];
            }
            $hours = $d['heures_travaillees'] ?? 0;
            $dayCode = strtolower(substr($date->format('D'), 0, 3));
            if ($date->isSunday()) {
                $weeks[$weekKey]['sunday_hours'] += $hours;
            } elseif ($dayCode === 'sat') {
                $weeks[$weekKey]['saturday_hours'] += $hours;
            } else {
                $weeks[$weekKey]['weekday_hours'] += $hours;
            }
        }

        $totalHsHours = 0;
        $totalHsAmount = 0;

        foreach ($weeks as $week) {
            $weekdayHours = $week['weekday_hours'];
            $saturdayHours = $week['saturday_hours'];
            $sundayHours = $week['sunday_hours'];

            // Si samedi "normal", il compte dans les heures ouvrées pour le seuil
            if ($saturdayMode === 'normal') {
                $weekdayHours += $saturdayHours;
                $saturdayHsHours = 0;
            } else {
                // samedi traité comme HS à taux spécifique
                $saturdayHsHours = $saturdayHours;
            }

            $overtime = max(0, $weekdayHours - $threshold);
            $first8 = min(8, $overtime);
            $next12 = min(12, max(0, $overtime - $first8));
            $beyond = max(0, $overtime - $first8 - $next12);

            $hsWeekHours = $overtime + $sundayHours + $saturdayHsHours;

            $amount = 0;
            $amount += $first8 * $tauxHoraire * ($mult['weekday_first8'] ?? 1.3);
            $amount += $next12 * $tauxHoraire * ($mult['weekday_next12'] ?? 1.5);
            $amount += $beyond * $tauxHoraire * ($mult['weekday_beyond'] ?? 1.5);
            $amount += $sundayHours * $tauxHoraire * ($mult['sunday'] ?? 1.4);
            $amount += $saturdayHsHours * $tauxHoraire * ($mult['saturday'] ?? 1.4);

            $totalHsHours += $hsWeekHours;
            $totalHsAmount += $amount;
        }

        return [round($totalHsHours, 2), round($totalHsAmount, 2)];
    }

    /**
     * Charge les paramètres depuis la table, ou fallback sur config/worktime.php.
     */
    protected function loadWorktimeSettings(): array
    {
        $setting = \App\Models\WorktimeSetting::first();
        if ($setting) {
            return [
                'working_days' => $setting->working_days ?: config('worktime.working_days'),
                'saturday_mode' => $setting->saturday_mode ?: config('worktime.saturday_mode'),
                'start_hour' => $setting->start_hour ?? config('worktime.start_hour'),
                'start_minute' => $setting->start_minute ?? config('worktime.start_minute'),
                'hours_per_day' => $setting->hours_per_day ?? config('worktime.hours_per_day'),
                'weekly_threshold' => $setting->weekly_threshold ?? config('worktime.weekly_threshold'),
                'multipliers' => $setting->multipliers ?: config('worktime.multipliers'),
            ];
        }
        return config('worktime');
    }
}
