<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveSetting extends Model
{
    protected $table = 'archive_settings';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'retention_years',
        'retention_months',
        'action_on_expiry',
        'notify_before_days',
        'legal_reference',
        'is_active',
    ];

    protected $casts = [
        'retention_years' => 'integer',
        'retention_months' => 'integer',
        'notify_before_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Calcule la durée totale de rétention en jours
     */
    public function getRetentionDaysAttribute(): int
    {
        return ($this->retention_years * 365) + ($this->retention_months * 30);
    }

    /**
     * Calcule la date d'expiration à partir d'une date donnée
     */
    public function calculateExpirationDate($fromDate): \Carbon\Carbon
    {
        $date = \Carbon\Carbon::parse($fromDate);
        return $date->addYears($this->retention_years)->addMonths($this->retention_months);
    }

    /**
     * Documents archivés avec ce paramètre
     */
    public function archivedDocuments()
    {
        return $this->hasMany(ArchivedDocument::class, 'archive_setting_id');
    }

    /**
     * Scope pour les paramètres actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Récupère un paramètre par son code
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
