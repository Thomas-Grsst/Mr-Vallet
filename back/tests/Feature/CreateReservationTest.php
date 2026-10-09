<?php

namespace Tests\Feature;

class CreateReservationTest extends ValletTestCase
{
    public function test_overlapping_reservation_is_refused_with_the_occupied_period(): void
    {
        $this->reserve('NAC112', 'Maconnerie Duclos', '2026-10-16', '2026-10-17', 'villeurbanne@vallet.test')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'overlap')
            ->assertJsonPath('violations.0.message', 'Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026');
    }

    public function test_reservation_starting_the_day_another_ends_overlaps(): void
    {
        $this->reserve('NAC112', 'Facades Martin', '2026-10-18', '2026-10-20')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'overlap');
    }

    public function test_nacelle_with_expired_vgp_is_blocked(): void
    {
        $this->reserve('NAC089', 'Facades Martin', '2026-11-02', '2026-11-05')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.message', "VGP non à jour, contacter l'atelier");
    }

    public function test_nacelle_with_expired_vgp_on_an_already_held_period_reports_the_vgp(): void
    {
        $this->reserve('NAC089', 'Facades Martin', '2026-10-20', '2026-10-31')
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => "VGP non à jour, contacter l'atelier"]);
    }

    public function test_nacelle_whose_vgp_expires_during_the_period_is_blocked(): void
    {
        $this->reserve('NAC118', 'BTP Rhone', '2026-10-13', '2026-10-16')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'vgp_expired');
    }

    public function test_machine_in_workshop_is_blocked_until_its_return_date(): void
    {
        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-20', '2026-10-22')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'workshop');

        $this->reserve('MINI07', 'Artisan Ferreira', '2026-10-21', '2026-10-22')
            ->assertCreated();
    }

    public function test_free_machine_is_reserved_and_visible_to_every_agency(): void
    {
        $this->reserve('NAC140', 'Facades Martin', '2026-10-26', '2026-10-28', 'lyon-est@vallet.test')
            ->assertCreated()
            ->assertJsonPath('machine_agency', 'Grenoble')
            ->assertJsonPath('entered_by', 'Lyon Est');

        $this->getJson('/api/reservations')
            ->assertOk()
            ->assertJsonFragment(['machine_ref' => 'NAC140', 'client' => 'Facades Martin', 'starts_at' => '2026-10-26']);
    }

    public function test_end_date_before_start_date_is_refused(): void
    {
        $this->reserve('COMP30', 'BTP Rhone', '2026-10-20', '2026-10-19')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_reservation_in_the_past_is_refused(): void
    {
        $this->reserve('COMP30', 'BTP Rhone', '2026-10-11', '2026-10-13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('starts_at');
    }

    public function test_client_is_required(): void
    {
        $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP30',
            'starts_at' => '2026-10-20',
            'ends_at' => '2026-10-21',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client');
    }

    public function test_entering_agency_is_the_logged_in_account_agency(): void
    {
        $this->actingAsAccount('villeurbanne@vallet.test');

        $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP30',
            'client' => 'BTP Rhone',
            'starts_at' => '2026-10-20',
            'ends_at' => '2026-10-21',
            'agency_id' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('entered_by', 'Villeurbanne');
    }
}
