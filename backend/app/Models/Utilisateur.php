<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'utilisateur';
    protected $primaryKey = 'id_utilisateur';
    public $timestamps = false;

    protected $fillable = [
        'identifiant',
        'mdp',
        'id_role',
        'id_personne'
    ];

    protected $hidden = ['mdp'];

    public function getAuthPassword()
    {
        return $this->mdp;
    }
}
