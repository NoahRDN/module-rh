<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paie extends Model
{
    protected $table = 'paies';

    protected $fillable = [
        'employe_id',
        'mois',
        'statut',
        'demande_validation_le',
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
        'paye_le',
        'valide_le',
    ];

    protected $casts = [
        'demande_validation_le' => 'datetime',
        'paye_le' => 'date',
        'valide_le' => 'datetime',
    ];

    protected $appends = [
        'taux_horaire',
        'taux_journalier',
    ];

    public function getTauxHoraireAttribute(): float
    {
        return round(((float) $this->salaire_base) / 173.33, 2);
    }

    public function getTauxJournalierAttribute(): float
    {
        return round(((float) $this->salaire_base) / 30, 2);
    }

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

    public function mouvementsCaisse()
    {
        return $this->hasMany(CaisseMouvement::class);
    }
}
