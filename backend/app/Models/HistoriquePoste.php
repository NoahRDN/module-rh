<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriquePoste extends Model
{
    protected $table = 'historique_postes';

    protected $fillable = [
        'employe_id',
        'poste_id',
        'departement_id',
        'date_changement',
        'motif'
    ];

    protected $casts = [
        'date_changement' => 'date'
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
