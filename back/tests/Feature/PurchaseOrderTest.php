<?php

namespace Tests\Feature;

class PurchaseOrderTest extends ValletTestCase
{
    private function book(string $client, ?string $purchaseOrder = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/reservations', [
            'machine_ref' => 'COMP30',
            'client' => $client,
            'purchase_order' => $purchaseOrder,
            'starts_at' => '2026-10-26',
            'ends_at' => '2026-10-27',
        ]);
    }

    public function test_key_account_without_purchase_order_is_refused(): void
    {
        $this->book('btp rhone')
            ->assertUnprocessable()
            ->assertJsonPath('violations.0.code', 'missing_purchase_order')
            ->assertJsonPath('violations.0.message', 'BTP Rhone est un grand compte : le numéro de bon de commande est obligatoire');
    }

    public function test_blank_purchase_order_is_refused(): void
    {
        $this->book('  BTP Rhone ', '   ')->assertUnprocessable();
    }

    public function test_key_account_with_purchase_order_is_accepted_and_shown(): void
    {
        $this->book('BTP Rhone', 'BC-2026-0412')
            ->assertCreated()
            ->assertJsonPath('purchase_order', 'BC-2026-0412');

        $this->getJson('/api/reservations')->assertJsonFragment(['client' => 'BTP Rhone', 'purchase_order' => 'BC-2026-0412']);
        $this->getJson('/api/reservation-history')->assertJsonFragment(['purchase_order' => 'BC-2026-0412']);
    }

    public function test_key_account_is_saved_under_its_listed_name(): void
    {
        $this->book('  btp rhone ', 'BC-2026-0412')
            ->assertCreated()
            ->assertJsonPath('client', 'BTP Rhone');
    }

    public function test_other_clients_need_no_purchase_order(): void
    {
        $this->book('Facades Martin')->assertCreated()->assertJsonPath('purchase_order', null);
    }

    public function test_imported_key_account_reservations_without_purchase_order_are_anomalies(): void
    {
        $refs = collect($this->getJson('/api/anomalies')->json())
            ->where('code', 'missing_purchase_order')
            ->pluck('machine_ref')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['NAC112', 'NAC140'], $refs);
    }

    public function test_key_accounts_are_listed(): void
    {
        $this->getJson('/api/key-accounts')->assertOk()->assertExactJson(['BTP Rhone']);
    }
}
