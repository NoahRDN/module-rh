<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('solde', 14, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('caisses')->insert([
            'nom' => 'Caisse principale',
            'description' => 'Caisse par défaut de l’entreprise',
            'solde' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
