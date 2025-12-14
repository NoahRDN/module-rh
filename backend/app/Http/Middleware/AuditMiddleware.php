<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour journaliser les actions d'authentification
 * et les accès aux ressources sensibles.
 */
class AuditMiddleware
{
    /**
     * Routes sensibles à auditer automatiquement
     */
    protected array $sensitiveRoutes = [
        'api/v1/paies',
        'api/v1/employes',
        'api/v1/contrats',
        'api/v1/documents',
        'api/v1/demandes-rh',
    ];

    /**
     * Actions à toujours auditer (basées sur la méthode HTTP)
     */
    protected array $auditMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log uniquement pour les utilisateurs authentifiés
        if (!$request->user()) {
            return $response;
        }

        // Log les requêtes de modification ou les accès sensibles
        if ($this->shouldAudit($request, $response)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Détermine si la requête doit être auditée
     */
    protected function shouldAudit(Request $request, Response $response): bool
    {
        // Toujours auditer les modifications réussies
        if (in_array($request->method(), $this->auditMethods) && $response->isSuccessful()) {
            return true;
        }

        // Auditer les exports
        if (str_contains($request->path(), '/pdf') || str_contains($request->path(), '/export')) {
            return true;
        }

        // Auditer les accès aux routes sensibles
        foreach ($this->sensitiveRoutes as $route) {
            if (str_starts_with($request->path(), $route)) {
                return $request->method() === 'GET' && $response->isSuccessful();
            }
        }

        return false;
    }

    /**
     * Enregistre la requête dans les logs d'audit
     */
    protected function logRequest(Request $request, Response $response): void
    {
        $action = $this->determineAction($request);
        
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => $this->determineAuditableType($request),
            'auditable_id' => $this->extractResourceId($request),
            'description' => $this->generateDescription($request, $action),
            'old_values' => null,
            'new_values' => $this->sanitizeRequestData($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'metadata' => [
                'response_status' => $response->getStatusCode(),
                'route_name' => $request->route()?->getName(),
            ],
        ]);
    }

    /**
     * Détermine le type d'action basé sur la méthode HTTP
     */
    protected function determineAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            'GET' => str_contains($request->path(), '/pdf') || str_contains($request->path(), '/export') 
                ? 'export' 
                : 'view',
            default => 'view',
        };
    }

    /**
     * Détermine le type d'entité basé sur l'URL
     */
    protected function determineAuditableType(Request $request): string
    {
        $path = $request->path();

        $typeMap = [
            'employes' => 'App\\Models\\Employe',
            'contrats' => 'App\\Models\\Contrat',
            'demandes-conges' => 'App\\Models\\DemandeConge',
            'demandes-rh' => 'App\\Models\\Demande',
            'documents' => 'App\\Models\\DocumentEmploye',
            'paies' => 'App\\Models\\Paie',
            'formations' => 'App\\Models\\Formation',
            'evaluations' => 'App\\Models\\Evaluation',
            'departements' => 'App\\Models\\Departement',
            'postes' => 'App\\Models\\Poste',
        ];

        foreach ($typeMap as $segment => $model) {
            if (str_contains($path, $segment)) {
                return $model;
            }
        }

        return 'App\\Http\\Request';
    }

    /**
     * Extrait l'ID de la ressource depuis l'URL
     */
    protected function extractResourceId(Request $request): ?int
    {
        // Cherche un paramètre numérique dans la route
        $routeParams = $request->route()?->parameters() ?? [];
        
        foreach ($routeParams as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * Génère une description lisible de l'action
     */
    protected function generateDescription(Request $request, string $action): string
    {
        $user = $request->user();
        $path = $request->path();

        return sprintf(
            "%s a effectué une action '%s' sur %s",
            $user->name ?? $user->email,
            AuditLog::ACTIONS[$action] ?? $action,
            $path
        );
    }

    /**
     * Nettoie les données de la requête pour l'audit (supprime les champs sensibles)
     */
    protected function sanitizeRequestData(Request $request): ?array
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return null;
        }

        $data = $request->all();
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***MASQUÉ***';
            }
        }

        return $data;
    }
}
