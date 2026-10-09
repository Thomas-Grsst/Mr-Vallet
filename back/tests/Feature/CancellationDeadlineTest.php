<?php

namespace Tests\Feature;

use App\Models\Reservation;

class CancellationDeadlineTest extends ValletTestCase
{
    private function reservationOf(string $client, string $ref): Reservation
    {
        return Reservation::query()
            ->where('client', $client)
            ->whereHas('machine', fn ($query) => $query->where('ref', $ref))
            ->firstOrFail();
    }

    public function test_reservation_starting_in_more_than_48_hours_can_be_cancelled(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->getJson('/api/reservations')->assertJsonFragment([
            'id' => $duclos->id,
            'cancellable_until' => '2026-10-14',
            'cancellable' => true,
        ]);

        $this->actingAsAccount('responsable.villeurbanne@vallet.test');
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();
    }

    public function test_reservation_starting_exactly_48_hours_later_can_still_be_cancelled(): void
    {
        $btp = $this->reservationOf('BTP Rhone', 'NAC112');

        $this->actingAsAccount('responsable.lyon-est@vallet.test');
        $this->deleteJson("/api/reservations/{$btp->id}")->assertNoContent();
    }

    public function test_reservation_starting_within_48_hours_must_be_kept(): void
    {
        $ferreira = $this->reservationOf('Artisan Ferreira', 'MINI12');

        $this->getJson('/api/reservations')->assertJsonFragment(['id' => $ferreira->id, 'cancellable' => false]);

        $this->deleteJson("/api/reservations/{$ferreira->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Annulation impossible : la location commence le 13/10/2026, il fallait annuler au plus tard le 11/10/2026. Le client doit garder la réservation.');

        $this->assertNull($ferreira->fresh()->cancelled_at);
    }

    public function test_ongoing_reservation_cannot_be_cancelled(): void
    {
        $pereira = $this->reservationOf('M. Pereira (particulier)', 'COMP21');

        $this->deleteJson("/api/reservations/{$pereira->id}")->assertUnprocessable();
    }

    public function test_director_cancels_within_48_hours(): void
    {
        $this->actingAsAccount('brice.vallet@vallet.test');
        $ferreira = $this->reservationOf('Artisan Ferreira', 'MINI12');

        $this->getJson('/api/reservations')->assertJsonFragment([
            'id' => $ferreira->id,
            'cancellable' => false,
            'can_cancel' => true,
            'cancel_beyond_deadline' => true,
        ]);

        $this->deleteJson("/api/reservations/{$ferreira->id}")->assertNoContent();
    }

    public function test_director_cancels_an_ongoing_reservation(): void
    {
        $this->actingAsAccount('brice.vallet@vallet.test');

        $this->deleteJson("/api/reservations/{$this->reservationOf('M. Pereira (particulier)', 'COMP21')->id}")->assertNoContent();
    }

    public function test_director_cannot_cancel_a_finished_reservation(): void
    {
        config(['vallet.today' => '2026-10-20']);
        $this->actingAsAccount('brice.vallet@vallet.test');

        $this->deleteJson("/api/reservations/{$this->reservationOf('Artisan Ferreira', 'MINI12')->id}")->assertUnprocessable();
    }

    public function test_agency_manager_stays_bound_by_the_deadline(): void
    {
        $this->actingAsAccount('responsable.saint-etienne@vallet.test');
        $ferreira = $this->reservationOf('Artisan Ferreira', 'MINI12');

        $this->getJson('/api/reservations')->assertJsonFragment(['id' => $ferreira->id, 'can_cancel' => false, 'cancel_beyond_deadline' => false]);
        $this->deleteJson("/api/reservations/{$ferreira->id}")->assertUnprocessable();
    }
}
