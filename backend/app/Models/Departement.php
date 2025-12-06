<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $table = 'departements';

    protected $fillable = [
        'nom',
        'description',
    ];

    public function postes()
    {
        return $this->hasMany(Poste::class);
    }

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }
}
