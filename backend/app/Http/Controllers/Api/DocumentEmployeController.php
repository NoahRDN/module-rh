<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Models\DocumentEmploye;
use Illuminate\Http\Request;

class DocumentEmployeController extends Controller
{
    public function index(Request $request)
    {
        $emp = $request->query('employe_id');

        $query = DocumentEmploye::with('employe')->orderBy('id', 'desc');

        if ($emp) {
            $query->where('employe_id', $emp);
        }

        return response()->json($query->paginate(10));
    }

    public function store(DocumentRequest $request)
    {
        $doc = DocumentEmploye::create($request->validated());
        return response()->json($doc, 201);
    }

    public function show($id)
    {
        return DocumentEmploye::with('employe')->findOrFail($id);
    }

    public function update(DocumentRequest $request, $id)
    {
        $doc = DocumentEmploye::findOrFail($id);
        $doc->update($request->validated());

        return response()->json($doc);
    }

    public function destroy($id)
    {
        DocumentEmploye::findOrFail($id)->delete();
        return response()->json(['message' => 'Document supprimé']);
    }
}
