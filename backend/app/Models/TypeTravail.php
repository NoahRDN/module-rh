<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeTravail extends Model
{
    protected $table = 'type_travail';
    protected $primaryKey = 'id_type_travail';
    public $timestamps = false;
    protected $fillable = ['nom', 'description'];
}