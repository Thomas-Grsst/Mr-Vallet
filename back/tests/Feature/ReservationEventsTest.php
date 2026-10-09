<?php

namespace Tests\Feature;

use App\Models\Reservation;

class ReservationEventsTest extends ValletTestCase
{
    public function test_every_modification_and_the_cancellation_are_kept_with_who_did_it(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->actingAsAccount('villeurbanne@vallet.test');
        $this->patchJson("/api/reservations/{$duclos->id}", ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-22'])->assertOk();
        $this->patchJson("/api/reservations/{$duclos->id}", ['starts_at' => '2026-10-21', 'ends_at' => '2026-10-23'])->assertOk();

        $this->actingAsAccount('responsable.lyon-est@vallet.test');
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $history = collect($this->getJson('/api/reservation-history')->json())->firstWhere('id', $duclos->id);

        $this->assertSame([
            'Modifiée le 12/10/2026 par Villeurbanne · Agence : du 16/10 au 17/10 → du 20/10 au 22/10',
            'Modifiée le 12/10/2026 par Villeurbanne · Agence : du 20/10 au 22/10 → du 21/10 au 23/10',
            "Annulée le 12/10/2026 par Sandrine Morin · Responsable d'agence · Lyon Est",
        ], array_column($history['events'], 'description'));
    }

    public function test_purchase_order_change_is_described(): void
    {
        $btp = Reservation::query()->where('client', 'BTP Rhone')->whereHas('machine', fn ($query) => $query->where('ref', 'NAC140'))->firstOrFail();

        $this->actingAsAccount('grenoble@vallet.test');
        $this->patchJson("/api/reservations/{$btp->id}", ['starts_at' => '2026-10-19', 'ends_at' => '2026-10-23', 'purchase_order' => 'BC-9'])->assertOk();

        $history = collect($this->getJson('/api/reservation-history')->json())->firstWhere('id', $btp->id);

        $this->assertSame('Modifiée le 12/10/2026 par Grenoble · Agence : bon de commande aucun → BC-9', $history['events'][0]['description']);
    }

    public function test_director_is_named_in_the_follow_up(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->actingAsAccount('brice.vallet@vallet.test');
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $history = collect($this->getJson('/api/reservation-history')->json())->firstWhere('id', $duclos->id);

        $this->assertSame('Annulée le 12/10/2026 par Brice Vallet · Direction', $history['events'][0]['description']);
    }

    public function test_refused_changes_leave_no_trace(): void
    {
        $duclos = Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();

        $this->actingAsAccount('villeurbanne@vallet.test');
        $this->patchJson("/api/reservations/{$duclos->id}", ['starts_at' => '2026-10-18', 'ends_at' => '2026-10-19'])->assertUnprocessable();
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertForbidden();

        $history = collect($this->getJson('/api/reservation-history')->json())->firstWhere('id', $duclos->id);

        $this->assertSame([], $history['events']);
    }
}
