<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Api\Fhir\FhirIngestController;
use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\DeveloperAccount;
use App\Models\FhirIngestEvent;
use App\Models\IntegrationClient;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookSubscription;
use App\Services\AuditLogger;
use App\Support\Fhir\FhirOutcome;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Developer portal — the pages a partner needs AFTER it has credentials.
 *
 * Built from docs/DEVELOPER_DASHBOARD_AUDIT.md (D-1 … D-6):
 *   - /developer/webhooks    register an endpoint, pause/resume it, recent deliveries
 *   - /developer/activity    the partner's own fhir_ingest_events + api_usage_logs
 *   - /developer/quickstart  a first call with the partner's own sandbox client_id
 *
 * Scope rule, applied on every read: a client is visible only when
 * integration_clients.created_by is the signed-in developer's e-mail. An id
 * from the query string that belongs to anyone else is a 404, never a 403 —
 * the portal must not confirm that a foreign id exists.
 */
class DeveloperExtController extends Controller
{
    /** The only event types SendWebhookJob will ever be asked to deliver (docs/webhooks). */
    public const EVENT_TYPES = ['consent.granted', 'lab_result.released', 'prescription.issued'];

    /** refusal_code (fhir_ingest_events) → FHIR OperationOutcome issue.code, as FhirOutcome renders it. */
    private const ISSUE_CODES = [
        'unknown_patient'          => 'not-found',
        'consent_required'         => 'forbidden',
        'insufficient_scope'       => 'forbidden',
        'no_facility_in_token'     => 'security',
        'idempotency_key_required' => 'required',
        'idempotency_in_flight'    => 'conflict',
        'idempotency_conflict'     => 'conflict',
        'body_missing'             => 'structure',
        'invalid_code'             => 'code-invalid',
        'business_rule'            => 'business-rule',
    ];

    private function currentDeveloper(): ?DeveloperAccount
    {
        $email = auth()->user()?->email;

        return $email ? DeveloperAccount::where('email', $email)->first() : null;
    }

