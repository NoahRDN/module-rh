<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categorie_postes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Pré-remplir avec les catégories existantes (config/categories.php)
        $defaults = Config::get('categories.list', []);
        $now = now();
        DB::table('categorie_postes')->insert(
            collect($defaults)->map(fn ($item) => [
                'nom' => $item['code'],
                'code' => $item['code'],
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorie_postes');
    }
};
