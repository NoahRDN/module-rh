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
    ];

    protected $casts = [
        'working_days' => 'array',
        'multipliers' => 'array',
    ];
}
