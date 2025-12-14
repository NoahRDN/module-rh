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
        // Champs workflow manager
        'manager_id',
        'date_validation_manager',
        'commentaire_manager',
        // Champs workflow RH
        'rh_id',
        'date_validation_rh',
        'commentaire_rh',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'date_validation_manager' => 'datetime',
        'date_validation_rh' => 'datetime',
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
