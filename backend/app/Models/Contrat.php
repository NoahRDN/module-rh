<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrat extends Model
{
    protected $table = 'contrats';

    protected $fillable = [
        'employe_id',
        'type_contrat',
        'date_debut',
        'date_fin',
        'periode_essai_debut',
        'periode_essai_fin',
        'renouvelable',
        'salaire_base'
    ];

    protected $casts = [
        'date_debut'           => 'date',
        'date_fin'             => 'date',
        'periode_essai_debut'  => 'date',
        'periode_essai_fin'    => 'date',
        'renouvelable'         => 'boolean',
        'salaire_base'         => 'decimal:2'
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
