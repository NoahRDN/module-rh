<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivedDocument;
use App\Models\ArchiveSetting;
use App\Services\ArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller pour la gestion des archives.
 */
class ArchiveController extends Controller
{
    public function __construct(private ArchiveService $archiveService)
    {
    }

    // ========================================
    // PARAMÈTRES D'ARCHIVAGE
    // ========================================

    /**
     * Liste des paramètres d'archivage
     */
    public function settings()
    {
        try {
            $settings = ArchiveSetting::orderBy('code')->get();
            return response()->json(['data' => $settings]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération archive settings', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'un paramètre
     */
    public function showSetting($id)
    {
        try {
            $setting = ArchiveSetting::findOrFail($id);
            return response()->json($setting);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération archive setting', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mise à jour d'un paramètre
     */
    public function updateSetting(Request $request, $id)
    {
        try {
            $setting = ArchiveSetting::findOrFail($id);

            $validated = $request->validate([
                'libelle' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'retention_years' => 'sometimes|integer|min:0|max:100',
                'retention_months' => 'sometimes|integer|min:0|max:11',
                'action_on_expiry' => 'sometimes|in:archive,delete,review',
                'notify_before_days' => 'sometimes|integer|min:0|max:365',
                'legal_reference' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $setting->update($validated);

            return response()->json([
                'message' => 'Paramètre mis à jour',
                'setting' => $setting,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour archive setting', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    // ========================================
    // DOCUMENTS ARCHIVÉS
    // ========================================

    /**
     * Liste des documents archivés
     */
    public function index(Request $request)
    {
        try {
            $query = ArchivedDocument::with(['employe', 'archiveSetting', 'archivedByUser'])
                ->orderBy('created_at', 'desc');

            if ($request->query('employe_id')) {
                $query->where('employe_id', $request->query('employe_id'));
            }

            if ($request->query('document_type')) {
                $query->where('document_type', $request->query('document_type'));
            }

            if ($request->query('statut')) {
                $query->where('statut', $request->query('statut'));
            }

            if ($request->query('expiring')) {
                $query->expirantBientot((int) $request->query('expiring'));
            }

            return response()->json($query->paginate($request->query('per_page', 20)));
        } catch (\Throwable $e) {
            Log::error('Erreur récupération archives', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'un document archivé
     */
    public function show($id)
    {
        try {
            $doc = ArchivedDocument::with(['employe', 'archiveSetting', 'archivedByUser'])
                ->findOrFail($id);

            return response()->json([
                'document' => $doc,
                'is_expired' => $doc->isExpired(),
                'days_until_expiration' => $doc->days_until_expiration,
                'integrity_verified' => $doc->verifyIntegrity(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération archive', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Archiver un document manuellement
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'document_type' => 'required|string|max:100',
                'original_id' => 'nullable|integer',
                'original_table' => 'nullable|string|max:100',
                'employe_id' => 'nullable|integer|exists:employes,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'fichier_path' => 'required|string',
                'date_document' => 'required|date',
                'metadata' => 'nullable|array',
            ]);

            $archive = $this->archiveService->archiveDocument(
                $validated['document_type'],
                $validated['original_id'] ?? 0,
                $validated['original_table'] ?? '',
                $validated['employe_id'] ?? null,
                $validated['titre'],
                $validated['fichier_path'],
                \Carbon\Carbon::parse($validated['date_document']),
                $request->user(),
                $validated['description'] ?? null,
                $validated['metadata'] ?? null
            );

            return response()->json([
                'message' => 'Document archivé avec succès',
                'archive' => $archive,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur archivage document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Télécharger un document archivé
     */
    public function download($id)
    {
        try {
            $doc = ArchivedDocument::findOrFail($id);

            if (!Storage::disk('local')->exists($doc->fichier_path)) {
                return response()->json(['message' => 'Fichier non trouvé'], 404);
            }

            return Storage::disk('local')->download(
                $doc->fichier_path,
                $doc->titre . '.' . pathinfo($doc->fichier_path, PATHINFO_EXTENSION)
            );
        } catch (\Throwable $e) {
            Log::error('Erreur téléchargement archive', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ajouter une note à un document archivé
     */
    public function addNote(Request $request, $id)
    {
        try {
            $doc = ArchivedDocument::findOrFail($id);

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            $doc->update(['notes' => $validated['notes']]);

            return response()->json([
                'message' => 'Note ajoutée',
                'document' => $doc,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout note archive', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    // ========================================
    // STATISTIQUES ET MAINTENANCE
    // ========================================

    /**
     * Statistiques d'archivage
     */
    public function statistiques()
    {
        try {
            $stats = $this->archiveService->getStatistics();
            return response()->json($stats);
        } catch (\Throwable $e) {
            Log::error('Erreur stats archives', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Documents expirant bientôt
     */
    public function expiringDocuments(Request $request)
    {
        try {
            $jours = $request->query('days', 30);
            $docs = $this->archiveService->getDocumentsExpirantBientot($jours);

            return response()->json(['data' => $docs]);
        } catch (\Throwable $e) {
            Log::error('Erreur docs expirants', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Documents expirés
     */
    public function expiredDocuments()
    {
        try {
            $docs = $this->archiveService->getDocumentsExpires();
            return response()->json(['data' => $docs]);
        } catch (\Throwable $e) {
            Log::error('Erreur docs expirés', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Traiter les documents expirés
     */
    public function processExpired(Request $request)
    {
        try {
            $results = $this->archiveService->processExpiredDocuments($request->user());

            return response()->json([
                'message' => 'Traitement terminé',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement docs expirés', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Vérifier l'intégrité des archives
     */
    public function verifyIntegrity()
    {
        try {
            $results = $this->archiveService->verifyIntegrity();

            return response()->json([
                'message' => 'Vérification terminée',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur vérification intégrité', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Types de documents disponibles
     */
    public function types()
    {
        $types = ArchivedDocument::selectRaw('DISTINCT document_type')
            ->pluck('document_type');

        return response()->json($types);
    }
}
