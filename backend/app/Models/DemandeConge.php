<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class DemandeConge extends Model
{
    use Auditable;

    protected $table = 'demandes_conges';

    protected $fillable = [
        'employe_id',
        'type_conge_id',
        'jours_demandes',
        'date_debut',
        'date_fin',
        'statut',
        'motif',
        'approuve_par',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function typeConge()
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }

    public function approbateur()
    {
        return $this->belongsTo(User::class, 'approuve_par');
    }
}
