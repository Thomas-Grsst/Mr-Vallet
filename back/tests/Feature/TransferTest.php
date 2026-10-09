<?php

namespace Tests\Feature;

class TransferTest extends ValletTestCase
{
    public function test_machine_from_another_agency_must_be_free_the_day_before(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-24', '2026-10-25', 'villeurbanne@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'transfer')
            ->assertJsonPath('violations.0.message', 'Transfert depuis Grenoble impossible : la machine doit être libre la veille du départ (23/10/2026) — Période déjà occupée par BTP Rhone du 19/10/2026 au 23/10/2026');
    }

    public function test_same_agency_needs_no_transfer_day(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-24', '2026-10-25', 'grenoble@vallet.test')
            ->assertCreated();
    }

    public function test_free_day_before_allows_the_transfer(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-25', '2026-10-26', 'villeurbanne@vallet.test')
            ->assertCreated()
            ->assertJsonPath('entered_by', 'Villeurbanne');
    }

    public function test_workshop_the_day_before_blocks_the_transfer(): void
    {
        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-21', '2026-10-22', 'villeurbanne@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'transfer')
            ->assertJsonPath('violations.0.message', 'Transfert depuis Lyon Est impossible : la machine doit être libre la veille du départ (20/10/2026) — Machine en atelier du 01/10/2026 au 20/10/2026 (verin casse)');

        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-21', '2026-10-22', 'lyon-est@vallet.test')
            ->assertCreated();
    }

    public function test_search_of_an_agency_shows_the_transfer_reason(): void
    {
        $this->actingAsAccount('villeurbanne@vallet.test');

        $machines = collect($this->getJson('/api/machines?type=Nacelle%2012%20m&from=2026-10-24&to=2026-10-25')->json())->keyBy('ref');

        $this->assertFalse($machines['NAC140']['available']);
        $this->assertStringStartsWith('Transfert depuis Grenoble impossible', $machines['NAC140']['reasons'][0]);
    }

    public function test_conflict_already_inside_the_period_is_not_reported_twice(): void
    {
        $response = $this->reserve('NAC112', 'Facades Martin', '2026-10-16', '2026-10-17', 'villeurbanne@vallet.test')
            ->assertUnprocessable();

        $this->assertNotContains('transfer', array_column($response->json('violations'), 'code'));
    }

    public function test_director_is_subject_to_the_transfer_rule_for_the_chosen_agency(): void
    {
        $this->actingAsAccount('brice.vallet@vallet.test');

        $this->postJson('/api/reservations', [
            'machine_ref' => 'NAC140',
            'client' => 'Facades Martin',
            'starts_at' => '2026-10-24',
            'ends_at' => '2026-10-25',
            'agency_id' => \App\Models\Agency::query()->where('name', 'Villeurbanne')->value('id'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'transfer');
    }
}
