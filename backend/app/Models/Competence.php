<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    protected $table = 'competences';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'categorie_id',
        'actif',
        'ordre',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieCompetence::class, 'categorie_id');
    }

    public function employes()
    {
        return $this->belongsToMany(Employe::class, 'employe_competences')
            ->withPivot('niveau', 'date_evaluation', 'commentaire', 'evalue_par')
            ->withTimestamps();
    }

    public function postes()
    {
        return $this->belongsToMany(Poste::class, 'poste_competences')
            ->withPivot('niveau_requis', 'obligatoire', 'poids')
            ->withTimestamps();
    }

    public function formations()
    {
        return $this->belongsToMany(Formation::class, 'formation_competences')
            ->withPivot('niveau_apport')
            ->withTimestamps();
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    public function scopeByCategorie($query, $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }
}
