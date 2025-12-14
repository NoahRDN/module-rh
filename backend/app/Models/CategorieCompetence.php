<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieCompetence extends Model
{
    protected $table = 'categorie_competences';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'couleur',
        'actif',
        'ordre',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function competences()
    {
        return $this->hasMany(Competence::class, 'categorie_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }
}
