<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorktimeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WorktimeSettingController extends Controller
{
    public function show()
    {
        $setting = WorktimeSetting::first();
        if (!$setting) {
            $setting = $this->defaults();
            $setting->save();
        }
        return response()->json($this->present($setting));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'working_days' => 'nullable|array',
            'working_days.*' => 'string',
            'saturday_mode' => 'required|string|in:normal,hs',
            'start_hour' => 'required|integer|min:0|max:23',
            'start_minute' => 'required|integer|min:0|max:59',
            'retard_tolerance_minutes' => 'required|integer|min:0|max:180',
            'retard_threshold_hours' => 'required|numeric|min:0|max:12',
            'hours_per_day' => 'required|numeric|min:0|max:24',
            'weekly_threshold' => 'required|numeric|min:0|max:100',
            'multipliers' => 'nullable|array',
            'night_start' => 'required|string',
            'night_end' => 'required|string',
            'night_rate' => 'required|numeric|min:0',
            'deduct_from_leave_balance' => 'boolean',
            'deduct_from_salary' => 'boolean',
        ]);

        $setting = WorktimeSetting::first() ?? $this->defaults();
        $setting->fill($data);
        $setting->save();
        Cache::forget('settings:worktime');

        return response()->json($this->present($setting));
    }

    private function defaults(): WorktimeSetting
    {
        return new WorktimeSetting([
            'working_days' => config('worktime.working_days'),
            'saturday_mode' => config('worktime.saturday_mode'),
            'start_hour' => config('worktime.start_hour'),
            'start_minute' => config('worktime.start_minute'),
            'retard_tolerance_minutes' => config('worktime.retard_tolerance_minutes', 0),
            'retard_threshold_hours' => config('worktime.retard_threshold_hours', 2),
            'hours_per_day' => config('worktime.hours_per_day'),
            'weekly_threshold' => config('worktime.weekly_threshold'),
            'multipliers' => config('worktime.multipliers'),
            'night_start' => config('worktime.night_start', '22:00'),
            'night_end' => config('worktime.night_end', '05:00'),
            'night_rate' => config('worktime.night_rate', 20),
            'deduct_from_leave_balance' => config('worktime.deduct_from_leave_balance', true),
            'deduct_from_salary' => config('worktime.deduct_from_salary', true),
        ]);
    }

    private function present(WorktimeSetting $setting): WorktimeSetting
    {
        $setting->night_rate = $this->normalizePercent($setting->night_rate);
        $mult = $setting->multipliers ?: config('worktime.multipliers', []);
        $setting->multipliers = collect($mult)->map(fn($v) => $this->normalizePercent($v))->toArray();
        $setting->deduct_from_leave_balance = (bool) ($setting->deduct_from_leave_balance ?? true);
        $setting->deduct_from_salary = (bool) ($setting->deduct_from_salary ?? true);
        return $setting;
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
}
