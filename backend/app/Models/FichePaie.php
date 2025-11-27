<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichePaie extends Model
{
    protected $table = 'fiche_paie';
    protected $primaryKey = 'id_fiche';
    public $timestamps = false;

    protected $fillable = [
        'id_employe', 'mois', 'annee', 'salaire_base', 'heures_supp', 'prime', 'retenue', 'cnaps', 'ostie', 'irsa'
        // Net_A_Payer est généré par la DB
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }

    // Relation Many-to-Many avec ParametrePaie via la table pivot
    public function parametres()
    {
        return $this->belongsToMany(ParametrePaie::class, 'fiche_paie_parametre', 'id_fiche', 'id_param')
                    ->withPivot('valeur');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'id_fiche');
    }
}