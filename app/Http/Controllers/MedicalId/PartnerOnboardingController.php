<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Support\ClientSecretHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PartnerOnboardingController — the desk that hands out API credentials.
 *
 * `integration_clients` is the table every external system authenticates
 * against: the Connect token endpoint reads it, the bearer middleware trusts
 * what it signed from it, and the FHIR/ingest surface is gated on the scopes
 * stored in it. On production it holds ZERO rows, and until this screen there
 * was no way in the product for a platform administrator to add one. The
 * integration API was, in the strict sense, usable by nobody.
 *
 * WHAT ALREADY EXISTED, AND WHY THIS IS NOT A RIVAL TO IT
 * ------------------------------------------------------
 * A developer self-service portal exists (`/developer/*`,
 * DeveloperPortalController): an external developer registers a
 * DeveloperAccount, creates a SANDBOX app, and later files a
 * ProductionAccessRequest that a platform admin approves at
 * /admin/developer/production-requests. That flow is real and stays.
 *
 * What it does not give the platform is a way to see or act on the clients
 * themselves. It only ever shows a developer their OWN rows
 * (`where created_by = <their email>`), and the only admin screens are the
 * production-request queue and the developer-account list. Nothing anywhere
 * could:
 *   - see every integration client on the platform,
 *   - onboard a partner who is an ORGANISATION rather than a self-service
 *     developer (a hospital's HIS, a lab's LIS, the bridge agent),
 *   - choose the scopes a client is granted at the moment it is created,
 *   - revoke one.
 *
 * So this desk is the admin half of the same story, over the same table.
 * It deliberately duplicates nothing: it does not approve production
 * requests (that queue keeps its screen) and it does not create developer
 * accounts.
 *
 * SECURITY DECISIONS, EACH ONE DELIBERATE
 * ---------------------------------------
 * 1. THE SECRET IS NEVER STORED. Only an Argon2id hash is written, into
 *    `client_secret_argon`, which is the column both AuthController and
 *    VerifyIntegrationClient check FIRST. The legacy `client_secret` column
 *    is NOT NULL and is a bare SHA-256 hash with no work factor, so a client
 *    born on this screen never gets one: the column is filled with the
 *    non-hex sentinel below, which cannot equal hash('sha256', anything) and
 *    therefore cannot authenticate. The legacy path is closed by
 *    construction, not by convention.
 *
 * 2. THE SECRET IS SHOWN EXACTLY ONCE, in the response to the POST that
 *    created or rotated it, through a one-request flash. It is never written
 *    to a log, never placed in a URL, and there is no route that can read it
 *    back — because after this request nothing on the platform holds it. An
 *    administrator who loses it rotates.
 *
 * 3. SCOPES ARE EXPLICIT AND LEAST-PRIVILEGE. There is no default. The form
 *    requires at least one, every value must be a key of
 *    IntegrationClient::availableScopes(), and '*' is rejected by that same
 *    check — a wildcard is not a scope this desk can spell.
 *
 * 4. FACILITY_ID IS NOT TAKEN FROM USER INPUT. The form's organisation
 *    picker submits an id which is used for exactly one thing: to LOOK UP a
 *    Facility row. What is stored is `$facility->id` read back off that
 *    model. It is never consulted to decide what this administrator may see
 *    or do — that is the platform tier, and the audit trail is stamped with
 *    the facility the auth middleware put on the request attributes.
 *
 * 5. REVOCATION BITES IMMEDIATELY. See VerifyBearerToken: a token whose
 *    client is no longer `active` is refused at the middleware, so revoking
 *    here kills tokens already in the wild, not merely future ones.
 *
 * NO INVENTED DATA. Zero clients is a real and expected state and the view
 * says so in words; it is not an error and not an empty grid.
 */
class PartnerOnboardingController extends Controller
{
    /**
     * Written into the NOT NULL legacy `client_secret` column for every client
     * this desk creates. Not a hash of anything: it is not valid hex, so the
     * SHA-256 fallback comparison in AuthController/VerifyIntegrationClient
     * can never match it for any input. Argon2id is the only way in.
     */
    private const LEGACY_SHA256_DISABLED = 'disabled:argon2id-only';

    /** Statuses this desk will set. Matches the base migration's comment. */
    private const STATUSES = ['active', 'suspended', 'revoked'];

    /**
     * Hash a client secret with Argon2id, explicitly.
     *
     * `Hash::make()` uses the DEFAULT hash driver, and this application ships
     * no config/hashing.php — so the default is bcrypt. Every existing caller
     * that writes `client_secret_argon` (the developer portal, and the rolling
     * upgrade inside AuthController and VerifyIntegrationClient) therefore
     * writes a `$2y$` bcrypt hash into a column named "argon". The column name
     * has been a claim, not a fact.
     *
     * Naming the driver makes it a fact for everything this desk issues.
     * Verification is unaffected: the bcrypt driver's check() calls
     * password_verify(), which reads the algorithm off the hash prefix, so
     * AuthController and VerifyIntegrationClient authenticate an Argon2id
     * secret without a line of change.
     */
    private function hashSecret(string $secret): string
    {
        return ClientSecretHasher::make($secret);
    }

