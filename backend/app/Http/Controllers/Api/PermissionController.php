<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour la gestion des permissions.
 */
class PermissionController extends Controller
{
    /**
     * Liste de toutes les permissions
     */
    public function index()
    {
        try {
            $permissions = Permission::orderBy('groupe')->orderBy('code')->get();
            return response()->json(['data' => $permissions]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération permissions', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Permissions groupées par catégorie
     */
    public function grouped()
    {
        try {
            $grouped = Permission::getAllGrouped();
            return response()->json($grouped);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération permissions groupées', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Permissions d'un rôle spécifique
     */
    public function forRole(string $role)
    {
        try {
            $permissions = Permission::getForRole($role);
            return response()->json(['data' => $permissions]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération permissions rôle', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour les permissions d'un rôle
     */
    public function updateRole(Request $request, string $role)
    {
        try {
            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,code',
            ]);

            // Supprime les permissions actuelles
            DB::table('role_permissions')->where('role', $role)->delete();

            // Ajoute les nouvelles permissions
            foreach ($validated['permissions'] as $code) {
                Permission::grantToRole($role, $code);
            }

            return response()->json([
                'message' => 'Permissions mises à jour',
                'permissions' => Permission::getForRole($role),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour permissions', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Vérifier si un rôle a une permission
     */
    public function check(Request $request)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string',
                'permission' => 'required|string',
            ]);

            $has = Permission::roleHas($validated['role'], $validated['permission']);

            return response()->json(['has_permission' => $has]);
        } catch (\Throwable $e) {
            Log::error('Erreur vérification permission', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Liste des rôles disponibles
     */
    public function roles()
    {
        return response()->json([
            ['code' => 'admin', 'libelle' => 'Administrateur'],
            ['code' => 'rh', 'libelle' => 'Ressources Humaines'],
            ['code' => 'manager', 'libelle' => 'Manager'],
            ['code' => 'employe', 'libelle' => 'Employé'],
        ]);
    }

    /**
     * Matrice des permissions par rôle
     */
    public function matrix()
    {
        try {
            $roles = ['admin', 'rh', 'manager', 'employe'];
            $permissions = Permission::orderBy('groupe')->orderBy('code')->get();

            $matrix = [];
            foreach ($permissions as $perm) {
                $row = [
                    'code' => $perm->code,
                    'libelle' => $perm->libelle,
                    'groupe' => $perm->groupe,
                ];
                foreach ($roles as $role) {
                    $row[$role] = Permission::roleHas($role, $perm->code);
                }
                $matrix[] = $row;
            }

            return response()->json([
                'roles' => $roles,
                'permissions' => $matrix,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur matrice permissions', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
