<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\DeveloperAccount;
use App\Models\IntegrationClient;
use App\Models\IntegrationCertification;
use App\Models\ProductionAccessRequest;
use App\Models\ApiUsageSnapshot;
use App\Models\SdkToken;
use App\Models\WebhookSubscription;
use App\Models\WebhookDeliveryLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DeveloperPortalController
 *
 * Manages the external-developer-facing portal at /portals/developer/.
 * Developers registered via /signup/developer land here after verification.
 *
 * Security:
 *  - All routes require the developer session (auth_email present).
 *  - Developers can only see and manage their own apps/keys.
 *  - Production access requires a separate admin-reviewed request.
 *  - API keys are shown once at creation; only the hash is stored.
 */
class DeveloperPortalController extends Controller
{
    // ── Demo helpers (inline, per project convention) ─────────────────────────

    private function demoActorId(): string
    {
        return session('auth_email')
            ?? (auth()->check() ? auth()->user()->email : 'developer-demo');
    }

    private function currentDeveloper(): ?DeveloperAccount
    {
        $email = session('auth_email') ?? (auth()->check() ? auth()->user()->email : null);
        if (!$email) {
            return null;
        }
        return DeveloperAccount::where('email', $email)->first();
    }

    // ── Developer Onboarding ──────────────────────────────────────────────────

    public function onboard(Request $request)
    {
        // If already onboarded, redirect to dashboard
        if ($this->currentDeveloper()) {
            return redirect()->route('portals.developer.dashboard');
        }

        return view('portals.developer.onboard', [
            'email' => session('auth_email'),
        ]);
    }

    public function onboardStore(Request $request)
    {
        $email = session('auth_email');
        if (!$email) {
            return redirect()->route('portals.developer.dashboard');
        }

        // Prevent duplicate onboarding
        if (DeveloperAccount::where('email', $email)->exists()) {
            return redirect()->route('portals.developer.dashboard');
        }

        $request->validate([
            'display_name'  => ['required', 'string', 'max:100'],
            'company_name'  => ['nullable', 'string', 'max:150'],
            'website_url'   => ['nullable', 'url', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
        ]);

        $account = DeveloperAccount::create([
            'email'                   => $email,
            'display_name'            => $request->input('display_name'),
            'company_name'            => $request->input('company_name'),
            'website_url'             => $request->input('website_url'),
            'status'                  => 'active',
            'api_terms_accepted'      => true,
            'api_terms_accepted_at'   => now(),
            'api_terms_version'       => '1.0',
            'sandbox_only'            => true,
            'email_verified_at'       => now(),  // session-auth implies verified
        ]);

        AuditLogger::log($request, 'developer_account_created', 'developer_account', $account->id);

        return redirect()->route('portals.developer.dashboard')
            ->with('success', __('flash.developer_welcome'));
    }

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $developer = $this->currentDeveloper();

        // If developer account doesn't exist for session email, show onboarding prompt
        if (!$developer) {
            return view('portals.developer.onboard', [
                'email' => session('auth_email'),
            ]);
        }

        $clients = IntegrationClient::where('created_by', $developer->email)
            ->orderByDesc('created_at')
            ->get();

        $productionRequests = ProductionAccessRequest::where('developer_account_id', $developer->id)
            ->orderByDesc('created_at')
            ->get();

        // 30-day usage across all this developer's clients
        $totalRequests = 0;
        $totalErrors   = 0;
        foreach ($clients as $client) {
            $summary       = ApiUsageSnapshot::summaryForClient($client->id, 30);
            $totalRequests += collect($summary)->sum('total_requests');
            $totalErrors   += collect($summary)->sum('total_errors');
        }

        return view('portals.developer.dashboard', compact(
            'developer',
            'clients',
            'productionRequests',
            'totalRequests',
            'totalErrors'
        ));
    }

    // ── Apps (Integration Clients) ─────────────────────────────────────────────

