<?php

namespace Tests\Feature;

class SearchMachinesTest extends ValletTestCase
{
    public function test_machine_in_workshop_and_already_reserved_machine_are_not_available(): void
    {
        $response = $this->getJson('/api/machines?type=Mini-pelle%201.8%20t&from=2026-10-13&to=2026-10-15')
            ->assertOk();

        $machines = collect($response->json())->keyBy('ref');

        $this->assertFalse($machines['MINI07']['available']);
        $this->assertStringContainsString('atelier', $machines['MINI07']['reasons'][0]);
        $this->assertFalse($machines['MINI12']['available']);
    }

    public function test_free_machine_from_another_agency_is_proposed(): void
    {
        $response = $this->getJson('/api/machines?type=Mini-pelle%201.8%20t&from=2026-10-16&to=2026-10-17')
            ->assertOk();

        $machines = collect($response->json())->keyBy('ref');

        $this->assertFalse($machines['MINI07']['available']);
        $this->assertTrue($machines['MINI12']['available']);
        $this->assertSame('Saint-Etienne', $machines['MINI12']['agency']);
    }

    public function test_reserved_machine_shows_who_holds_it(): void
    {
        $response = $this->getJson('/api/machines?type=Nacelle%2012%20m&from=2026-10-20&to=2026-10-21')
            ->assertOk();

        $machines = collect($response->json())->keyBy('ref');

        $this->assertFalse($machines['NAC140']['available']);
        $this->assertStringContainsString('BTP Rhone', $machines['NAC140']['reasons'][0]);
        $this->assertTrue($machines['NAC112']['available']);
    }
}
