<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

/**
 * Controller pour la gestion des audits.
 */
class AuditController extends Controller
{
    public function __construct(private AuditService $auditService)
    {
    }

    /**
     * Liste des logs d'audit avec filtres
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'user_id' => $request->query('user_id'),
                'action' => $request->query('action'),
                'auditable_type' => $request->query('type'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'search' => $request->query('search'),
            ];

            $perPage = $request->query('per_page', 20);
            $logs = $this->auditService->getLogs($filters, $perPage);

            return response()->json($logs);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération audit logs', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'un log
     */
    public function show($id)
    {
        try {
            $log = AuditLog::with('user')->findOrFail($id);

            return response()->json([
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                    'role' => $log->user->role,
                ] : null,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'auditable_type' => $log->auditable_type,
                'auditable_name' => $log->auditable_name,
                'auditable_id' => $log->auditable_id,
                'description' => $log->description,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'changed_fields' => $log->changed_fields,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'url' => $log->url,
                'method' => $log->method,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération audit log', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Statistiques d'audit
     */
    public function statistiques(Request $request)
    {
        try {
            $period = $request->query('period', 'week');
            $stats = $this->auditService->getStatistics($period);

            return response()->json($stats);
        } catch (\Throwable $e) {
            Log::error('Erreur stats audit', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Liste des actions disponibles
     */
    public function actions()
    {
        return response()->json(AuditLog::ACTIONS);
    }

    /**
     * Liste des types d'entités auditées
     */
    public function types()
    {
        $types = AuditLog::selectRaw('DISTINCT auditable_type')
            ->pluck('auditable_type')
            ->map(fn($type) => [
                'value' => $type,
                'label' => class_basename($type),
            ]);

        return response()->json($types);
    }

    /**
     * Liste des utilisateurs ayant des logs
     */
    public function users()
    {
        $userIds = AuditLog::selectRaw('DISTINCT user_id')
            ->whereNotNull('user_id')
            ->pluck('user_id');

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email', 'role')
            ->get();

        return response()->json($users);
    }

    /**
     * Export des logs en CSV
     */
    public function export(Request $request)
    {
        try {
            $filters = [
                'user_id' => $request->query('user_id'),
                'action' => $request->query('action'),
                'auditable_type' => $request->query('type'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ];

            $csv = $this->auditService->exportToCsv($filters);

            // Log l'export
            $this->auditService->logExport(
                $request->user(),
                AuditLog::class,
                'Export des logs d\'audit',
                ['filters' => $filters]
            );

            $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur export audit', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Historique d'audit d'une entité spécifique
     */
    public function entityHistory(Request $request)
    {
        try {
            $type = $request->query('type');
            $id = $request->query('id');

            if (!$type || !$id) {
                return response()->json([
                    'message' => 'Type et ID requis',
                ], 422);
            }

            $logs = AuditLog::with('user')
                ->where('auditable_type', $type)
                ->where('auditable_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => $logs]);
        } catch (\Throwable $e) {
            Log::error('Erreur historique entité', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
