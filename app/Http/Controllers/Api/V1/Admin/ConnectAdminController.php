<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationClient;
use App\Models\SdkToken;
use App\Modules\Connect\Services\ConnectAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConnectAdminController — OpesCare Connect Integration Client Management API.
 *
 * Manages the full lifecycle of integration clients (partner applications)
 * that connect to OpesCare via the Connect API.
 *
 * SECURITY RULES:
 * - Client secrets are generated server-side; callers never supply secrets.
 * - Raw SDK tokens are returned ONCE on creation — never again.
 *   Store them immediately; retrieval is impossible after the response.
 * - Never expose token_hash in API responses.
 * - Client IDs use the 'oc_' prefix; SDK tokens use 'ocsdk_' prefix.
 *
 * Routes protected by VerifyIntegrationClient middleware (super-admin only).
 *
 * Endpoints:
 *  GET   /v1/admin/connect/clients               — list all integration clients
 *  POST  /v1/admin/connect/clients               — create a new client
 *  GET   /v1/admin/connect/clients/{client}      — get client details
 *  POST  /v1/admin/connect/clients/{client}/approve   — approve a pending client
 *  POST  /v1/admin/connect/clients/{client}/suspend   — suspend an active client
 *  POST  /v1/admin/connect/clients/{client}/revoke    — revoke a client (disables all tokens)
 *  POST  /v1/admin/connect/clients/{client}/tokens    — issue a new SDK token
 *  DELETE /v1/admin/connect/tokens/{token}            — revoke an SDK token
 *  GET   /v1/admin/connect/webhooks/stats         — webhook delivery statistics
 *  GET   /v1/admin/connect/stats                  — dashboard summary stats
 */
class ConnectAdminController extends Controller
{
    public function __construct(private readonly ConnectAdminService $service) {}

    // ── Clients ───────────────────────────────────────────────────────────

    /**
     * List all integration clients.
     * ?status=active|pending|suspended|revoked
     * ?environment=sandbox|production
     */
    public function listClients(Request $request): JsonResponse
    {
        $query = IntegrationClient::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('environment')) {
            $query->where('environment', $request->query('environment'));
        }

        $clients = $query->orderByDesc('created_at')->paginate(50);

        return response()->json([
            'data' => $clients->map(fn ($c) => $this->serializeClient($c)),
            'meta' => [
                'total'        => $clients->total(),
                'per_page'     => $clients->perPage(),
                'current_page' => $clients->currentPage(),
            ],
        ]);
    }

    /**
     * Create a new integration client.
     * Body: { name, environment, scopes, facility_id?, description?, contact_email?, actor_id }
     */
    public function createClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'environment'   => ['required', 'in:sandbox,production'],
            'scopes'        => ['required', 'array'],
            'scopes.*'      => ['string'],
            'facility_id'   => ['nullable', 'uuid'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'actor_id'      => ['required', 'uuid'],
        ]);

        $client = $this->service->createClient($validated, $validated['actor_id']);

        return response()->json([
            'message' => __('api.integration_client_created'),
            'data'    => $this->serializeClient($client),
        ], 201);
    }

    /**
     * Get integration client details.
     */
    public function showClient(IntegrationClient $client): JsonResponse
    {
        return response()->json(['data' => $this->serializeClient($client)]);
    }

    /**
     * Approve a pending integration client.
     * Body: { actor_id }
     */
    public function approveClient(IntegrationClient $client, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        $updated = $this->service->approveClient($client, $validated['actor_id']);

        return response()->json([
            'message' => __('api.client_approved'),
            'data'    => $this->serializeClient($updated),
        ]);
    }

    /**
     * Suspend an active integration client.
     * Body: { actor_id }
     */
    public function suspendClient(IntegrationClient $client, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        $updated = $this->service->suspendClient($client, $validated['actor_id']);

        return response()->json([
            'message' => __('api.client_suspended'),
            'data'    => $this->serializeClient($updated),
        ]);
    }

    /**
     * Revoke an integration client.
     * This also deactivates all its SDK tokens and pauses all webhooks.
     * Body: { actor_id }
     */
    public function revokeClient(IntegrationClient $client, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        $updated = $this->service->revokeClient($client, $validated['actor_id']);

        return response()->json([
            'message' => __('api.client_revoked'),
            'data'    => $this->serializeClient($updated),
        ]);
    }

    // ── SDK Tokens ────────────────────────────────────────────────────────

    /**
     * Issue a new SDK token for a client.
     * The raw_token is returned ONCE — it cannot be retrieved again.
     * Body: { scopes?, environment?, label?, expires_days?, actor_id }
     */
    public function issueToken(IntegrationClient $client, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scopes'       => ['nullable', 'array'],
            'scopes.*'     => ['string'],
            'environment'  => ['nullable', 'in:sandbox,production'],
            'label'        => ['nullable', 'string', 'max:255'],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'actor_id'     => ['required', 'uuid'],
        ]);

        $result = $this->service->issueToken($client, $validated, $validated['actor_id']);

        return response()->json([
            'message'   => __('api.token_issued_once'),
            'raw_token' => $result['raw_token'], // Only time the raw token is exposed
            'token'     => $this->serializeToken($result['token']),
        ], 201);
    }

    /**
     * Revoke an SDK token.
     * Body: { actor_id }
     */
    public function revokeToken(SdkToken $token, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        $this->service->revokeToken($token, $validated['actor_id']);

        return response()->json(['message' => __('api.token_revoked')]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────

    /**
     * Webhook delivery statistics.
     */
    public function webhookStats(): JsonResponse
    {
        return response()->json(['data' => $this->service->webhookStats()]);
    }

    /**
     * Dashboard summary statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboardStats()]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function serializeClient(IntegrationClient $c): array
    {
        return [
            'id'            => $c->id,
            'client_id'     => $c->client_id,
            // client_secret is NEVER returned after creation
            'name'          => $c->name,
            'description'   => $c->description,
            'status'        => $c->status,
            'environment'   => $c->environment,
            'scopes'        => $c->scopes,
            'facility_id'   => $c->facility_id,
            'contact_email' => $c->contact_email,
            'approved_at'   => $c->approved_at?->toISOString(),
            'approved_by'   => $c->approved_by,
            'created_by'    => $c->created_by,
            'created_at'    => $c->created_at?->toISOString(),
        ];
    }

    private function serializeToken(SdkToken $t): array
    {
        return [
            'id'           => $t->id,
            'client_id'    => $t->client_id,
            'token_prefix' => $t->token_prefix, // e.g. "ocsdk_abc123" (first 12 chars)
            // token_hash is NEVER exposed
            'scopes'       => $t->scopes,
            'environment'  => $t->environment,
            'label'        => $t->label,
            'is_active'    => $t->is_active,
            'expires_at'   => $t->expires_at?->toISOString(),
            'created_at'   => $t->created_at?->toISOString(),
        ];
    }
}
