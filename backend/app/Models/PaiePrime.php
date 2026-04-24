<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiePrime extends Model
{
    protected $table = 'paie_primes';

    protected $fillable = [
        'paie_id',
        'remuneration_item_id',
        'libelle',
        'nature',
        'is_taxable',
        'montant',
        'source_code',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
    ];

    public function paie()
    {
        return $this->belongsTo(Paie::class);
    }

    public function remunerationItem()
    {
        return $this->belongsTo(RemunerationItem::class);
    }
}
