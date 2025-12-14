<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Système de permissions granulaires par rôle.
     */
    public function up(): void
    {
        // Table des permissions disponibles
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('groupe'); // Groupe de permissions (employes, conges, paie, etc.)
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Table pivot rôle-permissions
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // admin, rh, manager, employe
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['role', 'permission_id']);
            $table->index('role');
        });

        // Insertion des permissions par défaut
        $permissions = [
            // Employés
            ['code' => 'employes.view', 'libelle' => 'Voir les employés', 'groupe' => 'employes'],
            ['code' => 'employes.create', 'libelle' => 'Créer un employé', 'groupe' => 'employes'],
            ['code' => 'employes.edit', 'libelle' => 'Modifier un employé', 'groupe' => 'employes'],
            ['code' => 'employes.delete', 'libelle' => 'Supprimer un employé', 'groupe' => 'employes'],
            ['code' => 'employes.view_team', 'libelle' => 'Voir son équipe', 'groupe' => 'employes'],
            
            // Congés
            ['code' => 'conges.view', 'libelle' => 'Voir les congés', 'groupe' => 'conges'],
            ['code' => 'conges.create', 'libelle' => 'Créer une demande', 'groupe' => 'conges'],
            ['code' => 'conges.approve_manager', 'libelle' => 'Valider (Manager)', 'groupe' => 'conges'],
            ['code' => 'conges.approve_rh', 'libelle' => 'Valider (RH)', 'groupe' => 'conges'],
            ['code' => 'conges.reject', 'libelle' => 'Rejeter une demande', 'groupe' => 'conges'],
            ['code' => 'conges.view_team', 'libelle' => 'Voir congés équipe', 'groupe' => 'conges'],
            
            // Paie
            ['code' => 'paie.view', 'libelle' => 'Voir les paies', 'groupe' => 'paie'],
            ['code' => 'paie.generate', 'libelle' => 'Générer les paies', 'groupe' => 'paie'],
            ['code' => 'paie.edit_params', 'libelle' => 'Modifier paramètres', 'groupe' => 'paie'],
            
            // Documents
            ['code' => 'documents.view', 'libelle' => 'Voir les documents', 'groupe' => 'documents'],
            ['code' => 'documents.upload', 'libelle' => 'Uploader documents', 'groupe' => 'documents'],
            ['code' => 'documents.delete', 'libelle' => 'Supprimer documents', 'groupe' => 'documents'],
            ['code' => 'documents.archive', 'libelle' => 'Archiver documents', 'groupe' => 'documents'],
            
            // Évaluations
            ['code' => 'evaluations.view', 'libelle' => 'Voir évaluations', 'groupe' => 'evaluations'],
            ['code' => 'evaluations.create', 'libelle' => 'Créer évaluation', 'groupe' => 'evaluations'],
            ['code' => 'evaluations.edit', 'libelle' => 'Modifier évaluation', 'groupe' => 'evaluations'],
            ['code' => 'evaluations.view_team', 'libelle' => 'Voir évaluations équipe', 'groupe' => 'evaluations'],
            
            // Formations
            ['code' => 'formations.view', 'libelle' => 'Voir formations', 'groupe' => 'formations'],
            ['code' => 'formations.manage', 'libelle' => 'Gérer formations', 'groupe' => 'formations'],
            ['code' => 'formations.assign', 'libelle' => 'Assigner formations', 'groupe' => 'formations'],
            
            // Administration
            ['code' => 'admin.settings', 'libelle' => 'Paramètres système', 'groupe' => 'admin'],
            ['code' => 'admin.users', 'libelle' => 'Gérer utilisateurs', 'groupe' => 'admin'],
            ['code' => 'admin.roles', 'libelle' => 'Gérer permissions', 'groupe' => 'admin'],
            
            // Audit
            ['code' => 'audit.view', 'libelle' => 'Voir audit logs', 'groupe' => 'audit'],
            ['code' => 'audit.export', 'libelle' => 'Exporter audit', 'groupe' => 'audit'],
            
            // Archives
            ['code' => 'archives.view', 'libelle' => 'Voir archives', 'groupe' => 'archives'],
            ['code' => 'archives.manage', 'libelle' => 'Gérer archives', 'groupe' => 'archives'],
            ['code' => 'archives.settings', 'libelle' => 'Paramètres rétention', 'groupe' => 'archives'],
            
            // Dashboard
            ['code' => 'dashboard.view', 'libelle' => 'Voir dashboard', 'groupe' => 'dashboard'],
            ['code' => 'dashboard.manager', 'libelle' => 'Dashboard manager', 'groupe' => 'dashboard'],
            ['code' => 'dashboard.rh', 'libelle' => 'Dashboard RH', 'groupe' => 'dashboard'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->insert([
                'code' => $perm['code'],
                'libelle' => $perm['libelle'],
                'groupe' => $perm['groupe'],
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Attribution des permissions par rôle
        $rolePermissions = [
            'admin' => [
                'employes.view', 'employes.create', 'employes.edit', 'employes.delete', 'employes.view_team',
                'conges.view', 'conges.create', 'conges.approve_manager', 'conges.approve_rh', 'conges.reject', 'conges.view_team',
                'paie.view', 'paie.generate', 'paie.edit_params',
                'documents.view', 'documents.upload', 'documents.delete', 'documents.archive',
                'evaluations.view', 'evaluations.create', 'evaluations.edit', 'evaluations.view_team',
                'formations.view', 'formations.manage', 'formations.assign',
                'admin.settings', 'admin.users', 'admin.roles',
                'audit.view', 'audit.export',
                'archives.view', 'archives.manage', 'archives.settings',
                'dashboard.view', 'dashboard.manager', 'dashboard.rh',
            ],
            'rh' => [
                'employes.view', 'employes.create', 'employes.edit', 'employes.view_team',
                'conges.view', 'conges.create', 'conges.approve_rh', 'conges.reject', 'conges.view_team',
                'paie.view', 'paie.generate', 'paie.edit_params',
                'documents.view', 'documents.upload', 'documents.archive',
                'evaluations.view', 'evaluations.create', 'evaluations.edit', 'evaluations.view_team',
                'formations.view', 'formations.manage', 'formations.assign',
                'audit.view',
                'archives.view', 'archives.manage',
                'dashboard.view', 'dashboard.rh',
            ],
            'manager' => [
                'employes.view_team',
                'conges.view', 'conges.create', 'conges.approve_manager', 'conges.view_team',
                'documents.view',
                'evaluations.view', 'evaluations.create', 'evaluations.view_team',
                'formations.view', 'formations.assign',
                'dashboard.view', 'dashboard.manager',
            ],
            'employe' => [
                'conges.create',
                'documents.view',
                'evaluations.view',
                'formations.view',
                'dashboard.view',
            ],
        ];

        foreach ($rolePermissions as $role => $permCodes) {
            foreach ($permCodes as $code) {
                $permId = DB::table('permissions')->where('code', $code)->value('id');
                if ($permId) {
                    DB::table('role_permissions')->insert([
                        'role' => $role,
                        'permission_id' => $permId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
