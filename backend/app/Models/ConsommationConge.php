<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsommationConge extends Model
{
    protected $table = 'consommations_conges';
    protected $fillable = [
        'demande_conge_id',
        'acquis_conge_id',
        'jours_utilises',
    ];

    public function demande()
    {
        return $this->belongsTo(DemandeConge::class, 'demande_conge_id');
    }

    public function acquis()
    {
        return $this->belongsTo(AcquisConge::class, 'acquis_conge_id');
    }
}
