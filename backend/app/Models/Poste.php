<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poste extends Model
{
    protected $table = 'poste';
    protected $primaryKey = 'id_poste';
    public $timestamps = false;

    protected $fillable = [
        'id_profil', 'id_departement', 'id_unite', 'fonction', 'description', 'nombre'
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'id_unite');
    }

    public function profil()
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }

    public function details()
    {
        return $this->hasMany(PosteDetail::class, 'id_poste');
    }
    
    public function employes()
    {
        return $this->hasMany(Employe::class, 'id_poste');
    }
}