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
    ];

    protected $casts = [
        'jour' => 'date',
    ];

    public function paie()
    {
        return $this->belongsTo(Paie::class);
    }
}
