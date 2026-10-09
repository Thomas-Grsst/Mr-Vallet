<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Testing\TestResponse;

class UpdateReservationTest extends ValletTestCase
{
    private function reservationOf(string $client, string $ref): Reservation
    {
        return Reservation::query()
            ->where('client', $client)
            ->whereHas('machine', fn ($query) => $query->where('ref', $ref))
            ->firstOrFail();
    }

    private function move(Reservation $reservation, string $from, string $to, string $account = 'villeurbanne@vallet.test'): TestResponse
    {
        $this->actingAsAccount($account);

        return $this->patchJson("/api/reservations/{$reservation->id}", ['starts_at' => $from, 'ends_at' => $to]);
    }

    public function test_reservation_is_moved_and_the_change_is_traced(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->move($duclos, '2026-10-20', '2026-10-22')
            ->assertOk()
            ->assertJsonPath('starts_at', '2026-10-20')
            ->assertJsonPath('modified_at', '2026-10-12')
            ->assertJsonPath('modified_by', 'Villeurbanne');

        $this->getJson('/api/anomalies')->assertJsonMissing(['code' => 'overlap']);
        $this->getJson('/api/reservation-history')->assertJsonFragment(['id' => $duclos->id, 'modified_by' => 'Villeurbanne']);
    }

    public function test_moving_onto_a_return_day_is_refused(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->move($duclos, '2026-10-18', '2026-10-19')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.message', 'Retour de BTP Rhone le 18/10/2026 : la machine est nettoyée et contrôlée ce jour-là, elle est relouable à partir du 19/10/2026');
    }

    public function test_reservation_does_not_conflict_with_itself(): void
    {
        $btp = $this->reservationOf('BTP Rhone', 'NAC140');
        $btp->update(['purchase_order' => 'BC-1']);

        $this->move($btp, '2026-10-19', '2026-10-24', 'grenoble@vallet.test')->assertOk();
    }

    public function test_locked_reservation_can_only_be_extended(): void
    {
        $ferreira = $this->reservationOf('Artisan Ferreira', 'MINI12');

        $this->move($ferreira, '2026-10-13', '2026-10-16', 'saint-etienne@vallet.test')
            ->assertOk()
            ->assertJsonPath('ends_at', '2026-10-16');

        $this->move($ferreira, '2026-10-13', '2026-10-13', 'saint-etienne@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'locked');

        $this->move($ferreira, '2026-10-14', '2026-10-17', 'saint-etienne@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'locked');
    }

    public function test_ongoing_reservation_can_be_extended_despite_starting_in_the_past(): void
    {
        $alpes = $this->reservationOf('Constructions Alpes', 'ECH40');

        $this->move($alpes, '2026-10-06', '2026-10-28', 'lyon-est@vallet.test')->assertOk();
    }

    public function test_key_account_without_purchase_order_cannot_be_modified(): void
    {
        $btp = $this->reservationOf('BTP Rhone', 'NAC140');

        $this->move($btp, '2026-10-19', '2026-10-24', 'grenoble@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'missing_purchase_order');
    }

    public function test_purchase_order_added_during_modification_regularises_the_key_account(): void
    {
        $btp = $this->reservationOf('BTP Rhone', 'NAC140');
        $this->actingAsAccount('grenoble@vallet.test');

        $this->patchJson("/api/reservations/{$btp->id}", ['starts_at' => '2026-10-19', 'ends_at' => '2026-10-23', 'purchase_order' => 'BC-2026-0500'])
            ->assertOk()
            ->assertJsonPath('purchase_order', 'BC-2026-0500');

        $this->assertFalse(collect($this->getJson('/api/anomalies')->json())->contains(
            fn (array $anomaly) => $anomaly['code'] === 'missing_purchase_order' && $anomaly['machine_ref'] === 'NAC140',
        ));
    }

    public function test_sales_and_workshop_cannot_modify(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');

        $this->move($duclos, '2026-10-20', '2026-10-22', 'julie.ferrand@vallet.test')->assertForbidden();
        $this->move($duclos, '2026-10-20', '2026-10-22', 'atelier@vallet.test')->assertForbidden();
    }

    public function test_cancelled_reservation_cannot_be_modified(): void
    {
        $duclos = $this->reservationOf('Maconnerie Duclos', 'NAC112');
        $this->actingAsAccount('villeurbanne@vallet.test');
        $this->deleteJson("/api/reservations/{$duclos->id}")->assertNoContent();

        $this->move($duclos, '2026-10-20', '2026-10-22')->assertConflict();
    }
}
