<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeConge extends Model
{
    protected $table = 'types_conges';

    protected $fillable = [
        'libelle',
        'code',
        'jours_forfait',
        'utilise_solde',
        'paye',
        'limite_par_an',
        'limite_par_mois',
        'justificatif_obligatoire',
        'sexe_autorise',
        'description',
    ];

    public function regles()
    {
        return $this->hasMany(RegleConge::class);
    }

    public function acquis()
    {
        return $this->hasMany(AcquisConge::class);
    }

    public function soldes()
    {
        return $this->hasMany(SoldeConge::class);
    }
}
