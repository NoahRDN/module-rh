<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    protected $fillable = [
        'employe_id',
        'type',
        'pointe_a',
        'source',
        'commentaire',
    ];

    protected $casts = [
        'pointe_a' => 'datetime',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function scopeForEmploye(Builder $query, $employeId): Builder
    {
        if (!$employeId) {
            return $query;
        }
        return $query->where('employe_id', $employeId);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        if ($from) {
            $query->whereDate('pointe_a', '>=', $from);
        }
        if ($to) {
            $query->whereDate('pointe_a', '<=', $to);
        }
        return $query;
    }
}
