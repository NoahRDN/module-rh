<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosteCompetence extends Model
{
    protected $table = 'poste_competences';

    protected $fillable = [
        'poste_id',
        'competence_id',
        'niveau_requis',
        'obligatoire',
        'poids',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
    ];

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function competence()
    {
        return $this->belongsTo(Competence::class);
    }
}
