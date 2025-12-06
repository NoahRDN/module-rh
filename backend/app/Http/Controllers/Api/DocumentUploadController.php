<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\DocumentEmploye;
use Illuminate\Support\Facades\Log;

class DocumentUploadController extends Controller
{
    public function store(DocumentUploadRequest $request)
    {
        try {
            $file = $request->file('fichier');
            $path = $file->store('documents', 'public');

            $doc = DocumentEmploye::create([
                'employe_id'      => $request->employe_id,
                'type_document'   => $request->type_document,
                'fichier'         => $path,
                'date_expiration' => $request->date_expiration,
            ]);

            return response()->json([
                'message' => 'Document uploadé',
                'data'    => $doc,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur upload document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
