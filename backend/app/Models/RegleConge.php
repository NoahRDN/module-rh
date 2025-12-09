<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegleConge extends Model
{
    protected $table = 'regles_conges';

    protected $fillable = [
        'type_conge_id',
        'anciennete_min',
        'jours_acquis_par_mois',
        'contrat_type',
        'temps_partiel_ratio',
    ];

    public function type()
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }
}
