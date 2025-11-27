<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametrePaie extends Model
{
    protected $table = 'parametre_paie';
    protected $primaryKey = 'id_param';
    public $timestamps = false;

    protected $fillable = [
        'libelle', 'taux', 'date_effet'
    ];

    protected $casts = [
        'date_effet' => 'date',
        'taux' => 'decimal:2' // Utile pour garder la précision
    ];
}