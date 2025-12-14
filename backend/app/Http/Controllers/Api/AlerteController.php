<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteSetting;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\SoldeConge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AlerteController extends Controller
{
    public function index()
    {
        try {
            $alerts = [];
            $now = Carbon::now();

            // Charger tous les paramètres d'alertes actifs
            $settings = AlerteSetting::where('actif', true)->get()->keyBy('code');

            // 1. Alertes de fin de contrat
            if ($settings->has('fin_contrat')) {
                $setting = $settings->get('fin_contrat');
                $seuilJours = $setting->seuil_jours ?? 30;
                
                $contratsExpirants = Contrat::with('employe')
                    ->whereNotNull('date_fin')
                    ->whereDate('date_fin', '<=', $now->copy()->addDays($seuilJours))
                    ->whereDate('date_fin', '>=', $now)
                    ->get();
                
                foreach ($contratsExpirants as $contrat) {
                    $joursRestants = $now->diffInDays($contrat->date_fin);
                    $alerts[] = [
                        'type' => 'fin_contrat',
                        'level' => $joursRestants <= 7 ? 'danger' : $setting->niveau,
                        'message' => "Contrat de {$contrat->employe?->nom} {$contrat->employe?->prenom} expire dans {$joursRestants} jours",
                        'contrat_id' => $contrat->id,
                        'employe_id' => $contrat->employe_id,
                        'date_fin' => $contrat->date_fin->format('Y-m-d'),
                    ];
                }
            }

            // 2. Congés non pris
            if ($settings->has('conges_non_pris')) {
                $setting = $settings->get('conges_non_pris');
                $seuilJours = $setting->seuil_nombre ?? 15;
                
                // Utiliser la vue solde_conges si elle existe
                try {
                    $soldesEleves = DB::table('view_solde_conges')
                        ->where('solde', '>=', $seuilJours)
                        ->get();
                    
                    foreach ($soldesEleves as $solde) {
                        $alerts[] = [
                            'type' => 'conges_non_pris',
                            'level' => $setting->niveau,
                            'message' => "Employé ID {$solde->employe_id} a {$solde->solde} jours de congés non pris",
                            'employe_id' => $solde->employe_id,
                            'solde' => $solde->solde,
                        ];
                    }
                } catch (\Exception $e) {
                    // Vue non disponible, ignorer cette alerte
                }
            }

            // 3. Demandes en attente depuis X heures
            if ($settings->has('conge_en_attente')) {
                $setting = $settings->get('conge_en_attente');
                $seuilHeures = ($setting->seuil_jours ?? 2) * 24;
                
                $pending = DemandeConge::with('employe')
                    ->whereNot('statut', 'rh_valide')
                    ->where('created_at', '<', $now->copy()->subHours($seuilHeures))
                    ->get();
                
                foreach ($pending as $d) {
                    $alerts[] = [
                        'type' => 'conge_en_attente',
                        'level' => $setting->niveau,
                        'message' => "Demande en attente >{$seuilHeures}h pour {$d->employe?->nom} {$d->employe?->prenom}",
                        'demande_id' => $d->id,
                    ];
                }
            }

            // 4. Congés qui commencent bientôt mais non validés
            if ($settings->has('conge_proche')) {
                $setting = $settings->get('conge_proche');
                $seuilJours = $setting->seuil_jours ?? 2;
                
                $startingSoon = DemandeConge::with('employe')
                    ->whereNot('statut', 'rh_valide')
                    ->whereDate('date_debut', '<=', $now->copy()->addDays($seuilJours)->toDateString())
                    ->whereDate('date_debut', '>=', $now->toDateString())
                    ->get();
                
                foreach ($startingSoon as $d) {
                    $alerts[] = [
                        'type' => 'conge_proche',
                        'level' => $setting->niveau,
                        'message' => "Le congé de {$d->employe?->nom} {$d->employe?->prenom} débute bientôt et n'est pas validé",
                        'demande_id' => $d->id,
                    ];
                }
            }

            // 5. Absences maladie fréquentes
            if ($settings->has('absences_maladie')) {
                $setting = $settings->get('absences_maladie');
                $seuilNombre = $setting->seuil_nombre ?? 4;
                $periodeJours = $setting->periode_jours ?? 60;
                
                $maladieCounts = DemandeConge::with(['employe', 'type'])
                    ->whereHas('type', function ($q) {
                        $q->where('libelle', 'like', '%malad%')
                          ->orWhere('code', 'like', '%malad%');
                    })
                    ->whereDate('date_debut', '>=', $now->copy()->subDays($periodeJours)->toDateString())
                    ->get()
                    ->groupBy('employe_id');
                
                foreach ($maladieCounts as $empId => $list) {
                    if ($list->count() >= $seuilNombre) {
                        $emp = $list->first()->employe;
                        $alerts[] = [
                            'type' => 'absences_maladie',
                            'level' => $setting->niveau,
                            'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés maladie sur {$periodeJours} jours",
                            'employe_id' => $empId,
                        ];
                    }
                }
            }

            // 6. Congés exceptionnels fréquents
            if ($settings->has('absences_exceptionnelles')) {
                $setting = $settings->get('absences_exceptionnelles');
                $seuilNombre = $setting->seuil_nombre ?? 3;
                $periodeJours = $setting->periode_jours ?? 90;
                
                $exceptionCounts = DemandeConge::with(['employe', 'type'])
                    ->whereHas('type', function ($q) {
                        $q->where('libelle', 'like', '%exception%')
                          ->orWhere('code', 'like', '%exception%');
                    })
                    ->whereDate('date_debut', '>=', $now->copy()->subDays($periodeJours)->toDateString())
                    ->get()
                    ->groupBy('employe_id');
                
                foreach ($exceptionCounts as $empId => $list) {
                    if ($list->count() >= $seuilNombre) {
                        $emp = $list->first()->employe;
                        $alerts[] = [
                            'type' => 'absences_exceptionnelles',
                            'level' => $setting->niveau,
                            'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés exceptionnels sur {$periodeJours} jours",
                            'employe_id' => $empId,
                        ];
                    }
                }
            }

            // Trier par niveau de criticité
            usort($alerts, function ($a, $b) {
                $priority = ['danger' => 0, 'warning' => 1, 'info' => 2];
                return ($priority[$a['level']] ?? 3) <=> ($priority[$b['level']] ?? 3);
            });

            return response()->json(['data' => $alerts]);
        } catch (\Throwable $e) {
            Log::error('Erreur generation alertes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
