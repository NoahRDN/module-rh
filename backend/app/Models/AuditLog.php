<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Actions disponibles
     */
    public const ACTIONS = [
        'create' => 'Création',
        'update' => 'Modification',
        'delete' => 'Suppression',
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'view' => 'Consultation',
        'export' => 'Export',
        'approve' => 'Approbation',
        'reject' => 'Rejet',
        'archive' => 'Archivage',
    ];

    /**
     * Utilisateur qui a effectué l'action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entité auditée (polymorphique)
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour filtrer par type d'entité
     */
    public function scopeByType($query, $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeBetweenDates($query, $from, $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Récupère les champs modifiés
     */
    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }

    /**
     * Formatte l'action pour l'affichage
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Récupère le nom du modèle audité en format lisible
     */
    public function getAuditableNameAttribute(): string
    {
        $typeMap = [
            'App\\Models\\Employe' => 'Employé',
            'App\\Models\\Contrat' => 'Contrat',
            'App\\Models\\DemandeConge' => 'Demande de congé',
            'App\\Models\\Demande' => 'Demande RH',
            'App\\Models\\DocumentEmploye' => 'Document',
            'App\\Models\\Paie' => 'Paie',
            'App\\Models\\Formation' => 'Formation',
            'App\\Models\\Evaluation' => 'Évaluation',
            'App\\Models\\User' => 'Utilisateur',
            'App\\Models\\Departement' => 'Département',
            'App\\Models\\Poste' => 'Poste',
        ];

        return $typeMap[$this->auditable_type] ?? class_basename($this->auditable_type);
    }
}
