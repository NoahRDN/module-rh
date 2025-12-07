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
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
