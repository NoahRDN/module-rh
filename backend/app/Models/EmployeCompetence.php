<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @note Fonction prête mais non utilisée pour l'instant.
 * @deprecated Activation ultérieure (phase 2).
 * 
 * Modèle pour la gestion des compétences des employés.
 * Features: Cartographie des compétences, Matching automatique profil/poste, 
 * Suggestion de formations en fonction des écarts de compétences
 */
class EmployeCompetence extends Model
{
    protected $table = 'employe_competences';

    protected $fillable = [
        'employe_id',
        'competence_id',
        'niveau',
        'date_evaluation',
        'commentaire',
        'evalue_par',
    ];

    protected $casts = [
        'date_evaluation' => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function competence()
    {
        return $this->belongsTo(Competence::class);
    }

    public function evaluateur()
    {
        return $this->belongsTo(User::class, 'evalue_par');
    }

    public function niveauDetail()
    {
        return NiveauCompetence::where('niveau', $this->niveau)->first();
    }
}
