<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conge extends Model
{
    protected $table = 'conge';
    protected $primaryKey = 'id_conge';
    public $timestamps = false; // La colonne Date_Demande est gérée par la DB par défaut

    protected $fillable = [
        'id_employe', 'id_type_conge', 'date_debut', 'date_fin', 
        'commentaire', 'valide_par', 'id_statut'
    ];

    // Pour gérer Date_Demande si vous voulez l'écrire depuis Laravel
    protected $dates = ['Date_Demande', 'Date_Debut', 'Date_Fin'];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }

    public function typeConge()
    {
        return $this->belongsTo(TypeConge::class, 'id_type_conge');
    }

    public function statut()
    {
        return $this->belongsTo(Statut::class, 'id_statut');
    }
}