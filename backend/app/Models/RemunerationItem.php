<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class RemunerationItem extends Model
{
    use Auditable;

    protected $table = 'remuneration_items';

    protected $fillable = [
        'libelle',
        'nature',
        'scope_type',
        'poste_id',
        'employe_id',
        'contrat_id',
        'recurrence_type',
        'mois_application',
        'condition_type',
        'condition_operator',
        'condition_value',
        'montant',
        'calculation_type',
        'prorata',
        'depends_on_presence',
        'is_taxable',
        'actif',
    ];

    protected $casts = [
        'condition_value' => 'decimal:2',
        'montant' => 'decimal:2',
        'prorata' => 'boolean',
        'depends_on_presence' => 'boolean',
        'is_taxable' => 'boolean',
        'actif' => 'boolean',
    ];

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }
}
