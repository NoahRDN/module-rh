<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArchivedDocument extends Model
{
    protected $table = 'archived_documents';

    protected $fillable = [
        'document_type',
        'original_id',
        'original_table',
        'employe_id',
        'titre',
        'description',
        'fichier_path',
        'fichier_hash',
        'fichier_size',
        'date_document',
        'date_archivage',
        'date_expiration',
        'archive_setting_id',
        'statut',
        'archived_by',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'date_document' => 'date',
        'date_archivage' => 'date',
        'date_expiration' => 'date',
        'metadata' => 'array',
        'fichier_size' => 'integer',
    ];

    /**
     * Statuts possibles
     */
    public const STATUTS = [
        'actif' => 'Actif',
        'expire' => 'Expiré',
        'supprime' => 'Supprimé',
        'en_revision' => 'En révision',
    ];

    /**
     * Employé concerné
     */
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    /**
     * Paramètre d'archivage utilisé
     */
    public function archiveSetting()
    {
        return $this->belongsTo(ArchiveSetting::class, 'archive_setting_id');
    }

    /**
     * Utilisateur qui a archivé
     */
    public function archivedByUser()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Vérifie si le document est expiré
     */
    public function isExpired(): bool
    {
        return $this->date_expiration && $this->date_expiration->isPast();
    }

    /**
     * Jours restants avant expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->date_expiration) {
            return null;
        }
        return now()->diffInDays($this->date_expiration, false);
    }

    /**
     * Vérifie l'intégrité du fichier via le hash
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->fichier_hash || !Storage::disk('archives')->exists($this->fichier_path)) {
            return false;
        }

        $currentHash = hash_file('sha256', Storage::disk('archives')->path($this->fichier_path));
        return $this->fichier_hash === $currentHash;
    }

    /**
     * Scope pour les documents actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope pour les documents expirés
     */
    public function scopeExpires($query)
    {
        return $query->where('statut', 'actif')
            ->where('date_expiration', '<', now());
    }

    /**
     * Scope pour les documents expirant bientôt
     */
    public function scopeExpirantBientot($query, int $jours = 30)
    {
        return $query->where('statut', 'actif')
            ->whereBetween('date_expiration', [now(), now()->addDays($jours)]);
    }

    /**
     * Scope par type de document
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope par employé
     */
    public function scopeByEmploye($query, int $employeId)
    {
        return $query->where('employe_id', $employeId);
    }

    /**
     * Marque le document comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['statut' => 'expire']);
    }

    /**
     * Marque le document comme supprimé (soft delete logique)
     */
    public function markAsDeleted(): void
    {
        $this->update(['statut' => 'supprime']);
    }
}
