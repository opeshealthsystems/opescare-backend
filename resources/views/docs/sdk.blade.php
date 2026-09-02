@extends('layouts.docs')
@section('title', 'SDK')
@section('content')

<h1>OpesCare SDK</h1>
<p class="docs-lead">
    The OpesCare SDK provides typed wrappers around the Connect API and FHIR R4 layer.
    Install via Composer (PHP), npm (TypeScript/JavaScript), or pip (Python) and start making
    calls in minutes. All three SDKs expose identical module names and method signatures.
</p>

<h2 id="installation">Installation</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP (Composer)</button>
        <button class="docs-code-tab" data-lang="js">TypeScript/JS (npm)</button>
        <button class="docs-code-tab" data-lang="python">Python (pip)</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>composer require opescare/sdk</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>npm install @opescare/sdk
# or
yarn add @opescare/sdk</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>pip install opescare-sdk</pre>
    </div>
</div>

<h2 id="initialisation">Initialisation</h2>

<p>
    Pass your <code>client_id</code> and <code>client_secret</code> from the developer portal.
    The SDK fetches a Bearer token automatically and refreshes it 60 seconds before expiry.
</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>use OpesCare\OpesCareClient;

$client = new OpesCareClient(
    clientId:     env('OPESCARE_CLIENT_ID'),
    clientSecret: env('OPESCARE_CLIENT_SECRET'),
    environment:  'sandbox', // or 'production'
);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>import { OpesCareClient } from '@opescare/sdk';

