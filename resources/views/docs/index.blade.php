@extends('layouts.docs')
@section('title', 'Getting Started')
@section('content')

<h1>Getting Started with OpesCare APIs</h1>
<p class="docs-lead">
    OpesCare provides five integration pathways so any healthcare system can connect
    securely: a REST API, an SDK, a Bridge Agent for legacy HIS systems, an embeddable
    Widget, and event-driven Webhooks. This guide gets you from zero to your first API call
    in five minutes.
</p>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>
        <strong>Sandbox environment:</strong> All examples use sandbox credentials.
        No real patient data is accessed. You can start immediately — no approval needed for sandbox.
    </div>
</div>

<h2 id="base-urls">Base URLs</h2>

<table class="docs-table">
    <thead>
        <tr><th>Environment</th><th>Base URL</th><th>Access</th></tr>
    </thead>
    <tbody>
        <tr><td>Sandbox</td><td><code>https://opescare.test/api/v1</code></td><td>Open — use sandbox credentials below</td></tr>
        <tr><td>Production</td><td><code>https://api.opescare.health/v1</code></td><td>Requires approved developer account</td></tr>
    </tbody>
</table>

<h2 id="sandbox-credentials">Sandbox Credentials</h2>

<p>Use these credentials to authenticate against the sandbox. They are pre-loaded and ready — no approval needed.</p>

<table class="docs-table">
    <thead>
        <tr><th>Field</th><th>Value</th></tr>
    </thead>
    <tbody>
        <tr><td><code>client_id</code></td><td><code>demo_dev_sandbox</code></td></tr>
        <tr><td><code>client_secret</code></td><td><code>demo_secret_sandbox_2026</code></td></tr>
        <tr><td>Scopes available</td><td>
            <code>patient:profile:read</code> &nbsp;
            <code>pharmacy:stock:read</code> &nbsp;
            <code>blood:inventory:read</code> &nbsp;
            <code>lab:results:read</code> &nbsp;
            <code>patient:diagnostics:read</code>
        </td></tr>
    </tbody>
</table>

<h2 id="production-credentials">Production Credentials — OpesCare HIS</h2>

<p>For the production integration with <strong>OpesCare HIS</strong>, use the credentials below. These are fully approved with access to Health ID resolution, patient data read, and clinical record push.</p>

<table class="docs-table">
    <thead>
        <tr><th>Field</th><th>Value</th></tr>
    </thead>
    <tbody>
        <tr><td><code>client_id</code></td><td><code>opeshisos_production</code></td></tr>
        <tr><td><code>client_secret</code></td><td><code>prod_secret_opeshisos_2026</code></td></tr>
        <tr><td><code>environment</code></td><td><code>production</code></td></tr>
        <tr><td>Approved scopes</td><td>
            <code>health_id:verify</code> &nbsp;
            <code>patient:read</code> &nbsp;
            <code>encounter:push</code> &nbsp;
            <code>lab:push</code> &nbsp;
            <code>prescription:push</code> &nbsp;
            <code>facility:sync</code>
        </td></tr>
        <tr><td>Key endpoint</td><td><code>POST /api/v1/connect/patients/resolve</code> — find or auto-create Health ID</td></tr>
    </tbody>
</table>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div><strong>Integration workflow for OpesCare HIS:</strong> (1) Call <code>/auth/token</code> with production credentials. (2) For each patient, call <code>/patients/resolve</code> — OpesCare returns or creates a Health ID. (3) Push clinical data using the Health ID via <code>/records/encounters</code>, <code>/records/lab-results</code>, or the Bridge Agent. See <a href="{{ route('docs.api') }}#health-id-resolution">Health ID Resolution</a> for full examples.</div>
</div>

<h2 id="quickstart">5-Minute Quickstart</h2>

<p>Step 1 — get an access token. Step 2 — use it to call any endpoint.</p>

<h3>Step 1: Get a Token</h3>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">JavaScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
        <button class="docs-code-tab" data-lang="java">Java</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl -X POST https://opescare.test/api/v1/connect/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "demo_dev_sandbox",
    "client_secret": "demo_secret_sandbox_2026",
    "scope": "patient:profile:read"
  }'</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>&lt;?php
