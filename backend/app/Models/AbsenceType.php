<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceType extends Model
{
    protected $table = 'absences_types';

    protected $fillable = [
        'nom',
        'description',
        'est_payant',
        'jours_annuels',
    ];

    protected $casts = [
        'est_payant' => 'boolean',
    ];

    public function soldes()
    {
        return $this->hasMany(SoldeConge::class, 'type_id');
    }

    public function demandes()
    {
        return $this->hasMany(DemandeConge::class, 'type_id');
    }
}
