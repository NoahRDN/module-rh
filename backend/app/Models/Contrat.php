<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ContratHistorique;

class Contrat extends Model
{
    use Auditable;

    protected $table = 'contrats';

    protected $fillable = [
        'numero',
        'employe_id',
        'type_contrat',
        'date_debut',
        'date_fin',
        'periode_essai_debut',
        'periode_essai_fin',
        'renouvelable',
        'salaire_base',
        'statut',
    ];

    protected $casts = [
        'date_debut'           => 'date',
        'date_fin'             => 'date',
        'periode_essai_debut'  => 'date',
        'periode_essai_fin'    => 'date',
        'renouvelable'         => 'boolean',
        'salaire_base'         => 'decimal:2'
    ];

    protected $appends = [
        'taux_horaire',
        'taux_journalier',
    ];

    public function getTauxHoraireAttribute(): float
    {
        return round(((float) $this->salaire_base) / 173.33, 2);
    }

    public function getTauxJournalierAttribute(): float
    {
        return round(((float) $this->salaire_base) / 30, 2);
    }

    protected static function booted(): void
    {
        static::created(function (Contrat $contrat) {
            ContratHistorique::create([
                'contrat_id' => $contrat->id,
                'numero' => $contrat->numero,
                'employe_id' => $contrat->employe_id,
                'type_contrat' => $contrat->type_contrat,
                'date_debut' => $contrat->date_debut,
                'date_fin' => $contrat->date_fin,
                'periode_essai_debut' => $contrat->periode_essai_debut,
                'periode_essai_fin' => $contrat->periode_essai_fin,
                'renouvelable' => $contrat->renouvelable,
                'salaire_base' => $contrat->salaire_base,
            ]);
        });
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function remunerationItems()
    {
        return $this->hasMany(RemunerationItem::class);
    }
}
