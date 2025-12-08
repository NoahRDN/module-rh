<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratHistorique extends Model
{
    protected $fillable = [
        'contrat_id',
        'numero',
        'employe_id',
        'type_contrat',
        'date_debut',
        'date_fin',
        'periode_essai_debut',
        'periode_essai_fin',
        'renouvelable',
        'salaire_base',
    ];

    protected $casts = [
        'renouvelable' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'periode_essai_debut' => 'date',
        'periode_essai_fin' => 'date',
    ];

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
