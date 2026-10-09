<?php

namespace Tests\Feature;

use App\Models\Reservation;

class AnomaliesTest extends ValletTestCase
{
    public function test_imported_anomalies_are_listed(): void
    {
        $anomalies = collect($this->getJson('/api/anomalies')->assertOk()->json());

        $this->assertCount(4, $anomalies);
        $this->assertCount(2, $anomalies->where("code", "missing_purchase_order"));
        $this->assertTrue($anomalies->contains(fn ($anomaly) => $anomaly['code'] === 'overlap'
            && str_contains($anomaly['message'], 'BTP Rhone')
            && str_contains($anomaly['message'], 'Maconnerie Duclos')));
        $this->assertTrue($anomalies->contains(fn ($anomaly) => $anomaly['code'] === 'vgp_expired'
            && $anomaly['machine_ref'] === 'NAC089'
            && str_contains($anomaly['message'], 'Facades Martin')));
    }

    public function test_anomalies_point_to_the_reservations_concerned(): void
    {
        $idsByClient = Reservation::query()
            ->whereHas('machine', fn ($query) => $query->whereIn('ref', ['NAC112', 'NAC089']))
            ->pluck('id', 'client');

        $anomalies = collect($this->getJson('/api/anomalies')->assertOk()->json())->keyBy('code');

        $this->assertEqualsCanonicalizing(
            [$idsByClient['BTP Rhone'], $idsByClient['Maconnerie Duclos']],
            $anomalies['overlap']['reservation_ids'],
        );
        $this->assertSame([$idsByClient['Facades Martin']], $anomalies['vgp_expired']['reservation_ids']);
    }

    public function test_cancelling_a_reservation_removes_the_overlap(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->actingAsAccount('villeurbanne@vallet.test');
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->getJson('/api/anomalies')
            ->assertOk()
            ->assertJsonMissing(['code' => 'overlap']);
    }
}
