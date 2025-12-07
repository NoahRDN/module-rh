<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paie;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PaiePdfController extends Controller
{
    public function telecharger($id)
    {
        try {
            $paie = Paie::with(['employe', 'details', 'primes'])->findOrFail($id);

            $pdf = Pdf::loadView('pdf.bulletin_paie', [
                'paie' => $paie,
                'employe' => $paie->employe,
                'details' => $paie->details,
                'primes' => $paie->primes,
            ]);

            return $pdf->download("bulletin_paie_{$paie->employe->id}_{$paie->mois}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF paie', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
