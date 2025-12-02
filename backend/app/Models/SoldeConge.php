<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldeConge extends Model
{
    protected $table = 'solde_conge';
    protected $primaryKey = 'id_solde';
    public $timestamps = false;

    protected $fillable = [
        'id_employe', 'annee', 'total_acquis', 'total_pris'
        // Total_Restant est généré automatiquement par la DB (GENERATED ALWAYS), ne pas l'ajouter ici
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }
}