<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paie;
use App\Models\PaieDetail;
use App\Models\PaieParametre;
use App\Models\PaiePrime;
use App\Models\Pointage;
use App\Models\Contrat;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use App\Models\Employe;
use App\Models\IrsaTranche;
use App\Models\DemandeConge;
use App\Models\JourFerie;
use App\Services\CongeService;
use Barryvdh\DomPDF\Facade\Pdf;
use DateInterval;
use DatePeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaieController extends Controller
{
    protected CongeService $congeService;

    public function __construct(CongeService $congeService)
    {
        $this->congeService = $congeService;
    }

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

            if (Paie::where('employe_id', $employe->id)->where('mois', $mois)->exists()) {
                return response()->json(['message' => 'Une fiche de paie existe déjà pour cet employé et ce mois'], 422);
            }

            $salaireBase = $this->recupererSalaireBase($employe->id);
            $tauxHoraire = $salaireBase > 0 ? $salaireBase / 173.33 : 0;

            [$heuresTrav, $details, $absences, $retardsTotal, $heuresManquantes] = $this->calculerHeuresMois($employe->id, $mois);
            [$heuresSup, $montantHs, $heuresNuit, $montantNuit] = $this->calculerHsHebdo($details, $tauxHoraire);

            $deductionRetards = ($retardsTotal / 60) * $tauxHoraire;
            $deductionAbsences = $absences * ($salaireBase / 30);
            $deductionPartiel = $heuresManquantes * $tauxHoraire;

            $brut = $salaireBase
                + $param->prime_transport
                + $param->prime_presence
                + $montantHs
                + $montantNuit;

            $baseCnaps = min($brut, $param->cnaps_plafond ?? $brut);
            $cnaps = $baseCnaps * (($param->cnaps_taux_employe ?? $param->cnaps) / 100);
            $ostie = $brut * (($param->ostie_taux_employe ?? $param->ostie) / 100);
            $revenuImposable = max(0, $brut - $cnaps - $ostie);
            $irsa  = $this->calculerIrsaProgressif($revenuImposable);
            $settings = $this->loadWorktimeSettings();
            $appliquerSalaire = (bool) ($settings['deduct_from_salary'] ?? true);
            $appliquerSolde = (bool) ($settings['deduct_from_leave_balance'] ?? false);
            $retenues = $cnaps + $ostie + $irsa;
            if ($appliquerSalaire) {
                $retenues += $deductionRetards + $deductionAbsences + $deductionPartiel;
            }

            // Consommer le solde congé pour absences/retards si activé
            if ($appliquerSolde) {
                $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
                $joursRetards = ($retardsTotal > 0 && $hoursPerDay > 0) ? ($retardsTotal / 60) / $hoursPerDay : 0;
                $joursPartiels = ($heuresManquantes > 0 && $hoursPerDay > 0) ? ($heuresManquantes / $hoursPerDay) : 0;
                $joursTotal = round($absences + $joursRetards + $joursPartiels, 4);
                if ($joursTotal > 0) {
                    $this->congeService->consommerAbsenceAuto($employe->id, $joursTotal, $start->copy(), $mois);
                }
            }

            Log::info("Calcul paie pour Employe ID: {$employe->id}, Mois: {$mois}");
            Log::info("retenues: CNAPS: {$cnaps}, OSTIE: {$ostie}, IRSA: {$irsa}, Retards: {$deductionRetards}, Absences: {$deductionAbsences}");
            Log::info("Total retenues: {$retenues}");
            $net = $brut - $retenues;

            $paie = Paie::create([
                'employe_id'            => $employe->id,
                'mois'                  => $mois,
                'statut'                => 'en_attente_validation',
                'demande_validation_le' => now(),
                'salaire_base'          => $salaireBase,
                'heures_travaillees'    => $heuresTrav,
                'heures_supplementaires'=> $heuresSup,
                'montant_hs'            => $montantHs,
                'heures_nuit'           => $heuresNuit,
                'montant_nuit'          => $montantNuit,
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
        $settings = $this->loadWorktimeSettings();

        $pointages = Pointage::forEmploye($employeId)
            ->between($start->toDateString(), $end->toDateString())
            ->orderBy('pointe_a')
            ->get()
            ->groupBy(fn($p) => $p->pointe_a->toDateString());

        $totalHeures = 0;
        $totalRetards = 0;
        $absences = 0;
        $heuresManquantes = 0;
        $details = [];

        $period = new DatePeriod($start, new DateInterval('P1D'), $end->copy()->addDay());

        foreach ($period as $day) {
            $jour = $day->format('Y-m-d');
            $liste = $pointages[$jour] ?? collect();
            $resume = $this->calculerJournee($liste, $jour);
            if ($resume['absent'] && $this->isCongeValide($employeId, $jour)) {
                $resume['absent'] = false;
                $resume['absence_justifiee'] = true;
            }
            $totalHeures += $resume['heures_travaillees'];
            $totalRetards += $resume['retard_minutes'];
            if ($resume['absent']) {
                $absences++;
            }

            if (!$resume['ferie'] && !$resume['weekend'] && !$resume['absence_justifiee']) {
                if ($resume['present_partiel'] && ($settings['hours_per_day'] ?? 8) > $resume['heures_travaillees']) {
                    $heuresManquantes += ($settings['hours_per_day'] ?? 8) - $resume['heures_travaillees'];
                }
            }

            $details[] = [
                'jour' => $jour,
                'heures_travaillees' => $resume['heures_travaillees'],
                'heures_supplementaires' => $resume['heures_supplementaires'],
                'retard_minutes' => $resume['retard_minutes'],
                'absent' => $resume['absent'],
                'absence_justifiee' => $resume['absence_justifiee'],
                'ferie' => $resume['ferie'],
                'weekend' => $resume['weekend'],
                'present_partiel' => $resume['present_partiel'],
            ];
        }

        return [$totalHeures, $details, $absences, $totalRetards, $heuresManquantes];
    }

    protected function calculerJournee($pointages, $jour)
    {
        $settings = $this->loadWorktimeSettings();
        $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
        $startHour = (int) ($settings['start_hour'] ?? 8);
        $startMinute = (int) ($settings['start_minute'] ?? 0);
        $workingDays = $settings['working_days'] ?? ['mon','tue','wed','thu','fri'];
        $saturdayMode = $settings['saturday_mode'] ?? 'normal';

        $date = Carbon::parse($jour);
        $dayCode = strtolower(substr($date->format('D'), 0, 3));
        $isSunday = $date->isSunday();
        $isSaturday = $dayCode === 'sat';
        $isHoliday = $this->isHoliday($date);
        $isWorking = in_array($dayCode, $workingDays) || ($isSaturday && $saturdayMode === 'normal');
        $isWeekend = ($isSaturday || $isSunday) && !$isWorking;

        if ($pointages->isEmpty()) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'premiere_entree' => null,
                'derniere_sortie' => null,
                'absent' => $isWorking && !$isHoliday,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $isSunday,
                'ferie' => $isHoliday,
                'weekend' => $isWeekend,
                'minutes_pauses' => 0,
                'present_partiel' => false,
            ];
        }

        $premiereEntree = $pointages->firstWhere('type', 'entree');
        $derniereSortie = $pointages->where('type', 'sortie')->last();

        if (!$premiereEntree || !$derniereSortie) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'premiere_entree' => optional($premiereEntree)->pointe_a,
                'derniere_sortie' => optional($derniereSortie)->pointe_a,
                'absent' => $isWorking && !$isHoliday,
                'absence_justifiee' => false,
                'conge' => false,
                'dimanche' => $isSunday,
                'ferie' => $isHoliday,
                'weekend' => $isWeekend,
                'minutes_pauses' => 0,
                'present_partiel' => false,
            ];
        }

        $debut = $premiereEntree->pointe_a;
        $fin   = $derniereSortie->pointe_a;

        $minutesBrut = $debut->diffInMinutes($fin);
        $pauses = $this->calculerDureePauses($pointages);
        $minutesTravail = max(0, $minutesBrut - $pauses);
        $minutesNormales = max(0, ($hoursPerDay * 60) - ($settings['pause_minutes'] ?? 60));

        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresSupp = max(0, round(($minutesTravail - $minutesNormales) / 60, 2));

        if ($isHoliday || $isWeekend) {
            $heuresSupp = $heuresTravaillees;
            $heuresTravaillees = 0;
        }

        $heureTheorique = (clone $date)->setTime($startHour, $startMinute, 0);
        $retardMinutes = 0;
        if (!$isHoliday && !$isWeekend && $debut->greaterThan($heureTheorique)) {
            $retardMinutes = $heureTheorique->diffInMinutes($debut);
        }

        $presentPartiel = $heuresTravaillees > 0 && $heuresTravaillees < $hoursPerDay && !$isHoliday && !$isWeekend;

        return [
            'heures_travaillees'      => $heuresTravaillees,
            'heures_supplementaires'  => $heuresSupp,
            'retard_minutes'          => $retardMinutes,
            'premiere_entree'         => $debut,
            'derniere_sortie'         => $fin,
            'absent'                  => false,
            'absence_justifiee'       => false,
            'conge'                   => false,
            'dimanche'                => $isSunday,
            'ferie'                   => $isHoliday,
            'weekend'                 => $isWeekend,
            'minutes_pauses'          => $pauses,
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
        $nightStart = $config['night_start'] ?? '22:00';
        $nightEnd = $config['night_end'] ?? '05:00';
        $nightRatePercent = $this->normalizePercent($config['night_rate'] ?? 0);
        $nightRateFactor = $nightRatePercent / 100;

        [$nightStartH, $nightStartM] = array_map('intval', explode(':', $nightStart));
        [$nightEndH, $nightEndM] = array_map('intval', explode(':', $nightEnd));

        $weeks = [];
        foreach ($details as $d) {
            $date = Carbon::parse($d['jour']);
            $weekKey = $date->isoWeekYear() . '-' . $date->isoWeek();
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = ['weekday_hours' => 0, 'saturday_hours' => 0, 'sunday_hours' => 0, 'night_hours' => 0];
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

            // Calcul heures de nuit (approche simple : si début de nuit dans la journée, on ajoute l'intégralité des heures de la journée comme de nuit si pointage de nuit indiqué dans détail)
            if (!empty($d['heures_supplementaires']) || !empty($d['heures_travaillees'])) {
                // approximation : si la journée est marquée comme nuit (heures_nuit dans détail si présent) sinon 0
                $weeks[$weekKey]['night_hours'] += $d['heures_nuit'] ?? 0;
            }
        }

        $totalHsHours = 0;
        $totalHsAmount = 0;
        $totalNightHours = 0;
        $totalNightAmount = 0;

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
            $amount += $first8 * $tauxHoraire * $this->toMultiplier($this->normalizePercent($mult['weekday_first8'] ?? 30));
            $amount += $next12 * $tauxHoraire * $this->toMultiplier($this->normalizePercent($mult['weekday_next12'] ?? 50));
            $amount += $beyond * $tauxHoraire * $this->toMultiplier($this->normalizePercent($mult['weekday_beyond'] ?? 50));
            $amount += $sundayHours * $tauxHoraire * $this->toMultiplier($this->normalizePercent($mult['sunday'] ?? 40));
            $amount += $saturdayHsHours * $tauxHoraire * $this->toMultiplier($this->normalizePercent($mult['saturday'] ?? 40));

            $totalHsHours += $hsWeekHours;
            $totalHsAmount += $amount;

            // Bonus nuit en plus (sur les heures de nuit de la semaine)
            $totalNightHours += $week['night_hours'];
            $totalNightAmount += $week['night_hours'] * $tauxHoraire * $nightRateFactor;
        }

        return [round($totalHsHours, 2), round($totalHsAmount, 2), round($totalNightHours, 2), round($totalNightAmount, 2)];
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
                'night_start' => $setting->night_start ?? config('worktime.night_start'),
                'night_end' => $setting->night_end ?? config('worktime.night_end'),
                'night_rate' => $setting->night_rate ?? config('worktime.night_rate'),
                'deduct_from_leave_balance' => $setting->deduct_from_leave_balance ?? config('worktime.deduct_from_leave_balance', true),
                'deduct_from_salary' => $setting->deduct_from_salary ?? config('worktime.deduct_from_salary', true),
            ];
        }
        return config('worktime');
    }

    private function normalizePercent($value): float
    {
        if ($value === null) {
            return 0;
        }
        $v = (float) $value;
        if ($v < 1) {
            return $v * 100; // ex: 0.2 -> 20%
        }
        if ($v <= 3) {
            return max(0, ($v - 1) * 100); // ex: 1.3 -> 30%
        }
        return $v; // déjà en pourcentage
    }

    private function toMultiplier(float $percent): float
    {
        return 1 + ($percent / 100);
    }

    /**
     * Calcul IRSA avec tranches progressives si configurées, sinon fallback paramètre unique.
     */
    protected function calculerIrsaProgressif(float $brut): float
    {
        $tranches = IrsaTranche::orderBy('min_base')->get();
        if ($tranches->isEmpty()) {
            $param = PaieParametre::first();
            return max(0, ($brut - ($param->irsa_base ?? 0)) * (($param->irsa_taux ?? 0) / 100));
        }

        $irsa = 0;
        foreach ($tranches as $t) {
            $borne_inf = $t->min_base > 0 ? $t->min_base - 1 : 0; // bornes inclusives
            $max = $t->max_base ?? $brut;
            if ($brut <= $borne_inf) {
                continue;
            }
            $plafond = min($brut, $max);
            $assiette = max(0, $plafond - $borne_inf);
            $assiette_arrondie = ceil($assiette);
            $irsa += $assiette_arrondie * ($t->taux / 100);
            if ($brut <= $max) {
                break;
            }
        }
        return $irsa;
    }

    /**
     * État de paie (agrégation) pour un mois (YYYY-MM) ou une année (YYYY).
     * - mois=YYYY-MM -> détails par employé + totaux mois
     * - annee=YYYY -> totaux annuels + totaux par mois
     */
    public function etat(Request $request)
    {
        $validated = $request->validate([
            'mois' => 'nullable|date_format:Y-m',
            'annee' => 'nullable|digits:4',
            'paiement' => 'nullable|in:prevision,paye',
            'statut' => 'nullable|in:tous,non_genere,en_attente_validation,non_paye,paiement_en_validation,paye',
        ]);

        $mois = $validated['mois'] ?? null;
        $annee = $validated['annee'] ?? null;
        $statut = $validated['statut'] ?? null;
        if (!$statut && ($validated['paiement'] ?? null) === 'paye') {
            $statut = 'paye';
        }
        $statut = $statut ?: 'tous';

        if (!$mois && !$annee) {
            $mois = now()->format('Y-m');
            $annee = now()->format('Y');
        }

        if ($mois) {
            $annee = substr($mois, 0, 4);
        }

        if (!$mois) {
            $pattern = "{$annee}-%";

            $baseQuery = Paie::query()->where('mois', 'like', $pattern);
            if ($statut === 'paye') {
                $baseQuery->whereNotNull('paye_le');
            } elseif ($statut === 'non_paye') {
                $baseQuery->whereIn('statut', ['non_paye', 'paiement_en_validation']);
            } elseif (in_array($statut, ['en_attente_validation', 'paiement_en_validation'], true)) {
                $baseQuery->where('statut', $statut);
            } elseif ($statut === 'non_genere') {
                $baseQuery->whereRaw('1 = 0');
            }

            $totaux = (clone $baseQuery)
                ->selectRaw('COUNT(*) as bulletins')
                ->selectRaw('COALESCE(SUM(net_a_payer), 0) as net_a_payer')
                ->selectRaw('COALESCE(SUM(total_brut), 0) as total_brut')
                ->selectRaw('COALESCE(SUM(total_retenues), 0) as total_retenues')
                ->selectRaw('COALESCE(SUM(retenue_cnaps), 0) as retenue_cnaps')
                ->selectRaw('COALESCE(SUM(retenue_ostie), 0) as retenue_ostie')
                ->selectRaw('COALESCE(SUM(retenue_irsa), 0) as retenue_irsa')
                ->selectRaw('COALESCE(SUM(prime_transport), 0) as prime_transport')
                ->selectRaw('COALESCE(SUM(prime_presence), 0) as prime_presence')
                ->selectRaw('COALESCE(SUM(autres_primes), 0) as autres_primes')
                ->first();

            $parMois = (clone $baseQuery)
                ->select('mois')
                ->selectRaw('COUNT(*) as bulletins')
                ->selectRaw('COALESCE(SUM(net_a_payer), 0) as net_a_payer')
                ->selectRaw('COALESCE(SUM(total_brut), 0) as total_brut')
                ->selectRaw('COALESCE(SUM(total_retenues), 0) as total_retenues')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get();

            return response()->json([
                'periode' => [
                    'annee' => (int) $annee,
                    'mois' => null,
                    'statut' => $statut,
                ],
                'totaux' => [
                    'bulletins' => (int) ($totaux->bulletins ?? 0),
                    'net_a_payer' => (float) ($totaux->net_a_payer ?? 0),
                    'total_brut' => (float) ($totaux->total_brut ?? 0),
                    'total_retenues' => (float) ($totaux->total_retenues ?? 0),
                    'retenue_cnaps' => (float) ($totaux->retenue_cnaps ?? 0),
                    'retenue_ostie' => (float) ($totaux->retenue_ostie ?? 0),
                    'retenue_irsa' => (float) ($totaux->retenue_irsa ?? 0),
                    'prime_transport' => (float) ($totaux->prime_transport ?? 0),
                    'prime_presence' => (float) ($totaux->prime_presence ?? 0),
                    'autres_primes' => (float) ($totaux->autres_primes ?? 0),
                ],
                'par_mois' => $parMois,
            ]);
        }

        $rows = $this->buildEtatPaieRows($mois);
        $statusCounts = $rows->countBy('statut')->all();

        if ($statut !== 'tous') {
            $rows = $rows->filter(function ($row) use ($statut) {
                if ($statut === 'non_paye') {
                    return in_array($row['statut'], ['non_genere', 'non_paye', 'paiement_en_validation'], true);
                }

                return $row['statut'] === $statut;
            })->values();
        }

        $resteAPayer = $rows->sum(function ($row) {
            if (!in_array($row['statut'], ['non_genere', 'en_attente_validation', 'non_paye', 'paiement_en_validation'], true)) {
                return 0;
            }

            return $row['paie_id'] ? (float) $row['net_a_payer'] : (float) $row['salaire_previsionnel'];
        });

        $totaux = [
            'employes_actifs' => $rows->count(),
            'bulletins' => $rows->whereNotNull('paie_id')->count(),
            'prevision_salaire_base' => round($rows->sum('salaire_previsionnel'), 2),
            'net_a_payer' => round($rows->sum('net_a_payer'), 2),
            'total_brut' => round($rows->sum('total_brut'), 2),
            'total_retenues' => round($rows->sum('total_retenues'), 2),
            'retenue_cnaps' => round($rows->sum('retenue_cnaps'), 2),
            'retenue_ostie' => round($rows->sum('retenue_ostie'), 2),
            'retenue_irsa' => round($rows->sum('retenue_irsa'), 2),
            'reste_a_payer' => round($resteAPayer, 2),
            'deja_paye' => round($rows->where('statut', 'paye')->sum('net_a_payer'), 2),
        ];

        return response()->json([
            'periode' => [
                'annee' => (int) $annee,
                'mois' => $mois,
                'statut' => $statut,
            ],
            'totaux' => $totaux,
            'status_counts' => [
                'non_genere' => (int) ($statusCounts['non_genere'] ?? 0),
                'en_attente_validation' => (int) ($statusCounts['en_attente_validation'] ?? 0),
                'non_paye' => (int) ($statusCounts['non_paye'] ?? 0),
                'paiement_en_validation' => (int) ($statusCounts['paiement_en_validation'] ?? 0),
                'paye' => (int) ($statusCounts['paye'] ?? 0),
            ],
            'details' => $rows->values(),
        ]);
    }

    public function payer(Request $request, $id)
    {
        $data = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
        ]);

        try {
            $mouvement = DB::transaction(function () use ($id, $data) {
                $paie = Paie::with('employe')->lockForUpdate()->findOrFail($id);

                if ($this->statutPaie($paie) !== 'non_paye') {
                    abort(422, 'Seule une fiche validée et non payée peut être envoyée au paiement');
                }

                if (!Caisse::where('id', $data['caisse_id'])->where('active', true)->exists()) {
                    abort(422, 'Cette caisse est désactivée');
                }

                $dejaEnAttente = CaisseMouvement::where('paie_id', $paie->id)
                    ->where('statut', 'en_attente_validation')
                    ->exists();

                if ($dejaEnAttente) {
                    abort(422, 'Une demande de paiement est déjà en attente de validation');
                }

                $mouvement = CaisseMouvement::create([
                    'caisse_id' => $data['caisse_id'],
                    'paie_id' => $paie->id,
                    'type' => 'sortie',
                    'montant' => $paie->net_a_payer,
                    'source' => "Paiement fiche de paie {$paie->mois}",
                    'description' => 'Paiement de la fiche de paie de ' . trim(($paie->employe->nom ?? '') . ' ' . ($paie->employe->prenom ?? '')),
                    'statut' => 'en_attente_validation',
                    'demande_validation_le' => now(),
                ]);

                $paie->update(['statut' => 'paiement_en_validation']);

                return $mouvement->load('caisse');
            });

            return response()->json([
                'message' => 'Demande de paiement envoyée en validation caisse',
                'mouvement' => $mouvement,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Erreur paiement paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function annuler($id)
    {
        try {
            $paie = Paie::findOrFail($id);

            if ($this->statutPaie($paie) !== 'en_attente_validation') {
                return response()->json(['message' => 'Seule une fiche en attente de validation peut être annulée'], 422);
            }

            $paie->delete();

            return response()->json(['message' => 'Génération de fiche annulée']);
        } catch (\Throwable $e) {
            Log::error('Erreur annulation paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function valider($id)
    {
        try {
            $paie = Paie::findOrFail($id);
            $paie->update([
                'statut' => 'non_paye',
                'valide_le' => now(),
            ]);

            return response()->json(['message' => 'Fiche de paie validée', 'paie' => $paie]);
        } catch (\Throwable $e) {
            Log::error('Erreur validation paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function enAttenteValidation(Request $request)
    {
        $validated = $request->validate([
            'mois' => 'nullable|date_format:Y-m',
        ]);

        $query = Paie::with(['employe:id,matricule,nom,prenom'])
            ->where('statut', 'en_attente_validation')
            ->orderByDesc('created_at');

        if (!empty($validated['mois'])) {
            $query->where('mois', $validated['mois']);
        }

        return response()->json($query->paginate(15));
    }

    public function show($id)
    {
        try {
            $paie = Paie::with([
                'employe.poste',
                'employe.departement',
                'details' => fn ($query) => $query->orderBy('jour'),
                'primes',
            ])->findOrFail($id);

            $start = Carbon::createFromFormat('Y-m', $paie->mois)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $contrat = Contrat::where('employe_id', $paie->employe_id)
                ->whereDate('date_debut', '<=', $end->toDateString())
                ->where(function ($query) use ($start) {
                    $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $start->toDateString());
                })
                ->orderByDesc('date_debut')
                ->first();
            $mouvementPaiement = CaisseMouvement::with('caisse:id,nom,solde')
                ->where('paie_id', $paie->id)
                ->where('type', 'sortie')
                ->orderByDesc('created_at')
                ->first();

            return response()->json([
                'paie' => $paie,
                'contrat' => $contrat,
                'mouvement_paiement' => $mouvementPaiement,
                'statut' => [
                    'code' => $this->statutPaie($paie),
                    'label' => $this->statutPaieLabel($this->statutPaie($paie)),
                ],
                'resume' => $this->resumePaie($paie),
                'retenues' => [
                    ['label' => 'CNAPS', 'montant' => (float) $paie->retenue_cnaps],
                    ['label' => 'OSTIE', 'montant' => (float) $paie->retenue_ostie],
                    ['label' => 'IRSA', 'montant' => (float) $paie->retenue_irsa],
                ],
                'primes' => [
                    ['label' => 'Prime transport', 'montant' => (float) $paie->prime_transport],
                    ['label' => 'Prime présence', 'montant' => (float) $paie->prime_presence],
                    ['label' => 'Autres primes', 'montant' => (float) $paie->autres_primes],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur détail paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function prevision(Request $request)
    {
        $validated = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'mois' => 'required|date_format:Y-m',
        ]);

        try {
            $start = Carbon::createFromFormat('Y-m', $validated['mois'])->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $contrat = Contrat::with(['employe.poste', 'employe.departement'])
                ->where('employe_id', $validated['employe_id'])
                ->where('statut', 'en_cours')
                ->whereDate('date_debut', '<=', $end->toDateString())
                ->where(function ($query) use ($start) {
                    $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $start->toDateString());
                })
                ->orderByDesc('date_debut')
                ->firstOrFail();

            $salaireBase = (float) $contrat->salaire_base;

            return response()->json([
                'paie' => [
                    'id' => null,
                    'employe_id' => $contrat->employe_id,
                    'mois' => $validated['mois'],
                    'employe' => $contrat->employe,
                    'salaire_base' => $salaireBase,
                    'heures_travaillees' => 0,
                    'heures_supplementaires' => 0,
                    'montant_hs' => 0,
                    'prime_transport' => 0,
                    'prime_presence' => 0,
                    'autres_primes' => 0,
                    'retenue_cnaps' => 0,
                    'retenue_ostie' => 0,
                    'retenue_irsa' => 0,
                    'total_brut' => $salaireBase,
                    'total_retenues' => 0,
                    'net_a_payer' => $salaireBase,
                    'demande_validation_le' => null,
                    'valide_le' => null,
                    'paye_le' => null,
                    'details' => [],
                ],
                'contrat' => $contrat,
                'mouvement_paiement' => null,
                'statut' => [
                    'code' => 'non_genere',
                    'label' => $this->statutPaieLabel('non_genere'),
                ],
                'resume' => $this->resumePaie(null),
                'retenues' => [
                    ['label' => 'CNAPS', 'montant' => 0],
                    ['label' => 'OSTIE', 'montant' => 0],
                    ['label' => 'IRSA', 'montant' => 0],
                ],
                'primes' => [
                    ['label' => 'Prime transport', 'montant' => 0],
                    ['label' => 'Prime présence', 'montant' => 0],
                    ['label' => 'Autres primes', 'montant' => 0],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur prévision paie', ['data' => $validated, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Aucun contrat actif trouvé pour cette période'], 404);
        }
    }

    public function recuPaiement($id)
    {
        try {
            $paie = Paie::with(['employe.poste'])->findOrFail($id);
            $mouvement = CaisseMouvement::with('caisse')
                ->where('paie_id', $paie->id)
                ->where('type', 'sortie')
                ->where('statut', 'valide')
                ->orderByDesc('valide_le')
                ->first();

            if (!$mouvement) {
                return response()->json(['message' => 'Aucun paiement validé pour cette fiche'], 422);
            }

            $pdf = Pdf::loadView('pdf.recu_paiement_paie', [
                'paie' => $paie,
                'mouvement' => $mouvement,
                'employe' => $paie->employe,
                'caisse' => $mouvement->caisse,
            ]);

            return $pdf->download("recu_paiement_{$paie->employe_id}_{$paie->mois}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération reçu paiement', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function buildEtatPaieRows(string $mois)
    {
        $start = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $contrats = Contrat::with(['employe.poste', 'employe.departement'])
            ->where('statut', 'en_cours')
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->where(function ($query) use ($start) {
                $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $start->toDateString());
            })
            ->orderBy('employe_id')
            ->orderByDesc('date_debut')
            ->get()
            ->unique('employe_id')
            ->values();

        $paies = Paie::query()
            ->with(['details'])
            ->where('mois', $mois)
            ->whereIn('employe_id', $contrats->pluck('employe_id'))
            ->get()
            ->keyBy('employe_id');

        $paiementMouvements = CaisseMouvement::with('caisse:id,nom')
            ->whereIn('paie_id', $paies->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('paie_id')
            ->map(fn ($items) => $items->first());

        return $contrats->map(function (Contrat $contrat) use ($paies, $paiementMouvements) {
            $paie = $paies->get($contrat->employe_id);
            $statut = $this->statutPaie($paie);
            $mouvementPaiement = $paie ? $paiementMouvements->get($paie->id) : null;

            return [
                'employe_id' => $contrat->employe_id,
                'contrat_id' => $contrat->id,
                'contrat_numero' => $contrat->numero,
                'contrat_debut' => optional($contrat->date_debut)->toDateString(),
                'contrat_fin' => optional($contrat->date_fin)->toDateString(),
                'employe' => $contrat->employe,
                'salaire_previsionnel' => (float) $contrat->salaire_base,
                'paie_id' => $paie?->id,
                'statut' => $statut,
                'statut_label' => $this->statutPaieLabel($statut),
                'salaire_base' => (float) ($paie?->salaire_base ?? $contrat->salaire_base),
                'total_brut' => (float) ($paie?->total_brut ?? 0),
                'total_retenues' => (float) ($paie?->total_retenues ?? 0),
                'net_a_payer' => (float) ($paie?->net_a_payer ?? 0),
                'retenue_cnaps' => (float) ($paie?->retenue_cnaps ?? 0),
                'retenue_ostie' => (float) ($paie?->retenue_ostie ?? 0),
                'retenue_irsa' => (float) ($paie?->retenue_irsa ?? 0),
                'paye_le' => $paie?->paye_le?->toDateString(),
                'demande_validation_le' => $paie?->demande_validation_le?->toDateTimeString() ?? $paie?->created_at?->toDateTimeString(),
                'valide_le' => $paie?->valide_le?->toDateTimeString(),
                'paiement_mouvement_id' => $mouvementPaiement?->id,
                'paiement_demande_le' => $mouvementPaiement?->demande_validation_le?->toDateTimeString(),
                'paiement_valide_le' => $mouvementPaiement?->valide_le?->toDateTimeString(),
                'caisse_nom' => $mouvementPaiement?->caisse?->nom,
                'details_paie' => $this->resumePaie($paie),
            ];
        });
    }

    protected function resumePaie(?Paie $paie): array
    {
        if (!$paie) {
            return [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0,
                'retard_minutes' => 0,
                'absences' => 0,
                'absences_justifiees' => 0,
                'jours_feries' => 0,
                'weekends' => 0,
            ];
        }

        $details = $paie->relationLoaded('details') ? $paie->details : $paie->details()->get();

        return [
            'heures_travaillees' => round((float) $details->sum('heures_travaillees'), 2),
            'heures_supplementaires' => round((float) $details->sum('heures_supplementaires'), 2),
            'retard_minutes' => round((float) $details->sum('retard_minutes'), 2),
            'absences' => (int) $details->where('absent', true)->count(),
            'absences_justifiees' => (int) $details->where('absence_justifiee', true)->count(),
            'jours_feries' => (int) $details->where('ferie', true)->count(),
            'weekends' => (int) $details->where('weekend', true)->count(),
        ];
    }

    protected function statutPaie(?Paie $paie): string
    {
        if (!$paie) {
            return 'non_genere';
        }
        if ($paie->paye_le || $paie->statut === 'paye') {
            return 'paye';
        }
        if ($paie->statut === 'paiement_en_validation') {
            return 'paiement_en_validation';
        }
        if ($paie->statut === 'non_paye') {
            return 'non_paye';
        }
        return 'en_attente_validation';
    }

    protected function statutPaieLabel(string $statut): string
    {
        return match ($statut) {
            'non_genere' => 'Non générée',
            'en_attente_validation' => 'En attente de validation',
            'non_paye' => 'Non payé',
            'paiement_en_validation' => 'Paiement en validation',
            'paye' => 'Payé',
            default => $statut,
        };
    }
}
