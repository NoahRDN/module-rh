<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcquisConge extends Model
{
    protected $fillable = [
        'employe_id',
        'type_conge_id',
        'jours_acquis',
        'acquis_le',
        'expire_le',
        'acquis_first',
        'expire_first',
        'contrat_type',
        'contrat_fin',
    ];

    protected $casts = [
        'acquis_le' => 'date',
        'expire_le' => 'date',
        'acquis_first' => 'date',
        'expire_first' => 'date',
        'contrat_fin' => 'date',
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
        $consomme = $this->consommations()->sum('jours_utilises');
        return (float) $this->jours_acquis - (float) $consomme;
    }
}
