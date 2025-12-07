<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldeConge extends Model
{
    protected $table = 'soldes_conges';

    protected $fillable = [
        'employe_id',
        'type_id',
        'solde_actuel',
        'solde_annuel',
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'solde_annuel' => 'decimal:2',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function type()
    {
        return $this->belongsTo(AbsenceType::class, 'type_id');
    }
}
