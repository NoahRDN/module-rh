<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Models\DocumentEmploye;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste documents', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(DocumentRequest $request)
    {
        try {
            $doc = DocumentEmploye::create($request->validated());
            return response()->json($doc, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return DocumentEmploye::with('employe')->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(DocumentRequest $request, $id)
    {
        try {
            $doc = DocumentEmploye::findOrFail($id);
            $doc->update($request->validated());

            return response()->json($doc);
        } catch (\Throwable $e) {
            Log::error('Erreur update document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DocumentEmploye::findOrFail($id)->delete();
            return response()->json(['message' => 'Document supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression document', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