    public function apps(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $clients = IntegrationClient::where('created_by', $developer->email)
            ->withCount([])
            ->orderByDesc('created_at')
            ->get();

        return view('portals.developer.apps', compact('developer', 'clients'));
    }

    public function createApp(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer || !$developer->isActive()) {
            return redirect()->route('portals.developer.dashboard')
                ->with('error', __('flash.developer_account_must_be_active_apps'));
        }

        return view('portals.developer.create_app', compact('developer'));
    }

    public function storeApp(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer || !$developer->isActive()) {
            return redirect()->route('portals.developer.dashboard')
                ->with('error', __('flash.developer_account_must_be_active'));
        }

        $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:255'],
        ]);

        // Generate sandbox client credentials
        $clientId     = 'sandbox_' . Str::random(24);
        $clientSecret = 'sk_sandbox_' . Str::random(48);

        // Argon2id is the primary verifier; the legacy SHA-256 column is NOT NULL,
        // so populate it in sync with the same secret (facility_id is now nullable
        // for developer-owned apps).
        $client = IntegrationClient::create([
            'client_id'           => $clientId,
            'client_secret'       => hash('sha256', $clientSecret),
            'client_secret_argon' => \Illuminate\Support\Facades\Hash::make($clientSecret),
            'secret_upgraded_at'  => now(),
            'name'                => $request->input('name'),
            'description'         => $request->input('description'),
            'contact_email'       => $developer->email,
            'created_by'          => $developer->email,
            'environment'         => 'sandbox',
            'scopes'              => json_encode(['health_id:read', 'consents:read', 'patients:read']),
            'status'              => 'active',
        ]);

        AuditLogger::log($request, 'developer_app_created', 'integration_client', $client->id);

        // Show the secret ONCE — store the plain secret in flash so the view can display it
        return redirect()->route('portals.developer.apps.show', $client->id)
            ->with('success', __('flash.developer_app_created'))
            ->with('new_client_secret', $clientSecret)  // shown once in view
            ->with('new_client_id', $clientId);
    }

    public function showApp(Request $request, string $clientId)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $client = IntegrationClient::where('id', $clientId)
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $tokens = SdkToken::where('client_id', $client->client_id)
            ->whereNull('revoked_at')
            ->orderByDesc('created_at')
            ->get();

        $webhooks = WebhookSubscription::where('client_id', $client->client_id)
            ->orderByDesc('created_at')
            ->get();

        // 30-day usage for this app
        $usageSummary = ApiUsageSnapshot::summaryForClient($client->client_id, 30);
        $usageTrend   = ApiUsageSnapshot::dailyTrendForClient($client->client_id, 30);

        // integration_certifications.integration_client_id is a UUID FK to
        // integration_clients.id — match the UUID PK, not the string client_id.
        $certification = IntegrationCertification::where('integration_client_id', $client->id)
            ->with('badge')
            ->latest()
            ->first();

        // Metered plan + current-month usage + recent invoices (billing).
        $plan         = $client->apiPlan();
        $monthlyUsage = $client->monthlyUsageCount();
        $invoices     = \App\Models\ApiInvoice::where('client_id', $client->client_id)
            ->orderByDesc('period_start')
            ->limit(6)
            ->get();

        return view('portals.developer.app_detail', compact(
            'developer',
            'client',
            'tokens',
            'webhooks',
            'usageSummary',
            'usageTrend',
            'certification',
            'plan',
            'monthlyUsage',
            'invoices'
        ));
    }

    /** Pay an outstanding API invoice via MTN MoMo / Orange Money. */
    public function payInvoice(Request $request, string $clientId, \App\Services\ApiBilling\ApiBillingService $billing)
    {
        $developer = $this->currentDeveloper();
        if (! $developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $client = IntegrationClient::where('id', $clientId)
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'provider'   => ['required', 'in:mtn_momo,orange_money'],
            'phone'      => ['required', 'string', 'max:20'],
        ]);

        $invoice = \App\Models\ApiInvoice::where('id', $validated['invoice_id'])
            ->where('client_id', $client->client_id)
            ->firstOrFail();

        if (! \App\Support\Payments::onlineAvailable()) {
            return redirect()->route('portals.developer.apps.show', $client->id)
                ->with('error', __('public.developer_portal.pay_offline_note'));
        }

        $result = $billing->payInvoice($invoice, $validated['provider'], $validated['phone']);

        return redirect()
            ->route('portals.developer.apps.show', $client->id)
            ->with(($result['success'] ?? false) ? 'success' : 'error',
                ($result['success'] ?? false)
                    ? __('public.developer_portal.pay_initiated', [], app()->getLocale()) ?: 'Payment request sent — approve it on your phone.'
                    : (__('public.developer_portal.pay_failed', [], app()->getLocale()) ?: 'Payment could not be initiated. Please try again.'));
    }

    /** Rotate an app's client secret — Argon2id hash stored, plaintext shown once. */
    public function rotateSecret(Request $request, string $clientId)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $client = IntegrationClient::where('id', $clientId)
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $newSecret = ($client->environment === 'production' ? 'sk_live_' : 'sk_sandbox_') . Str::random(48);

        $client->update([
            // client_secret is NOT NULL; keep the legacy SHA-256 column in sync with
            // the new secret (Argon2id remains the primary verifier).
            'client_secret'       => hash('sha256', $newSecret),
            'client_secret_argon' => \Illuminate\Support\Facades\Hash::make($newSecret),
            'secret_upgraded_at'  => now(),
        ]);

        AuditLogger::log($request, 'developer_app_secret_rotated', 'integration_client', $client->id);

        return redirect()->route('portals.developer.apps.show', $client->id)
            ->with('success', __('flash.developer_secret_rotated'))
            ->with('new_client_secret', $newSecret);
    }

    /** Enable/disable an app (toggle status active ↔ disabled). */
    public function toggleApp(Request $request, string $clientId)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $client = IntegrationClient::where('id', $clientId)
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $disabling = $client->status === 'active';
        $client->update(['status' => $disabling ? 'disabled' : 'active']);

        AuditLogger::log($request, $disabling ? 'developer_app_disabled' : 'developer_app_enabled', 'integration_client', $client->id);

        return redirect()->route('portals.developer.apps.show', $client->id)
            ->with('success', __($disabling ? 'flash.developer_app_disabled' : 'flash.developer_app_enabled'));
    }

    // ── Production Access Requests ─────────────────────────────────────────────

    public function productionRequests(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $requests = ProductionAccessRequest::where('developer_account_id', $developer->id)
            ->orderByDesc('created_at')
            ->get();

        return view('portals.developer.production_requests', compact('developer', 'requests'));
    }

    public function createProductionRequest(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer || !$developer->isActive()) {
            return redirect()->route('portals.developer.dashboard')
                ->with('error', __('flash.developer_account_must_be_active'));
        }

        $clients = IntegrationClient::where('created_by', $developer->email)
            ->where('environment', 'sandbox')
            ->get();

        $scopeOptions = [
            'health_id:read',
            'health_id:verify',
            'patients:read',
            'patients:write',
            'consents:read',
            'consents:write',
            'labs:read',
            'labs:write',
            'prescriptions:read',
            'prescriptions:write',
            'documents:read',
            'documents:write',
            'appointments:read',
            'appointments:write',
            'pharmacy:read',
            'pharmacy:write',
            'blood:read',
            'insurance:read',
            'public_health:read',
            'webhooks:manage',
            // FHIR R4 ingest (routes/fhir.php). Without these a partner (e.g.
            // OPES Triage) could never request the scopes the ingest routes
            // check, so no self-service app could ever reach /api/fhir/R4.
            \App\Http\Controllers\Api\Fhir\FhirIngestController::SCOPE_OBSERVATION,
            \App\Http\Controllers\Api\Fhir\FhirIngestController::SCOPE_DIAGNOSTIC_REPORT,
            \App\Http\Controllers\Api\Fhir\FhirIngestController::SCOPE_IMMUNIZATION,
        ];

        return view('portals.developer.create_production_request', compact(
            'developer',
            'clients',
            'scopeOptions'
        ));
    }

    public function storeProductionRequest(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer || !$developer->isActive()) {
            return redirect()->route('portals.developer.dashboard')
                ->with('error', __('flash.developer_account_must_be_active'));
        }

        $request->validate([
            'integration_client_id'    => ['required', 'string'],
            'use_case'                 => ['required', 'string', 'max:255'],
            'technical_description'    => ['required', 'string', 'min:50'],
            'requested_scopes'         => ['required', 'array', 'min:1'],
            'estimated_daily_requests' => ['nullable', 'string', 'max:50'],
            'handles_patient_data'     => ['boolean'],
            'data_residency_region'    => ['nullable', 'string', 'max:100'],
            'security_review_done'     => ['boolean'],
            'terms_accepted'           => ['required', 'accepted'],
        ]);

        // Ensure the client belongs to this developer
        $client = IntegrationClient::where('client_id', $request->input('integration_client_id'))
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $prodRequest = ProductionAccessRequest::create([
            'developer_account_id'     => $developer->id,
            'integration_client_id'    => $client->client_id,
            'use_case'                 => $request->input('use_case'),
            'technical_description'    => $request->input('technical_description'),
            'requested_scopes'         => $request->input('requested_scopes', []),
            'estimated_daily_requests' => $request->input('estimated_daily_requests'),
            'handles_patient_data'     => $request->boolean('handles_patient_data'),
            'data_residency_region'    => $request->input('data_residency_region'),
            'security_review_done'     => $request->boolean('security_review_done'),
            'terms_accepted'           => true,
            'terms_version'            => '1.0',
            'status'                   => 'pending',
        ]);

        AuditLogger::log($request, 'production_access_requested', 'production_access_request', $prodRequest->id);

        return redirect()->route('portals.developer.production_requests')
            ->with('success', __('flash.developer_prod_access_submitted'));
    }

    // ── Webhook Delivery Logs ─────────────────────────────────────────────────

    public function webhookDeliveries(Request $request, string $clientId)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        $client = IntegrationClient::where('id', $clientId)
            ->where('created_by', $developer->email)
            ->firstOrFail();

        $subscriptionIds = WebhookSubscription::where('client_id', $client->client_id)
            ->pluck('id');

        $deliveries = WebhookDeliveryLog::whereIn('webhook_subscription_id', $subscriptionIds)
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('portals.developer.webhook_deliveries', compact('developer', 'client', 'deliveries'));
    }

    // ── Admin: Production Request Review ─────────────────────────────────────

    public function adminProductionRequests(Request $request)
    {
        $query = ProductionAccessRequest::with('developerAccount')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(25);

        $stats = [
            'pending'      => ProductionAccessRequest::where('status', 'pending')->count(),
            'under_review' => ProductionAccessRequest::where('status', 'under_review')->count(),
            'approved'     => ProductionAccessRequest::where('status', 'approved')->count(),
            'rejected'     => ProductionAccessRequest::where('status', 'rejected')->count(),
        ];

        return view('portals.admin.developer.production_requests', compact('requests', 'stats'));
    }

    public function adminApproveProductionRequest(Request $request, ProductionAccessRequest $prodRequest)
    {
        $request->validate([
            'review_notes'      => ['nullable', 'string', 'max:1000'],
            'approved_scopes'   => ['nullable', 'array'],
            'approved_scopes.*' => ['string', 'max:100'],
            // D-5 resolution: a production app is ALWAYS facility-bound. The admin
            // names the facility the app acts for — exact name, no search.
            'facility_name'     => ['required', 'string', 'max:255'],
        ]);

        $actor = $this->demoActorId();

        // Exact-name resolution among facilities. Ambiguity (two facilities with
        // the same name) is refused rather than guessed.
        $matches = \App\Models\Facility::where('name', trim($request->input('facility_name')))->limit(2)->get();
        if ($matches->count() !== 1) {
            return back()->withInput()->with('error', $matches->isEmpty()
                ? __('developer_ext.flash_approve_facility_not_found')
                : __('developer_ext.flash_approve_facility_ambiguous'));
        }
        $facility = $matches->first();

        // Default: approve with the exact scopes the developer requested. The
        // previous version read approved_scopes a second time for the client row
        // and wrote json_encode(null) — the literal string "null" — into scopes
        // whenever the admin form omitted it (docs/DEVELOPER_DASHBOARD_AUDIT.md).
        $approvedScopes = array_values(array_filter(
            (array) $request->input('approved_scopes', []),
            fn ($s) => is_string($s) && $s !== ''
        ));
        if ($approvedScopes === []) {
            $approvedScopes = array_values((array) ($prodRequest->requested_scopes ?? []));
        }

        $prodRequest->approve(
            $actor,
            $approvedScopes,
            $request->input('review_notes')
        );

        // Promote the integration_client to production AND bind it to the
        // facility. The token endpoint copies integration_clients.facility_id
        // into the JWT, and FhirIngestGuard::facilityId() reads it from there —
        // this is what stops `no_facility_in_token`.
        if ($prodRequest->integration_client_id) {
            IntegrationClient::where('client_id', $prodRequest->integration_client_id)
                ->update([
                    'environment'  => 'production',
                    'scopes'       => json_encode($approvedScopes),
                    'facility_id'  => $facility->id,
                    'approved_at'  => now(),
                    'approved_by'  => $actor,
                ]);

            // Webhook subscriptions registered while the app was facility-less
            // follow the app into its facility, so facility-scoped events reach them.
            WebhookSubscription::where('client_id', $prodRequest->integration_client_id)
                ->whereNull('facility_id')
                ->update(['facility_id' => $facility->id]);
        }

        AuditLogger::log($request, 'production_access_approved', 'production_access_request', $prodRequest->id, afterState: [
            'facility_id'     => $facility->id,
            'approved_scopes' => $approvedScopes,
        ]);

        return back()->with('success', __('flash.developer_prod_access_approved'));
    }

    public function adminRejectProductionRequest(Request $request, ProductionAccessRequest $prodRequest)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $prodRequest->reject($this->demoActorId(), $request->input('reason'));

        AuditLogger::log($request, 'production_access_rejected', 'production_access_request', $prodRequest->id);

        return back()->with('success', __('flash.developer_request_rejected'));
    }

    public function adminDeveloperAccounts(Request $request)
    {
        $accounts = DeveloperAccount::withCount([])
            ->orderByDesc('created_at')
            ->paginate(30);

        $stats = [
            'total'                => DeveloperAccount::count(),
            'active'               => DeveloperAccount::where('status', 'active')->count(),
            'sandbox_only'         => DeveloperAccount::where('sandbox_only', true)->count(),
            'suspended'            => DeveloperAccount::where('status', 'suspended')->count(),
        ];

        return view('portals.admin.developer.accounts', compact('accounts', 'stats'));
    }

    public function adminSuspendDeveloper(Request $request, DeveloperAccount $account)
    {
        $request->validate([
            'suspend_reason' => ['required', 'string', 'max:500'],
        ]);

        $account->suspend($request->input('suspend_reason'), $this->demoActorId());

        AuditLogger::log($request, 'developer_account_suspended', 'developer_account', $account->id);

        return back()->with('success', __('flash.developer_account_suspended'));
    }

    // ── API Usage Analytics ───────────────────────────────────────────────────

    public function analytics(Request $request)
    {
        $developer = $this->currentDeveloper();
        if (!$developer) {
            return redirect()->route('portals.developer.dashboard');
        }

        // Every number on this page comes from api_usage_logs — the one usage
        // table this platform actually writes (LogApiUsage, attributed from the
        // bearer token). api_usage_metrics / api_credentials / api_usage_snapshots
        // are written by nothing and are not read here any more (D-7).
        $clients = IntegrationClient::where('created_by', $developer->email)
            ->orderByDesc('created_at')
            ->get();

        $selected = null;
        if (($app = $request->query('app')) !== null && $app !== '') {
            $selected = $clients->firstWhere('id', (string) $app) ?? abort(404);
        }

        $clientIds = ($selected ? collect([$selected]) : $clients)->pluck('client_id')->all();
        $since     = now()->subDays(30)->startOfDay();

        $base = fn () => \App\Models\ApiUsageLog::query()
            ->whereIn('integration_client_id', $clientIds ?: ['__none__'])
            ->where('logged_at', '>=', $since);

        // Requests per day, last 30 days — every day present, zero when silent.
        $perDayRows = $base()
            ->selectRaw("TO_CHAR(logged_at, 'YYYY-MM-DD') AS day, COUNT(*) AS requests")
            ->groupBy('day')
            ->pluck('requests', 'day');
        $perDayErr = $base()
            ->selectRaw("TO_CHAR(logged_at, 'YYYY-MM-DD') AS day, SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) AS errors")
            ->groupBy('day')
            ->pluck('errors', 'day');

        $perDay = [];
        for ($d = $since->copy(); $d <= now()->endOfDay(); $d = $d->addDay()) {
            $key = $d->format('Y-m-d');
            $perDay[] = [
                'day'      => $key,
                'requests' => (int) ($perDayRows[$key] ?? 0),
                'errors'   => (int) ($perDayErr[$key] ?? 0),
            ];
        }

        $totalsRow = $base()
            ->selectRaw("COUNT(*) AS requests, SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) AS errors, SUM(CASE WHEN response_status >= 500 THEN 1 ELSE 0 END) AS server_errors")
            ->first();

        // p50 / p95 latency — Postgres percentile_cont over response_time_ms.
        $latency = $base()
            ->whereNotNull('response_time_ms')
            ->selectRaw('percentile_cont(0.5) WITHIN GROUP (ORDER BY response_time_ms) AS p50, percentile_cont(0.95) WITHIN GROUP (ORDER BY response_time_ms) AS p95')
            ->first();

        $totals = [
            'requests'      => (int) ($totalsRow->requests ?? 0),
            'errors'        => (int) ($totalsRow->errors ?? 0),
            'server_errors' => (int) ($totalsRow->server_errors ?? 0),
            'p50_ms'        => $latency && $latency->p50 !== null ? (int) round((float) $latency->p50) : null,
            'p95_ms'        => $latency && $latency->p95 !== null ? (int) round((float) $latency->p95) : null,
        ];
        $totals['error_rate'] = $totals['requests'] > 0 ? round($totals['errors'] / $totals['requests'] * 100, 1) : null;

        $byEndpoint = $base()
            ->selectRaw("method, endpoint, COUNT(*) AS requests, SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) AS errors, AVG(response_time_ms) AS avg_ms")
            ->groupBy('method', 'endpoint')
            ->orderByDesc('requests')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'method'   => $r->method,
                'endpoint' => $r->endpoint,
                'requests' => (int) $r->requests,
                'errors'   => (int) $r->errors,
                'avg_ms'   => $r->avg_ms !== null ? (int) round((float) $r->avg_ms) : null,
            ]);

        $byApp = $base()
            ->selectRaw('integration_client_id, COUNT(*) AS requests')
            ->groupBy('integration_client_id')
            ->pluck('requests', 'integration_client_id');

        return view('portals.developer.analytics', [
            'developer'  => $developer,
            'clients'    => $clients,
            'selected'   => $selected,
            'since'      => $since,
            'perDay'     => $perDay,
            'maxPerDay'  => max(1, max(array_column($perDay, 'requests') ?: [0])),
            'totals'     => $totals,
            'byEndpoint' => $byEndpoint,
            'byApp'      => $byApp,
        ]);
    }
}
