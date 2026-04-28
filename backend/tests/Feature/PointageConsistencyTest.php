<?php

namespace Tests\Feature;

use App\Models\Employe;
use App\Models\Pointage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class PointageConsistencyTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    private function createEmploye(): Employe
    {
        return Employe::create([
            'matricule' => 'EMP-' . uniqid(),
            'nom' => 'Test',
            'prenom' => 'Employe',
            'email' => uniqid('emp_') . '@example.test',
            'telephone' => '0320000000',
            'adresse' => 'Test address',
            'date_naissance' => '1990-01-01',
            'date_embauche' => '2025-01-01',
        ]);
    }

    private function buildPayload(Employe $employe, string $type, string $timestamp): array
    {
        return [
            'employe_id' => $employe->id,
            'type' => $type,
            'pointe_a' => $timestamp,
            'source' => 'test',
            'commentaire' => null,
        ];
    }

    public function test_double_entree_is_blocked(): void
    {
        $employe = $this->createEmploye();

        Pointage::create([
            'employe_id' => $employe->id,
            'type' => 'entree',
            'pointe_a' => '2026-04-28 08:00:00',
            'source' => 'test',
            'commentaire' => null,
        ]);

        $response = $this->postJson('/api/v1/pointages', $this->buildPayload($employe, 'entree', '2026-04-28T09:00:00Z'));

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Double pointage detecte: une entree est deja ouverte sans sortie.'
            ]);
    }

    public function test_sortie_sans_entree_is_blocked(): void
    {
        $employe = $this->createEmploye();

        $response = $this->postJson('/api/v1/pointages', $this->buildPayload($employe, 'sortie', '2026-04-28T09:00:00Z'));

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Sortie sans entree: aucune entree ouverte a cette heure.'
            ]);
    }

    public function test_chevauchement_same_slot_is_blocked(): void
    {
        $employe = $this->createEmploye();

        Pointage::create([
            'employe_id' => $employe->id,
            'type' => 'entree',
            'pointe_a' => '2026-04-28 10:00:00',
            'source' => 'test',
            'commentaire' => null,
        ]);

        $response = $this->postJson('/api/v1/pointages', $this->buildPayload($employe, 'pause_debut', '2026-04-28T10:00:00Z'));

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Chevauchement: un pointage existe deja sur le meme creneau.'
            ]);
    }
}
