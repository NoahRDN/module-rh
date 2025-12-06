<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    protected $table = 'employes';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'date_naissance',
        'poste_id',
        'departement_id',
        'photo',
        'date_embauche'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche'  => 'date',
    ];

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
    }

    public function documents()
    {
        return $this->hasMany(DocumentEmploye::class);
    }

    public function historiquePostes()
    {
        return $this->hasMany(HistoriquePoste::class);
    }

    public function ajouterChangementPoste($nouveauPosteId, $nouveauDepartementId, $motif = null): void
    {
        $this->historiquePostes()->create([
            'poste_id'        => $nouveauPosteId,
            'departement_id'  => $nouveauDepartementId,
            'date_changement' => now(),
            'motif'           => $motif,
        ]);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'LIKE', "%{$term}%")
                ->orWhere('prenom', 'LIKE', "%{$term}%")
                ->orWhere('matricule', 'LIKE', "%{$term}%");
        });
    }
}
