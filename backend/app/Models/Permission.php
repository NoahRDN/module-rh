<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'code',
        'libelle',
        'groupe',
        'description',
    ];

    /**
     * Récupère les permissions par rôle
     */
    public static function getForRole(string $role): array
    {
        return \DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $role)
            ->pluck('permissions.code')
            ->toArray();
    }

    /**
     * Vérifie si un rôle a une permission
     */
    public static function roleHas(string $role, string $permissionCode): bool
    {
        return \DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $role)
            ->where('permissions.code', $permissionCode)
            ->exists();
    }

    /**
     * Attribue une permission à un rôle
     */
    public static function grantToRole(string $role, string $permissionCode): bool
    {
        $permission = static::where('code', $permissionCode)->first();
        if (!$permission) {
            return false;
        }

        \DB::table('role_permissions')->insertOrIgnore([
            'role' => $role,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Retire une permission d'un rôle
     */
    public static function revokeFromRole(string $role, string $permissionCode): bool
    {
        $permission = static::where('code', $permissionCode)->first();
        if (!$permission) {
            return false;
        }

        \DB::table('role_permissions')
            ->where('role', $role)
            ->where('permission_id', $permission->id)
            ->delete();

        return true;
    }

    /**
     * Récupère toutes les permissions groupées
     */
    public static function getAllGrouped(): array
    {
        return static::all()
            ->groupBy('groupe')
            ->map(fn ($perms) => $perms->pluck('libelle', 'code'))
            ->toArray();
    }
}
