<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paie_parametres', function (Blueprint $table) {
            $table->decimal('cnaps_plafond', 12, 2)->default(568000)->after('cnaps');
            $table->decimal('cnaps_taux_employe', 5, 2)->default(1.0)->after('cnaps_plafond');
            $table->decimal('cnaps_taux_employeur', 5, 2)->default(1.0)->after('cnaps_taux_employe');
            $table->decimal('ostie_taux_employe', 5, 2)->default(1.0)->after('ostie');
            $table->decimal('ostie_taux_employeur', 5, 2)->default(1.0)->after('ostie_taux_employe');
        });
    }

    public function down(): void
    {
        Schema::table('paie_parametres', function (Blueprint $table) {
            $table->dropColumn([
                'cnaps_plafond',
                'cnaps_taux_employe',
                'cnaps_taux_employeur',
                'ostie_taux_employe',
                'ostie_taux_employeur',
            ]);
        });
    }
};