// Laravel / Guzzle example
$response = Http::post('https://opescare.test/api/v1/connect/auth/token', [
    'grant_type'    =&gt; 'client_credentials',
    'client_id'     =&gt; 'demo_dev_sandbox',
    'client_secret' =&gt; 'demo_secret_sandbox_2026',
    'scope'         =&gt; 'patient:profile:read',
]);
$token = $response->json('access_token');</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const res = await fetch('https://opescare.test/api/v1/connect/auth/token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    grant_type: 'client_credentials',
    client_id: 'demo_dev_sandbox',
    client_secret: 'demo_secret_sandbox_2026',
    scope: 'patient:profile:read',
  }),
});
const { access_token } = await res.json();</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import requests

resp = requests.post('https://opescare.test/api/v1/connect/auth/token', json={
    'grant_type': 'client_credentials',
    'client_id': 'demo_dev_sandbox',
    'client_secret': 'demo_secret_sandbox_2026',
    'scope': 'patient:profile:read',
})
access_token = resp.json()['access_token']</pre>
    </div>
    <div class="docs-code-pane" data-lang="java">
<pre>import java.net.http.*;
import java.net.URI;

HttpClient client = HttpClient.newHttpClient();
String body = """
    {
      "grant_type": "client_credentials",
      "client_id": "demo_dev_sandbox",
      "client_secret": "demo_secret_sandbox_2026",
      "scope": "patient:profile:read"
    }
    """;
HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("https://opescare.test/api/v1/connect/auth/token"))
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();
HttpResponse&lt;String&gt; response = client.send(request, HttpResponse.BodyHandlers.ofString());</pre>
    </div>
</div>

<p><strong>Response:</strong></p>
<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">JSON</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "patient:profile:read"
}</pre>
    </div>
</div>

<h3>Step 2: Call an Endpoint</h3>
<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">JavaScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
        <button class="docs-code-tab" data-lang="java">Java</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl "https://opescare.test/api/v1/connect/patient/search?health_id=OPC-2024-DEMO1" \
  -H "Authorization: Bearer {access_token}"</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>$patient = Http::withToken($token)
    ->get('https://opescare.test/api/v1/connect/patient/search', [
        'health_id' =&gt; 'OPC-2024-DEMO1',
    ])->json();</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const patient = await fetch(
  'https://opescare.test/api/v1/connect/patient/search?health_id=OPC-2024-DEMO1',
  { headers: { 'Authorization': `Bearer ${access_token}` } }
).then(r => r.json());</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>patient = requests.get(
    'https://opescare.test/api/v1/connect/patient/search',
    params={'health_id': 'OPC-2024-DEMO1'},
    headers={'Authorization': f'Bearer {access_token}'}
).json()</pre>
    </div>
    <div class="docs-code-pane" data-lang="java">
<pre>HttpRequest req = HttpRequest.newBuilder()
    .uri(URI.create("https://opescare.test/api/v1/connect/patient/search?health_id=OPC-2024-DEMO1"))
    .header("Authorization", "Bearer " + accessToken)
    .GET().build();
HttpResponse&lt;String&gt; res = client.send(req, HttpResponse.BodyHandlers.ofString());</pre>
    </div>
</div>

<h2 id="choose-integration">Which Integration Type?</h2>

<table class="docs-table">
    <thead>
        <tr><th>Integration</th><th>Best For</th><th>Auth</th></tr>
    </thead>
    <tbody>
        <tr><td><a href="{{ route('docs.api') }}">Connect API</a></td><td>Backend integrations, partner systems, EMR bridges</td><td>OAuth 2.0 client credentials</td></tr>
        <tr><td><a href="{{ route('docs.sdk') }}">SDK</a></td><td>PHP/JS apps that want a typed wrapper</td><td>SDK Bearer token</td></tr>
        <tr><td><a href="{{ route('docs.bridge') }}">Bridge Agent</a></td><td>On-premise HIS pushing data to OpesCare</td><td>Bridge token header</td></tr>
        <tr><td><a href="{{ route('docs.widget') }}">Widget</a></td><td>Embed patient health summary in any web page</td><td>SDK token in config</td></tr>
        <tr><td><a href="{{ route('docs.webhooks') }}">Webhooks</a></td><td>Receive push events when records change</td><td>Subscribe via API</td></tr>
    </tbody>
</table>

<div class="docs-page-nav">
    <span></span>
    <a href="{{ route('docs.authentication') }}">Authentication →</a>
</div>

@endsection
