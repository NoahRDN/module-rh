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
    ];

    protected $casts = [
        'working_days' => 'array',
        'multipliers' => 'array',
        'night_start' => 'string',
        'night_end' => 'string',
    ];
}
