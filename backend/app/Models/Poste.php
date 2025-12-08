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
}
