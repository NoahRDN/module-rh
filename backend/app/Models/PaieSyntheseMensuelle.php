<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaieSyntheseMensuelle extends Model
{
    protected $table = 'paie_synthese_mensuelle';

    protected $fillable = [
        'mois',
        'employe_id',
        'contrat_id',
        'paie_id',
        'statut',
        'statut_label',
        'contrat_numero',
        'contrat_debut',
        'contrat_fin',
        'employe_matricule',
        'employe_nom',
        'employe_prenom',
        'source_montants',
        'source_montants_label',
        'source_montants_description',
        'est_prevision',
        'salaire_base',
        'total_brut',
        'total_retenues',
        'net_a_payer',
        'retenue_cnaps',
        'retenue_ostie',
        'retenue_irsa',
        'cnaps_employeur',
        'ostie_employeur',
        'charges_patronales',
        'cotisations_a_reverser',
        'salaire_previsionnel',
        'net_a_payer_previsionnel',
        'brut_previsionnel',
        'paye_le',
        'demande_validation_le',
        'valide_le',
        'paiement_mouvement_id',
        'paiement_demande_le',
        'paiement_valide_le',
        'caisse_nom',
        'details_paie',
        'generated_at',
    ];

    protected $casts = [
        'contrat_debut' => 'date',
        'contrat_fin' => 'date',
        'est_prevision' => 'boolean',
        'salaire_base' => 'decimal:2',
        'total_brut' => 'decimal:2',
        'total_retenues' => 'decimal:2',
        'net_a_payer' => 'decimal:2',
        'retenue_cnaps' => 'decimal:2',
        'retenue_ostie' => 'decimal:2',
        'retenue_irsa' => 'decimal:2',
        'cnaps_employeur' => 'decimal:2',
        'ostie_employeur' => 'decimal:2',
        'charges_patronales' => 'decimal:2',
        'cotisations_a_reverser' => 'decimal:2',
        'salaire_previsionnel' => 'decimal:2',
        'net_a_payer_previsionnel' => 'decimal:2',
        'brut_previsionnel' => 'decimal:2',
        'paye_le' => 'date',
        'demande_validation_le' => 'datetime',
        'valide_le' => 'datetime',
        'paiement_demande_le' => 'datetime',
        'paiement_valide_le' => 'datetime',
        'details_paie' => 'array',
        'generated_at' => 'datetime',
    ];
}
