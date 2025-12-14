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
                'hours_per_day' => config('worktime.hours_per_day'),
                'weekly_threshold' => config('worktime.weekly_threshold'),
                'multipliers' => config('worktime.multipliers'),
            ]
        );
    }
}
