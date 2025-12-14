<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PointageRequest;
use App\Models\Pointage;
use App\Models\DemandeConge;
use Carbon\Carbon;
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
     * Calcule les heures travaillées, heures sup, retard pour un jour donné.
     * Hypothèse : journée standard 08:00–17:00 avec 1h de pause (8h de travail).
     */
    protected function calculerJournee($pointages, $jour)
    {
        $estDimanche = Carbon::parse($jour)->isSunday();

        if ($pointages->isEmpty()) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'premiere_entree' => null,
                'derniere_sortie' => null,
                'absent' => true,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $estDimanche,
                'minutes_pauses' => 0,
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
                'premiere_entree' => optional($premiereEntree)->pointe_a,
                'derniere_sortie' => optional($derniereSortie)->pointe_a,
                'absent' => true,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $estDimanche,
                'minutes_pauses' => 0,
            ];
        }

        $debut = $premiereEntree->pointe_a;
        $fin   = $derniereSortie->pointe_a;

        $minutesBrut = $debut->diffInMinutes($fin);
        $pauses = $this->calculerDureePauses($pointages);
        $minutesTravail = max(0, $minutesBrut - $pauses);
        Log::debug("Minutes travail: " . $minutesTravail);
        $minutesNormales = ($this->journeeFinHeure - $this->journeeDebutHeure) * 60 - $this->pauseMinutes;

        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresSupp = max(0, round(($minutesTravail - $minutesNormales) / 60, 2));

        $heureTheorique = (clone $date)->setTime($this->journeeDebutHeure, 0, 0);
        $retardMinutes = 0;
        if ($debut->greaterThan($heureTheorique)) {
            $retardMinutes = $heureTheorique->diffInMinutes($debut);
        }

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
            'dimanche'                => $estDimanche,
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
}
