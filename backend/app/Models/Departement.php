<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $table = 'departements';

    protected $fillable = [
        'nom',
        'description',
        'manager_id',
    ];

    public function postes()
    {
        return $this->hasMany(Poste::class);
    }

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    /**
     * Manager/Responsable du département
     */
    public function manager()
    {
        return $this->belongsTo(Employe::class, 'manager_id');
    }

    /**
     * Vérifie si un employé est le manager de ce département
     */
    public function isManager(Employe $employe): bool
    {
        return $this->manager_id === $employe->id;
    }

    /**
     * Récupère les employés de l'équipe du manager (hors manager)
     */
    public function getEquipeAttribute()
    {
        return $this->employes()->where('id', '!=', $this->manager_id)->get();
    }
}
