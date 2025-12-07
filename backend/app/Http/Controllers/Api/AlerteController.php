<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AlerteController extends Controller
{
    public function index()
    {
        try {
            $alerts = [];
            $now = Carbon::now();

            // Demandes en attente depuis >48h
            $pending = DemandeConge::with('employe')
                ->whereNot('statut', 'rh_valide')
                ->where('created_at', '<', $now->copy()->subHours(48))
                ->get();
            foreach ($pending as $d) {
                $alerts[] = [
                    'type' => 'conge_en_attente',
                    'level' => 'warning',
                    'message' => "Demande en attente >48h pour {$d->employe?->nom} {$d->employe?->prenom}",
                    'demande_id' => $d->id,
                ];
            }

            // Congés qui commencent sous 2 jours mais non validés RH
            $startingSoon = DemandeConge::with('employe')
                ->whereNot('statut', 'rh_valide')
                ->whereDate('date_debut', '<=', $now->copy()->addDays(2)->toDateString())
                ->get();
            foreach ($startingSoon as $d) {
                $alerts[] = [
                    'type' => 'conge_proche',
                    'level' => 'danger',
                    'message' => "Le congé de {$d->employe?->nom} {$d->employe?->prenom} débute bientôt et n'est pas validé",
                    'demande_id' => $d->id,
                ];
            }

            // Absences répétées (maladie) sur 60 jours : >4
            $maladieCounts = DemandeConge::with(['employe', 'type'])
                ->whereHas('type', function ($q) {
                    $q->where('nom', 'ilike', '%malad%');
                })
                ->whereDate('date_debut', '>=', $now->copy()->subDays(60)->toDateString())
                ->get()
                ->groupBy('employe_id');
            foreach ($maladieCounts as $empId => $list) {
                if ($list->count() > 4) {
                    $emp = $list->first()->employe;
                    $alerts[] = [
                        'type' => 'absences_maladie',
                        'level' => 'warning',
                        'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés maladie sur 60 jours",
                        'employe_id' => $empId,
                    ];
                }
            }

            // Congés exceptionnels fréquents (90 jours) >3
            $exceptionCounts = DemandeConge::with(['employe', 'type'])
                ->whereHas('type', function ($q) {
                    $q->where('nom', 'ilike', '%exception%');
                })
                ->whereDate('date_debut', '>=', $now->copy()->subDays(90)->toDateString())
                ->get()
                ->groupBy('employe_id');
            foreach ($exceptionCounts as $empId => $list) {
                if ($list->count() > 3) {
                    $emp = $list->first()->employe;
                    $alerts[] = [
                        'type' => 'absences_exceptionnelles',
                        'level' => 'warning',
                        'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés exceptionnels sur 90 jours",
                        'employe_id' => $empId,
                    ];
                }
            }

            return response()->json(['data' => $alerts]);
        } catch (\Throwable $e) {
            Log::error('Erreur generation alertes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
