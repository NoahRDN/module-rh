<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeContrat extends Model
{
    protected $table = 'type_contrat';
    protected $primaryKey = 'id_type_contrat';
    public $timestamps = false;
    protected $fillable = ['nom', 'description'];
}