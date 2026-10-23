@extends('layouts.docs')
@section('title', 'Connect API')
@section('head')
@include('public.partials._od_pages_css')
@endsection

@section('content')

<h1>Connect API</h1>
<p class="docs-lead">
    The Connect API is OpesCare's primary REST interface for healthcare system integration.
    Use OAuth 2.0 client credentials to authenticate, then access patient records, consent,
    inventory, and operational data.
</p>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>All Connect API endpoints require a Bearer token in the <code>Authorization</code> header. See <a href="{{ route('docs.authentication') }}">Authentication</a> for how to obtain one.</div>
</div>

<h2 id="base">Base URL</h2>
<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="text">URL</button></div>
    <div class="docs-code-pane active" data-lang="text"><pre>{{ $docsBase }}/api/v1/connect</pre></div>
</div>

<h2 id="auth-endpoint">Step 1 — Get a Token</h2>

<div class="endpoint-pill">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-path">/connect/auth/token</span>
</div>
<p>Issues an OAuth 2.0 Bearer token using <code>client_credentials</code> grant. See <a href="{{ route('docs.authentication') }}">Authentication</a> for full code examples and available scopes.</p>

<h2 id="health-id-resolution">Health ID Resolution <span class="badge-key">KEY ENDPOINT</span></h2>

<p>Find the OpesCare Health ID of the patient in front of you, by Health ID <em>or</em> by demographics. OpesCare never creates a patient through this endpoint: when nobody matches, the patient registers for a Health ID in the OpesCare app. Scope <code>health_id:read</code>; limited to 60 lookups per minute per app. A sandbox app sees only sandbox (demo) patients, a production app only real patients.</p>

<div class="endpoint-pill">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-path">/connect/patients/resolve</span>
</div>

<table class="docs-table">
    <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>health_id</code></td><td>string</td><td>Either/or</td><td>Known OpesCare Health ID (e.g. <code>CM-HID-7KQ9-MP42-X8D1</code>); a retired (merged) id resolves to its survivor</td></tr>
        <tr><td><code>first_name</code> + <code>last_name</code>, or <code>full_name</code></td><td>string</td><td>Either/or</td><td>Patient name (case and spacing ignored; a full name may be in either order)</td></tr>
        <tr><td><code>date_of_birth</code> (or <code>dob</code>)</td><td>string</td><td>With a name</td><td>ISO date <code>YYYY-MM-DD</code></td></tr>
        <tr><td><code>sex</code></td><td>string</td><td>No</td><td><code>male</code> / <code>female</code></td></tr>
        <tr><td><code>phone_number</code> (or <code>phone</code>)</td><td>string</td><td>No</td><td>Raises match confidence; never used alone</td></tr>
        <tr><td><code>purpose</code></td><td>string</td><td>Yes</td><td>Reason for the lookup, recorded in the audit trail</td></tr>
        <tr><td><code>consent_acknowledged</code></td><td>boolean</td><td>For demographics</td><td><code>true</code> once the patient has agreed to the lookup; without it a demographic lookup answers <code>422 CONSENT_REQUIRED</code> before any search</td></tr>
        <tr><td><code>external_reference</code></td><td>string</td><td>No</td><td>Your own patient id (accepted, not stored)</td></tr>
    </tbody>
</table>

<p><strong>Responses:</strong></p>
<ul>
    <li><code>200 found</code> — <code>{status, health_id, match: {confidence, method}, patient: {display_name (masked), sex, year_of_birth, country_code, verification_status, identity_status, active, verified}, next_action}</code></li>
    <li><code>404 HEALTH_ID_NOT_FOUND</code> / <code>404 PATIENT_NOT_FOUND</code> — nobody matches</li>
    <li><code>409 AMBIGUOUS_MATCH</code> — several people match; no Health ID is returned, ask the patient for their Health ID or phone</li>
    <li><code>422 MISSING_LOOKUP_CRITERIA</code>, <code>422 CONSENT_REQUIRED</code>, <code>422 INVALID_PAYLOAD</code></li>
</ul>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">cURL</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre>curl -X POST {{ $docsBase }}/api/v1/connect/patients/resolve \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"first_name": "Marie", "last_name": "Fouda", "date_of_birth": "1990-07-22", "sex": "female",
       "purpose": "his_registration", "consent_acknowledged": true}'

# → 200 {"status": "found", "health_id": "CM-HID-7KQ9-MP42-X8D1",
#        "match": {"confidence": 0.9, "method": "demographics"},
#        "patient": {"display_name": "M**** F****", "sex": "female", "year_of_birth": 1990, ...}}</pre>
    </div>
</div>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/patients/verify/{health_id}</span>
</div>
<p>Checks the Health ID format and its check block (<code>422 HEALTH_ID_INVALID_FORMAT</code> on a typo), then whether it is registered (<code>404 HEALTH_ID_NOT_FOUND</code>). <code>200</code> returns <code>{status: "valid", health_id, active, verified, resolved_via_alias, queried_health_id, patient{...}}</code>. Scope <code>health_id:read</code>.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">cURL</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre>curl "{{ $docsBase }}/api/v1/connect/patients/verify/CM-HID-7KQ9-MP42-X8D1" \
  -H "Authorization: Bearer {access_token}"</pre>
    </div>
</div>

<h2 id="consent">Consent Endpoints</h2>

