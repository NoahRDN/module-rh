<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaieParametre extends Model
{
    protected $table = 'paie_parametres';

    protected $fillable = [
        'cnaps',
        'ostie',
        'irsa_base',
        'irsa_taux',
        'hs_taux',
        'prime_transport',
        'prime_presence',
    ];
}
