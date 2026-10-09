<?php

namespace Tests\Feature;

use App\Models\Reservation;

class DirectorTest extends ValletTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAccount('brice.vallet@vallet.test');
    }

    public function test_director_profile_has_every_right(): void
    {
        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('role_label', 'Direction')
            ->assertJsonPath('can_book', true)
            ->assertJsonPath('can_maintain', true)
            ->assertJsonPath('chooses_entering_agency', true);
    }

    public function test_director_books_for_the_chosen_agency(): void
    {
        $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP30',
            'client' => 'BTP Rhone',
            'starts_at' => '2026-10-20',
            'ends_at' => '2026-10-21',
            'agency_id' => $this->agencyId('Annecy'),
        ])
            ->assertCreated()
            ->assertJsonPath('entered_by', 'Annecy');
    }

    public function test_director_must_choose_an_agency(): void
    {
        $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP30',
            'client' => 'BTP Rhone',
            'starts_at' => '2026-10-20',
            'ends_at' => '2026-10-21',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('agency_id');
    }

    public function test_director_cancellation_is_signed_direction(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->getJson('/api/reservation-history')
            ->assertJsonFragment(['id' => $duclos->id, 'status' => 'cancelled', 'cancelled_by' => 'Direction']);
    }

    public function test_director_manages_the_workshop_and_the_vgp(): void
    {
        $this->postJson('/api/machines/NAC201/workshop-periods', ['starts_at' => '2026-11-02', 'ends_at' => '2026-11-03', 'reason' => 'VGP'])
            ->assertCreated();

        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-10-12'])->assertOk();
    }

    public function test_agency_account_cannot_choose_another_agency(): void
    {
        $this->reserve('COMP30', 'BTP Rhone', '2026-10-20', '2026-10-21', 'villeurbanne@vallet.test');

        $this->assertSame('Villeurbanne', Reservation::query()->latest('id')->firstOrFail()->enteredBy->name);
    }

    private function agencyId(string $name): int
    {
        return \App\Models\Agency::query()->where('name', $name)->value('id');
    }
}