<p>A Health ID identifies a patient; it never opens their record. Your facility asks, the patient approves or refuses in the OpesCare app, and the answer arrives by webhook (<code>consent.granted</code> carries the <code>grant_id</code>; <code>consent.denied</code>, <code>consent.revoked</code> and <code>consent.expired</code> close it). These endpoints need an app bound to a facility (production access); the facility is always the one in your token. A facility-less app gets <code>403 FACILITY_REQUIRED</code>.</p>

<div class="endpoint-pill">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-path">/connect/consents/request-medical-id</span>
</div>
<p>Scope <code>consents:write</code>. Body <code>{health_id, purpose, requested_scope: ["patients:read"], expires_in_days (1–365, default 30), justification?}</code>; <code>purpose</code> is a coded token such as <code>treatment</code>. Returns <code>201 {status: "success", consent_id, request_id, consent_status: "pending", expires_at}</code> (<code>expires_at</code> is when the unanswered request lapses, 72 h). Asking again while a request is open returns it (<code>200</code>, <code>existing: true</code>); if an active grant already covers the scope you get <code>consent_status: "granted"</code> and its <code>grant_id</code>. Errors: <code>404 HEALTH_ID_NOT_FOUND</code>, <code>403 HEALTH_ID_SUSPENDED</code>, <code>422 INVALID_PAYLOAD</code>.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">cURL</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre>curl -X POST "{{ $docsBase }}/api/v1/connect/consents/request-medical-id" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"health_id": "CM-HID-7KQ9-MP42-X8D1", "purpose": "treatment",
       "requested_scope": ["patients:read"], "expires_in_days": 30}'</pre>
    </div>
</div>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/consents/status/{health_id}</span>
</div>
<p>Scope <code>consents:read</code>. Your facility's standing with this patient: <code>{health_id, status, consent_status, grant_id, request_id, scope[], expires_at}</code>, where <code>status</code> is <code>granted</code>, <code>pending</code>, <code>denied</code>, <code>revoked</code>, <code>expired</code> or <code>none</code>.</p>

<h2 id="records">Records Endpoints</h2>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/patients/{health_id}/summary</span>
</div>
<p>Scope <code>patients:read</code> plus header <code>X-Consent-Grant-Id</code>: an active grant from this patient to your facility that covers <code>patients:read</code> (optional <code>X-Purpose-Of-Use</code>, default <code>treatment</code>). Every refusal is <code>403 CONSENT_REQUIRED</code> (missing, unknown, expired or revoked grant, or a grant for another patient), with <code>reason: facility_mismatch</code> for another facility's grant and <code>reason: scope_insufficient</code> for a grant that does not cover <code>patients:read</code>; an app without a facility gets <code>403 FACILITY_REQUIRED</code>. Every read is audited.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">cURL</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre>curl "{{ $docsBase }}/api/v1/connect/patients/CM-HID-7KQ9-MP42-X8D1/summary" \
  -H "Authorization: Bearer {access_token}" \
  -H "X-Consent-Grant-Id: {grant_id}"

# → 200 {"status": "success", "health_id": "...", "summary_generated_at": "...",
#        "consent": {"grant_id", "scope", "expires_at"}, "demographics": {...},
#        "sections": {
#          "recent_visits":      [{visit_id, visit_type, status, facility_name, started_at, ended_at, diagnoses[]}],
#          "recent_lab_results": [{parameter_name, test_name, test_code, loinc_code, value, unit, reference_range, flag, flagged, resulted_at}],
#          "medications":        [{drug_name, drug_code, strength, dosage_form, dose, frequency, route, duration_days, status, prescribed_at}],
#          "allergies":          [{substance, severity, status, recorded_at}],
#          "chronic_conditions": [{name, description, icd_code, snomed_code, status, onset_date}],
#          "immunizations":      [{vaccine, vaccine_code, vaccine_system, dose_number, status, occurrence_date}]}}</pre>
    </div>
</div>

<h2 id="inventory">Inventory Endpoints</h2>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/inventory/pharmacy/{facility_id}</span>
</div>
<p>Pharmacy stock levels. Requires <code>pharmacy:stock:read</code> scope.</p>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/inventory/blood/{facility_id}</span>
</div>
<p>Blood bank inventory by blood group and component. Requires <code>blood:inventory:read</code> scope.</p>

<div class="docs-callout warning">
    <i data-lucide="alert-triangle" class="docs-icon"></i>
    <div>Blood and medicine availability shown via the API is indicative only and does not guarantee supply. Always confirm directly with the facility before making clinical decisions.</div>
</div>

<h2 id="webhooks-sub">Webhook Subscriptions</h2>

<p>Webhook subscriptions are managed in the developer portal at
<a href="{{ $docsBase }}/developer/webhooks">{{ $docsBase }}/developer/webhooks</a>, not through API endpoints:
create a subscription (HTTPS callback URL + events), pause or resume it, see every delivery and replay failed ones.
Supported events: <code>lab_result.released</code>, <code>prescription.issued</code>, <code>consent.granted</code>.
See <a href="{{ route('docs.webhooks') }}">Webhooks</a> for signature verification and the retry policy.</p>

<h2 id="reconciliation">Reconciliation</h2>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/connect/reconciliation?date={date}</span>
</div>
<p>Retrieve payment reconciliation records for a specific date.</p>

<h2 id="try-it">Try It</h2>
<p>Use the <a href="{{ route('docs.playground') }}">Interactive Playground</a> to try all 16 Connect API endpoints live in your browser with the full OpenAPI spec.</p>

<div class="docs-page-nav">
    <a href="{{ route('docs.authentication') }}">← Authentication</a>
    <a href="{{ route('docs.sdk') }}">SDK →</a>
</div>

@endsection
