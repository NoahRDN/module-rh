<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paie;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaiePdfController extends Controller
{
    public function telecharger($id)
    {
        try {
            $paie = Paie::with(['employe', 'employe.poste'])->findOrFail($id);
            $employe = $paie->employe;

            // Calculer les données nécessaires
            $anciennete = $this->calculerAnciennete($employe->date_embauche);
            $taux_journalier = $paie->salaire_base / 22; // 22 jours par mois standard
            $taux_horaire = $taux_journalier / 8; // 8 heures par jour

            // Calculer les détails des revenus (heures supplémentaires, primes, etc.)
            $details_revenus = $this->extraireDetailsRevelus($paie);

            // Calculer les détails IRSA
            list($details_irsa, $irsa_brut, $reduction_irsa) = $this->calculerDetailIRSA($paie->total_brut - $paie->retenue_cnaps - $paie->retenue_ostie);

            // Autres données
            $revenu_imposable = $paie->total_brut - $paie->retenue_cnaps - $paie->retenue_ostie;
            $enfants_charge = $employe->enfants_a_charge ?? 0;
            Log::info("Generating PDF for Paie ID: {$paie->id}");
            Log::info("Employe ID: {$employe->id}, mois: {$paie->mois} ,Annee: {$paie->annee}");
            $pdf = Pdf::loadView('pdf.bulletin_paie', [
                'paie' => $paie,
                'employe' => $employe,
                'anciennete' => $anciennete,
                'taux_journalier' => $taux_journalier,
                'taux_horaire' => $taux_horaire,
                'details_revenus' => $details_revenus,
                'details_irsa' => $details_irsa,
                'irsa_brut' => $irsa_brut,
                'reduction_irsa' => $reduction_irsa,
                'revenu_imposable' => $revenu_imposable,
                'enfants_charge' => $enfants_charge,
            ]);

            return $pdf->download("bulletin_paie_{$employe->id}_{$paie->mois}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Calculer l'ancienneté en format lisible (X ans Y mois)
     */
    private function calculerAnciennete($date_embauche)
    {
        $now = Carbon::now();
        $years = $now->diffInYears($date_embauche);
        $months = $now->copy()->subYears($years)->diffInMonths($date_embauche);

        return "{$years} ans {$months} mois";
    }

    /**
     * Extraire les détails des revenus additionnels (primes, heures supplémentaires, etc.)
     */
    private function extraireDetailsRevelus($paie)
    {
        $details = [];

        // Ajouter les heures supplémentaires si présentes
        if ($paie->montant_hs > 0) {
            $details[] = [
                'libelle' => 'Heures supplémentaires',
                'nombre' => $paie->heures_supplementaires . ' h',
                'taux' => '—',
                'montant' => $paie->montant_hs,
            ];
        }

        // Ajouter les primes
        if ($paie->prime_transport > 0) {
            $details[] = [
                'libelle' => 'Prime de transport',
                'nombre' => '—',
                'taux' => '—',
                'montant' => $paie->prime_transport,
            ];
        }

        if ($paie->prime_presence > 0) {
            $details[] = [
                'libelle' => 'Prime de présence',
                'nombre' => '—',
                'taux' => '—',
                'montant' => $paie->prime_presence,
            ];
        }

        if ($paie->autres_primes > 0) {
            $details[] = [
                'libelle' => 'Autres primes',
                'nombre' => '—',
                'taux' => '—',
                'montant' => $paie->autres_primes,
            ];
        }

        return $details;
    }

    /**
     * Calculer le détail de l'IRSA selon le barème légal de Madagascar
     * Retourne [details_irsa, irsa_brut, reduction_irsa]
     */
    private function calculerDetailIRSA($salaire_brut)
    {
        $details_irsa = [];
        $irsa_total = 0;

        // Barèmes IRSA Madagascar 2024-2025
        $baremes = [
            ['min' => 0, 'max' => 350000, 'taux' => 0, 'libelle' => 'Jusqu\'à 350 000'],
            ['min' => 350001, 'max' => 400000, 'taux' => 5, 'libelle' => 'De 350 001 à 400 000'],
            ['min' => 400001, 'max' => 500000, 'taux' => 10, 'libelle' => 'De 400 001 à 500 000'],
            ['min' => 500001, 'max' => 600000, 'taux' => 15, 'libelle' => 'De 500 001 à 600 000'],
            ['min' => 600001, 'max' => 4000000, 'taux' => 20, 'libelle' => 'De 600 001 à 4 000 000'],
            ['min' => 4000001, 'max' => PHP_INT_MAX, 'taux' => 25, 'libelle' => 'Plus de 4 000 000'],
        ];

        foreach ($baremes as $bareme) {
            if ($salaire_brut >= $bareme['min'] && $salaire_brut <= $bareme['max']) {
                $base = min($salaire_brut, $bareme['max']) - max(0, $bareme['min'] - 1);
                $montant = $base * ($bareme['taux'] / 100);
                $irsa_total += $montant;

                $details_irsa[] = [
                    'libelle' => $bareme['libelle'],
                    'base' => $base,
                    'taux' => $bareme['taux'],
                    'montant' => $montant,
                ];
                break;
            }
        }

        // Réduction IRSA (exemple: 1000 par enfant)
        $reduction_irsa = 0; // À adapter selon vos règles métier
        $irsa_net = max(0, $irsa_total - $reduction_irsa);

        return [
            $details_irsa,
            $irsa_total,
            $reduction_irsa,
        ];
    }
}

