<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paie;
use App\Models\PaieParametre;
use App\Models\IrsaTranche;
use App\Models\WorktimeSetting;
use App\Models\JourFerie;
use App\Models\Contrat;
use App\Models\EntrepriseSetting;
use App\Services\PayrollRateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaiePdfController extends Controller
{
    public function telecharger($id, Request $request)
    {
        try {
            $paie = Paie::with(['employe', 'employe.poste', 'employe.historiquePostes', 'details', 'primes'])->findOrFail($id);
            $employe = $paie->employe;
            $monthStart = Carbon::createFromFormat('Y-m', $paie->mois)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $posteActif = $employe->posteActifPourDate($paie->mois);
            if ($posteActif) {
                // aligne la relation poste avec le poste effectif pour la période
                $employe->setRelation('poste', $posteActif);
            }

            $param = PaieParametre::first();
            $contratPeriode = Contrat::where('employe_id', $paie->employe_id)
                ->whereDate('date_debut', '<=', $monthEnd->toDateString())
                ->where(function ($query) use ($monthStart) {
                    $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $monthStart->toDateString());
                })
                ->orderByDesc('date_debut')
                ->first();
            $contrat = $contratPeriode ?: Contrat::where('employe_id', $paie->employe_id)
                ->orderByDesc('date_debut')
                ->first();
            $salaireBaseReference = round((float) ($contrat?->salaire_base ?? $paie->salaire_base ?? 0), 2);

            $anciennete = $this->calculerAnciennete($employe->date_embauche);
            $payrollRates = app(PayrollRateService::class)->ratesForMonth($salaireBaseReference, $paie->mois);
            $taux_journalier = $payrollRates['taux_journalier_affiche'];
            $taux_horaire = $payrollRates['taux_horaire_affiche'];
            $retardsMinutes = (int) $paie->details->sum('retard_minutes');
            $absencesNonJustifiees = (int) $paie->details
                ->filter(fn ($detail) => (bool) ($detail->absent ?? false) && !(bool) ($detail->absence_justifiee ?? false))
                ->count();
            $deductionRetards = round(($retardsMinutes / 60) * (float) $taux_horaire, 2);
            $deductionAbsences = round($absencesNonJustifiees * (float) $taux_journalier, 2);

            // Calculer les détails des revenus (heures supplémentaires, primes, etc.)
            $hs_breakdown = $this->calculerRepartitionHeuresSup($paie, $taux_horaire);
            $details_revenus = $this->extraireDetailsRevelus($paie, $taux_horaire, $hs_breakdown);

            $nonTaxableRemunerationTotal = (float) $paie->primes
                ->filter(fn ($prime) => !(bool) $prime->is_taxable)
                ->reduce(fn (float $carry, $prime) => $carry + (float) $prime->montant, 0.0);

            // Calculer les détails IRSA (progressif via tranches)
            [$details_irsa, $irsa_brut, $reduction_irsa] = $this->calculerDetailIRSAProgressif(
                ($paie->total_brut - $nonTaxableRemunerationTotal) - $paie->retenue_cnaps - $paie->retenue_ostie
            );
            $irsa_net = round($irsa_brut - $reduction_irsa, 2);

            $cotisations_sociales = $paie->retenue_cnaps + $paie->retenue_ostie;
            $autres_retenues = round(max(0, $paie->total_retenues - ($cotisations_sociales + $irsa_net)), 2);
            $total_retenues_affiche = round($cotisations_sociales + $irsa_net + $autres_retenues, 2);

            // Autres données
            $revenu_imposable = ($paie->total_brut - $nonTaxableRemunerationTotal) - $paie->retenue_cnaps - $paie->retenue_ostie;
            $enfants_charge = $employe->enfants_a_charge ?? 0;
            $sourceMontants = $this->sourceMontantsForMonth($paie->mois);
            $forcePrevision = $request->boolean('prevision');
            $sourceCode = (string) ($sourceMontants['code'] ?? '');
            $isPrevisionPdf = $forcePrevision || in_array($sourceCode, ['mixte', 'prevision'], true);

            // Filigrane métier:
            // - Réel + prévision
            // - Prévision présence
            // Pas de filigrane pour le réel calculé.
            $watermarkText = null;
            if ($sourceCode === 'mixte') {
                $watermarkText = 'RÉEL + PRÉVISION';
            } elseif ($sourceCode === 'prevision' || $forcePrevision) {
                $watermarkText = 'PRÉVISION PRÉSENCE';
            }

            $entreprise = EntrepriseSetting::firstOrCreate(
                [],
                ['nom' => config('app.name', 'Module RH')]
            );
            $entrepriseLogoPath = $entreprise->resolvePdfLogoSrc();

            Log::info("Generating PDF for Paie ID: {$paie->id}");
            Log::info("Employe ID: {$employe->id}, mois: {$paie->mois} ,Annee: {$paie->annee}");
            $pdf = Pdf::loadView('pdf.bulletin_paie', [
                'paie' => $paie,
                'employe' => $employe,
                'posteActif' => $posteActif,
                'param' => $param,
                'salaire_base_reference' => $salaireBaseReference,
                'anciennete' => $anciennete,
                'taux_journalier' => $taux_journalier,
                'taux_horaire' => $taux_horaire,
                'retards_minutes' => $retardsMinutes,
                'absences_non_justifiees' => $absencesNonJustifiees,
                'deduction_retards' => $deductionRetards,
                'deduction_absences' => $deductionAbsences,
                'details_revenus' => $details_revenus,
                'details_irsa' => $details_irsa,
                'irsa_brut' => $irsa_brut,
                'reduction_irsa' => $reduction_irsa,
                'irsa_net' => $irsa_net,
                'autres_retenues' => $autres_retenues,
                'total_retenues_affiche' => $total_retenues_affiche,
                'revenu_imposable' => $revenu_imposable,
                'enfants_charge' => $enfants_charge,
                'isPrevisionPdf' => $isPrevisionPdf,
                'watermarkText' => $watermarkText,
                'entreprise_nom' => $entreprise->nom ?: config('app.name', 'Module RH'),
                'entreprise_logo_path' => $entrepriseLogoPath,
            ]);

            return $pdf->download("bulletin_paie_{$employe->id}_{$paie->mois}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Calculer l'ancienneté en format lisible (X ans Y mois)
     */
    private function calculerAnciennete($date_embauche)
    {
        if (!$date_embauche) {
            return '—';
        }

        $start = Carbon::parse($date_embauche);
        $end = Carbon::now();
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start]; // éviter les valeurs négatives
        }

        $diff = $start->diff($end);
        $years = $diff->y;
        $months = $diff->m;
        $days = $diff->d;

        return "{$years} an(s) {$months} mois et {$days} jour(s)";
    }

    private function sourceMontantsForMonth(string $mois): array
    {
        $start = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = now()->startOfDay();

        if ($end->lt($today)) {
            return [
                'code' => 'reel_calcule',
                'label' => 'Réel calculé',
            ];
        }

        if ($start->gt($today)) {
            return [
                'code' => 'prevision',
                'label' => 'Prévision présence',
            ];
        }   

        return [
            'code' => 'mixte',
            'label' => 'Réel + prévision',
        ];
    }

    /**
     * Extraire les détails des revenus additionnels (primes, heures supplémentaires, etc.)
     */
    private function extraireDetailsRevelus($paie, float $taux_horaire, array $hs_breakdown)
    {
        $details = [];
        foreach ($hs_breakdown as $hs) {
            $details[] = [
                'libelle' => 'Heures supplémentaires majorées de ' . number_format($hs['taux'], 2, ',', ' ') . ' %',
                'nombre' => number_format($hs['heures'], 2, ',', ' ') . ' h',
                'taux' => number_format($hs['taux'], 2, ',', ' ') . ' %',
                'montant' => $hs['montant'],
            ];
        }

        // Ajouter les heures de nuit si présentes
        if (isset($paie->montant_nuit) && $paie->montant_nuit > 0) {
            $settings = $this->loadWorktimeSettings();
            $nightRatePercent = $this->normalizePercent($settings['night_rate'] ?? config('worktime.night_rate', 0));
            $nightBonusFactor = $nightRatePercent / 100;
            $heures_nuit = ($taux_horaire > 0 && $nightBonusFactor > 0)
                ? round($paie->montant_nuit / ($taux_horaire * $nightBonusFactor), 2)
                : null;

            $details[] = [
                'libelle' => 'Heures de nuit majorées de ' . number_format($nightRatePercent, 0) . '%',
                'nombre' => $heures_nuit ? number_format($heures_nuit, 2, ',', ' ') . ' h' : '—',
                'taux' => number_format($nightRatePercent, 0) . ' %',
                'montant' => $paie->montant_nuit,
            ];
        }

        $primes = $paie->relationLoaded('primes') ? $paie->primes : $paie->primes()->get();

        foreach ($primes as $prime) {
            if ((float) $prime->montant <= 0) {
                continue;
            }

            $details[] = [
                'libelle' => $prime->libelle,
                'nombre' => '—',
                'taux' => '—',
                'montant' => $prime->montant,
            ];
        }

        return $details;
    }

    /**
     * Calcule une répartition détaillée des heures sup par tranche/config worktime.
     */
    private function calculerRepartitionHeuresSup($paie, float $taux_horaire): array
    {
        $details = $paie->details ?? collect();
        $settings = $this->loadWorktimeSettings();
        $threshold = (float) ($settings['weekly_threshold'] ?? 40);
        $multipliers = $settings['multipliers'] ?? config('worktime.multipliers', []);
        $saturdayMode = $settings['saturday_mode'] ?? 'normal';

        $detailsEmpty = $details instanceof \Illuminate\Support\Collection ? $details->isEmpty() : empty($details);
        if ($detailsEmpty || $taux_horaire <= 0) {
            return $this->fallbackHsBreakdown($paie, $taux_horaire);
        }

        $weeks = [];
        foreach ($details as $d) {
            $date = Carbon::parse($d->jour);
            $weekKey = $date->isoWeekYear() . '-' . $date->isoWeek();
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'weekday_hours' => 0,
                    'saturday_hours' => 0,
                    'sunday_hours' => 0,
                    'holiday_hours' => 0,
                ];
            }

            $hoursDay = (float) ($d->heures_travaillees ?? 0) + (float) ($d->heures_supplementaires ?? 0);
            if ($hoursDay <= 0) {
                continue;
            }

            $isHoliday = $this->isHoliday($date);
            $dayCode = strtolower($date->format('D'));
            $isSaturday = $dayCode === 'sat';
            $isSunday = $date->isSunday();

            if ($isHoliday) {
                $weeks[$weekKey]['holiday_hours'] += $hoursDay;
            } elseif ($isSunday) {
                $weeks[$weekKey]['sunday_hours'] += $hoursDay;
            } elseif ($isSaturday && $saturdayMode === 'hs') {
                $weeks[$weekKey]['saturday_hours'] += $hoursDay;
            } else {
                $weeks[$weekKey]['weekday_hours'] += $hoursDay;
            }
        }

        $breakdownHours = [
            'weekday_first8' => 0,
            'weekday_next12' => 0,
            'weekday_beyond' => 0,
            'saturday' => 0,
            'sunday' => 0,
            'holiday' => 0,
        ];

        foreach ($weeks as $week) {
            $weekdayHours = $week['weekday_hours'];
            $overtime = max(0, $weekdayHours - $threshold);
            $first8 = min(8, $overtime);
            $next12 = min(12, max(0, $overtime - $first8));
            $beyond = max(0, $overtime - $first8 - $next12);

            $breakdownHours['weekday_first8'] += $first8;
            $breakdownHours['weekday_next12'] += $next12;
            $breakdownHours['weekday_beyond'] += $beyond;

            $breakdownHours['saturday'] += $week['saturday_hours'];
            $breakdownHours['sunday'] += $week['sunday_hours'];
            $breakdownHours['holiday'] += $week['holiday_hours'];
        }

        $labels = [
            'weekday_first8' => 'HS - 8 premières heures',
            'weekday_next12' => 'HS - 12 heures suivantes',
            'weekday_beyond' => 'HS - au-delà',
            'saturday' => 'HS samedi',
            'sunday' => 'HS dimanche',
            'holiday' => 'HS férié',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $hours = round($breakdownHours[$key] ?? 0, 2);
            if ($hours <= 0) {
                continue;
            }
            $tauxPercent = $this->normalizePercent($multipliers[$key] ?? 0);
            $mult = $this->toMultiplier($tauxPercent);
            $rows[] = [
                'libelle' => $label,
                'heures' => $hours,
                'taux' => round($tauxPercent, 2),
                'montant' => round($hours * $taux_horaire * $mult, 2),
            ];
        }

        return !empty($rows) ? $rows : $this->fallbackHsBreakdown($paie, $taux_horaire);
    }

    /**
     * Fallback : une seule ligne HS si on ne peut pas reconstituer le détail.
     */
    private function fallbackHsBreakdown($paie, float $taux_horaire): array
    {
        $heures = $paie->heures_supplementaires ?? 0;
        if ($heures <= 0 || $taux_horaire <= 0) {
            return [];
        }

        $mult = ($heures > 0 && $taux_horaire > 0)
            ? $paie->montant_hs / ($heures * $taux_horaire)
            : 1;
        $tauxPercent = max(0, ($mult - 1) * 100);

        return [[
            'libelle' => 'Heures supplémentaires',
            'heures' => $heures,
            'taux' => round($tauxPercent, 2),
            'montant' => $paie->montant_hs,
        ]];
    }

    private function isHoliday(Carbon $date): bool
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
     * Calcule un taux de majoration HS effectif à partir du montant, en fallback sur la config worktime.
     */
    private function calculerTauxHsEffectif($paie, float $taux_horaire): ?float
    {
        $heures_sup = $paie->heures_supplementaires ?? 0;
        if ($heures_sup > 0 && $taux_horaire > 0) {
            $mult = $paie->montant_hs / ($heures_sup * $taux_horaire);
            if ($mult > 0) {
                return round(($mult - 1) * 100, 2);
            }
        }

        $settings = $this->loadWorktimeSettings();
        $multipliers = $settings['multipliers'] ?? config('worktime.multipliers');
        if (empty($multipliers)) {
            return null;
        }

        $multiplieur_reference = $multipliers['weekday_first8'] ?? reset($multipliers);
        $percent = $this->normalizePercent($multiplieur_reference);
        if ($percent <= 0) {
            return null;
        }

        return round($percent, 2);
    }

    /**
     * Récupère la configuration worktime (base ou override en base).
     */
    private function loadWorktimeSettings(): array
    {
        $setting = WorktimeSetting::first();
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
     * Calcul IRSA progressif via les tranches paramétrées.
     * Retourne [details_irsa, irsa_brut, reduction_irsa]
     */
    private function calculerDetailIRSAProgressif($salaire_brut)
    {
        $details_irsa = [];
        $irsa_total = 0;

        $tranches = IrsaTranche::orderBy('min_base')->get();
        if ($tranches->isEmpty()) {
            return [$details_irsa, 0, 0];
        }

        foreach ($tranches as $t) {
            $borne_inf = $t->min_base > 0 ? $t->min_base - 1 : 0; // bornes inclusives
            $max = $t->max_base ?? $salaire_brut;

            if ($salaire_brut <= $borne_inf) {
                continue;
            }

            $plafond = min($salaire_brut, $max);
            $assiette = max(0, $plafond - $borne_inf);
            $assiette_arrondie = ceil($assiette);
            $montant = $assiette_arrondie * ($t->taux / 100);
            $irsa_total += $montant;

            $details_irsa[] = [
                'libelle' => "De " . number_format($t->min_base, 0, ',', ' ') . " à " . ($t->max_base ? number_format($max, 0, ',', ' ') : '∞'),
                'base' => $assiette_arrondie,
                'taux' => $t->taux,
                'montant' => $montant,
            ];
        }

        // Réduction IRSA (exemple: 1000 par enfant)
        $reduction_irsa = 0; // À adapter selon vos règles métier

        return [
            $details_irsa,
            $irsa_total,
            $reduction_irsa,
        ];
    }
}
