<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Contrat;
use App\Models\Paie;
use App\Models\DemandeConge;
use App\Models\Evaluation;
use App\Models\EntrepriseSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DocumentGeneratorService
{
    /**
     * Générer une attestation de travail
     */
    public function genererAttestationTravail(Employe $employe): array
    {
        $contrat = Contrat::where('employe_id', $employe->id)
            ->where('statut', 'actif')
            ->first();

        $data = $this->documentData([
            'employe' => $employe,
            'contrat' => $contrat,
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_attestation' => 'ATT-' . date('Y') . '-' . str_pad($employe->id, 5, '0', STR_PAD_LEFT),
            'anciennete' => $employe->date_embauche 
                ? Carbon::parse($employe->date_embauche)->diffInYears(now()) . ' ans' 
                : 'N/A',
        ]);

        $pdf = Pdf::loadView('documents.attestation_travail', $data);
        
        $filename = 'attestation_travail_' . $employe->matricule . '_' . date('Ymd') . '.pdf';
        $path = 'documents/attestations/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer un certificat de travail (fin de contrat)
     */
    public function genererCertificatTravail(Employe $employe, ?string $dateFin = null): array
    {
        $contrats = Contrat::where('employe_id', $employe->id)
            ->orderBy('date_debut')
            ->get();

        $premierContrat = $contrats->first();
        $dernierContrat = $contrats->last();

        $data = $this->documentData([
            'employe' => $employe,
            'date_debut' => $premierContrat?->date_debut?->format('d/m/Y') ?? 'N/A',
            'date_fin' => $dateFin ?? Carbon::now()->format('d/m/Y'),
            'postes_occupes' => $employe->historiquePostes()
                ->with('poste')
                ->orderBy('date_changement')
                ->get(),
            'dernier_poste' => $employe->poste?->nom ?? 'N/A',
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_certificat' => 'CERT-' . date('Y') . '-' . str_pad($employe->id, 5, '0', STR_PAD_LEFT),
        ]);

        $pdf = Pdf::loadView('documents.certificat_travail', $data);
        
        $filename = 'certificat_travail_' . $employe->matricule . '_' . date('Ymd') . '.pdf';
        $path = 'documents/certificats/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer une attestation de salaire
     */
    public function genererAttestationSalaire(Employe $employe, int $moisCount = 3): array
    {
        $paies = Paie::where('employe_id', $employe->id)
            ->orderBy('mois', 'desc')
            ->limit($moisCount)
            ->get();

        $contrat = Contrat::where('employe_id', $employe->id)
            ->where('statut', 'actif')
            ->first();

        $moyenneSalaire = $paies->avg('net_a_payer');

        $data = $this->documentData([
            'employe' => $employe,
            'contrat' => $contrat,
            'paies' => $paies,
            'moyenne_salaire' => number_format($moyenneSalaire, 0, ',', ' '),
            'periode' => $paies->isNotEmpty() 
                ? $paies->last()->mois . ' - ' . $paies->first()->mois 
                : 'N/A',
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_attestation' => 'SAL-' . date('Y') . '-' . str_pad($employe->id, 5, '0', STR_PAD_LEFT),
        ]);

        $pdf = Pdf::loadView('documents.attestation_salaire', $data);
        
        $filename = 'attestation_salaire_' . $employe->matricule . '_' . date('Ymd') . '.pdf';
        $path = 'documents/attestations/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer une attestation de congés
     */
    public function genererAttestationConge(DemandeConge $demande): array
    {
        $employe = $demande->employe;

        $data = $this->documentData([
            'employe' => $employe,
            'demande' => $demande,
            'type_conge' => $demande->typeConge?->nom ?? 'Congé',
            'date_debut' => $demande->date_debut->format('d/m/Y'),
            'date_fin' => $demande->date_fin->format('d/m/Y'),
            'jours' => $demande->jours_demandes,
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_attestation' => 'CON-' . date('Y') . '-' . str_pad($demande->id, 5, '0', STR_PAD_LEFT),
        ]);

        $pdf = Pdf::loadView('documents.attestation_conge', $data);
        
        $filename = 'attestation_conge_' . $employe->matricule . '_' . date('Ymd') . '.pdf';
        $path = 'documents/attestations/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer une lettre de recommandation
     */
    public function genererLettreRecommandation(Employe $employe, array $options = []): array
    {
        $evaluations = Evaluation::where('employe_id', $employe->id)
            ->orderBy('date_evaluation', 'desc')
            ->limit(3)
            ->get();

        $moyenneNote = $evaluations->avg('note_globale');
        $competences = $employe->competences()
            ->orderByPivot('niveau', 'desc')
            ->limit(5)
            ->get();

        $data = $this->documentData([
            'employe' => $employe,
            'evaluations' => $evaluations,
            'moyenne_note' => round($moyenneNote, 1),
            'competences_principales' => $competences,
            'duree_emploi' => $employe->date_embauche 
                ? Carbon::parse($employe->date_embauche)->diffForHumans(now(), true) 
                : 'N/A',
            'destinataire' => $options['destinataire'] ?? 'À qui de droit',
            'date_generation' => Carbon::now()->format('d/m/Y'),
        ]);

        $pdf = Pdf::loadView('documents.lettre_recommandation', $data);
        
        $filename = 'lettre_recommandation_' . $employe->matricule . '_' . date('Ymd') . '.pdf';
        $path = 'documents/recommandations/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer un contrat de travail
     */
    public function genererContrat(Contrat $contrat): array
    {
        $employe = $contrat->employe;

        $data = $this->documentData([
            'contrat' => $contrat,
            'employe' => $employe,
            'poste' => $employe->poste,
            'departement' => $employe->departement,
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'salaire_lettres' => $this->nombreEnLettres($contrat->salaire_base),
        ]);

        $pdf = Pdf::loadView('documents.contrat_travail', $data);
        
        $filename = 'contrat_' . $contrat->numero . '_' . date('Ymd') . '.pdf';
        $path = 'documents/contrats/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer un avenant au contrat
     */
    public function genererAvenant(Contrat $contrat, array $modifications): array
    {
        $employe = $contrat->employe;

        $data = $this->documentData([
            'contrat' => $contrat,
            'employe' => $employe,
            'modifications' => $modifications,
            'date_effet' => $modifications['date_effet'] ?? Carbon::now()->format('d/m/Y'),
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_avenant' => 'AV-' . $contrat->numero . '-' . date('Ymd'),
        ]);

        $pdf = Pdf::loadView('documents.avenant_contrat', $data);
        
        $filename = 'avenant_' . $contrat->numero . '_' . date('Ymd') . '.pdf';
        $path = 'documents/avenants/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Générer une attestation de stage/formation
     */
    public function genererAttestationFormation(Employe $employe, $formation): array
    {
        $inscription = $employe->formations()
            ->where('formation_id', $formation->id)
            ->first();

        $data = $this->documentData([
            'employe' => $employe,
            'formation' => $formation,
            'inscription' => $inscription,
            'date_debut' => $inscription?->pivot->date_debut,
            'date_fin' => $inscription?->pivot->date_fin,
            'note' => $inscription?->pivot->note,
            'certificat_obtenu' => $inscription?->pivot->certificat_obtenu,
            'date_generation' => Carbon::now()->format('d/m/Y'),
            'numero_attestation' => 'FOR-' . date('Y') . '-' . str_pad($formation->id, 5, '0', STR_PAD_LEFT),
        ]);

        $pdf = Pdf::loadView('documents.attestation_formation', $data);
        
        $filename = 'attestation_formation_' . $employe->matricule . '_' . $formation->id . '.pdf';
        $path = 'documents/formations/' . $filename;
        
        Storage::put('public/' . $path, $pdf->output());

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    /**
     * Liste des types de documents générables
     */
    public function getTypesDocuments(): array
    {
        return [
            [
                'code' => 'attestation_travail',
                'nom' => 'Attestation de travail',
                'description' => 'Confirme que l\'employé travaille dans l\'entreprise',
                'parametres' => [],
            ],
            [
                'code' => 'certificat_travail',
                'nom' => 'Certificat de travail',
                'description' => 'Document de fin de contrat attestant des fonctions exercées',
                'parametres' => ['date_fin'],
            ],
            [
                'code' => 'attestation_salaire',
                'nom' => 'Attestation de salaire',
                'description' => 'Confirme le niveau de rémunération de l\'employé',
                'parametres' => ['mois_count'],
            ],
            [
                'code' => 'attestation_conge',
                'nom' => 'Attestation de congé',
                'description' => 'Confirme une période de congé approuvée',
                'parametres' => ['demande_conge_id'],
            ],
            [
                'code' => 'lettre_recommandation',
                'nom' => 'Lettre de recommandation',
                'description' => 'Recommandation professionnelle pour l\'employé',
                'parametres' => ['destinataire'],
            ],
            [
                'code' => 'contrat_travail',
                'nom' => 'Contrat de travail',
                'description' => 'Contrat de travail complet',
                'parametres' => ['contrat_id'],
            ],
            [
                'code' => 'avenant_contrat',
                'nom' => 'Avenant au contrat',
                'description' => 'Modification du contrat de travail',
                'parametres' => ['contrat_id', 'modifications'],
            ],
            [
                'code' => 'attestation_formation',
                'nom' => 'Attestation de formation',
                'description' => 'Atteste de la participation à une formation',
                'parametres' => ['formation_id'],
            ],
        ];
    }

    /**
     * Générer un document par type
     */
    public function genererDocument(string $type, Employe $employe, array $params = []): array
    {
        return match ($type) {
            'attestation_travail' => $this->genererAttestationTravail($employe),
            'certificat_travail' => $this->genererCertificatTravail($employe, $params['date_fin'] ?? null),
            'attestation_salaire' => $this->genererAttestationSalaire($employe, $params['mois_count'] ?? 3),
            'lettre_recommandation' => $this->genererLettreRecommandation($employe, $params),
            default => ['success' => false, 'error' => 'Type de document inconnu'],
        };
    }

    /**
     * Convertir un nombre en lettres (simplifié)
     */
    private function nombreEnLettres(float $nombre): string
    {
        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $dizaines = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        
        $nombre = intval($nombre);
        
        if ($nombre == 0) return 'zéro';
        if ($nombre < 0) return 'moins ' . $this->nombreEnLettres(-$nombre);
        
        $result = '';
        
        if ($nombre >= 1000000) {
            $millions = intval($nombre / 1000000);
            $result .= ($millions == 1 ? 'un million ' : $this->nombreEnLettres($millions) . ' millions ');
            $nombre %= 1000000;
        }
        
        if ($nombre >= 1000) {
            $milliers = intval($nombre / 1000);
            $result .= ($milliers == 1 ? 'mille ' : $this->nombreEnLettres($milliers) . ' mille ');
            $nombre %= 1000;
        }
        
        if ($nombre >= 100) {
            $centaines = intval($nombre / 100);
            $result .= ($centaines == 1 ? 'cent ' : $unites[$centaines] . ' cent ');
            $nombre %= 100;
        }
        
        if ($nombre >= 10) {
            $diz = intval($nombre / 10);
            $unite = $nombre % 10;
            
            if ($diz == 1 && $unite > 0) {
                $onze = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
                $result .= $onze[$unite];
                $nombre = 0;
            } else {
                $result .= $dizaines[$diz] . ' ';
                $nombre = $unite;
            }
        }
        
        if ($nombre > 0) {
            $result .= $unites[$nombre];
        }
        
        return trim($result) . ' Ariary';
    }

    private function documentData(array $data): array
    {
        $entreprise = EntrepriseSetting::firstOrCreate(
            [],
            ['nom' => config('app.name', 'Module RH')]
        );

        return array_merge($data, [
            'entreprise' => $entreprise,
            'entreprise_logo_path' => $entreprise->resolvePdfLogoSrc(),
        ]);
    }
}
