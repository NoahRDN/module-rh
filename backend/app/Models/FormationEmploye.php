<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormationEmploye extends Model
{
    protected $table = 'formation_employes';

    protected $fillable = [
        'employe_id',
        'formation_id',
        'statut',
        'date_debut',
        'date_fin',
        'note',
        'commentaire',
        'certificat_obtenu',
        'demande_par',
        'valide_par',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'note' => 'decimal:2',
        'certificat_obtenu' => 'boolean',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function demandeur()
    {
        return $this->belongsTo(User::class, 'demande_par');
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeTerminees($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopePlanifiees($query)
    {
        return $query->where('statut', 'planifiee');
    }
}
