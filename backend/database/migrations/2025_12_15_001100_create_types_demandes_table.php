<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_demandes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('categorie', ['attestation', 'conge', 'remboursement', 'autre'])->default('autre');
            $table->boolean('necessite_validation')->default(true);
            $table->boolean('necessite_document')->default(false);
            $table->boolean('actif')->default(true);
            $table->integer('delai_traitement')->default(3); // Délai en jours ouvrés
            $table->timestamps();
        });

        // Types de demandes par défaut
        \DB::table('types_demandes')->insert([
            // Attestations
            [
                'code' => 'ATT_TRAVAIL',
                'libelle' => 'Attestation de travail',
                'description' => 'Document attestant que l\'employé travaille dans l\'entreprise',
                'categorie' => 'attestation',
                'necessite_validation' => true,
                'necessite_document' => false,
                'actif' => true,
                'delai_traitement' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ATT_SALAIRE',
                'libelle' => 'Attestation de salaire',
                'description' => 'Document attestant le salaire de l\'employé',
                'categorie' => 'attestation',
                'necessite_validation' => true,
                'necessite_document' => false,
                'actif' => true,
                'delai_traitement' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CERT_TRAVAIL',
                'libelle' => 'Certificat de travail',
                'description' => 'Certificat de travail pour employé en fin de contrat',
                'categorie' => 'attestation',
                'necessite_validation' => true,
                'necessite_document' => false,
                'actif' => true,
                'delai_traitement' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ATT_EMPLOI',
                'libelle' => 'Attestation d\'emploi',
                'description' => 'Attestation confirmant l\'emploi pour démarches administratives',
                'categorie' => 'attestation',
                'necessite_validation' => true,
                'necessite_document' => false,
                'actif' => true,
                'delai_traitement' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Remboursements
            [
                'code' => 'REMB_TRANSPORT',
                'libelle' => 'Remboursement frais de transport',
                'description' => 'Demande de remboursement des frais de déplacement',
                'categorie' => 'remboursement',
                'necessite_validation' => true,
                'necessite_document' => true,
                'actif' => true,
                'delai_traitement' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'REMB_MISSION',
                'libelle' => 'Remboursement frais de mission',
                'description' => 'Demande de remboursement des frais de mission',
                'categorie' => 'remboursement',
                'necessite_validation' => true,
                'necessite_document' => true,
                'actif' => true,
                'delai_traitement' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'REMB_MEDICAL',
                'libelle' => 'Remboursement frais médicaux',
                'description' => 'Demande de remboursement des frais médicaux',
                'categorie' => 'remboursement',
                'necessite_validation' => true,
                'necessite_document' => true,
                'actif' => true,
                'delai_traitement' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Autres
            [
                'code' => 'MODIF_INFO',
                'libelle' => 'Modification d\'informations personnelles',
                'description' => 'Demande de mise à jour des informations personnelles',
                'categorie' => 'autre',
                'necessite_validation' => true,
                'necessite_document' => true,
                'actif' => true,
                'delai_traitement' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('types_demandes');
    }
};
