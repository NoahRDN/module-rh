<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\EntrepriseSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ContratPdfController extends Controller
{
    public function telecharger($id)
    {
        try {
            $contrat = Contrat::with(['employe.poste', 'employe.departement'])->findOrFail($id);
            $entreprise = EntrepriseSetting::firstOrCreate(
                [],
                ['nom' => config('app.name', 'Module RH')]
            );
            $entrepriseLogoPath = $entreprise->resolvePdfLogoSrc();

            $pdf = Pdf::loadView('pdf.contrat', [
                'contrat' => $contrat,
                'entreprise_nom' => $entreprise->nom ?: config('app.name', 'Module RH'),
                'entreprise_logo_path' => $entrepriseLogoPath,
            ]);

            return $pdf->download("contrat_{$contrat->id}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
