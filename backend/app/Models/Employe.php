<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    protected $table = 'employe';
    protected $primaryKey = 'id_employe';
    public $timestamps = false;

    protected $fillable = [
        'debut', 'id_poste', 'id_personne'
    ];

    public function personne()
    {
        return $this->belongsTo(Personne::class, 'id_personne');
    }

    public function poste()
    {
        return $this->belongsTo(Poste::class, 'id_poste');
    }

    public function contrats()
    {
        return $this->hasMany(ContratEmploye::class, 'id_employe');
    }

    public function conges()
    {
        return $this->hasMany(Conge::class, 'id_employe');
    }

    public function fichesPaie()
    {
        return $this->hasMany(FichePaie::class, 'id_employe');
    }
    
    public function soldeConge()
    {
        return $this->hasOne(SoldeConge::class, 'id_employe');
    }
}