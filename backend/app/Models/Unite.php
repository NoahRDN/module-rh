<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unite extends Model
{
    protected $table = 'unite';
    protected $primaryKey = 'id_unite';
    public $timestamps = false;
    protected $fillable = ['nom', 'niveau'];
}