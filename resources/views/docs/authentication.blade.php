@extends('layouts.docs')
@section('title', 'Authentication')
@section('content')

<h1>Authentication</h1>
<p class="docs-lead">
    OpesCare uses three separate authentication mechanisms depending on which integration type
    you are using. All credentials should be kept secret and never exposed in client-side code.
</p>

<h2 id="connect-auth">Connect API — OAuth 2.0 Client Credentials (RS256 JWT)</h2>

<p>
    The Connect API uses the <strong>OAuth 2.0 client_credentials</strong> grant.
    Your integration client has a <code>client_id</code> and <code>client_secret</code>
    (created in the developer portal). Exchange them for a short-lived Bearer token (1 hour TTL).
</p>

<p>
    The returned <code>access_token</code> is a <strong>real RS256-signed JWT</strong> — three
    base64url segments separated by dots. It is signed with OpesCare's RSA-2048 private key
    and can be verified independently using OpesCare's public key.
</p>

<h3>Token Request</h3>
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
    "grant_type":    "client_credentials",
    "client_id":     "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET"
  }'</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>use Illuminate\Support\Facades\Http;

$response = Http::post('https://opescare.test/api/v1/connect/auth/token', [
    'grant_type'    =&gt; 'client_credentials',
    'client_id'     =&gt; env('OPESCARE_CLIENT_ID'),
    'client_secret' =&gt; env('OPESCARE_CLIENT_SECRET'),
]);

$accessToken = $response->json('access_token');
$expiresIn   = $response->json('expires_in'); // 3600 seconds</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>async function getToken() {
  const res = await fetch('https://opescare.test/api/v1/connect/auth/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      grant_type:    'client_credentials',
      client_id:     process.env.OPESCARE_CLIENT_ID,
      client_secret: process.env.OPESCARE_CLIENT_SECRET,
    }),
  });
  const { access_token, expires_in } = await res.json();
  return access_token; // RS256 JWT — cache until expires_in seconds
}</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import requests, os

def get_token() -> str:
    resp = requests.post('https://opescare.test/api/v1/connect/auth/token', json={
        'grant_type':    'client_credentials',
        'client_id':     os.environ['OPESCARE_CLIENT_ID'],
        'client_secret': os.environ['OPESCARE_CLIENT_SECRET'],
    })
    resp.raise_for_status()
    return resp.json()['access_token']  # RS256 JWT — cache for 3600s</pre>
    </div>
    <div class="docs-code-pane" data-lang="java">
<pre>import java.net.http.*;
import java.net.URI;

String body = String.format(
    "{\"grant_type\":\"client_credentials\",\"client_id\":\"%s\",\"client_secret\":\"%s\"}",
    System.getenv("OPESCARE_CLIENT_ID"),
    System.getenv("OPESCARE_CLIENT_SECRET")
);
HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("https://opescare.test/api/v1/connect/auth/token"))
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();
var response = HttpClient.newHttpClient().send(request, HttpResponse.BodyHandlers.ofString());</pre>
    </div>
</div>

<h3>Token Response</h3>
<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">JSON</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJvcGVzY2FyZS1jb25uZWN0...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "health_id:verify patients:read consents:read consents:write"
}</pre>
    </div>
</div>

<h3>Using the Token</h3>
<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">cURL</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre>curl https://opescare.test/api/fhir/R4/metadata \
  -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."</pre>
    </div>
</div>

<h3>Available Scopes</h3>
<table class="docs-table">
    <thead><tr><th>Scope</th><th>Description</th><th>Risk Level</th></tr></thead>
    <tbody>
        <tr><td><code>health_id:verify</code></td><td>Verify and resolve patient Health IDs</td><td>Low</td></tr>
        <tr><td><code>patients:read</code></td><td>Read consented patient summary and clinical data</td><td>Medium</td></tr>
        <tr><td><code>consents:read</code></td><td>Check consent grant status</td><td>Low</td></tr>
        <tr><td><code>consents:write</code></td><td>Request patient consent</td><td>Low</td></tr>
        <tr><td><code>encounters:write</code></td><td>Push clinical encounters and recommendations</td><td>High</td></tr>
        <tr><td><code>labs:read</code></td><td>Read lab orders and results</td><td>Medium</td></tr>
        <tr><td><code>labs:write</code></td><td>Push lab results and interpretations</td><td>High</td></tr>
        <tr><td><code>prescriptions:read</code></td><td>Read prescription records</td><td>Medium</td></tr>
        <tr><td><code>prescriptions:write</code></td><td>Push prescription alerts</td><td>High</td></tr>
        <tr><td><code>documents:read</code></td><td>Read clinical documents</td><td>Medium</td></tr>
        <tr><td><code>documents:write</code></td><td>Issue official documents</td><td>High</td></tr>
        <tr><td><code>appointments:read</code></td><td>Read appointment data</td><td>Medium</td></tr>
        <tr><td><code>appointments:write</code></td><td>Create or update appointments</td><td>Medium</td></tr>
        <tr><td><code>pharmacy:write</code></td><td>Sync pharmacy stock levels</td><td>Medium</td></tr>
        <tr><td><code>webhooks:manage</code></td><td>Create and manage webhook subscriptions</td><td>Medium</td></tr>
        <tr><td><code>bridge_agent:sync</code></td><td>Bridge Agent data synchronisation</td><td>High</td></tr>
        <tr><td><code>emergency:access</code></td><td>Emergency access override (audited)</td><td>Critical</td></tr>
    </tbody>
