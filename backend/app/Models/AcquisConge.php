<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcquisConge extends Model
{
    protected $fillable = [
        'employe_id',
        'type_conge_id',
        'jours_acquis',
        'jours_utilises',
        'acquis_le',
        'expire_le',
    ];

    protected $casts = [
        'acquis_le' => 'date',
        'expire_le' => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }

    public function consommations()
    {
        return $this->hasMany(ConsommationConge::class, 'acquis_conge_id');
    }

    public function reste(): float
    {
        return (float) $this->jours_acquis - (float) $this->jours_utilises;
    }
}
