<?php

namespace Database\Seeders;

use App\Models\WorktimeSetting;
use Illuminate\Database\Seeder;

class WorktimeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        WorktimeSetting::firstOrCreate(
            [],
            [
                'working_days' => config('worktime.working_days'),
                'saturday_mode' => config('worktime.saturday_mode'),
                'start_hour' => config('worktime.start_hour'),
                'start_minute' => config('worktime.start_minute'),
                'retard_tolerance_minutes' => config('worktime.retard_tolerance_minutes', 0),
                'retard_threshold_hours' => config('worktime.retard_threshold_hours', 2),
                'hours_per_day' => config('worktime.hours_per_day'),
                'weekly_threshold' => config('worktime.weekly_threshold'),
                'multipliers' => config('worktime.multipliers'),
                'night_start' => config('worktime.night_start'),
                'night_end' => config('worktime.night_end'),
                'night_rate' => config('worktime.night_rate'),
                'deduct_from_leave_balance' => config('worktime.deduct_from_leave_balance', false),
                'deduct_from_salary' => config('worktime.deduct_from_salary', false),
            ]
        );
    }
}
