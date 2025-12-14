<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormationCompetence extends Model
{
    protected $table = 'formation_competences';

    protected $fillable = [
        'formation_id',
        'competence_id',
        'niveau_apport',
    ];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function competence()
    {
        return $this->belongsTo(Competence::class);
    }
}
