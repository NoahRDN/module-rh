<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class EmployePdfController extends Controller
{
    public function telecharger($id)
    {
        try {
            $employe = Employe::with([
                'poste',
                'departement',
                'contrats' => fn($q) => $q->orderByDesc('date_debut'),
                'historiquePostes' => fn($q) => $q->orderByDesc('date_changement'),
            ])->findOrFail($id);

            $contratActuel = $employe->contrats->first();

            $pdf = Pdf::loadView('pdf.employe', [
                'employe' => $employe,
                'contratActuel' => $contratActuel,
            ]);

            $ref = $employe->matricule ? $employe->matricule : $employe->id;
            return $pdf->download("employe_{$ref}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
