<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'solde',
        'active',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function mouvements()
    {
        return $this->hasMany(CaisseMouvement::class);
    }
}
