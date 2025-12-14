<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlerteSetting extends Model
{
    protected $table = 'alerte_settings';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'actif',
        'seuil_jours',
        'seuil_nombre',
        'periode_jours',
        'niveau',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'seuil_jours' => 'integer',
        'seuil_nombre' => 'integer',
        'periode_jours' => 'integer',
    ];
}
