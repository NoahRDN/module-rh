<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devises', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('libelle', 100);
            $table->string('symbole', 16)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('devises')->insert([
            [
                'code' => 'MGA',
                'libelle' => 'Ariary malgache',
                'symbole' => 'MGA',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EUR',
                'libelle' => 'Euro',
                'symbole' => '€',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'libelle' => 'Dollar américain',
                'symbole' => '$',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('devises');
    }
};
