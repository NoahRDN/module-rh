<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle en lecture seule basé sur la vue SQL view_types_conges_full.
 */
class ViewTypeCongeFull extends Model
{
    protected $table = 'view_types_conges_full';
    public $timestamps = false;

    protected $fillable = [];

    // Helpers pour aligner avec les relations habituelles
    public function frequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'frequence_id');
    }

    public function limiteFrequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'limite_frequence_id');
    }

    public function cumulableFrequence()
    {
        return $this->belongsTo(FrequenceConge::class, 'cumulable_frequence_id');
    }
}
