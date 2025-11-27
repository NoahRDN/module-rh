<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourFerie extends Model
{
    protected $table = 'jour_ferie';
    protected $primaryKey = 'id_jour';
    public $timestamps = false;

    protected $fillable = [
        'nom', 'date_jour', 'repetition_annuelle'
    ];

    protected $casts = [
        'date_jour' => 'date',
        'repetition_annuelle' => 'boolean',
    ];
}