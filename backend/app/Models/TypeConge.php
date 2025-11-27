<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeConge extends Model
{
    protected $table = 'type_conge';
    protected $primaryKey = 'id_type_conge';
    public $timestamps = false;
    
    protected $fillable = [
        'nom', 'description', 'est_paye', 'duree_max_jours'
    ];

    // Conversion automatique du booléen pour PostgreSQL (t/f ou 1/0)
    protected $casts = [
        'est_paye' => 'boolean',
    ];
}