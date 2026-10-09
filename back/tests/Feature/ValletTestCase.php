<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ValletSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class ValletTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = ValletSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAccount('lyon-est@vallet.test');
    }

    protected function actingAsAccount(string $email): User
    {
        $user = User::query()->where('email', $email)->firstOrFail();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function reserve(string $ref, string $client, string $from, string $to, string $agencyEmail = 'lyon-est@vallet.test'): TestResponse
    {
        $this->actingAsAccount($agencyEmail);

        return $this->postJson('/api/reservations', [
            'machine_ref' => $ref,
            'client' => $client,
            'starts_at' => $from,
            'ends_at' => $to,
        ]);
    }
}
