<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourFerie extends Model
{
    protected $table = 'jours_feries';

    protected $fillable = [
        'nom',
        'date',
        'recurrent',
    ];

    protected $casts = [
        'date' => 'date',
        'recurrent' => 'boolean',
    ];
}
