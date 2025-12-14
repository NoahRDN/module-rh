<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaieParametre extends Model
{
    protected $table = 'paie_parametres';

    protected $fillable = [
        'cnaps_plafond',
        'cnaps_taux_employe',
        'cnaps_taux_employeur',
        'ostie_taux_employe',
        'ostie_taux_employeur',
        'irsa_base',
        'irsa_taux',
        'hs_taux',
        'prime_transport',
        'prime_presence',
    ];
}
