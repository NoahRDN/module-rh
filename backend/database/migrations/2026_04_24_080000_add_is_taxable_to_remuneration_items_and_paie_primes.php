<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remuneration_items', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('montant');
        });

        Schema::table('paie_primes', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('nature');
        });
    }

    public function down(): void
    {
        Schema::table('paie_primes', function (Blueprint $table) {
            $table->dropColumn('is_taxable');
        });

        Schema::table('remuneration_items', function (Blueprint $table) {
            $table->dropColumn('is_taxable');
        });
    }
};
