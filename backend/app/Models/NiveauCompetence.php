<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauCompetence extends Model
{
    protected $table = 'niveaux_competence';

    protected $fillable = [
        'niveau',
        'code',
        'libelle',
        'description',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('niveau');
    }
}
