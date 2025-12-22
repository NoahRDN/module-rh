<?php

namespace App\Services;

use App\Models\ArchivedDocument;
use App\Models\ArchiveSetting;
use App\Models\DocumentEmploye;
use App\Models\Employe;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Service pour la gestion de l'archivage légal des documents.
 */
class ArchiveService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Archive un document
     */
    public function archiveDocument(
        string $documentType,
        int $originalId,
        string $originalTable,
        ?int $employeId,
        string $titre,
        string $fichierPath,
        \DateTimeInterface $dateDocument,
        User $archivedBy,
        ?string $description = null,
        ?array $metadata = null
    ): ArchivedDocument {
        // Récupère les paramètres de rétention
        $setting = ArchiveSetting::getByCode($this->getSettingCodeForType($documentType));
        
        // Calcule la date d'expiration
        $dateExpiration = $setting 
            ? $setting->calculateExpirationDate($dateDocument)
            : now()->addYears(5);

        // Calcule le hash pour l'intégrité
        $hash = null;
        $size = null;
        
        if (Storage::disk('public')->exists($fichierPath)) {
            $fullPath = Storage::disk('public')->path($fichierPath);
            $hash = hash_file('sha256', $fullPath);
            $size = filesize($fullPath);
            
            // Copie vers le disque d'archives
            $archivePath = "archives/{$documentType}/" . now()->format('Y/m') . "/" . basename($fichierPath);
            Storage::disk('local')->put($archivePath, Storage::disk('public')->get($fichierPath));
            $fichierPath = $archivePath;
        }

        // Crée l'entrée d'archive
        $archive = ArchivedDocument::create([
            'document_type' => $documentType,
            'original_id' => $originalId,
            'original_table' => $originalTable,
            'employe_id' => $employeId,
            'titre' => $titre,
            'description' => $description,
            'fichier_path' => $fichierPath,
            'fichier_hash' => $hash,
            'fichier_size' => $size,
            'date_document' => $dateDocument,
            'date_archivage' => now(),
            'date_expiration' => $dateExpiration,
            'archive_setting_id' => $setting?->id,
            'statut' => 'actif',
            'archived_by' => $archivedBy->id,
            'metadata' => $metadata,
        ]);

        // Log l'archivage
        $this->auditService->logArchive(
            $archivedBy,
            ArchivedDocument::class,
            $archive->id,
            "Archivage du document '{$titre}' (type: {$documentType})"
        );

        return $archive;
    }

    /**
     * Récupère le code de paramétrage pour un type de document
     */
    protected function getSettingCodeForType(string $documentType): string
    {
        $map = [
            'bulletin_paie' => 'BULLETIN_PAIE',
            'contrat' => 'CONTRAT_TRAVAIL',
            'formation' => 'DOCUMENT_FORMATION',
            'evaluation' => 'EVALUATION_PERFORMANCE',
            'justificatif' => 'JUSTIFICATIF_ABSENCE',
            'identite' => 'DOCUMENT_IDENTITE',
        ];

        return $map[$documentType] ?? 'BULLETIN_PAIE';
    }

    /**
     * Récupère les documents expirant bientôt
     */
    public function getDocumentsExpirantBientot(int $jours = 30)
    {
        return ArchivedDocument::expirantBientot($jours)
            ->with(['employe', 'archiveSetting'])
            ->get();
    }

    /**
     * Récupère les documents expirés
     */
    public function getDocumentsExpires()
    {
        return ArchivedDocument::expires()
            ->with(['employe', 'archiveSetting'])
            ->get();
    }

    /**
     * Traite les documents expirés selon leur paramétrage
     */
    public function processExpiredDocuments(User $processedBy): array
    {
        $results = [
            'archived' => 0,
            'deleted' => 0,
            'review' => 0,
            'errors' => [],
        ];

        $expired = $this->getDocumentsExpires();

        foreach ($expired as $doc) {
            try {
                $action = $doc->archiveSetting?->action_on_expiry ?? 'archive';

                switch ($action) {
                    case 'delete':
                        $doc->markAsDeleted();
                        $results['deleted']++;
                        break;
                    case 'review':
                        $doc->update(['statut' => 'en_revision']);
                        $results['review']++;
                        break;
                    default:
                        $doc->markAsExpired();
                        $results['archived']++;
                        break;
                }

                $this->auditService->log(
                    $processedBy,
                    'archive',
                    ArchivedDocument::class,
                    $doc->id,
                    "Traitement document expiré: {$action}"
                );
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'document_id' => $doc->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Vérifie l'intégrité des archives
     */
    public function verifyIntegrity(): array
    {
        $results = [
            'verified' => 0,
            'corrupted' => [],
            'missing' => [],
        ];

        $documents = ArchivedDocument::actifs()->get();

        foreach ($documents as $doc) {
            if (!Storage::disk('local')->exists($doc->fichier_path)) {
                $results['missing'][] = $doc->id;
                continue;
            }

            if ($doc->fichier_hash) {
                $currentHash = hash_file('sha256', Storage::disk('local')->path($doc->fichier_path));
                if ($currentHash !== $doc->fichier_hash) {
                    $results['corrupted'][] = $doc->id;
                    continue;
                }
            }

            $results['verified']++;
        }

        return $results;
    }

    /**
     * Récupère les statistiques d'archivage
     */
    public function getStatistics(): array
    {
        return [
            'total' => ArchivedDocument::count(),
            'actifs' => ArchivedDocument::actifs()->count(),
            'expires' => ArchivedDocument::expires()->count(),
            'expirant_30j' => ArchivedDocument::expirantBientot(30)->count(),
            'par_type' => ArchivedDocument::selectRaw('document_type, COUNT(*) as count')
                ->groupBy('document_type')
                ->pluck('count', 'document_type')
                ->toArray(),
            'taille_totale' => ArchivedDocument::sum('fichier_size'),
        ];
    }
}
