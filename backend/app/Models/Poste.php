<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poste extends Model
{
    protected $table = 'postes';

    protected $fillable = [
        'nom',
        'description',
        'departement_id',
        'categorie',
        'categorie_level'
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'poste_competences')
            ->withPivot('niveau_requis', 'obligatoire', 'poids')
            ->withTimestamps();
    }

    public function competencesObligatoires()
    {
        return $this->competences()->wherePivot('obligatoire', true);
    }

    public function competencesSouhaitees()
    {
        return $this->competences()->wherePivot('obligatoire', false);
    }
}
