@extends('layouts.docs')
@section('title', 'Errors & Troubleshooting')
@section('content')

<h1>Errors & Troubleshooting</h1>
<p class="docs-lead">
    OpesCare uses standard HTTP status codes and returns a consistent JSON error body
    for every failure. Always check the <code>error</code> field for a machine-readable code
    and the <code>message</code> field for a human-readable explanation.
</p>

<h2 id="format">Error Response Format</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">JSON</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "error": "invalid_scope",
  "message": "The requested scope 'patient:records:write' is not permitted for this client.",
  "status": 403
}</pre>
    </div>
</div>

<h2 id="http-codes">HTTP Status Codes</h2>

<table class="docs-table">
    <thead><tr><th>Status</th><th>Meaning</th><th>Common Causes</th></tr></thead>
    <tbody>
        <tr><td><code>200</code></td><td>OK</td><td>Request succeeded</td></tr>
        <tr><td><code>201</code></td><td>Created</td><td>Resource created (subscriptions, consents)</td></tr>
        <tr><td><code>204</code></td><td>No Content</td><td>Successful deletion (webhook unsubscribe)</td></tr>
        <tr><td><code>400</code></td><td>Bad Request</td><td>Missing required fields, malformed JSON, invalid date format</td></tr>
        <tr><td><code>401</code></td><td>Unauthenticated</td><td>Token missing, expired, or malformed — re-authenticate</td></tr>
        <tr><td><code>403</code></td><td>Forbidden</td><td>Insufficient scope, consent not granted, sandbox restriction</td></tr>
        <tr><td><code>404</code></td><td>Not Found</td><td>Patient not found, invalid health ID, resource deleted</td></tr>
        <tr><td><code>409</code></td><td>Conflict</td><td>Duplicate webhook subscription endpoint</td></tr>
        <tr><td><code>422</code></td><td>Unprocessable</td><td>Validation failed — wrong data type, value out of range</td></tr>
        <tr><td><code>429</code></td><td>Rate Limited</td><td>Too many requests — back off and retry after the <code>Retry-After</code> header value</td></tr>
        <tr><td><code>500</code></td><td>Server Error</td><td>Unexpected server error — check the <a href="{{ route('public.status') }}">status page</a></td></tr>
    </tbody>
</table>

<h2 id="error-codes">Machine-Readable Error Codes</h2>

<table class="docs-table">
    <thead><tr><th>Error Code</th><th>HTTP</th><th>Description</th><th>Fix</th></tr></thead>
    <tbody>
        <tr><td><code>invalid_client</code></td><td>401</td><td>Wrong <code>client_id</code> or <code>client_secret</code></td><td>Check credentials in the developer portal</td></tr>
        <tr><td><code>token_expired</code></td><td>401</td><td>Bearer token has expired (1-hour TTL)</td><td>Request a new token using <code>client_credentials</code> grant</td></tr>
        <tr><td><code>token_invalid</code></td><td>401</td><td>Malformed or revoked token</td><td>Re-authenticate from scratch</td></tr>
        <tr><td><code>invalid_scope</code></td><td>403</td><td>Scope not granted to your client</td><td>Check allowed scopes in the developer portal</td></tr>
        <tr><td><code>consent_required</code></td><td>403</td><td>Patient has not granted consent to your client</td><td>Call the consent/grant endpoint first</td></tr>
        <tr><td><code>patient_not_found</code></td><td>404</td><td>No patient matches the given Health ID</td><td>Verify the Health ID is in the correct format (<code>OPC-YYYY-XXXXX</code>)</td></tr>
        <tr><td><code>facility_not_found</code></td><td>404</td><td>Facility ID does not exist</td><td>Verify your facility ID in the developer portal</td></tr>
        <tr><td><code>bridge_token_invalid</code></td><td>401</td><td>Bridge token missing or revoked</td><td>Re-generate the bridge token in the developer portal</td></tr>
        <tr><td><code>sdk_token_inactive</code></td><td>401</td><td>SDK token has been deactivated</td><td>Generate a new SDK token in the portal</td></tr>
        <tr><td><code>rate_limit_exceeded</code></td><td>429</td><td>Too many requests in a short window</td><td>Respect the <code>Retry-After</code> response header</td></tr>
        <tr><td><code>sandbox_only</code></td><td>403</td><td>Your account is restricted to sandbox</td><td>Request production access via the developer portal</td></tr>
    </tbody>
</table>

<h2 id="handling">Error Handling Best Practices</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">JavaScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>$response = Http::withToken($token)
    ->get('https://opescare.test/api/v1/connect/patient/search', [
        'health_id' =&gt; $healthId,
    ]);

if ($response->status() === 401) {
    // Re-fetch token and retry once
    $token    = getNewToken();
    $response = Http::withToken($token)
        ->get('https://opescare.test/api/v1/connect/patient/search', [
            'health_id' =&gt; $healthId,
        ]);
}

if ($response->failed()) {
    $error = $response->json();
    // $error['error'] is the machine-readable code
    // $error['message'] is the human-readable description
    throw new \RuntimeException("OpesCare error: {$error['error']} — {$error['message']}");
}

$patient = $response->json();</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>async function searchPatient(healthId, token) {
  const res = await fetch(
    `https://opescare.test/api/v1/connect/patient/search?health_id=${healthId}`,
    { headers: { Authorization: `Bearer ${token}` } }
  );

  if (res.status === 429) {
    const retryAfter = res.headers.get('Retry-After') || 5;
    await new Promise(r => setTimeout(r, retryAfter * 1000));
    return searchPatient(healthId, token); // retry once
  }

  if (!res.ok) {
    const err = await res.json();
    throw new Error(`OpesCare [${err.error}]: ${err.message}`);
  }

  return res.json();
}</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import time
import requests

def search_patient(health_id: str, token: str, retry: int = 1):
    resp = requests.get(
        'https://opescare.test/api/v1/connect/patient/search',
        params={'health_id': health_id},
        headers={'Authorization': f'Bearer {token}'},
    )
    if resp.status_code == 429 and retry > 0:
        time.sleep(int(resp.headers.get('Retry-After', 5)))
        return search_patient(health_id, token, retry - 1)
    resp.raise_for_status()
    return resp.json()</pre>
    </div>
</div>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>If you're hitting persistent <code>500</code> errors, check the <a href="{{ route('public.status') }}">OpesCare system status page</a>. For integration issues, <a href="{{ route('public.contact') }}">contact developer support</a>.</div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.webhooks') }}">← Webhooks</a>
    <a href="{{ route('docs.playground') }}">Playground →</a>
</div>

@endsection
