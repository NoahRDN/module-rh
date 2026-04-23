<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaieDetail extends Model
{
    protected $table = 'paie_details';

    protected $fillable = [
        'paie_id',
        'jour',
        'heures_travaillees',
        'heures_supplementaires',
        'retard_minutes',
        'absent',
        'absence_justifiee',
        'ferie',
        'weekend',
        'present_partiel',
    ];

    protected $casts = [
        'jour' => 'date',
        'absent' => 'boolean',
        'absence_justifiee' => 'boolean',
        'ferie' => 'boolean',
        'weekend' => 'boolean',
        'present_partiel' => 'boolean',
    ];

    public function paie()
    {
        return $this->belongsTo(Paie::class);
    }
}
