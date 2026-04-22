<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendrierEvenement extends Model
{
    protected $fillable = [
        'type',
        'employe_id',
        'date_debut',
        'date_fin',
        'description',
        'meta',
    ];

    protected $casts = [
        'date_debut' => 'date:Y-m-d',
        'date_fin'   => 'date:Y-m-d',
        'meta'       => 'array',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
