<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PointageRequest;
use App\Models\Pointage;
use App\Models\DemandeConge;
use Carbon\Carbon;
use App\Models\WorktimeSetting;
use App\Models\JourFerie;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointageController extends Controller
{
    /**
     * Paramètres horaires de référence.
     */
    private int $journeeDebutHeure = 8;
    private int $journeeFinHeure   = 17;
    private int $pauseMinutes      = 60;
    public function index(Request $request)
    {
        try {
            $employeId = $request->query('employe_id');
            $from      = $request->query('from');
            $to        = $request->query('to');

            $pointages = Pointage::with('employe')
                ->forEmploye($employeId)
                ->between($from, $to)
                ->orderBy('pointe_a', 'asc')
                ->paginate(50);

            return response()->json($pointages);
        } catch (\Throwable $e) {
            Log::error('Erreur liste pointages', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function relevePaie(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'mois'       => 'required|date_format:Y-m',
        ]);

        $start = Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $pointages = Pointage::forEmploye($request->employe_id)
            ->between($start->toDateString(), $end->toDateString())
            ->orderBy('pointe_a')
            ->get()
            ->groupBy(fn($p) => $p->pointe_a->toDateString());

        $period = new DatePeriod($start, new DateInterval('P1D'), $end->copy()->addDay());

        $totalHeures = 0;
        $totalHs = 0;
        $totalRetards = 0;
        $absences = 0;
        $details = [];

        foreach ($period as $day) {
            $jourStr = $day->format('Y-m-d');
            $liste = $pointages[$jourStr] ?? collect();
            $resume = $this->calculerJournee($liste, $jourStr);

            // Absence justifiée si un congé approuvé couvre ce jour
            if ($resume['absent'] && $this->isCongeValide($request->employe_id, $jourStr)) {
                $resume['absent'] = false;
                $resume['absence_justifiee'] = true;
            }

            $totalHeures += $resume['heures_travaillees'];
            $totalHs += $resume['heures_supplementaires'];
            $totalRetards += $resume['retard_minutes'];
            if ($resume['absent']) {
                $absences++;
            }

            $details[] = array_merge(['jour' => $jourStr], $resume);
        }

        return response()->json([
            'employe_id' => $request->employe_id,
            'mois' => $request->mois,
            'totaux' => [
                'heures_travaillees' => round($totalHeures, 2),
                'heures_supplementaires' => round($totalHs, 2),
                'retard_minutes' => $totalRetards,
                'absences' => $absences,
            ],
            'details' => $details,
        ]);
    }

    public function store(PointageRequest $request)
    {
        try {
            $pointage = Pointage::create($request->validated());
            return response()->json($pointage, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation pointage', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function releveJournalier(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date'       => 'required|date',
        ]);

        $employeId = $request->employe_id;
        $date      = Carbon::parse($request->date)->toDateString();

        $pointages = Pointage::forEmploye($employeId)
            ->whereDate('pointe_a', $date)
            ->orderBy('pointe_a')
            ->get();

        $resume = $this->calculerJournee($pointages, $date);

        if ($resume['absent'] && $this->isCongeValide($employeId, $date)) {
            $resume['absent'] = false;
            $resume['absence_justifiee'] = true;
        }

        return response()->json([
            'date'      => $date,
            'employe_id'=> $employeId,
            'pointages' => $pointages,
            'resume'    => $resume,
        ]);
    }

    public function releveMensuel(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'year'       => 'required|integer',
            'month'      => 'required|integer|between:1,12',
        ]);

        $employeId = $request->employe_id;
        $year      = $request->year;
        $month     = $request->month;

        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to   = (clone $from)->endOfMonth();

        $pointages = Pointage::forEmploye($employeId)
            ->between($from->toDateString(), $to->toDateString())
            ->orderBy('pointe_a')
            ->get()
            ->groupBy(fn($p) => $p->pointe_a->toDateString());

        $result = [];
        foreach ($pointages as $jour => $liste) {
            $result[$jour] = $this->calculerJournee($liste, $jour);
        }

        return response()->json([
            'employe_id' => $employeId,
            'year'       => $year,
            'month'      => $month,
            'jours'      => $result,
        ]);
    }

    /**
     * Calcule les heures travaillées, heures sup, retard pour un jour donné (configurable via worktime).
     */
    protected function calculerJournee($pointages, $jour)
    {
        $settings = $this->loadWorktimeSettings();
        $workingDays = $settings['working_days'] ?? ['mon','tue','wed','thu','fri'];
        $saturdayMode = $settings['saturday_mode'] ?? 'normal';
        $startHour = (int) ($settings['start_hour'] ?? 8);
        $startMinute = (int) ($settings['start_minute'] ?? 0);
        $retardTolerance = (int) ($settings['retard_tolerance_minutes'] ?? 0);
        $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
        $pauseMinutes = (int) ($settings['pause_minutes'] ?? 60);

        $dateObj = Carbon::parse($jour);
        $dayCode = strtolower(substr($dateObj->format('D'), 0, 3));
        $isSunday = $dateObj->isSunday();
        $isSaturday = $dayCode === 'sat';
        $isHoliday = $this->isHoliday($dateObj);
        $isWorkingDay = in_array($dayCode, $workingDays) || ($isSaturday && $saturdayMode === 'normal');

        if ($pointages->isEmpty()) {
            $absent = $isWorkingDay && !$isHoliday;
            $weekend = ($isSaturday || $isSunday) && !$isWorkingDay;
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'premiere_entree' => null,
                'derniere_sortie' => null,
                'absent' => $absent,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $isSunday,
                'ferie' => $isHoliday,
                'weekend' => $weekend,
                'minutes_pauses' => 0,
                'present_partiel' => false,
            ];
        }

        $premiereEntree = $pointages->firstWhere('type', 'entree');
        $derniereSortie = $pointages->where('type', 'sortie')->last();

        if (!$premiereEntree || !$derniereSortie) {
            $absent = $isWorkingDay && !$isHoliday;
            $weekend = ($isSaturday || $isSunday) && !$isWorkingDay;
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'premiere_entree' => optional($premiereEntree)->pointe_a,
                'derniere_sortie' => optional($derniereSortie)->pointe_a,
                'absent' => $absent,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $isSunday,
                'ferie' => $isHoliday,
                'weekend' => $weekend,
                'minutes_pauses' => 0,
            ];
        }

        $debut = $premiereEntree->pointe_a;
        $fin   = $derniereSortie->pointe_a;

        $minutesBrut = $debut->diffInMinutes($fin);
        $pauses = $this->calculerDureePauses($pointages);
        $minutesTravail = max(0, $minutesBrut - $pauses);
        $minutesNormales = max(0, ($hoursPerDay * 60) - $pauseMinutes);

        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresSupp = max(0, round(($minutesTravail - $minutesNormales) / 60, 2));

        // Si férié ou week-end non travaillé, tout le temps est compté en HS (majoré ailleurs)
        if ($isHoliday || (($isSaturday || $isSunday) && !$isWorkingDay)) {
            $heuresSupp = $heuresTravaillees;
            $heuresTravaillees = 0;
        }

        $heureTheorique = (clone $dateObj)->setTime($startHour, $startMinute, 0);
        $retardMinutes = 0;
        if (!$isHoliday && !$isSunday && !($isSaturday && !$isWorkingDay) && $debut->greaterThan($heureTheorique)) {
            $retardMinutes = max(0, $heureTheorique->diffInMinutes($debut) - $retardTolerance);
        }

        // Présence partielle si heures < heures_per_day
        $presentPartiel = $heuresTravaillees > 0 && $heuresTravaillees < $hoursPerDay;

        return [
            'heures_travaillees'      => $heuresTravaillees,
            'heures_supplementaires'  => $heuresSupp,
            'retard_minutes'          => $retardMinutes,
            'premiere_entree'         => $debut,
            'derniere_sortie'         => $fin,
            'minutes_pauses'          => $pauses,
            'absent'                  => false,
            'absence_justifiee'       => false,
            'conge'                   => false,
            'dimanche'                => $isSunday,
            'ferie'                   => $isHoliday,
            'weekend'                 => ($isSaturday || $isSunday) && !$isWorkingDay,
            'present_partiel'         => $presentPartiel,
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
     * Vérifie si un congé validé couvre le jour donné.
     */
    protected function isCongeValide(int $employeId, string $jour): bool
    {
        return DemandeConge::where('employe_id', $employeId)
            ->where('statut', 'rh_valide')
            ->whereDate('date_debut', '<=', $jour)
            ->whereDate('date_fin', '>=', $jour)
            ->exists();
    }

    protected function isHoliday(Carbon $date): bool
    {
        $dayMonth = $date->format('m-d');
        return JourFerie::where(function ($q) use ($date) {
                $q->whereDate('date', $date->toDateString())
                  ->where('recurrent', false);
            })
            ->orWhere(function ($q) use ($dayMonth) {
                $q->whereRaw("to_char(date, 'MM-DD') = ?", [$dayMonth])
                  ->where('recurrent', true);
            })
            ->exists();
    }

    protected function loadWorktimeSettings(): array
    {
        $setting = WorktimeSetting::first();
        if ($setting) {
            return [
                'working_days' => $setting->working_days ?: config('worktime.working_days'),
                'saturday_mode' => $setting->saturday_mode ?: config('worktime.saturday_mode'),
                'start_hour' => $setting->start_hour ?? config('worktime.start_hour'),
                'start_minute' => $setting->start_minute ?? config('worktime.start_minute'),
                'retard_tolerance_minutes' => $setting->retard_tolerance_minutes ?? config('worktime.retard_tolerance_minutes', 0),
                'hours_per_day' => $setting->hours_per_day ?? config('worktime.hours_per_day'),
                'weekly_threshold' => $setting->weekly_threshold ?? config('worktime.weekly_threshold'),
                'multipliers' => $setting->multipliers ?: config('worktime.multipliers'),
                'pause_minutes' => $setting->pause_minutes ?? config('worktime.pause_minutes', 60),
            ];
        }
        return config('worktime');
    }
}
