<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les champs pour le workflow de validation Manager → RH.
     */
    public function up(): void
    {
        // Mise à jour de la table demandes_conges
        if (Schema::hasTable('demandes_conges')) {
            Schema::table('demandes_conges', function (Blueprint $table) {
                // Manager qui a validé
                $table->foreignId('manager_id')
                    ->nullable()
                    ->after('approuve_par')
                    ->constrained('users')
                    ->nullOnDelete();
                
                // Date de validation manager
                $table->timestamp('date_validation_manager')->nullable()->after('manager_id');
                
                // Commentaire du manager
                $table->text('commentaire_manager')->nullable()->after('date_validation_manager');
                
                // Date de validation RH
                $table->timestamp('date_validation_rh')->nullable()->after('commentaire_manager');
                
                // Commentaire RH
                $table->text('commentaire_rh')->nullable()->after('date_validation_rh');
            });
        }

        // Mise à jour de la table demandes (demandes RH génériques)
        if (Schema::hasTable('demandes')) {
            Schema::table('demandes', function (Blueprint $table) {
                // Manager qui a validé
                $table->foreignId('manager_id')
                    ->nullable()
                    ->after('traite_par')
                    ->constrained('users')
                    ->nullOnDelete();
                
                // Date de validation manager
                $table->timestamp('date_validation_manager')->nullable()->after('manager_id');
                
                // Commentaire du manager
                $table->text('commentaire_manager')->nullable()->after('date_validation_manager');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('demandes_conges')) {
            Schema::table('demandes_conges', function (Blueprint $table) {
                if (Schema::hasColumn('demandes_conges', 'manager_id')) {
                    $table->dropForeign(['manager_id']);
                }
                $table->dropColumn([
                    'manager_id',
                    'date_validation_manager',
                    'commentaire_manager',
                    'date_validation_rh',
                    'commentaire_rh',
                ]);
            });
        }

        if (Schema::hasTable('demandes')) {
            Schema::table('demandes', function (Blueprint $table) {
                if (Schema::hasColumn('demandes', 'manager_id')) {
                    $table->dropForeign(['manager_id']);
                }
                $table->dropColumn([
                    'manager_id',
                    'date_validation_manager',
                    'commentaire_manager',
                ]);
            });
        }
    }
};
