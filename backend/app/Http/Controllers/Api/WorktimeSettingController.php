<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorktimeSetting;
use Illuminate\Http\Request;
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
        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'working_days' => 'nullable|array',
            'working_days.*' => 'string',
            'saturday_mode' => 'required|string|in:normal,hs',
            'start_hour' => 'required|integer|min:0|max:23',
            'start_minute' => 'required|integer|min:0|max:59',
            'hours_per_day' => 'required|numeric|min:0|max:24',
            'weekly_threshold' => 'required|numeric|min:0|max:100',
            'multipliers' => 'nullable|array',
        ]);

        $setting = WorktimeSetting::first() ?? $this->defaults();
        $setting->fill($data);
        $setting->save();

        return response()->json($setting);
    }

    private function defaults(): WorktimeSetting
    {
        return new WorktimeSetting([
            'working_days' => config('worktime.working_days'),
            'saturday_mode' => config('worktime.saturday_mode'),
            'start_hour' => config('worktime.start_hour'),
            'start_minute' => config('worktime.start_minute'),
            'hours_per_day' => config('worktime.hours_per_day'),
            'weekly_threshold' => config('worktime.weekly_threshold'),
            'multipliers' => config('worktime.multipliers'),
        ]);
    }
}
