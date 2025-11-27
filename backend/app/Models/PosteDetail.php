<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosteDetail extends Model
{
    protected $table = 'poste_detail';
    protected $primaryKey = 'id_poste_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_poste', 'description', 'objectif'
    ];

    public function poste()
    {
        return $this->belongsTo(Poste::class, 'id_poste');
    }
}