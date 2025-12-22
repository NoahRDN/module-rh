<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDemandeRH extends Model
{
    protected $table = 'types_demandes';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'categorie',
        'necessite_validation',
        'necessite_document',
        'actif',
        'delai_traitement',
    ];

    protected $casts = [
        'necessite_validation' => 'boolean',
        'necessite_document' => 'boolean',
        'actif' => 'boolean',
    ];

    public function demandes()
    {
        return $this->hasMany(Demande::class, 'type_demande_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}
