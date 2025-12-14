<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    /**
     * Champs exclus de l'audit (sensibles)
     */
    protected array $auditExclude = ['password', 'remember_token', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employe_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationsNonLues()
    {
        return $this->notifications()->where('lu', false);
    }

    public function conversationsAssignees()
    {
        return $this->hasMany(Conversation::class, 'assigne_a');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRH(): bool
    {
        return $this->role === 'rh';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isEmploye(): bool
    {
        return $this->role === 'employe';
    }

    public function canAccessSelfService(): bool
    {
        return $this->employe_id !== null;
    }

    /**
     * Récupère les permissions de l'utilisateur basées sur son rôle
     */
    public function getPermissionsAttribute(): array
    {
        return Permission::getForRole($this->role);
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        // Les admins ont toutes les permissions
        if ($this->isAdmin()) {
            return true;
        }

        return Permission::roleHas($this->role, $permission);
    }

    /**
     * Vérifie si l'utilisateur est manager d'un département
     */
    public function getDepartementGereAttribute(): ?Departement
    {
        if (!$this->employe_id) {
            return null;
        }

        return Departement::where('manager_id', $this->employe_id)->first();
    }

    /**
     * Vérifie si l'utilisateur gère un département
     */
    public function isManagerOfDepartement(): bool
    {
        return $this->departement_gere !== null;
    }

    /**
     * Récupère les employés de l'équipe (si manager)
     */
    public function getEquipeAttribute()
    {
        $dept = $this->departement_gere;
        if (!$dept) {
            return collect();
        }

        return $dept->employes()->where('id', '!=', $this->employe_id)->get();
    }

    /**
     * Vérifie si un employé fait partie de l'équipe du manager
     */
    public function isManagerOf(Employe $employe): bool
    {
        $dept = $this->departement_gere;
        if (!$dept) {
            return false;
        }

        return $employe->departement_id === $dept->id && $employe->id !== $this->employe_id;
    }
}
