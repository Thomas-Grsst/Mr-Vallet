<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Database\Seeders\ValletSeeder;

class AuthenticationTest extends ValletTestCase
{
    public function test_nothing_is_reachable_without_logging_in(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/machines')->assertUnauthorized();
        $this->get('/api/machines')->assertUnauthorized();
        $this->getJson('/api/reservations')->assertUnauthorized();
        $this->postJson('/api/reservations', [])->assertUnauthorized();
        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-10-12'])->assertUnauthorized();
    }

    public function test_agency_account_logs_in_and_gets_its_profile(): void
    {
        $this->app['auth']->forgetGuards();

        $token = $this->postJson('/api/login', ['email' => 'villeurbanne@vallet.test', 'password' => ValletSeeder::DEMO_PASSWORD])
            ->assertOk()
            ->assertJsonPath('user.agency', 'Villeurbanne')
            ->assertJsonPath('user.can_book', true)
            ->json('token');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('role', 'agency');
    }

    public function test_wrong_password_is_refused(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/login', ['email' => 'villeurbanne@vallet.test', 'password' => 'mauvais'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Identifiants incorrects');
    }

    public function test_agency_cannot_maintain_machines(): void
    {
        $this->patchJson('/api/machines/NAC089/vgp', ['last_vgp_at' => '2026-10-12'])->assertForbidden();
        $this->postJson('/api/machines/COMP21/workshop-periods', ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-25'])->assertForbidden();
    }

    public function test_workshop_and_sales_cannot_book_or_cancel(): void
    {
        $reservation = Reservation::query()->firstOrFail();

        foreach (['atelier@vallet.test', 'julie.ferrand@vallet.test'] as $email) {
            $this->actingAsAccount($email);

            $this->postJson('/api/reservations', [
                'machine_ref' => 'COMP30',
                'client' => 'BTP Rhone',
                'starts_at' => '2026-10-20',
                'ends_at' => '2026-10-21',
            ])->assertForbidden();

            $this->deleteJson("/api/reservations/{$reservation->id}")->assertForbidden();
        }
    }

    public function test_sales_account_can_search(): void
    {
        $this->actingAsAccount('julie.ferrand@vallet.test');

        $this->getJson('/api/machines?type=Compacteur&from=2026-10-20&to=2026-10-21')->assertOk();
    }

    public function test_logout_revokes_the_token(): void
    {
        $this->app['auth']->forgetGuards();

        $token = $this->postJson('/api/login', ['email' => 'atelier@vallet.test', 'password' => ValletSeeder::DEMO_PASSWORD])->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }
}
