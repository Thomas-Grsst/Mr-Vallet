<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;

class MachineTimelineTest extends ValletTestCase
{
    private function timeline(string $account, string $type): Collection
    {
        $this->actingAsAccount($account);

        return collect($this->getJson('/api/machines?'.http_build_query(['type' => $type, 'from' => '2026-10-12', 'to' => '2026-10-12']))->json())
            ->keyBy('ref')
            ->map(fn (array $machine) => $machine['timeline']);
    }

    public function test_occupations_and_free_windows_respect_the_transfer_day(): void
    {
        $nac140 = $this->timeline('villeurbanne@vallet.test', 'Nacelle 12 m')['NAC140'];

        $this->assertSame(
            [['kind' => 'reservation', 'starts_at' => '2026-10-19', 'ends_at' => '2026-10-23', 'label' => 'BTP Rhone']],
            $nac140['occupations'],
        );
        $this->assertSame(
            [['starts_at' => '2026-10-12', 'ends_at' => '2026-10-18'], ['starts_at' => '2026-10-25', 'ends_at' => null]],
            $nac140['free_windows'],
        );
    }

    public function test_same_agency_window_starts_the_day_after_the_return(): void
    {
        $nac140 = $this->timeline('grenoble@vallet.test', 'Nacelle 12 m')['NAC140'];

        $this->assertSame('2026-10-24', $nac140['free_windows'][1]['starts_at']);
    }

    public function test_vgp_expiry_closes_the_last_window(): void
    {
        $nac118 = $this->timeline('villeurbanne@vallet.test', 'Nacelle 12 m')['NAC118'];

        $this->assertSame([['starts_at' => '2026-10-12', 'ends_at' => '2026-10-14']], $nac118['free_windows']);
        $this->assertContains(['kind' => 'vgp', 'starts_at' => '2026-10-15', 'ends_at' => null, 'label' => null], $nac118['occupations']);
    }

    public function test_workshop_period_is_listed_with_its_reason(): void
    {
        $mini07 = $this->timeline('lyon-est@vallet.test', 'Mini-pelle 1.8 t')['MINI07'];

        $this->assertSame(
            [['kind' => 'workshop', 'starts_at' => '2026-10-01', 'ends_at' => '2026-10-20', 'label' => 'verin casse']],
            $mini07['occupations'],
        );
        $this->assertSame([['starts_at' => '2026-10-21', 'ends_at' => null]], $mini07['free_windows']);
    }

    public function test_every_day_of_a_free_window_can_really_be_booked(): void
    {
        foreach ($this->timeline('villeurbanne@vallet.test', 'Nacelle 12 m')['NAC140']['free_windows'] as $window) {
            $this->reserve('NAC140', 'Contrôle', $window['starts_at'], $window['ends_at'] ?? '2026-12-31', 'villeurbanne@vallet.test')
                ->assertCreated();
        }
    }
}
