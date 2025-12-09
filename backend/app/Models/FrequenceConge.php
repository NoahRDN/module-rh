<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrequenceConge extends Model
{
    protected $table = 'frequence_conges';

    protected $fillable = [
        'code',
        'libelle',
        'description',
    ];

    public function types()
    {
        return $this->hasMany(TypeConge::class, 'frequence_id');
    }
}