    // ── Listing ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->guard();

        $query = IntegrationClient::query();

        $status = $request->string('status')->toString();
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $environment = $request->string('environment')->toString();
        if (in_array($environment, ['sandbox', 'production'], true)) {
            $query->where('environment', $environment);
        }

        $clients = $query
            // Anything not active sorts first: a suspended partner is a state
            // someone has to resolve, not a row to scroll past.
            ->orderByRaw("case when status = 'active' then 1 else 0 end")
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $facilities = $this->facilityNames(
            collect($clients->items())->pluck('facility_id')->filter()->all()
        );

        $counts = IntegrationClient::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('portals.admin.partners.index', [
            'clients'     => $clients,
            'facilities'  => $facilities,
            'counts'      => $counts,
            'total'       => IntegrationClient::count(),
            'status'      => $status,
            'environment' => $environment,
            'statuses'    => self::STATUSES,
        ]);
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $this->guard();

        return view('portals.admin.partners.create', [
            'scopes'     => IntegrationClient::availableScopes(),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'description'     => ['nullable', 'string', 'max:500'],
            'contact_email'   => ['required', 'email', 'max:190'],
            'environment'     => ['required', 'in:sandbox,production'],
            // The organisation this client speaks for. Used ONLY to look up a
            // Facility; the stored value is read back off the model.
            'organisation_id' => ['nullable', 'uuid'],
            // No default, at least one, and every value must be a key of the
            // published catalogue — which is how '*' gets refused.
            'scopes'          => ['required', 'array', 'min:1'],
            'scopes.*'        => ['string', 'in:' . implode(',', array_keys(IntegrationClient::availableScopes()))],
        ]);

        $facility = $data['organisation_id'] ?? null
            ? Facility::find($data['organisation_id'])
            : null;

        if (($data['organisation_id'] ?? null) && ! $facility) {
            return back()->withInput()->withErrors([
                'organisation_id' => __('partners.err_unknown_organisation'),
            ]);
        }

        $prefix   = $data['environment'] === 'production' ? 'live' : 'sandbox';
        $clientId = $prefix . '_' . Str::random(24);
        $secret   = 'sk_' . $prefix . '_' . Str::random(48);

        $client = IntegrationClient::create([
            'client_id'           => $clientId,
            'client_secret'       => self::LEGACY_SHA256_DISABLED,
            'client_secret_argon' => $this->hashSecret($secret),
            'secret_upgraded_at'  => now(),
            'name'                => $data['name'],
            'description'         => $data['description'] ?? null,
            'contact_email'       => $data['contact_email'],
            // Never the raw input — the id as the database itself holds it.
            'facility_id'         => $facility?->id,
            'created_by'          => (string) (auth()->id() ?? ''),
            'environment'         => $data['environment'],
            'scopes'              => array_values($data['scopes']),
            'status'              => 'active',
            'approved_at'         => now(),
            'approved_by'         => (string) (auth()->id() ?? ''),
        ]);

        $this->audit($request, 'partner_client_created', $client, [
            'environment' => $client->environment,
            'scopes'      => $client->scopes,
            'facility_id' => $client->facility_id,
        ]);

