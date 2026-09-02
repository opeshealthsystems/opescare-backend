<?php

namespace Tests\Feature\Connect;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\WebhookEvent;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;

class WebhookEventReplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Realistic IntegrationClientFactory-format client_id (NOT a uuid):
     * webhook_replays.replayed_by is a string column as of migration
     * 2026_06_12_000001, so real client ids must work end-to-end.
     */
    private string $clientId = 'client_replay_test_abc123';

    /**
     * Create a sandbox integration client and exchange its credentials for a
     * real RS256 Bearer token via the production token endpoint.
     */
    private function authHeaders(): array
    {
        \App\Models\IntegrationClient::firstOrCreate(
            ['client_id' => $this->clientId],
            [
                'client_secret' => hash('sha256', 'test_client_secret'),
                'facility_id'   => '00000000-0000-0000-0000-000000000001',
                'name'          => 'Webhook Replay Test Client',
                'environment'   => 'sandbox',
                'scopes'        => ['*'],
                'status'        => 'active',
            ]
        );

        $response = $this->postJson('/api/v1/connect/auth/token', [
            'client_id'     => $this->clientId,
            'client_secret' => 'test_client_secret',
            'grant_type'    => 'client_credentials',
        ]);
        $response->assertStatus(200);

        return ['Authorization' => 'Bearer ' . $response->json('access_token')];
    }

    public function test_dispatch_persists_webhook_event_to_database(): void
    {
        $event = WebhookService::dispatch('lab_result.released', [
            'patient_health_id'     => 'OC-TST-001',
            'external_lab_order_id' => 'LAB-999',
        ]);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertDatabaseHas('webhook_events', [
            'id'         => $event->id,
            'event_type' => 'lab_result.released',
        ]);
    }

    public function test_replay_endpoint_returns_404_for_unknown_event(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/connect/webhooks/events/{$fakeId}/replay");

        $response->assertStatus(404);
    }

    public function test_replay_endpoint_re_dispatches_existing_event(): void
    {
        WebhookSubscription::create([
            // Must match the authenticated client — replay delivery is BOLA-scoped
            // to the caller's own subscriptions (FIX H-3).
            'client_id'         => $this->clientId,
            'callback_url'      => 'https://example.com/webhook',
            'webhook_secret'    => 'whsec_test',
            'subscribed_events' => ['appointment.booked'],
            'status'            => 'active',
        ]);

        $event = WebhookEvent::create([
            'event_type' => 'appointment.booked',
            'payload'    => ['event_type' => 'appointment.booked', 'resource' => []],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/connect/webhooks/events/{$event->id}/replay");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'replayed', 'event_id' => $event->id]);

        $this->assertDatabaseHas('webhook_replays', [
            'webhook_event_id'    => $event->id,
            'webhook_endpoint_id' => WebhookSubscription::first()->id,
            // The acting client's id must be recorded even though it is not a
            // uuid — this is the regression the column-type fix guards against.
            'replayed_by'         => $this->clientId,
        ]);
    }

    public function test_dispatch_payload_contains_stable_event_id(): void
    {
        $event = WebhookService::dispatch('consent.granted', ['patient_id' => 'p-001']);

        $this->assertNotEmpty($event->id);
        $stored = WebhookEvent::find($event->id);
        $this->assertEquals('consent.granted', $stored->event_type);
    }
}
