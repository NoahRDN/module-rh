<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paie extends Model
{
    protected $table = 'paies';

    protected $fillable = [
        'employe_id',
        'mois',
        'salaire_base',
        'heures_travaillees',
        'heures_supplementaires',
        'montant_hs',
        'prime_transport',
        'prime_presence',
        'autres_primes',
        'retenue_cnaps',
        'retenue_ostie',
        'retenue_irsa',
        'total_brut',
        'total_retenues',
        'net_a_payer',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function details()
    {
        return $this->hasMany(PaieDetail::class);
    }

    public function primes()
    {
        return $this->hasMany(PaiePrime::class);
    }
}
