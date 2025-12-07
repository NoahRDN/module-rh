<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeConge extends Model
{
    protected $table = 'demandes_conges';

    protected $fillable = [
        'employe_id',
        'type_id',
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

    public function type()
    {
        return $this->belongsTo(AbsenceType::class, 'type_id');
    }

    public function approbateur()
    {
        return $this->belongsTo(User::class, 'approuve_par');
    }
}
