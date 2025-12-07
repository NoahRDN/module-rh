<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiePrime extends Model
{
    protected $table = 'paie_primes';

    protected $fillable = [
        'paie_id',
        'libelle',
        'montant',
    ];

    public function paie()
    {
        return $this->belongsTo(Paie::class);
    }
}
