<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait pour ajouter l'audit automatique aux modèles.
 * 
 * Utilisation: 
 * 1. Ajouter `use Auditable;` dans le modèle
 * 2. Optionnel: définir $auditExclude pour exclure certains champs
 */
trait Auditable
{
    /**
     * Boot du trait pour enregistrer les événements
     */
    public static function bootAuditable(): void
    {
        // Audit à la création
        static::created(function ($model) {
            $model->logAudit('create', null, $model->getAuditableAttributes());
        });

        // Audit à la mise à jour
        static::updated(function ($model) {
            $oldValues = $model->getOriginalAuditableAttributes();
            $newValues = $model->getAuditableAttributes();
            
            // Ne log que s'il y a des changements réels
            if ($oldValues !== $newValues) {
                $model->logAudit('update', $oldValues, $newValues);
            }
        });

        // Audit à la suppression
        static::deleted(function ($model) {
            $model->logAudit('delete', $model->getAuditableAttributes(), null);
        });
    }

    /**
     * Enregistre une entrée d'audit
     */
    protected function logAudit(string $action, ?array $oldValues, ?array $newValues, ?string $description = null): void
    {
        $user = Auth::user();
        $request = Request::instance();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'description' => $description ?? $this->getAuditDescription($action),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'metadata' => $this->getAuditMetadata(),
        ]);
    }

    /**
     * Log manuel d'une action personnalisée
     */
    public function logCustomAction(string $action, ?string $description = null, ?array $metadata = null): void
    {
        $user = Auth::user();
        $request = Request::instance();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'description' => $description,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'metadata' => $metadata ?? $this->getAuditMetadata(),
        ]);
    }

    /**
     * Récupère les attributs à auditer (excluant les champs sensibles)
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        $exclude = $this->getAuditExclude();

        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Récupère les attributs originaux à auditer
     */
    protected function getOriginalAuditableAttributes(): array
    {
        $original = $this->getOriginal();
        $exclude = $this->getAuditExclude();

        return array_diff_key($original, array_flip($exclude));
    }

    /**
     * Champs à exclure de l'audit (sensibles)
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') 
            ? $this->auditExclude 
            : ['password', 'remember_token', 'updated_at'];
    }

    /**
     * Génère une description automatique de l'action
     */
    protected function getAuditDescription(string $action): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        return match ($action) {
            'create' => "Création de {$modelName} {$identifier}",
            'update' => "Modification de {$modelName} {$identifier}",
            'delete' => "Suppression de {$modelName} {$identifier}",
            default => "{$action} sur {$modelName} {$identifier}",
        };
    }

    /**
     * Identifiant lisible du modèle pour les logs
     */
    protected function getAuditIdentifier(): string
    {
        // Essaie plusieurs attributs communs
        return $this->nom 
            ?? $this->name 
            ?? $this->matricule 
            ?? $this->numero 
            ?? $this->email 
            ?? "#{$this->getKey()}";
    }

    /**
     * Métadonnées additionnelles pour l'audit
     */
    protected function getAuditMetadata(): array
    {
        return [];
    }

    /**
     * Relation vers les logs d'audit de ce modèle
     */
    public function auditLogs()
    {
        return AuditLog::where('auditable_type', get_class($this))
            ->where('auditable_id', $this->getKey())
            ->orderBy('created_at', 'desc');
    }
}
