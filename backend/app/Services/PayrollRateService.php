<?php

namespace App\Services;

use App\Models\JourFerie;
use App\Models\WorktimeSetting;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;

class PayrollRateService
{
    protected static array $ratesCache = [];
    protected static array $workingDaysCache = [];

    public function ratesForMonth(float $salaryBase, ?string $month = null): array
    {
        $month = $month ?: now()->format('Y-m');
        $cacheKey = $month . ':' . round($salaryBase, 2);
        if (isset(static::$ratesCache[$cacheKey])) {
            return static::$ratesCache[$cacheKey];
        }

        $settings = $this->loadWorktimeSettings();
        $workingDays = $this->workingDaysInMonth($month, $settings);
        $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
        $requiredHours = $workingDays * $hoursPerDay;

        return static::$ratesCache[$cacheKey] = [
            'jours_ouvres' => $workingDays,
            'heures_mensuelles_requises' => round($requiredHours, 2),
            'taux_journalier' => $workingDays > 0 ? $salaryBase / $workingDays : 0,
            'taux_horaire' => $requiredHours > 0 ? $salaryBase / $requiredHours : 0,
            'taux_journalier_affiche' => $workingDays > 0 ? round($salaryBase / $workingDays, 2) : 0,
            'taux_horaire_affiche' => $requiredHours > 0 ? round($salaryBase / $requiredHours, 2) : 0,
        ];
    }

    public function workingDaysInMonth(string $month, ?array $settings = null): int
    {
        $settings = $settings ?: $this->loadWorktimeSettings();
        $cacheKey = $month . ':' . md5(json_encode([
            $settings['working_days'] ?? [],
            $settings['saturday_mode'] ?? null,
            $settings['hours_per_day'] ?? null,
        ]));
        if (isset(static::$workingDaysCache[$cacheKey])) {
            return static::$workingDaysCache[$cacheKey];
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->copy()->addDay());
        $workingDays = 0;

        foreach ($period as $day) {
            $date = Carbon::instance($day);
            if ($this->isWorkingDay($date, $settings) && !$this->isHoliday($date)) {
                $workingDays++;
            }
        }

        return static::$workingDaysCache[$cacheKey] = $workingDays;
    }

    protected function isWorkingDay(Carbon $date, array $settings): bool
    {
        $workingDays = $settings['working_days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        $saturdayMode = $settings['saturday_mode'] ?? 'normal';
        $dayCode = strtolower(substr($date->format('D'), 0, 3));
        $isSaturday = $dayCode === 'sat';

        return in_array($dayCode, $workingDays, true) || ($isSaturday && $saturdayMode === 'normal');
    }

    protected function isHoliday(Carbon $date): bool
    {
        $dayMonth = $date->format('m-d');

        return JourFerie::where(function ($query) use ($date) {
                $query->whereDate('date', $date->toDateString())
                    ->where('recurrent', false);
            })
            ->orWhere(function ($query) use ($dayMonth) {
                $query->whereRaw("to_char(date, 'MM-DD') = ?", [$dayMonth])
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
                'hours_per_day' => $setting->hours_per_day ?? config('worktime.hours_per_day'),
            ];
        }

        return config('worktime');
    }
}
