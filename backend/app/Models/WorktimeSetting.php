<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorktimeSetting extends Model
{
    protected $fillable = [
        'working_days',
        'saturday_mode',
        'start_hour',
        'start_minute',
        'hours_per_day',
        'weekly_threshold',
        'multipliers',
        'night_start',
        'night_end',
        'night_rate',
        'deduct_from_leave_balance',
        'deduct_from_salary',
    ];

    protected $casts = [
        'working_days' => 'array',
        'multipliers' => 'array',
        'night_start' => 'string',
        'night_end' => 'string',
        'deduct_from_leave_balance' => 'boolean',
        'deduct_from_salary' => 'boolean',
    ];
}
