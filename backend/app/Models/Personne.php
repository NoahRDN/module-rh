<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personne extends Model
{
    protected $table = 'personne';
    protected $primaryKey = 'id_personne';
    public $timestamps = false;

    protected $fillable = [
        'nom', 'prenom', 'date_naissance', 'image', 'id_ville', 'id_genre'
    ];

    public function ville()
    {
        return $this->belongsTo(Ville::class, 'Id_Ville');
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class, 'Id_Genre');
    }

    public function utilisateur()
    {
        return $this->hasOne(Utilisateur::class, 'Id_Personne');
    }

    public function employe()
    {
        return $this->hasOne(Employe::class, 'Id_Personne');
    }
}