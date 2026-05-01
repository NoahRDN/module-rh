<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardStat extends Model
{
    protected $fillable = [
        'filtre',
        'date_debut',
        'date_fin',
        'statistiques',
        'donnees_rapides',
        'alertes_recentes',
        'generated_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statistiques' => 'array',
        'donnees_rapides' => 'array',
        'alertes_recentes' => 'array',
        'generated_at' => 'datetime',
    ];
}