// create() is async — it fetches the initial Bearer token
const client = await OpesCareClient.create({
  clientId:     process.env.OPESCARE_CLIENT_ID,
  clientSecret: process.env.OPESCARE_CLIENT_SECRET,
  environment:  'sandbox', // or 'production'
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>from opescare import OpesCareClient
import os

client = OpesCareClient(
    client_id=os.environ['OPESCARE_CLIENT_ID'],
    client_secret=os.environ['OPESCARE_CLIENT_SECRET'],
    environment='sandbox',  # or 'production'
)</pre>
    </div>
</div>

<h2 id="health-id-methods">Health ID Methods</h2>

<h3><code>client.healthIds.resolve(...)</code></h3>
<p>Resolve a patient to their canonical Health ID. Pass <code>health_id</code> for a direct lookup, or <code>first_name</code> + <code>last_name</code> + <code>date_of_birth</code> for demographic resolution.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>// By Health ID
$result = $client->healthIds->resolve(['health_id' => 'CM-HID-7KQ9-MP42-X8D1']);
echo $result['health_id']; // "CM-HID-7KQ9-MP42-X8D1"
echo $result['status'];    // "found"

// By demographics
$result = $client->healthIds->resolve([
    'first_name'    => 'Amara',
    'last_name'     => 'Ngo',
    'date_of_birth' => '1990-04-15',
]);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>// By Health ID
const result = await client.healthIds.resolve({ health_id: 'CM-HID-7KQ9-MP42-X8D1' });
console.log(result.health_id); // "CM-HID-7KQ9-MP42-X8D1"
console.log(result.status);    // "found"

// By demographics
const result2 = await client.healthIds.resolve({
  first_name:    'Amara',
  last_name:     'Ngo',
  date_of_birth: '1990-04-15',
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre># By Health ID
result = client.health_ids.resolve(health_id='CM-HID-7KQ9-MP42-X8D1')
print(result['health_id'])  # "CM-HID-7KQ9-MP42-X8D1"
print(result['status'])     # "found"

# By demographics
result2 = client.health_ids.resolve(
    first_name='Amara',
    last_name='Ngo',
    date_of_birth='1990-04-15',
)</pre>
    </div>
</div>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>SDK responses are plain dictionaries/arrays — use <code>result['key']</code>, not <code>result.key</code>.</div>
</div>

<h2 id="patient-methods">Patient Methods</h2>

<h3><code>client.patients.getSummary(healthId)</code></h3>
<p>Returns a consented patient summary — demographics, active allergies, current medications, recent labs, recent visits. Requires <code>patients:read</code> consent scope.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>$summary = $client->patients->getSummary('CM-HID-7KQ9-MP42-X8D1');

echo $summary['patient']['first_name'];          // "Amara"
echo $summary['patient']['blood_group'];         // "O+"
print_r($summary['active_medications']);
print_r($summary['active_allergies']);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const summary = await client.patients.getSummary('CM-HID-7KQ9-MP42-X8D1');

console.log(summary.patient.first_name);    // "Amara"
console.log(summary.patient.blood_group);   // "O+"
console.log(summary.active_medications);
console.log(summary.active_allergies);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>summary = client.patients.get_summary('CM-HID-7KQ9-MP42-X8D1')

print(summary['patient']['first_name'])      # "Amara"
print(summary['patient']['blood_group'])     # "O+"
print(summary['active_medications'])
print(summary['active_allergies'])</pre>
    </div>
</div>

<h2 id="fhir-methods">FHIR R4 Methods</h2>

<p>Read structured clinical data using FHIR R4 resources. All responses are FHIR R4-compliant JSON bundles.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>$hid = 'CM-HID-7KQ9-MP42-X8D1';

// Active medications
$meds     = $client->fhir->medicationRequests($hid, ['status' => 'active']);

// Allergies
$allergies = $client->fhir->allergyIntolerances($hid);

// Lab results
$labs      = $client->fhir->diagnosticReports($hid);

// Active diagnoses
$conditions = $client->fhir->conditions($hid, ['clinical-status' => 'active']);

// Full patient bundle (everything)
$bundle    = $client->fhir->patientEverything($hid);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const hid = 'CM-HID-7KQ9-MP42-X8D1';

const meds      = await client.fhir.medicationRequests(hid, { status: 'active' });
const allergies = await client.fhir.allergyIntolerances(hid);
const labs      = await client.fhir.diagnosticReports(hid);
const conditions = await client.fhir.conditions(hid, { 'clinical-status': 'active' });
const bundle    = await client.fhir.patientEverything(hid);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>hid = 'CM-HID-7KQ9-MP42-X8D1'

meds       = client.fhir.medication_requests(hid, status='active')
allergies  = client.fhir.allergy_intolerances(hid)
labs       = client.fhir.diagnostic_reports(hid)
conditions = client.fhir.conditions(hid, **{'clinical-status': 'active'})
bundle     = client.fhir.patient_everything(hid)</pre>
    </div>
</div>

<h2 id="records-methods">Push Clinical Records</h2>

<p>Push CDSS recommendations, lab interpretations, and prescription alerts back to OpesCare.
Idempotency keys are auto-generated — you do not need to manage them manually.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>// Push a CDSS clinical recommendation
$result = $client->records->pushEncounter([
    'health_id'      => 'CM-HID-7KQ9-MP42-X8D1',
    'encounter_type' => 'cdss_alert',
    'clinical_note'  => 'Drug interaction detected: Warfarin + Aspirin — increased bleeding risk.',
    'severity'       => 'high',
    'alert_type'     => 'drug_interaction',
    'cdss_rule_id'   => 'DDI-WARFARIN-ASPIRIN-001',
]);

// Push a lab interpretation
$client->records->pushLabResult([
    'health_id'      => 'CM-HID-7KQ9-MP42-X8D1',
    'test_name'      => 'HbA1c',
    'result_value'   => '9.2%',
    'flagged'        => true,
    'flag_level'     => 'critical',
    'interpretation' => 'Poor glycemic control — consider treatment escalation.',
]);</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>// Push a CDSS clinical recommendation
const result = await client.records.pushEncounter({
  health_id:      'CM-HID-7KQ9-MP42-X8D1',
  encounter_type: 'cdss_alert',
  clinical_note:  'Drug interaction detected: Warfarin + Aspirin — increased bleeding risk.',
  severity:       'high',
  alert_type:     'drug_interaction',
  cdss_rule_id:   'DDI-WARFARIN-ASPIRIN-001',
});

// Push a lab interpretation
await client.records.pushLabResult({
  health_id:      'CM-HID-7KQ9-MP42-X8D1',
  test_name:      'HbA1c',
  result_value:   '9.2%',
  flagged:        true,
  flag_level:     'critical',
  interpretation: 'Poor glycemic control — consider treatment escalation.',
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre># Push a CDSS clinical recommendation
result = client.records.push_encounter(
    health_id='CM-HID-7KQ9-MP42-X8D1',
    encounter_type='cdss_alert',
    clinical_note='Drug interaction detected: Warfarin + Aspirin — increased bleeding risk.',
    severity='high',
    alert_type='drug_interaction',
    cdss_rule_id='DDI-WARFARIN-ASPIRIN-001',
)

# Push a lab interpretation
client.records.push_lab_result(
    health_id='CM-HID-7KQ9-MP42-X8D1',
    test_name='HbA1c',
    result_value='9.2%',
    flagged=True,
    flag_level='critical',
    interpretation='Poor glycemic control — consider treatment escalation.',
)</pre>
    </div>
</div>

<h2 id="webhook-methods">Webhooks via SDK</h2>

<h3>Subscribe</h3>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="js">TypeScript</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>$subscription = $client->webhooks->subscribe(
    callbackUrl: 'https://your-system.example.com/opescare/webhook',
    events: ['lab_result.released', 'prescription.issued', 'consent.revoked'],
    description: 'My CDSS event listener',
);

// Save $subscription['webhook_secret'] immediately — shown only once
echo $subscription['webhook_secret']; // "whsec_xxxxxxxxxxxxxxxx"</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const subscription = await client.webhooks.subscribe(
  'https://your-system.example.com/opescare/webhook',
  ['lab_result.released', 'prescription.issued', 'consent.revoked'],
  'My CDSS event listener'
);

// Save subscription.webhook_secret immediately — shown only once
console.log(subscription.webhook_secret); // "whsec_xxxxxxxxxxxxxxxx"</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>subscription = client.webhooks.subscribe(
    callback_url='https://your-system.example.com/opescare/webhook',
    events=['lab_result.released', 'prescription.issued', 'consent.revoked'],
    description='My CDSS event listener',
)

# Save subscription['webhook_secret'] immediately — shown only once
print(subscription['webhook_secret'])  # "whsec_xxxxxxxxxxxxxxxx"</pre>
    </div>
</div>

<h3>Verify Incoming Webhook Signature</h3>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP (Laravel)</button>
        <button class="docs-code-tab" data-lang="js">TypeScript (Express)</button>
        <button class="docs-code-tab" data-lang="python">Python (Flask)</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>use OpesCare\Exceptions\WebhookSignatureException;

Route::post('/opescare/webhook', function (\Illuminate\Http\Request $request) {
    try {
        $client->webhooks->verifySignature(
            $request->getContent(),
            $request->header('X-OpesCare-Signature'),
            env('OPESCARE_WEBHOOK_SECRET') // "whsec_xxxxxxxxxxxxxxxx"
        );
    } catch (WebhookSignatureException $e) {
        abort(400, 'Invalid webhook signature');
    }

    $event = $client->webhooks->parseEvent($request->getContent());
    // $event['type'] === "lab_result.released"

    return response()->json(['received' => true]);
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>import { WebhookSignatureError } from '@opescare/sdk';
import express from 'express';
const app = express();

app.post('/opescare/webhook',
  express.raw({ type: 'application/json' }),
  async (req, res) => {
    try {
      client.webhooks.verifySignature(
        req.body.toString(),
        req.headers['x-opescare-signature'],
        process.env.OPESCARE_WEBHOOK_SECRET // "whsec_xxxxxxxxxxxxxxxx"
      );
    } catch (err) {
      if (err instanceof WebhookSignatureError) {
        return res.status(400).send('Invalid signature');
      }
    }

    const event = client.webhooks.parseEvent(req.body.toString());
    // event.type === "lab_result.released"

    res.status(200).json({ received: true });
  }
);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>from opescare import OpesCareClient
from opescare.exceptions import WebhookSignatureError
from flask import Flask, request, abort, jsonify

app = Flask(__name__)

@app.route('/opescare/webhook', methods=['POST'])
def webhook():
    try:
        client.webhooks.verify_signature(
            raw_payload=request.get_data(),
            signature_header=request.headers.get('X-OpesCare-Signature', ''),
            secret=os.environ['OPESCARE_WEBHOOK_SECRET']  # "whsec_xxxxxxxxxxxxxxxx"
        )
    except WebhookSignatureError:
        abort(400, 'Invalid signature')

    event = client.webhooks.parse_event(request.get_data())
    # event['type'] == "lab_result.released"

    return jsonify({'received': True})</pre>
    </div>
</div>

<h2 id="error-handling">Error Handling</h2>

<p>All exceptions inherit from <code>OpesCareError</code> / <code>OpesCareException</code>.</p>

<table class="docs-table">
    <thead><tr><th>Exception</th><th>HTTP Status</th><th>Cause</th></tr></thead>
    <tbody>
        <tr><td><code>AuthenticationError</code></td><td>401</td><td>Invalid or expired token / credentials</td></tr>
        <tr><td><code>AuthorizationError</code></td><td>403</td><td>Token does not have permission for this action</td></tr>
        <tr><td><code>ConsentRequiredError</code></td><td>403</td><td>Patient consent not granted</td></tr>
        <tr><td><code>ValidationError</code></td><td>422</td><td>Request payload failed server-side validation</td></tr>
        <tr><td><code>NotFoundException</code></td><td>404</td><td>Resource does not exist</td></tr>
        <tr><td><code>IdempotencyConflictError</code></td><td>409</td><td>Idempotency key reused with different payload</td></tr>
        <tr><td><code>RateLimitError</code></td><td>429</td><td>Too many requests — check <code>.retry_after</code></td></tr>
        <tr><td><code>ServerError</code></td><td>5xx</td><td>OpesCare server error after all retries exhausted</td></tr>
        <tr><td><code>WebhookSignatureError</code></td><td>—</td><td>HMAC signature invalid, missing, or replay detected</td></tr>
    </tbody>
</table>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="python">Python</button>
        <button class="docs-code-tab" data-lang="php">PHP</button>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>from opescare import OpesCareClient, AuthenticationError, ConsentRequiredError, RateLimitError
import time

try:
    summary = client.patients.get_summary('CM-HID-7KQ9-MP42-X8D1')
except ConsentRequiredError:
    # Patient has not granted consent — request it
    client.consents.request(
        'CM-HID-7KQ9-MP42-X8D1',
        purpose='clinical_decision_support',
        requested_scopes=['patients:read'],
    )
except AuthenticationError:
    # Token expired — get a new client
    client = client.refresh_token()
except RateLimitError as e:
    # Back off for the server-specified duration
    time.sleep(e.retry_after)</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>use OpesCare\Exceptions\{AuthenticationError, ConsentRequiredError, RateLimitError};

try {
    $summary = $client->patients->getSummary('CM-HID-7KQ9-MP42-X8D1');
} catch (ConsentRequiredError $e) {
    // Request consent
    $client->consents->request('CM-HID-7KQ9-MP42-X8D1', 'clinical_decision_support', ['patients:read']);
} catch (AuthenticationError $e) {
    // Token expired — create a new client
    $client = $client->refreshToken();
} catch (RateLimitError $e) {
    sleep($e->retryAfter); // honour server's specified wait time
}</pre>
    </div>
</div>

<h2 id="introspect">Token Introspection</h2>

<p>Check the token's validity, scopes, and expiry via the SDK endpoint:</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl https://opescare.test/api/v1/sdk/token/introspect \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"

# Response:
# {
#   "active": true,
#   "scopes": ["health_id:verify", "patients:read", "encounters:write"],
#   "client_id": "sandbox_xxxxxxxxxxxxxxxxxxxxxxxxxxxx",
#   "expires_at": "2026-06-01T11:00:00Z"
# }</pre>
    </div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.api') }}">← Connect API</a>
    <a href="{{ route('docs.bridge') }}">Bridge Agent →</a>
</div>

@endsection
