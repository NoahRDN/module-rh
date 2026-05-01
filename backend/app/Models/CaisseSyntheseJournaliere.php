<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaisseSyntheseJournaliere extends Model
{
    protected $table = 'caisse_synthese_journaliere';

    protected $fillable = [
        'caisse_id',
        'jour',
        'total_entrees',
        'total_sorties',
        'solde_net',
        'solde_caisse',
        'mouvements_total',
        'mouvements_valides',
        'mouvements_en_attente',
        'mouvements_rejetes',
        'par_categorie',
        'generated_at',
    ];

    protected $casts = [
        'jour' => 'date',
        'total_entrees' => 'decimal:2',
        'total_sorties' => 'decimal:2',
        'solde_net' => 'decimal:2',
        'solde_caisse' => 'decimal:2',
        'par_categorie' => 'array',
        'generated_at' => 'datetime',
    ];
}
