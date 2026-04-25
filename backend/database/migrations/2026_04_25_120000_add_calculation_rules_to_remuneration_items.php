<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remuneration_items', function (Blueprint $table) {
            $table->string('calculation_type', 16)->default('fixe')->after('montant');
            $table->boolean('prorata')->default(false)->after('calculation_type');
            $table->boolean('depends_on_presence')->default(false)->after('prorata');
        });
    }

    public function down(): void
    {
        Schema::table('remuneration_items', function (Blueprint $table) {
            $table->dropColumn(['depends_on_presence', 'prorata', 'calculation_type']);
        });
    }
};
