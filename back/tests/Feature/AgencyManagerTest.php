<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Testing\TestResponse;

class AgencyManagerTest extends ValletTestCase
{
    private function duclos(): Reservation
    {
        return Reservation::query()->where('client', 'Maconnerie Duclos')->firstOrFail();
    }

    private function cancelAs(string $account, Reservation $reservation): TestResponse
    {
        $this->actingAsAccount($account);

        return $this->deleteJson("/api/reservations/{$reservation->id}");
    }

    private function moveAs(string $account, Reservation $reservation): TestResponse
    {
        $this->actingAsAccount($account);

        return $this->patchJson("/api/reservations/{$reservation->id}", ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-22']);
    }

    public function test_agent_of_the_entering_agency_modifies_but_cannot_cancel(): void
    {
        $this->cancelAs('villeurbanne@vallet.test', $this->duclos())
            ->assertForbidden()
            ->assertJsonPath('message', 'Annulation réservée au responsable de Villeurbanne (agence de saisie) ou de Lyon Est (agence de la machine)');

        $this->moveAs('villeurbanne@vallet.test', $this->duclos())->assertOk();
    }

    public function test_agent_of_the_machine_agency_can_neither_modify_nor_cancel(): void
    {
        $this->moveAs('lyon-est@vallet.test', $this->duclos())
            ->assertForbidden()
            ->assertJsonPath('message', "Modification réservée à l'agence Villeurbanne (agence de saisie) et au responsable de Lyon Est (agence de la machine)");

        $this->cancelAs('lyon-est@vallet.test', $this->duclos())->assertForbidden();
    }

    public function test_manager_of_an_unrelated_agency_can_neither_modify_nor_cancel(): void
    {
        $this->cancelAs('responsable.grenoble@vallet.test', $this->duclos())->assertForbidden();
        $this->moveAs('responsable.grenoble@vallet.test', $this->duclos())->assertForbidden();
    }

    public function test_manager_of_the_machine_agency_modifies_and_cancels(): void
    {
        $this->moveAs('responsable.lyon-est@vallet.test', $this->duclos())->assertOk();
        $this->cancelAs('responsable.lyon-est@vallet.test', $this->duclos())->assertNoContent();

        $this->getJson('/api/reservation-history')->assertJsonFragment(['id' => $this->duclos()->id, 'cancelled_by' => 'Lyon Est']);
    }

    public function test_manager_of_the_entering_agency_cancels(): void
    {
        $this->cancelAs('responsable.villeurbanne@vallet.test', $this->duclos())->assertNoContent();
    }

    public function test_rights_are_sent_with_each_reservation(): void
    {
        $this->actingAsAccount('villeurbanne@vallet.test');

        $this->getJson('/api/reservations')->assertJsonFragment([
            'id' => $this->duclos()->id,
            'can_cancel' => false,
            'can_modify' => true,
            'cancel_denied_reason' => 'Annulation réservée au responsable de Villeurbanne (agence de saisie) ou de Lyon Est (agence de la machine)',
        ]);

        $this->actingAsAccount('julie.ferrand@vallet.test');

        $this->getJson('/api/reservations')->assertJsonFragment([
            'id' => $this->duclos()->id,
            'can_cancel' => false,
            'can_modify' => false,
        ]);
    }

    public function test_agency_manager_profile(): void
    {
        $this->actingAsAccount('responsable.lyon-est@vallet.test');

        $this->getJson('/api/me')
            ->assertJsonPath('name', 'Sandrine Morin')
            ->assertJsonPath('role_label', "Responsable d'agence")
            ->assertJsonPath('agency', 'Lyon Est')
            ->assertJsonPath('can_book', true);
    }
}
