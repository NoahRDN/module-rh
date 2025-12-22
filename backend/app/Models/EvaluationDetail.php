<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationDetail extends Model
{
    protected $table = 'evaluation_details';

    protected $fillable = [
        'evaluation_id',
        'critere_id',
        'note',
        'commentaire',
    ];

    protected $casts = [
        'note' => 'decimal:2',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function critere()
    {
        return $this->belongsTo(CritereEvaluation::class, 'critere_id');
    }
}
