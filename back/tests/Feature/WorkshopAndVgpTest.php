<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\WorkshopPeriod;

class WorkshopAndVgpTest extends ValletTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAccount('atelier@vallet.test');
    }

    private function machine(string $ref): array
    {
        return collect($this->getJson('/api/machines')->json())->firstWhere('ref', $ref);
    }

    private function search(string $type, string $from, string $to): \Illuminate\Support\Collection
    {
        return collect($this->getJson('/api/machines?'.http_build_query(compact('type', 'from', 'to')))->json())->keyBy('ref');
    }

    public function test_new_vgp_makes_the_nacelle_reservable_again(): void
    {
        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-10-12'])
            ->assertOk()
            ->assertJsonPath('vgp_expires_at', '2027-04-12');

        $this->getJson('/api/anomalies')->assertJsonMissing(['code' => 'vgp_expired']);

        $this->reserve('NAC089', 'Constructions Alpes', '2026-11-02', '2026-11-05')->assertCreated();
    }

    public function test_vgp_in_the_future_cannot_be_recorded_as_done(): void
    {
        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-11-03'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('last_vgp_at');
    }

    public function test_imported_workshop_period_has_a_start_and_an_end(): void
    {
        $this->assertSame(
            [['starts_at' => '2026-10-01', 'ends_at' => '2026-10-20', 'reason' => 'verin casse', 'status' => 'current']],
            collect($this->machine('MINI07')['workshop_periods'])->map(fn (array $period) => collect($period)->except('id')->all())->all(),
        );
    }

    public function test_planned_vgp_blocks_only_its_own_dates(): void
    {
        $this->postJson('/api/machines/NAC201/workshop-periods', ['starts_at' => '2026-11-02', 'ends_at' => '2026-11-03', 'reason' => 'VGP'])
            ->assertCreated()
            ->assertJsonPath('machine.workshop_periods.0.status', 'planned')
            ->assertJsonPath('impacted_reservations', []);

        $this->assertTrue($this->search('Nacelle 20 m', '2026-10-26', '2026-10-30')['NAC201']['available']);

        $later = $this->search('Nacelle 20 m', '2026-11-02', '2026-11-05')['NAC201'];
        $this->assertFalse($later['available']);
        $this->assertContains('Machine en atelier du 02/11/2026 au 03/11/2026 (VGP)', $later['reasons']);
    }

    public function test_planned_vgp_does_not_count_as_done(): void
    {
        $this->postJson('/api/machines/NAC089/workshop-periods', ['starts_at' => '2026-10-13', 'ends_at' => '2026-10-13', 'reason' => 'VGP'])
            ->assertCreated();

        $this->assertContains("VGP non à jour, contacter l'atelier", $this->search('Nacelle 16 m', '2026-11-02', '2026-11-05')['NAC089']['reasons']);
    }

    public function test_back_in_service_frees_the_machine_from_today(): void
    {
        $period = WorkshopPeriod::query()->firstOrFail();

        $this->deleteJson("/api/workshop-periods/{$period->id}")->assertNoContent();

        $this->assertSame('2026-10-11', $period->fresh()->ends_at->toDateString());
        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-12', '2026-10-13')->assertCreated();
    }

    public function test_cancelling_a_planned_period_removes_it(): void
    {
        $this->postJson('/api/machines/COMP30/workshop-periods', ['starts_at' => '2026-10-26', 'ends_at' => '2026-10-27'])->assertCreated();
        $period = Machine::query()->where('ref', 'COMP30')->firstOrFail()->workshopPeriods()->firstOrFail();

        $this->deleteJson("/api/workshop-periods/{$period->id}")->assertNoContent();

        $this->assertModelMissing($period);
        $this->assertTrue($this->search('Compacteur', '2026-10-26', '2026-10-27')['COMP30']['available']);
    }

    public function test_overlapping_periods_on_the_same_machine_are_refused(): void
    {
        $this->postJson('/api/machines/COMP30/workshop-periods', ['starts_at' => '2026-10-26', 'ends_at' => '2026-10-27'])->assertCreated();

        $this->postJson('/api/machines/COMP30/workshop-periods', ['starts_at' => '2026-10-27', 'ends_at' => '2026-10-28'])
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'workshop_overlap');
    }

    public function test_period_cannot_start_in_the_past_or_end_before_it_starts(): void
    {
        $this->postJson('/api/machines/COMP30/workshop-periods', ['starts_at' => '2026-10-11', 'ends_at' => '2026-10-13'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');

        $this->postJson('/api/machines/COMP30/workshop-periods', ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-19'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_breakdown_on_a_reserved_machine_is_accepted_and_flagged(): void
    {
        $this->postJson('/api/machines/NAC140/workshop-periods', ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-21', 'reason' => 'panne moteur'])
            ->assertCreated()
            ->assertJsonPath('impacted_reservations', ['BTP Rhone du 19/10/2026 au 23/10/2026']);

        $this->getJson('/api/anomalies')
            ->assertOk()
            ->assertJsonFragment(['code' => 'workshop', 'machine_ref' => 'NAC140']);
    }

    public function test_finished_period_cannot_be_ended_again(): void
    {
        $period = WorkshopPeriod::query()->firstOrFail();
        $period->update(['ends_at' => '2026-10-05']);

        $this->deleteJson("/api/workshop-periods/{$period->id}")->assertConflict();
    }
}
