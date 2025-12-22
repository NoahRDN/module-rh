<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Service pour la gestion des audits.
 */
class AuditService
{
    /**
     * Log une connexion utilisateur
     */
    public function logLogin(User $user): void
    {
        $this->log($user, 'login', 'App\\Models\\User', $user->id, "Connexion de {$user->name}");
    }

    /**
     * Log une déconnexion utilisateur
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'logout', 'App\\Models\\User', $user->id, "Déconnexion de {$user->name}");
    }

    /**
     * Log une approbation
     */
    public function logApproval(User $user, string $type, int $id, string $description, ?array $metadata = null): void
    {
        $this->log($user, 'approve', $type, $id, $description, null, null, $metadata);
    }

    /**
     * Log un rejet
     */
    public function logRejection(User $user, string $type, int $id, string $description, ?array $metadata = null): void
    {
        $this->log($user, 'reject', $type, $id, $description, null, null, $metadata);
    }

    /**
     * Log un archivage
     */
    public function logArchive(User $user, string $type, int $id, string $description, ?array $metadata = null): void
    {
        $this->log($user, 'archive', $type, $id, $description, null, null, $metadata);
    }

    /**
     * Log un export
     */
    public function logExport(User $user, string $type, string $description, ?array $metadata = null): void
    {
        $this->log($user, 'export', $type, null, $description, null, null, $metadata);
    }

    /**
     * Log une consultation
     */
    public function logView(User $user, string $type, int $id, string $description): void
    {
        $this->log($user, 'view', $type, $id, $description);
    }

    /**
     * Log générique
     */
    public function log(
        ?User $user,
        string $action,
        string $auditableType,
        ?int $auditableId,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        $request = Request::instance();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Récupère les logs avec filtres
     */
    public function getLogs(array $filters = [], int $perPage = 20)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupère les statistiques d'audit
     */
    public function getStatistics(string $period = 'week'): array
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfWeek(),
        };

        $logs = AuditLog::where('created_at', '>=', $startDate)->get();

        return [
            'total' => $logs->count(),
            'by_action' => $logs->groupBy('action')->map->count(),
            'by_user' => $logs->groupBy('user_id')->map->count()->take(10),
            'by_type' => $logs->groupBy('auditable_type')->map->count(),
            'logins' => $logs->where('action', 'login')->count(),
            'modifications' => $logs->whereIn('action', ['create', 'update', 'delete'])->count(),
        ];
    }

    /**
     * Exporte les logs en CSV
     */
    public function exportToCsv(array $filters = []): string
    {
        $logs = $this->getLogs($filters, 10000)->items();

        $csv = "Date,Utilisateur,Action,Type,ID,Description,IP\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user?->name ?? 'Système',
                $log->action,
                class_basename($log->auditable_type),
                $log->auditable_id ?? '',
                str_replace(',', ';', $log->description ?? ''),
                $log->ip_address ?? ''
            );
        }

        return $csv;
    }
}
