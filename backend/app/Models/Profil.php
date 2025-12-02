<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $table = 'profil';
    protected $primaryKey = 'id_profil';
    public $timestamps = false;

    protected $fillable = [
        'nom', 'date_derniere_postulation', 'id_type_contrat', 'id_type_travail', 'id_niveau_carriere'
    ];

    public function typeContrat()
    {
        return $this->belongsTo(TypeContrat::class, 'Id_Type_Contrat');
    }
    
    // Ajoutez les autres relations (TypeTravail, NiveauCarriere) ici...
}