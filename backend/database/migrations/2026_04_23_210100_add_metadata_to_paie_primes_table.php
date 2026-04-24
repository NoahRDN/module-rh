<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paie_primes', function (Blueprint $table) {
            $table->string('nature', 20)->default('prime')->after('libelle');
            $table->foreignId('remuneration_item_id')->nullable()->after('paie_id')->constrained('remuneration_items')->nullOnDelete();
            $table->string('source_code', 40)->nullable()->after('montant');
            $table->index('nature', 'idx_paie_primes_nature');
        });
    }

    public function down(): void
    {
        Schema::table('paie_primes', function (Blueprint $table) {
            $table->dropIndex('idx_paie_primes_nature');
            $table->dropConstrainedForeignId('remuneration_item_id');
            $table->dropColumn(['nature', 'source_code']);
        });
    }
};