</table>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>
        Tokens expire after <strong>3600 seconds (1 hour)</strong>. Cache them and re-request when they expire.
        Never request a new token for every API call. The OpesCare SDKs handle token caching and refresh automatically.
    </div>
</div>

<h2 id="sdk-auth">SDK — OAuth2 Client Credentials via SDK</h2>

<p>
    When using the official OpesCare SDK, authentication is handled automatically.
    Pass your <code>client_id</code> and <code>client_secret</code> to the SDK client
    at construction time. The SDK fetches and caches the Bearer token, and refreshes
    it 60 seconds before expiry.
</p>

<div class="docs-callout warning">
    <i data-lucide="alert-triangle" class="docs-icon"></i>
    <div>Never include client credentials in client-side JavaScript or mobile apps. Use server-side code only.</div>
</div>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>use OpesCare\OpesCareClient;

// Token is fetched automatically at construction — raises AuthenticationError on bad credentials
$client = new OpesCareClient(
    clientId:     env('OPESCARE_CLIENT_ID'),
    clientSecret: env('OPESCARE_CLIENT_SECRET'),
    environment:  'sandbox', // or 'production'
);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>import { OpesCareClient } from '@opescare/sdk';

// Token is fetched at construction — throws AuthenticationError on bad credentials
const client = await OpesCareClient.create({
  clientId:     process.env.OPESCARE_CLIENT_ID,
  clientSecret: process.env.OPESCARE_CLIENT_SECRET,
  environment:  'sandbox', // or 'production'
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>from opescare import OpesCareClient

# Token is fetched at construction — raises AuthenticationError on bad credentials
client = OpesCareClient(
    client_id=os.environ['OPESCARE_CLIENT_ID'],
    client_secret=os.environ['OPESCARE_CLIENT_SECRET'],
    environment='sandbox',  # or 'production'
)</pre>
    </div>
</div>

<h2 id="bridge-auth">Bridge Agent — X-Bridge-Agent-Key</h2>

<p>
    Bridge Agents authenticate using two headers: <code>X-Bridge-Agent-ID</code> (your agent's ID)
    and <code>X-Bridge-Agent-Key</code> (the agent key from the developer portal).
    The key is verified server-side against its SHA-256 hash stored in the database.
</p>

<p>
    If you are using the official Bridge Agent daemon (<code>pip install opescare-bridge-agent</code>),
    authentication is handled automatically. The headers below are for custom integrations only.
</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl -X POST https://opescare.test/api/v1/bridge/heartbeat \
  -H "X-Bridge-Agent-ID: YOUR_AGENT_ID" \
  -H "X-Bridge-Agent-Key: YOUR_AGENT_KEY" \
  -H "X-Bridge-Timestamp: 1717228800" \
  -H "Content-Type: application/json" \
  -d '{"agent_id":"YOUR_AGENT_ID","facility_id":"YOUR_FACILITY_ID","version":"1.0.0"}'</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>Http::withHeaders([
    'X-Bridge-Agent-ID'  =&gt; env('BRIDGE_AGENT_ID'),
    'X-Bridge-Agent-Key' =&gt; env('BRIDGE_AGENT_KEY'),
    'X-Bridge-Timestamp' =&gt; time(),
])->post('https://opescare.test/api/v1/bridge/heartbeat', [
    'agent_id'    =&gt; env('BRIDGE_AGENT_ID'),
    'facility_id' =&gt; env('BRIDGE_FACILITY_ID'),
    'version'     =&gt; '1.0.0',
]);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import requests, os, time

requests.post('https://opescare.test/api/v1/bridge/heartbeat',
    headers={
        'X-Bridge-Agent-ID':  os.environ['BRIDGE_AGENT_ID'],
        'X-Bridge-Agent-Key': os.environ['BRIDGE_AGENT_KEY'],
        'X-Bridge-Timestamp': str(int(time.time())),
    },
    json={
        'agent_id':    os.environ['BRIDGE_AGENT_ID'],
        'facility_id': os.environ['BRIDGE_FACILITY_ID'],
        'version':     '1.0.0',
    }
)</pre>
    </div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.index') }}">← Getting Started</a>
    <a href="{{ route('docs.api') }}">Connect API →</a>
</div>

@endsection
