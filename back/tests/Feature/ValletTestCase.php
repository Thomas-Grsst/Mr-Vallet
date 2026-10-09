<?php

namespace Tests\Feature;

use App\Models\Agency;
use Database\Seeders\ValletSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ValletTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = ValletSeeder::class;

    protected function agencyId(string $name): int
    {
        return Agency::query()->where('name', $name)->value('id');
    }

    protected function reserve(string $ref, string $client, string $from, string $to, string $agency = 'Lyon Est'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/reservations', [
            'machine_ref' => $ref,
            'client' => $client,
            'starts_at' => $from,
            'ends_at' => $to,
            'agency_id' => $this->agencyId($agency),
        ]);
    }
}