        // The plaintext leaves this method exactly once, in a one-request
        // flash consumed by the show view. Nothing persists it.
        return redirect()
            ->route('portals.admin.partners.show', $client->id)
            ->with('success', __('partners.flash_created'))
            ->with('new_client_secret', $secret);
    }

    // ── Detail ───────────────────────────────────────────────────────────

    public function show(Request $request, string $id)
    {
        $this->guard();

        $client = IntegrationClient::findOrFail($id);

        // What this client has actually done. `audit_events` records
        // machine-to-machine calls with actor_id null and the client id in
        // actor_role (that column is a uuid; a client id is not one), so the
        // trail is assembled from both shapes: rows ABOUT the client, and
        // rows BY the client.
        $trail = AuditEvent::query()
            ->where(function ($q) use ($client) {
                $q->where(function ($r) use ($client) {
                    $r->where('resource_type', 'integration_client')
                      ->where('resource_id', $client->id);
                })->orWhere('actor_role', 'integration_client:' . $client->client_id);
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $facility = $client->facility_id ? Facility::find($client->facility_id) : null;

        return view('portals.admin.partners.show', [
            'client'      => $client,
            'facility'    => $facility,
            'trail'       => $trail,
            'scopeLabels' => IntegrationClient::availableScopes(),
            // One request only. Present on the redirect that created or
            // rotated the secret, gone on refresh, and unreachable by any
            // other route because nothing stores it.
            'newSecret'   => session('new_client_secret'),
        ]);
    }

    // ── Credential lifecycle ─────────────────────────────────────────────

    /** Issue a new secret and invalidate the old one. The only cure for a lost secret. */
    public function rotate(Request $request, string $id)
    {
        $this->guard();

        $client = IntegrationClient::findOrFail($id);

        $prefix = $client->environment === 'production' ? 'live' : 'sandbox';
        $secret = 'sk_' . $prefix . '_' . Str::random(48);

        $client->update([
            'client_secret'       => self::LEGACY_SHA256_DISABLED,
            'client_secret_argon' => $this->hashSecret($secret),
            'secret_upgraded_at'  => now(),
        ]);

        $this->audit($request, 'partner_client_secret_rotated', $client, [
            'rotated_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('portals.admin.partners.show', $client->id)
            ->with('success', __('partners.flash_rotated'))
            ->with('new_client_secret', $secret);
    }

    /** Change scopes. Same rules as creation: explicit, catalogued, at least one. */
    public function scopes(Request $request, string $id)
    {
        $this->guard();

        $client = IntegrationClient::findOrFail($id);

        $data = $request->validate([
            'scopes'   => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:' . implode(',', array_keys(IntegrationClient::availableScopes()))],
        ]);

        $before = $client->scopes;
        $client->update(['scopes' => array_values($data['scopes'])]);

        $this->audit($request, 'partner_client_scopes_changed', $client, [
            'scopes' => $client->scopes,
        ], ['scopes' => $before]);

        return back()->with('success', __('partners.flash_scopes_changed'));
    }

    /**
     * Suspend, revoke, or restore.
     *
     * Suspended and revoked both stop authentication now — the token endpoint
     * only loads `status = active`, and VerifyBearerToken refuses a live token
     * whose client is no longer active. The difference is intent: suspended
     * is expected to come back, revoked is not.
     */
    public function status(Request $request, string $id)
    {
        $this->guard();

        $client = IntegrationClient::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'reason' => ['required_unless:status,active', 'nullable', 'string', 'max:500'],
        ]);

        $before = $client->status;
        $client->update(['status' => $data['status']]);

        $this->audit(
            $request,
            match ($data['status']) {
                'revoked'   => 'partner_client_revoked',
                'suspended' => 'partner_client_suspended',
                default     => 'partner_client_reinstated',
            },
            $client,
            ['status' => $client->status],
            ['status' => $before],
            $data['reason'] ?? null,
        );

        return back()->with('success', __('partners.flash_status_' . $data['status']));
    }

    // ── Internals ────────────────────────────────────────────────────────

    /**
     * Platform tier, and a refusal that says so.
     *
     * `platform.admin` already guards every /admin/* path, so this is the
     * second lock rather than the first — but it makes the refusal a 403 with
     * a named reason at the controller boundary too, so the answer can never
     * degrade into a 404 that tells a probing facility admin nothing and tells
     * an honest one something false.
     */
    private function guard(): void
    {
        if (! RequirePlatformAdmin::isPlatformTier(auth()->user())) {
            abort(403, __('errors.abort.platform_admin_only'));
        }
    }

    /**
     * Write the audit row.
     *
     * actor_id is a UUID column and has been broken twice by a string that was
     * not one, so the value is passed through Str::isUuid() and becomes null
     * if it is anything else. The human is identified in actor_role regardless.
     */
    private function audit(
        Request $request,
        string $action,
        IntegrationClient $client,
        array $after = [],
        array $before = [],
        ?string $reason = null,
    ): void {
        $actorId = auth()->id();
        $actorId = (is_string($actorId) && Str::isUuid($actorId)) ? $actorId : null;

        AuditEvent::create([
            'actor_id'      => $actorId,
            'actor_role'    => 'platform_admin',
            // Only ever the middleware's value. Never a header, never the
            // client's own facility, never a fallback.
            'facility_id'   => $request->attributes->get('facility_id'),
            'action_type'   => $action,
            'resource_type' => 'integration_client',
            'resource_id'   => $client->id,
            'source_system' => 'opescare_admin',
            'ip_address'    => $request->ip(),
            'reason'        => $reason,
            // The secret is not in $after and never will be.
            'before_state'  => $before,
            'after_state'   => array_merge(['client_id' => $client->client_id], $after),
            'created_at'    => now(),
        ]);
    }

    /** id => name for the facilities on this page. One query, no N+1. */
    private function facilityNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return DB::table('facilities')
            ->whereIn('id', array_unique($ids))
            ->pluck('name', 'id')
            ->all();
    }
}
