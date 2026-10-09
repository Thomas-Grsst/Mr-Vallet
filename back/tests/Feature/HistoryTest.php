<?php

namespace Tests\Feature;

use App\Models\Reservation;

class HistoryTest extends ValletTestCase
{
    public function test_history_gives_each_reservation_its_status_relative_to_today(): void
    {
        $history = collect($this->getJson('/api/reservation-history')->assertOk()->json())
            ->keyBy(fn (array $reservation) => "{$reservation['machine_ref']} {$reservation['client']}");

        $this->assertSame('ongoing', $history['ECH40 Constructions Alpes']['status']);
        $this->assertSame('ongoing', $history['COMP21 M. Pereira (particulier)']['status']);
        $this->assertSame('upcoming', $history['NAC112 BTP Rhone']['status']);
        $this->assertSame('à venir', $history['NAC112 BTP Rhone']['status_label']);
    }

    public function test_finished_reservation_is_marked_finished(): void
    {
        config(['vallet.today' => '2026-10-25']);

        $history = collect($this->getJson('/api/reservation-history')->json())
            ->keyBy(fn (array $reservation) => "{$reservation['machine_ref']} {$reservation['client']}");

        $this->assertSame('finished', $history['ECH40 Constructions Alpes']['status']);
        $this->assertSame('ongoing', $history['NAC089 Facades Martin']['status']);
    }

    public function test_cancelled_reservation_stays_in_history_with_who_and_when(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->getJson('/api/reservation-history')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $duclos->id,
                'status' => 'cancelled',
                'cancelled_at' => '2026-10-12',
                'cancelled_by' => 'Lyon Est',
            ]);

        $this->getJson('/api/reservations')->assertOk()->assertJsonMissing(['client' => 'Maconnerie Duclos']);
        $this->getJson('/api/anomalies')->assertOk()->assertJsonMissing(['code' => 'overlap']);
    }

    public function test_cancelled_reservation_frees_the_machine(): void
    {
        $btp = Reservation::query()->where('client', 'BTP Rhone')->orderBy('starts_at')->firstOrFail();

        $this->deleteJson("/api/reservations/{$btp->id}")->assertNoContent();

        $this->reserve('NAC112', 'Facades Martin', '2026-10-14', '2026-10-15')->assertCreated();
    }

    public function test_reservation_cannot_be_cancelled_twice(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertConflict();
    }
}
