<?php

namespace Tests\Feature;

class WorkshopAndVgpTest extends ValletTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAccount('atelier@vallet.test');
    }

    public function test_new_vgp_makes_the_nacelle_reservable_again(): void
    {
        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-10-12'])
            ->assertOk()
            ->assertJsonPath('vgp_expires_at', '2027-04-12');

        $this->getJson('/api/anomalies')->assertJsonMissing(['code' => 'vgp_expired']);

        $this->reserve('NAC089', 'BTP Rhone', '2026-11-02', '2026-11-05')->assertCreated();
    }

    public function test_machine_put_in_workshop_is_no_longer_proposed(): void
    {
        $this->patchJson('/api/machines/COMP21/workshop', ['until' => '2026-10-25', 'note' => 'courroie'])
            ->assertOk();

        $machines = collect($this->getJson('/api/machines?type=Compacteur&from=2026-10-25&to=2026-10-26')->json())->keyBy('ref');

        $this->assertFalse($machines['COMP21']['available']);
        $this->assertTrue($machines['COMP30']['available']);
    }

    public function test_machine_back_in_service_is_reservable(): void
    {
        $this->patchJson('/api/machines/MINI07/workshop', ['until' => null])
            ->assertOk()
            ->assertJsonPath('workshop_until', null);

        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-15', '2026-10-16')->assertCreated();
    }
}
