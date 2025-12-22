<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    protected $table = 'formations';

    protected $fillable = [
        'code',
        'titre',
        'description',
        'duree_heures',
        'type',
        'niveau',
        'cout',
        'organisme',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'cout' => 'decimal:2',
    ];

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'formation_competences')
            ->withPivot('niveau_apport')
            ->withTimestamps();
    }

    public function employes()
    {
        return $this->belongsToMany(Employe::class, 'formation_employes')
            ->withPivot('statut', 'date_debut', 'date_fin', 'note', 'commentaire', 'certificat_obtenu', 'demande_par', 'valide_par')
            ->withTimestamps();
    }

    public function inscriptions()
    {
        return $this->hasMany(FormationEmploye::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }
}
