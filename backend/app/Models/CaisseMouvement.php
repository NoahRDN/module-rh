<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaisseMouvement extends Model
{
    protected $fillable = [
        'caisse_id',
        'paie_id',
        'type',
        'categorie',
        'montant',
        'source',
        'description',
        'statut',
        'demande_validation_le',
        'valide_le',
        'rejete_le',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'demande_validation_le' => 'datetime',
        'valide_le' => 'datetime',
        'rejete_le' => 'datetime',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function paie()
    {
        return $this->belongsTo(Paie::class);
    }
}
