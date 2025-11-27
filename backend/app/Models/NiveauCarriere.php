<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauCarriere extends Model
{
    protected $table = 'niveau_carriere';
    protected $primaryKey = 'id_niveau_carriere';
    public $timestamps = false;
    protected $fillable = ['nom', 'description'];
}