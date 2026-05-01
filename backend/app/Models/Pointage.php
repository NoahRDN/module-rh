<?php

namespace App\Models;

use Carbon\Carbon;
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
            $query->where('pointe_a', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $query->where('pointe_a', '<=', Carbon::parse($to)->endOfDay());
        }
        return $query;
    }
}
