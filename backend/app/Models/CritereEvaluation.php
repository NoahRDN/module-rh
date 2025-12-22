<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CritereEvaluation extends Model
{
    protected $table = 'criteres_evaluation';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'poids',
        'actif',
        'ordre',
    ];

    protected $casts = [
        'poids' => 'integer',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public function evaluationDetails()
    {
        return $this->hasMany(EvaluationDetail::class, 'critere_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true)->orderBy('ordre');
    }
}
