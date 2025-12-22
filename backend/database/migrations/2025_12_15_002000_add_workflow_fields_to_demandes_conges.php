<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            // Champs pour le workflow de validation manager
            if (!Schema::hasColumn('demandes_conges', 'manager_id')) {
                $table->unsignedBigInteger('manager_id')->nullable()->after('approuve_par');
            }
            if (!Schema::hasColumn('demandes_conges', 'date_validation_manager')) {
                $table->timestamp('date_validation_manager')->nullable()->after('manager_id');
            }
            if (!Schema::hasColumn('demandes_conges', 'commentaire_manager')) {
                $table->text('commentaire_manager')->nullable()->after('date_validation_manager');
            }
            
            // Champs pour le workflow de validation RH
            if (!Schema::hasColumn('demandes_conges', 'rh_id')) {
                $table->unsignedBigInteger('rh_id')->nullable()->after('commentaire_manager');
            }
            if (!Schema::hasColumn('demandes_conges', 'date_validation_rh')) {
                $table->timestamp('date_validation_rh')->nullable()->after('rh_id');
            }
            if (!Schema::hasColumn('demandes_conges', 'commentaire_rh')) {
                $table->text('commentaire_rh')->nullable()->after('date_validation_rh');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropColumn([
                'manager_id',
                'date_validation_manager',
                'commentaire_manager',
                'rh_id',
                'date_validation_rh',
                'commentaire_rh',
            ]);
        });
    }
};
