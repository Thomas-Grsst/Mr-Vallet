<?php

namespace Tests\Feature;

use App\Models\KeyAccount;

class KeyAccountManagementTest extends ValletTestCase
{
    public function test_sales_adds_a_key_account_which_then_requires_a_purchase_order(): void
    {
        $this->actingAsAccount('julie.ferrand@vallet.test');

        $this->postJson('/api/key-accounts', ['name' => ' Facades Martin '])
            ->assertCreated()
            ->assertJsonPath('name', 'Facades Martin');

        $this->assertTrue(collect($this->getJson('/api/anomalies')->json())->contains(
            fn (array $anomaly) => $anomaly['code'] === 'missing_purchase_order' && $anomaly['machine_ref'] === 'NAC089',
        ));

        $this->reserve('COMP30', 'Facades Martin', '2026-10-26', '2026-10-27')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.message', 'Facades Martin est un grand compte : le numéro de bon de commande est obligatoire');
    }

    public function test_duplicate_is_refused_whatever_the_case(): void
    {
        $this->actingAsAccount('brice.vallet@vallet.test');

        $this->postJson('/api/key-accounts', ['name' => 'btp rhone'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'BTP Rhone est déjà un grand compte.');
    }

    public function test_director_removes_a_key_account(): void
    {
        $this->actingAsAccount('brice.vallet@vallet.test');
        $btp = KeyAccount::query()->firstOrFail();

        $this->deleteJson("/api/key-accounts/{$btp->id}")->assertNoContent();

        $this->getJson('/api/anomalies')->assertJsonMissing(['code' => 'missing_purchase_order']);
    }

    public function test_agency_and_workshop_can_read_but_not_change_the_list(): void
    {
        $btp = KeyAccount::query()->firstOrFail();

        foreach (['lyon-est@vallet.test', 'atelier@vallet.test'] as $email) {
            $this->actingAsAccount($email);

            $this->getJson('/api/key-accounts')->assertOk();
            $this->postJson('/api/key-accounts', ['name' => 'Facades Martin'])->assertForbidden();
            $this->deleteJson("/api/key-accounts/{$btp->id}")->assertForbidden();
        }
    }

    public function test_known_clients_are_listed_once(): void
    {
        $clients = $this->getJson('/api/clients')->assertOk()->json();

        $this->assertSame(array_values(array_unique($clients)), $clients);
        $this->assertContains('Facades Martin', $clients);
        $this->assertContains('BTP Rhone', $clients);
    }
}
