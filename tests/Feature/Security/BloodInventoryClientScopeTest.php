<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\IntegrationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blood inventory writes over X-Client-ID/Secret need the client to hold
 * inventory:blood:write. Reads are unchanged.
 */
class BloodInventoryClientScopeTest extends TestCase
{
    use RefreshDatabase;

    private function clientWith(array $scopes): array
    {
        $facility = Facility::factory()->create();
        $secret   = 'secret-' . bin2hex(random_bytes(8));
        $client   = IntegrationClient::factory()->create([
            'facility_id'         => $facility->id,
            'scopes'              => $scopes,
            'status'              => 'active',
            'client_secret'       => hash('sha256', $secret),
            'client_secret_argon' => null,
        ]);

        return ['X-Client-ID' => $client->client_id, 'X-Client-Secret' => $secret, 'Accept' => 'application/json'];
    }

    public function test_write_without_scope_is_403(): void
    {
        $this->postJson('/api/v1/inventory/blood', [], $this->clientWith(['inventory:read']))
            ->assertForbidden()
            ->assertJson(['error' => 'insufficient_scope', 'required_scope' => 'inventory:blood:write']);
    }

    public function test_write_with_scope_passes_the_gate(): void
    {
        // Empty body: reaching validation (422) proves the scope gate let it through.
        $this->postJson('/api/v1/inventory/blood', [], $this->clientWith(['inventory:blood:write']))
            ->assertStatus(422);
    }

    public function test_read_does_not_need_the_write_scope(): void
    {
        $this->getJson('/api/v1/inventory/blood', $this->clientWith([]))->assertOk();
    }
}
