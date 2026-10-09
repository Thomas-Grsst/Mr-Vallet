<?php

namespace Tests\Feature;

class CleaningDayTest extends ValletTestCase
{
    public function test_machine_cannot_be_rented_again_on_its_return_day(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-23', '2026-10-24', 'grenoble@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'overlap')
            ->assertJsonPath('violations.0.message', 'Retour de BTP Rhone le 23/10/2026 : la machine est nettoyée et contrôlée ce jour-là, elle est relouable à partir du 24/10/2026');
    }

    public function test_machine_can_be_rented_the_day_after_its_return(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-24', '2026-10-25', 'grenoble@vallet.test')
            ->assertCreated();
    }

    public function test_free_window_for_the_same_agency_starts_the_day_after_the_return(): void
    {
        $this->actingAsAccount('grenoble@vallet.test');

        $nac140 = collect($this->getJson('/api/machines?type=Nacelle%2012%20m&from=2026-10-12&to=2026-10-12')->json())->firstWhere('ref', 'NAC140');

        $this->assertSame('2026-10-24', $nac140['timeline']['free_windows'][1]['starts_at']);
    }

    public function test_return_day_on_a_transfer_is_explained_once(): void
    {
        $violations = $this->reserve('NAC140', 'Facades Martin', '2026-10-23', '2026-10-24', 'villeurbanne@vallet.test')
            ->assertUnprocessable()
            ->json('violations');

        $this->assertCount(1, $violations);
        $this->assertStringStartsWith('Retour de BTP Rhone le 23/10/2026', $violations[0]['message']);
    }
}
