<?php

namespace App\Modules\Connect\Services;

use App\Models\IntegrationClient;
use App\Models\SdkToken;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

class ConnectAdminService
{
    // ── Integration Client management ─────────────────────────────

    public function createClient(array $data, string $actorId): IntegrationClient
    {
        $clientId     = 'oc_' . Str::lower(Str::random(20));
        $clientSecret = 'ocs_' . bin2hex(random_bytes(24));

        return IntegrationClient::create([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,   // Store hashed in prod; plain for demo
            'facility_id'   => $data['facility_id'] ?? 'global',
            'scopes'        => $data['scopes'] ?? [],
            'status'        => 'pending',
            'environment'   => $data['environment'] ?? 'sandbox',
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'created_by'    => $actorId,
        ]);
    }

    public function approveClient(IntegrationClient $client, string $actorId): IntegrationClient
    {
        $client->update([
            'status'      => 'active',
            'approved_at' => now(),
            'approved_by' => $actorId,
        ]);
        return $client->fresh();
    }

    public function suspendClient(IntegrationClient $client, string $actorId): IntegrationClient
    {
        $client->update(['status' => 'suspended']);
        return $client->fresh();
    }

    public function revokeClient(IntegrationClient $client, string $actorId): IntegrationClient
    {
        $client->update(['status' => 'revoked']);
        // Deactivate all its SDK tokens
        SdkToken::where('client_id', $client->client_id)->update([
            'is_active'  => false,
            'revoked_by' => $actorId,
            'revoked_at' => now(),
        ]);
        // Pause all webhooks
        WebhookSubscription::where('client_id', $client->client_id)->update(['status' => 'paused']);
        return $client->fresh();
    }

    // ── SDK Tokens ────────────────────────────────────────────────

    /**
     * Issue a new SDK token. Returns the raw token (only shown once).
     */
    public function issueToken(IntegrationClient $client, array $data, string $actorId): array
    {
        $rawToken  = 'ocsdk_' . bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $prefix    = substr($rawToken, 0, 12);

        $token = SdkToken::create([
            'client_id'   => $client->client_id,
            'token_hash'  => $tokenHash,
            'token_prefix'=> $prefix,
            'scopes'      => $data['scopes'] ?? $client->scopes,
            'environment' => $data['environment'] ?? $client->environment,
            'label'       => $data['label'] ?? null,
            'expires_at'  => isset($data['expires_days']) ? now()->addDays((int)$data['expires_days']) : null,
            'is_active'   => true,
        ]);

        return ['token' => $token, 'raw_token' => $rawToken];
    }

    public function revokeToken(SdkToken $token, string $actorId): void
    {
        $token->update([
            'is_active'  => false,
            'revoked_by' => $actorId,
            'revoked_at' => now(),
        ]);
    }

    // ── Webhook overview ──────────────────────────────────────────

    public function webhookStats(): array
    {
        return [
            'total'    => WebhookDeliveryLog::count(),
            'delivered'=> WebhookDeliveryLog::where('status','delivered')->count(),
            'failed'   => WebhookDeliveryLog::where('status','failed')->count(),
            'pending'  => WebhookDeliveryLog::where('status','pending')->count(),
        ];
    }

    // ── Dashboard stats ───────────────────────────────────────────

    public function dashboardStats(): array
    {
        return [
            'total_clients'   => IntegrationClient::count(),
            'active_clients'  => IntegrationClient::where('status','active')->count(),
            'pending_clients' => IntegrationClient::where('status','pending')->count(),
            'sandbox_clients' => IntegrationClient::where('environment','sandbox')->count(),
            'active_webhooks' => WebhookSubscription::where('status','active')->count(),
            'active_tokens'   => SdkToken::where('is_active',true)->count(),
            'webhook_stats'   => $this->webhookStats(),
        ];
    }
}
