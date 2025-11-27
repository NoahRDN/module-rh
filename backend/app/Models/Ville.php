<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $table = 'ville';
    protected $primaryKey = 'id_ville';
    public $timestamps = false;
    protected $fillable = ['nom'];
}