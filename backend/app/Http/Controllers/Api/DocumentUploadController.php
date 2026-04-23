<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\DocumentEmploye;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentUploadController extends Controller
{
    public function types()
    {
        return response()->json([
            'data' => config('documents.types', []),
        ]);
    }

    public function store(DocumentUploadRequest $request)
    {
        try {
            $documents = [];
            $groupUuid = (string) ($request->input('group_uuid') ?: Str::uuid());

            foreach ($this->extractFiles($request) as $file) {
                $path = $file->store("documents/employes/{$request->employe_id}", 'public');
                $documents[] = DocumentEmploye::create([
                    'employe_id' => $request->employe_id,
                    'group_uuid' => $groupUuid,
                    'type_document' => $request->type_document,
                    'fichier' => $path,
                    'date_expiration' => $request->date_expiration,
                ]);
            }

            $documentIds = collect($documents)->pluck('id')->filter()->values();
            $collection = DocumentEmploye::with('employe')
                ->whereIn('id', $documentIds)
                ->orderByDesc('id')
                ->get();
            $count = $collection->count();

            return response()->json([
                'message' => $count > 1 ? "{$count} documents uploadés" : 'Document uploadé',
                'data' => $collection->values(),
                'count' => $count,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur upload document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function extractFiles(DocumentUploadRequest $request): array
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
