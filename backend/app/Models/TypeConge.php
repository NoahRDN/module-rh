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
        'limite',
        'limite_frequence_id',
        'frequence_id',
        'cumulable',
        'cumulable_duree',
        'cumulable_frequence_id',
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

    public function frequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'frequence_id');
    }

    public function cumulableFrequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'cumulable_frequence_id');
    }

    public function limiteFrequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'limite_frequence_id');
    }
}
