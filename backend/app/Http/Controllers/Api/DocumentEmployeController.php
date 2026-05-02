<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Requests\DocumentUpdateRequest;
use App\Models\DocumentEmploye;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DocumentEmployeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');

            $query = DocumentEmploye::with('employe')->orderBy('id', 'desc');

            if ($emp) {
                $query->where('employe_id', $emp);
            }
            if ($request->filled('matricule')) {
                $term = $request->query('matricule');
                $query->whereHas('employe', fn ($q) => $q->where('matricule', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('nom')) {
                $term = $request->query('nom');
                $query->whereHas('employe', function ($q) use ($term) {
                    $q->where('nom', 'ILIKE', "%{$term}%")
                      ->orWhere('prenom', 'ILIKE', "%{$term}%");
                });
            }
            if ($request->filled('type')) {
                $query->where('type_document', 'ILIKE', '%' . $request->query('type') . '%');
            }
            if ($request->filled('fichier')) {
                $query->where('fichier', 'ILIKE', '%' . $request->query('fichier') . '%');
            }
            if ($request->filled('date_expiration')) {
                $query->whereDate('date_expiration', $request->query('date_expiration'));
            }

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste documents', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(DocumentRequest $request)
    {
        try {
            $payload = $request->validated();
            $payload['group_uuid'] = $payload['group_uuid'] ?? (string) Str::uuid();

            $doc = DocumentEmploye::create($payload);
            return response()->json($doc->load('employe'), 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            return $this->resolveAccessibleDocument($request, $id);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document introuvable'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur show document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(DocumentUpdateRequest $request, $id)
    {
        try {
            $doc = DocumentEmploye::findOrFail($id);
            $payload = $request->validated();
            $files = $this->extractFiles($request);
            $createdDocuments = [];
            $groupUuid = $payload['group_uuid'] ?? $doc->group_uuid ?? (string) Str::uuid();

            $payload['group_uuid'] = $groupUuid;

            if (count($files) > 0) {
                $replacementFile = array_shift($files);

                $payload['fichier'] = $replacementFile->store(
                    "documents/employes/{$payload['employe_id']}",
                    'public'
                );

                if ($doc->fichier && Storage::disk('public')->exists($doc->fichier)) {
                    Storage::disk('public')->delete($doc->fichier);
                }
            }

            $doc->update($payload);

            foreach ($files as $file) {
                $createdDocuments[] = DocumentEmploye::create([
                    'employe_id' => $payload['employe_id'],
                    'group_uuid' => $groupUuid,
                    'type_document' => $payload['type_document'],
                    'fichier' => $file->store("documents/employes/{$payload['employe_id']}", 'public'),
                    'date_expiration' => $payload['date_expiration'] ?? null,
                ]);
            }

            return response()->json([
                'message' => count($createdDocuments) > 0
                    ? 'Document mis à jour et fichiers supplémentaires ajoutés'
                    : 'Document mis à jour',
                'data' => $doc->fresh('employe'),
                'created' => DocumentEmploye::with('employe')
                    ->whereIn('id', collect($createdDocuments)->pluck('id')->filter()->values())
                    ->orderByDesc('id')
                    ->get()
                    ->values(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document introuvable'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur update document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $doc = DocumentEmploye::findOrFail($id);

            if ($doc->fichier && Storage::disk('public')->exists($doc->fichier)) {
                Storage::disk('public')->delete($doc->fichier);
            }

            $doc->delete();

            return response()->json(['message' => 'Document supprimé']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document introuvable'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function download(Request $request, $id)
    {
        try {
            $doc = $this->resolveAccessibleDocument($request, $id);

            if (!$doc->fichier || !Storage::disk('public')->exists($doc->fichier)) {
                return response()->json(['message' => 'Fichier introuvable'], 404);
            }

            return Storage::disk('public')->download($doc->fichier, $doc->nom_fichier);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document introuvable'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur téléchargement document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function resolveAccessibleDocument(Request $request, $id): DocumentEmploye
    {
        $doc = DocumentEmploye::with('employe')->findOrFail($id);
        $user = $request->user();

        if (!$user) {
            abort(401, 'Authentification requise');
        }

        if ($user->isAdmin() || $user->isRH()) {
            return $doc;
        }

        if ($user->isManager() && $doc->employe && $user->isManagerOf($doc->employe)) {
            return $doc;
        }

        if ((int) $user->employe_id === (int) $doc->employe_id) {
            return $doc;
        }

        abort(403, 'Accès non autorisé à ce document');
    }

    protected function extractFiles(DocumentUpdateRequest $request): array
    {
        $files = [];

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            if ($file instanceof UploadedFile) {
                $files[] = $file;
            }
        }

        if ($request->hasFile('fichiers')) {
            foreach ((array) $request->file('fichiers') as $file) {
                if ($file instanceof UploadedFile) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
