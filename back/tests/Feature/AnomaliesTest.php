<?php

namespace Tests\Feature;

use App\Models\Reservation;

class AnomaliesTest extends ValletTestCase
{
    public function test_imported_anomalies_are_listed(): void
    {
        $anomalies = collect($this->getJson('/api/anomalies')->assertOk()->json());

        $this->assertCount(2, $anomalies);
        $this->assertTrue($anomalies->contains(fn ($anomaly) => $anomaly['code'] === 'overlap'
            && str_contains($anomaly['message'], 'BTP Rhone')
            && str_contains($anomaly['message'], 'Maconnerie Duclos')));
        $this->assertTrue($anomalies->contains(fn ($anomaly) => $anomaly['code'] === 'vgp_expired'
            && $anomaly['machine_ref'] === 'NAC089'
            && str_contains($anomaly['message'], 'Facades Martin')));
    }

    public function test_cancelling_a_reservation_removes_the_overlap(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->getJson('/api/anomalies')
            ->assertOk()
            ->assertJsonMissing(['code' => 'overlap']);
    }
}