    /** Every app this developer owns, newest first. */
    private function ownClients(DeveloperAccount $developer): Collection
    {
        return IntegrationClient::where('created_by', $developer->email)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Resolve `?app=` (an integration_clients.id) against the developer's own
     * apps. Absent → null (all apps). Present but not owned → 404.
     */
    private function selectedClient(Request $request, Collection $clients): ?IntegrationClient
    {
        $app = $request->query('app');
        if ($app === null || $app === '') {
            return null;
        }

        return $clients->firstWhere('id', (string) $app) ?? abort(404);
    }

    // ── Webhooks ───────────────────────────────────────────────────────────

    public function webhooks(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (! $developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $clients   = $this->ownClients($developer);
        $clientIds = $clients->pluck('client_id');

        $subscriptions = WebhookSubscription::whereIn('client_id', $clientIds)
            ->orderByDesc('created_at')
            ->get();

        $deliveries = WebhookDeliveryLog::whereIn('webhook_subscription_id', $subscriptions->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('portals.developer.webhooks', [
            'developer'     => $developer,
            'clients'       => $clients,
            'subscriptions' => $subscriptions,
            'deliveries'    => $deliveries,
            'eventTypes'    => self::EVENT_TYPES,
            'clientNames'   => $clients->pluck('name', 'client_id'),
        ]);
    }

    public function storeWebhook(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (! $developer || ! $developer->isActive()) {
            return redirect()->route('portals.developer.dashboard')
                ->with('error', __('flash.developer_account_must_be_active'));
        }

        $validated = $request->validate([
            'integration_client_id' => ['required', 'string', 'max:191'],
            'callback_url'          => ['required', 'url', 'starts_with:https://', 'max:255'],
            'events'                => ['required', 'array', 'min:1'],
            'events.*'              => ['string', 'in:' . implode(',', self::EVENT_TYPES)],
        ]);

        // Ownership: the chosen app must be this developer's. Anything else is a 404.
        $client = IntegrationClient::where('id', $validated['integration_client_id'])
            ->where('created_by', $developer->email)
            ->firstOrFail();

        // The signing secret. SendWebhookJob HMAC-signs every delivery with the
        // stored value, so the column holds it as-is; the portal shows it here,
        // once, and never again.
        $secret = 'whsec_' . Str::random(40);

        $subscription = WebhookSubscription::create([
            'client_id'         => $client->client_id,
            'facility_id'       => $client->facility_id,
            'callback_url'      => $validated['callback_url'],
            'webhook_secret'    => $secret,
            'subscribed_events' => array_values(array_unique($validated['events'])),
            'status'            => 'active',
        ]);

        AuditLogger::log($request, 'developer_webhook_registered', 'webhook_subscription', $subscription->id);

        return redirect()->route('portals.developer.webhooks')
            ->with('success', __('developer_ext.flash_webhook_registered'))
            ->with('new_webhook_secret', $secret)
            ->with('new_webhook_id', $subscription->id);
    }

    public function toggleWebhook(Request $request, string $subscription)
    {
        $developer = $this->currentDeveloper();
        if (! $developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $clientIds = $this->ownClients($developer)->pluck('client_id');

        $sub = WebhookSubscription::where('id', $subscription)
            ->whereIn('client_id', $clientIds)
            ->firstOrFail();

        $pausing = $sub->status === 'active';
        $sub->update(['status' => $pausing ? 'paused' : 'active']);

        AuditLogger::log($request, $pausing ? 'developer_webhook_paused' : 'developer_webhook_resumed', 'webhook_subscription', $sub->id);

        return redirect()->route('portals.developer.webhooks')
            ->with('success', __($pausing ? 'developer_ext.flash_webhook_paused' : 'developer_ext.flash_webhook_resumed'));
    }

    // ── Activity: what the partner's calls actually did ────────────────────

    public function activity(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (! $developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $clients  = $this->ownClients($developer);
        $selected = $this->selectedClient($request, $clients);
        $clientIds = $selected ? [$selected->client_id] : $clients->pluck('client_id')->all();

        $ingest = FhirIngestEvent::whereIn('integration_client_id', $clientIds)
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'ingest')
            ->withQueryString();

        $requests = ApiUsageLog::whereIn('integration_client_id', $clientIds)
            ->orderByDesc('logged_at')
            ->limit(50)
            ->get();

        $since = now()->subDays(30);
        $counts = [
            'accepted' => FhirIngestEvent::whereIn('integration_client_id', $clientIds)
                ->where('outcome', FhirIngestEvent::OUTCOME_ACCEPTED)->where('created_at', '>=', $since)->count(),
            'refused'  => FhirIngestEvent::whereIn('integration_client_id', $clientIds)
                ->where('outcome', FhirIngestEvent::OUTCOME_REFUSED)->where('created_at', '>=', $since)->count(),
            'requests' => ApiUsageLog::whereIn('integration_client_id', $clientIds)
                ->where('logged_at', '>=', $since)->count(),
            'errors'   => ApiUsageLog::whereIn('integration_client_id', $clientIds)
                ->where('logged_at', '>=', $since)->where('response_status', '>=', 400)->count(),
        ];

        return view('portals.developer.activity', [
            'developer'   => $developer,
            'clients'     => $clients,
            'selected'    => $selected,
            'ingest'      => $ingest,
            'requests'    => $requests,
            'counts'      => $counts,
            'clientNames' => $clients->pluck('name', 'client_id'),
            'outcomeFor'  => fn (FhirIngestEvent $e) => $this->operationOutcome($e),
        ]);
    }

    /** The OperationOutcome the partner was answered with, rebuilt from the audit row. */
    private function operationOutcome(FhirIngestEvent $event): ?string
    {
        if ($event->outcome !== FhirIngestEvent::OUTCOME_REFUSED) {
            return null;
        }

        $code = self::ISSUE_CODES[$event->refusal_code] ?? 'processing';

        return json_encode(
            FhirOutcome::make('error', $code, (string) $event->refusal_detail),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /** integration_clients.scopes is array-cast but the portal stores a JSON string into it (double-encoded); read both shapes. */
    public static function scopesOf(IntegrationClient $client): array
    {
        $scopes = $client->scopes;
        if (is_string($scopes)) {
            $scopes = json_decode($scopes, true);
        }

        return array_values(array_filter((array) $scopes, 'is_string'));
    }

    // ── Quickstart: a first call with the partner's own client ─────────────

    public function quickstart(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (! $developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $clients  = $this->ownClients($developer);
        $selected = $this->selectedClient($request, $clients)
            ?? $clients->firstWhere('status', 'active')
            ?? $clients->first();

        return view('portals.developer.quickstart', [
            'developer'   => $developer,
            'clients'     => $clients,
            'client'      => $selected,
            'tokenUrl'    => url('/api/v1/connect/auth/token'),
            'fhirBase'    => url('/api/fhir/R4'),
            'fhirScopes'  => [
                FhirIngestController::SCOPE_OBSERVATION,
                FhirIngestController::SCOPE_DIAGNOSTIC_REPORT,
                FhirIngestController::SCOPE_IMMUNIZATION,
            ],
            'clientScopes' => $selected ? self::scopesOf($selected) : [],
        ]);
    }
}
