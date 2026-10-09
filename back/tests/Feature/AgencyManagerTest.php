<?php

namespace Tests\Feature;

use App\Models\Reservation;

class AgencyManagerTest extends ValletTestCase
{
    private function reservationOf(string $client, string $ref): Reservation
    {
        return Reservation::query()
            ->where('client', $client)
            ->whereHas('machine', fn ($query) => $query->where('ref', $ref))
            ->firstOrFail();
    }

    public function test_agent_cannot_cancel_a_reservation_entered_by_another_agency(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->deleteJson("/api/reservations/{$duclos->id}")
            ->assertForbidden()
            ->assertJsonPath('message', "Seul un responsable d'agence peut annuler une réservation saisie par une autre agence (saisie par Villeurbanne)");

        $this->assertNull($duclos->fresh()->cancelled_at);
    }

    public function test_agency_manager_cancels_a_reservation_of_another_agency(): void
    {
        $this->actingAsAccount('responsable.lyon-est@vallet.test');
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->getJson('/api/reservation-history')->assertJsonFragment(['id' => $duclos->id, 'cancelled_by' => 'Lyon Est']);
    }

    public function test_agent_still_cancels_reservations_of_its_own_agency(): void
    {
        $facades = $this->reservationOf('Facades Martin', 'NAC089');

        $this->deleteJson("/api/reservations/{$facades->id}")->assertNoContent();
    }

    public function test_agency_manager_profile(): void
    {
        $this->actingAsAccount('responsable.lyon-est@vallet.test');

        $this->getJson('/api/me')
            ->assertJsonPath('name', 'Sandrine Morin')
            ->assertJsonPath('role_label', "Responsable d'agence")
            ->assertJsonPath('agency', 'Lyon Est')
            ->assertJsonPath('can_book', true)
            ->assertJsonPath('can_cancel_other_agencies', true);

        $this->actingAsAccount('lyon-est@vallet.test');
        $this->getJson('/api/me')->assertJsonPath('can_cancel_other_agencies', false);
    }

    public function test_agency_manager_books_for_its_own_agency(): void
    {
        $this->actingAsAccount('responsable.lyon-est@vallet.test');

        $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP21',
            'client' => 'Facades Martin',
            'starts_at' => '2026-10-20',
            'ends_at' => '2026-10-21',
        ])
            ->assertCreated()
            ->assertJsonPath('entered_by', 'Lyon Est');
    }
}
