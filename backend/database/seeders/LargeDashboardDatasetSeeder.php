<?php

namespace Database\Seeders;

use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Departement;
use App\Models\Employe;
use App\Models\HistoriquePoste;
use App\Models\Pointage;
use App\Models\Poste;
use App\Models\TypeConge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LargeDashboardDatasetSeeder extends Seeder
{
    private const COUNT = 1200;
    private const PREFIX = 'LOAD-20260501-';

    public function run(): void
    {
        mt_srand(20260501);

        DB::transaction(function () {
            $this->clearPreviousRun();

            $departementIds = Departement::query()->orderBy('id')->pluck('id')->values();
            $postes = Poste::query()->select(['id', 'departement_id'])->orderBy('id')->get()->values();
            $typeIds = TypeConge::query()->pluck('id', 'code');
            $today = now()->startOfDay();
            $passwordHash = Hash::make(env('DEFAULT_USER_PASSWORD', 'password'));

            for ($i = 1; $i <= self::COUNT; $i++) {
                $poste = $postes[($i - 1) % max(1, $postes->count())] ?? null;
                $departementId = $poste?->departement_id ?: ($departementIds[($i - 1) % max(1, $departementIds->count())] ?? null);
                $matricule = self::PREFIX . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
                $dateEmbauche = $today->copy()->subDays(mt_rand(20, 3650));

                if ($i % 12 === 0) {
                    $dateEmbauche = $today->copy()->subDays(mt_rand(1, 45));
                }

                $employe = Employe::create([
                    'matricule' => $matricule,
                    'nom' => $this->nom($i),
                    'prenom' => $this->prenom($i),
                    'email' => 'load.employee.' . $i . '@example.test',
                    'telephone' => '+261 34 ' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'adresse' => 'Lot Test ' . $i . ', Antananarivo',
                    'date_naissance' => $today->copy()->subYears(mt_rand(21, 60))->subDays(mt_rand(0, 360))->toDateString(),
                    'poste_id' => $poste?->id,
                    'departement_id' => $departementId,
                    'num_cnaps' => 'LOAD-CNAPS-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                    'photo' => null,
                    'date_embauche' => $dateEmbauche->toDateString(),
                ]);

                User::create([
                    'name' => trim($employe->nom . ' ' . $employe->prenom),
                    'email' => $employe->email,
                    'password' => $passwordHash,
                    'role' => $i % 40 === 0 ? 'manager' : 'employe',
                    'employe_id' => $employe->id,
                ]);

                HistoriquePoste::create([
                    'employe_id' => $employe->id,
                    'poste_id' => $employe->poste_id,
                    'departement_id' => $employe->departement_id,
                    'date_changement' => $employe->date_embauche,
                    'motif' => 'Jeu de test charge dashboard',
                ]);

                $this->createContract($employe, $i, $dateEmbauche, $today);
                $this->createLeaveScenarios($employe, $i, $today, $typeIds);
                $this->createPointageScenarios($employe, $i, $today);
            }
        });
    }

    private function clearPreviousRun(): void
    {
        User::query()
            ->where('email', 'like', 'load.employee.%@example.test')
            ->delete();

        Employe::query()
            ->where('matricule', 'like', self::PREFIX . '%')
            ->delete();
    }

    private function createContract(Employe $employe, int $index, Carbon $dateEmbauche, Carbon $today): void
    {
        $type = match (true) {
            $index % 15 === 0 => 'Stage',
            $index % 4 === 0 => 'CDD',
            default => 'CDI',
        };

        $dateFin = null;
        $statut = 'en_cours';

        if ($index % 18 === 0) {
            $dateFin = $today->copy()->subDays(mt_rand(5, 180));
            $statut = 'termine';
        } elseif ($type !== 'CDI' || $index % 9 === 0) {
            $dateFin = $today->copy()->addDays($index % 30 === 0 ? 0 : mt_rand(1, 180));
        }

        Contrat::create([
            'numero' => 'LOAD-CTR-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT),
            'employe_id' => $employe->id,
            'type_contrat' => $type,
            'date_debut' => $dateEmbauche->toDateString(),
            'date_fin' => $dateFin?->toDateString(),
            'periode_essai_debut' => $type === 'Stage' || $index % 3 === 0 ? $dateEmbauche->toDateString() : null,
            'periode_essai_fin' => $type === 'Stage' || $index % 3 === 0 ? $dateEmbauche->copy()->addMonth()->toDateString() : null,
            'renouvelable' => $type === 'CDD',
            'statut' => $statut,
            'salaire_base' => mt_rand(350000, 2500000),
        ]);
    }

    private function createLeaveScenarios(Employe $employe, int $index, Carbon $today, $typeIds): void
    {
        if ($index % 7 === 0) {
            $start = $today->copy()->subDays(mt_rand(1, 20));
            $this->createLeave($employe->id, $typeIds['PAYE'] ?? null, $start, $start->copy()->addDays(mt_rand(1, 4)), 'en_attente', 'Demande en attente test');
        }

        if ($index % 11 === 0) {
            $start = $today->copy()->addDays(mt_rand(0, 7));
            $this->createLeave($employe->id, $typeIds['PAYE'] ?? null, $start, $start->copy()->addDays(2), 'en_attente', 'Congé proche non validé');
        }

        if ($index % 13 === 0) {
            $start = $today->copy()->subDays(mt_rand(5, 90));
            $this->createLeave($employe->id, $typeIds['PAYE'] ?? null, $start, $start->copy()->addDays(mt_rand(2, 8)), 'rh_valide', 'Congé validé historique');
        }

        if ($index % 17 === 0) {
            for ($j = 0; $j < 4; $j++) {
                $start = $today->copy()->subDays(12 + ($j * 9));
                $this->createLeave($employe->id, $typeIds['MALADIE'] ?? null, $start, $start, 'rh_valide', 'Maladie répétée test');
            }
        }

        if ($index % 23 === 0) {
            for ($j = 0; $j < 3; $j++) {
                $start = $today->copy()->subDays(10 + ($j * 20));
                $this->createLeave($employe->id, $typeIds['DECES'] ?? null, $start, $start->copy()->addDay(), 'rh_valide', 'Exceptionnel répétée test');
            }
        }
    }

    private function createLeave(int $employeId, ?int $typeId, Carbon $start, Carbon $end, string $statut, string $motif): void
    {
        DemandeConge::create([
            'employe_id' => $employeId,
            'type_conge_id' => $typeId,
            'date_debut' => $start->toDateString(),
            'date_fin' => $end->toDateString(),
            'jours_demandes' => max(1, $start->diffInWeekdays($end) + 1),
            'statut' => $statut,
            'motif' => $motif,
            'created_at' => $start->copy()->subDays(mt_rand(2, 12)),
            'updated_at' => $start->copy()->subDays(mt_rand(0, 2)),
        ]);
    }

    private function createPointageScenarios(Employe $employe, int $index, Carbon $today): void
    {
        if ($index > 450 || $index % 18 === 0) {
            return;
        }

        for ($dayOffset = 1; $dayOffset <= 12; $dayOffset++) {
            $day = $today->copy()->subDays($dayOffset);
            if ($day->isWeekend()) {
                continue;
            }

            if ($index % 19 === 0 && $dayOffset % 4 === 0) {
                continue;
            }

            $lateMinutes = $index % 10 === 0 ? mt_rand(45, 150) : mt_rand(0, 20);
            $entry = $day->copy()->setTime(8, 0)->addMinutes($lateMinutes);
            $exit = $day->copy()->setTime(17, 0)->addMinutes(mt_rand(-20, 30));

            Pointage::insert([
                [
                    'employe_id' => $employe->id,
                    'type' => 'entree',
                    'pointe_a' => $entry,
                    'source' => 'seed',
                    'commentaire' => 'Charge dashboard',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'employe_id' => $employe->id,
                    'type' => 'pause_debut',
                    'pointe_a' => $day->copy()->setTime(12, 0),
                    'source' => 'seed',
                    'commentaire' => 'Charge dashboard',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'employe_id' => $employe->id,
                    'type' => 'pause_fin',
                    'pointe_a' => $day->copy()->setTime(13, 0),
                    'source' => 'seed',
                    'commentaire' => 'Charge dashboard',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'employe_id' => $employe->id,
                    'type' => 'sortie',
                    'pointe_a' => $exit,
                    'source' => 'seed',
                    'commentaire' => 'Charge dashboard',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    private function nom(int $index): string
    {
        $values = ['Rakoto', 'Rasoanaivo', 'Andriamampianina', 'Randrianarisoa', 'Raveloson', 'Rajaonarivelo', 'Rakotomalala', 'Razafindrakoto'];
        return $values[$index % count($values)] . ' Test' . $index;
    }

    private function prenom(int $index): string
    {
        $values = ['Mika', 'Lova', 'Hery', 'Tiana', 'Soa', 'Nirina', 'Fanja', 'Toky', 'Mamy', 'Hanitra'];
        return $values[$index % count($values)];
    }
}
