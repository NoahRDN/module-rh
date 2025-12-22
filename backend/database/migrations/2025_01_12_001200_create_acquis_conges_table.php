<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acquis_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            $table->foreignId('type_conge_id')->constrained('types_conges')->cascadeOnDelete();
            $table->decimal('jours_acquis', 8, 2);
            $table->decimal('jours_utilises', 8, 2)->default(0);
            $table->date('acquis_le');
            $table->date('expire_le');
            $table->timestamps();
            $table->index(['employe_id', 'type_conge_id', 'expire_le'], 'idx_acquis_conges_fifo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquis_conges');
    }
};
