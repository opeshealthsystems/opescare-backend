@extends('layouts.docs')
@section('title', 'Webhooks')
@section('head')
@include('public.partials._od_pages_css')
@endsection

@section('content')

<h1>Webhooks</h1>
<p class="docs-lead">
    OpesCare pushes real-time events to your HTTPS endpoint whenever something meaningful happens —
    a lab result published, a prescription issued, a consent granted or revoked. Each delivery is
    signed with HMAC-SHA256 and includes a timestamp, so you can verify authenticity and reject
    replays.
</p>

<h2 id="delivery-flow">{{ __('docs_ui.dg.webhooks.title') }}</h2>
<p>{{ __('docs_ui.dg.webhooks.lead') }} <a href="{{ route('docs.flows', 'webhooks') }}">{{ __('docs_ui.flow.h_sequence') }}</a></p>
@include('docs.diagrams.flow', ['diagram' => 'webhooks'])

<h2 id="subscribe">Subscribe to Events</h2>

<p>
    Webhook subscriptions are created in the developer portal, not through the API. Sign in at
    <a href="{{ $docsBase }}/developer/webhooks">{{ $docsBase }}/developer/webhooks</a>, choose the
    integration client, enter an <strong>HTTPS</strong> callback URL that is reachable from the public
    internet (private and local addresses are refused), and tick the events you want.
</p>
<ol>
    <li>Open <a href="{{ $docsBase }}/developer/webhooks">Developer portal &rarr; Webhooks</a>.</li>
    <li>Select the integration client the events belong to (events are scoped to that client's facility).</li>
    <li>Enter your callback URL, e.g. <code>https://your-system.example.com/opescare/webhook</code>.</li>
    <li>Choose one or more events from the list below and save.</li>
    <li>Copy the signing secret (<code>whsec_&hellip;</code>) shown after saving. <strong>It is displayed only once</strong>; it signs every delivery (see <a href="#verification">Signature Verification</a>).</li>
</ol>
<p>You can pause or resume a subscription from the same page.</p>

<h2 id="events-list">Event Types</h2>

<p>These are the events OpesCare delivers today. Each delivery is a JSON <code>POST</code> to your callback URL.</p>

<table class="docs-table">
    <thead><tr><th>Event</th><th>Triggered When</th></tr></thead>
    <tbody>
        <tr><td><code>lab_result.released</code></td><td>A laboratory result is released for a patient of your facility</td></tr>
        <tr><td><code>prescription.issued</code></td><td>A prescription is issued at your facility</td></tr>
        <tr><td><code>consent.granted</code></td><td>A patient approves a consent request from your facility</td></tr>
    </tbody>
</table>

<h2 id="payload">Payload Schema</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">JSON</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "id":         "evt_01HX9K2ABCD",
  "type":       "lab_result.released",
  "version":    "1.0",
  "created_at": "2026-06-01T10:30:00Z",
  "data": {
    "health_id":     "CM-HID-7KQ9-MP42-X8D1",
    "lab_result_id": "lr_xxxxxx",
    "test_name":     "Complete Blood Count",
    "flagged":       true,
    "facility_id":   "00000000-0000-0000-0000-100000000001"
  },
  "meta": {
    "organization_id": "org-uuid",
    "facility_id":     "00000000-0000-0000-0000-100000000001",
    "environment":     "production",
    "request_id":      "req-uuid"
  }
}</pre>
    </div>
</div>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>The event type is in the <code>type</code> field (not <code>event</code>). Payload sensitivity varies by event — most deliver only metadata by default. Contact support to request full payload delivery for specific events.</div>
</div>

<h2 id="verification">Signature Verification</h2>

<p>
    Every delivery includes an <code>X-OpesCare-Signature</code> header in the format
    <code>t=<em>timestamp</em>,v1=<em>hmac-hex</em></code>.
    The signature is computed as <code>HMAC-SHA256(timestamp + "." + raw_body, webhook_secret)</code>.
    <strong>Always verify this before processing any payload.</strong>
</p>

<p>Also check that the timestamp is within <strong>5 minutes</strong> of the current time to reject replay attacks.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="php">PHP (Laravel)</button>
        <button class="docs-code-tab" data-lang="js">JavaScript (Node/Express)</button>
        <button class="docs-code-tab" data-lang="python">Python (Flask)</button>
        <button class="docs-code-tab" data-lang="curl">Go</button>
        <button class="docs-code-tab" data-lang="java">Java</button>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>// routes/api.php
Route::post('/opescare/webhook', function (\Illuminate\Http\Request $request) {
    $sigHeader = $request->header('X-OpesCare-Signature'); // "t=1717228800,v1=abc123..."
    $body      = $request->getContent();
    $secret    = env('OPESCARE_WEBHOOK_SECRET'); // "whsec_xxxxxxxxxxxxxxxx"

    // Parse t= and v1= from signature header
    $parts = [];
    foreach (explode(',', $sigHeader) as $seg) {
        [$k, $v] = explode('=', $seg, 2);
        $parts[trim($k)] = trim($v);
    }

    // Replay protection — reject events older than 5 minutes
    if (abs(time() - (int)$parts['t']) > 300) {
        abort(400, 'Webhook timestamp out of tolerance');
    }

    // Verify HMAC-SHA256 signature
    $expected = hash_hmac('sha256', $parts['t'] . '.' . $body, $secret);
    if (!hash_equals($expected, $parts['v1'])) {
        abort(400, 'Invalid signature');
    }

    $payload = $request->json()->all();
    // Process $payload['type'] ...

    return response()->json(['received' => true]);
});</pre>
    </div>
    <div class="docs-code-pane" data-lang="js">
<pre>const crypto = require('crypto');
const express = require('express');
const app = express();

app.post('/opescare/webhook',
  express.raw({ type: 'application/json' }),
  (req, res) => {
    const sigHeader = req.headers['x-opescare-signature']; // "t=...,v1=..."
    const secret    = process.env.OPESCARE_WEBHOOK_SECRET;

    // Parse t= and v1= from header
    const parts = Object.fromEntries(
      sigHeader.split(',').map(s => s.split('=', 2))
    );

    // Replay protection
    if (Math.abs(Math.floor(Date.now() / 1000) - parseInt(parts.t, 10)) > 300) {
      return res.status(400).send('Timestamp out of tolerance');
    }

    // Verify HMAC-SHA256
    const signed   = `${parts.t}.${req.body.toString()}`;
    const expected = crypto.createHmac('sha256', secret).update(signed).digest('hex');
    const expBuf   = Buffer.from(expected, 'hex');
    const recBuf   = Buffer.from(parts.v1, 'hex');

    if (expBuf.length !== recBuf.length || !crypto.timingSafeEqual(expBuf, recBuf)) {
      return res.status(400).send('Invalid signature');
    }

    const payload = JSON.parse(req.body);
    // Handle payload.type ...

    res.status(200).json({ received: true });
  }
);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import hmac, hashlib, time, os
from flask import Flask, request, abort, jsonify

app = Flask(__name__)

@app.route('/opescare/webhook', methods=['POST'])
def webhook():
    sig_header = request.headers.get('X-OpesCare-Signature', '')
    secret     = os.environ['OPESCARE_WEBHOOK_SECRET']
    body       = request.get_data()  # raw bytes — do NOT call request.json first

    # Parse t= and v1= from header
    parts = {}
    for seg in sig_header.split(','):
        if '=' in seg:
            k, v = seg.split('=', 1)
            parts[k.strip()] = v.strip()

    # Replay protection — reject events older than 5 minutes
    if abs(int(time.time()) - int(parts.get('t', 0))) > 300:
        abort(400, 'Timestamp out of tolerance')

    # Verify HMAC-SHA256: HMAC(timestamp + "." + raw_body)
    signed   = f"{parts['t']}.".encode() + body
    expected = hmac.new(secret.encode(), signed, hashlib.sha256).hexdigest()

    if not hmac.compare_digest(expected, parts.get('v1', '')):
        abort(400, 'Invalid signature')

    payload = request.json
    # Handle payload['type'] ...

    return jsonify({'received': True})</pre>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>// Go example
package main

import (
    "crypto/hmac"
    "crypto/sha256"
    "encoding/hex"
    "fmt"
    "io"
    "math"
    "net/http"
    "os"
    "strconv"
    "strings"
    "time"
)

func verifyWebhook(body []byte, sigHeader, secret string) bool {
    parts := make(map[string]string)
    for _, seg := range strings.Split(sigHeader, ",") {
        kv := strings.SplitN(seg, "=", 2)
        if len(kv) == 2 {
            parts[strings.TrimSpace(kv[0])] = strings.TrimSpace(kv[1])
        }
    }

    ts, err := strconv.ParseInt(parts["t"], 10, 64)
    if err != nil || math.Abs(float64(time.Now().Unix()-ts)) > 300 {
        return false // missing timestamp or replay
    }

    signed := []byte(fmt.Sprintf("%d.", ts))
    signed = append(signed, body...)

    mac := hmac.New(sha256.New, []byte(secret))
    mac.Write(signed)
    expected := hex.EncodeToString(mac.Sum(nil))

    return hmac.Equal([]byte(expected), []byte(parts["v1"]))
}

func webhookHandler(w http.ResponseWriter, r *http.Request) {
    body, _ := io.ReadAll(r.Body)
    sig    := r.Header.Get("X-OpesCare-Signature")
    secret := os.Getenv("OPESCARE_WEBHOOK_SECRET")

    if !verifyWebhook(body, sig, secret) {
        http.Error(w, "Invalid signature", http.StatusBadRequest)
        return
    }
    fmt.Fprintln(w, `{"received":true}`)
}</pre>
    </div>
    <div class="docs-code-pane" data-lang="java">
<pre>import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.util.*;

public class WebhookVerifier {
    public static boolean verify(byte[] body, String sigHeader, String secret)
            throws Exception {

        // Parse t= and v1= from header
        Map&lt;String, String&gt; parts = new HashMap&lt;&gt;();
        for (String seg : sigHeader.split(",")) {
            String[] kv = seg.strip().split("=", 2);
            if (kv.length == 2) parts.put(kv[0].strip(), kv[1].strip());
        }

        long ts = Long.parseLong(parts.getOrDefault("t", "0"));
        if (Math.abs(System.currentTimeMillis() / 1000L - ts) > 300) {
            return false; // replay protection
        }

        // Compute HMAC-SHA256(timestamp + "." + raw_body)
        byte[] prefix  = (ts + ".").getBytes();
        byte[] signed  = new byte[prefix.length + body.length];
        System.arraycopy(prefix, 0, signed, 0, prefix.length);
        System.arraycopy(body, 0, signed, prefix.length, body.length);

        Mac mac = Mac.getInstance("HmacSHA256");
        mac.init(new SecretKeySpec(secret.getBytes(), "HmacSHA256"));
        String expected = HexFormat.of().formatHex(mac.doFinal(signed));

        return MessageDigest.isEqual(
            expected.getBytes(), parts.getOrDefault("v1", "").getBytes()
        );
    }
}</pre>
    </div>
</div>

<div class="docs-callout warning">
    <i data-lucide="alert-triangle" class="docs-icon"></i>
    <div>
        If you are using an OpesCare SDK, call <code>client.webhooks.verify_signature()</code> — it handles
        parsing, HMAC verification, and replay protection correctly in all three languages.
    </div>
</div>

<h2 id="retry">Retry Policy</h2>

<p>
    Your endpoint must return a <code>2xx</code> status within <strong>10 seconds</strong>.
    OpesCare retries failed deliveries on this schedule:
</p>

<table class="docs-table">
    <thead><tr><th>Attempt</th><th>Delay Before Retry</th></tr></thead>
    <tbody>
        <tr><td>1 (initial)</td><td>Immediate</td></tr>
        <tr><td>2</td><td>1 minute</td></tr>
        <tr><td>3</td><td>5 minutes</td></tr>
        <tr><td>4</td><td>15 minutes</td></tr>
        <tr><td>5</td><td>1 hour</td></tr>
        <tr><td>6</td><td>6 hours</td></tr>
        <tr><td>7 (final)</td><td>24 hours</td></tr>
    </tbody>
</table>

<p>After the final attempt the delivery is marked <code>exhausted</code>. Use the <strong>Replay</strong> button in the developer portal to resend it. View delivery logs under <strong>Apps → Webhook Deliveries</strong>.</p>

<h2 id="replay">Replay a Failed Event</h2>

<p>
    Every delivery is listed under <a href="{{ $docsBase }}/developer/webhooks">Developer portal &rarr; Webhooks</a>
    and per app under <strong>Apps &rarr; Webhook deliveries</strong>, with its status
    (<code>pending</code>, <code>delivered</code>, <code>failed</code>, <code>exhausted</code>), response code and attempts.
    A delivery that failed or exhausted its retries can be resent with the <strong>Replay</strong> button; the replay
    is signed with the subscription's current secret.
</p>

<h2 id="testing">Testing Your Endpoint</h2>

<p>Your callback URL must be public HTTPS. During development, expose your local server with a tunnel tool such as
<a href="https://ngrok.com" rel="noopener noreferrer" target="_blank">ngrok</a> or
<a href="https://expose.dev" rel="noopener noreferrer" target="_blank">expose</a>, then register the tunnel URL in the portal:</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="curl">Shell</button></div>
    <div class="docs-code-pane active" data-lang="curl">
<pre># 1. Start a tunnel to your local server on port 8000
ngrok http 8000

# 2. Register the HTTPS tunnel URL as the callback in
#    {{ $docsBase }}/developer/webhooks
#    e.g. https://abc123.ngrok.io/opescare/webhook

# 3. Check that your endpoint answers 2xx quickly
curl -i -X POST https://abc123.ngrok.io/opescare/webhook   -H "Content-Type: application/json" -d '{"ping": true}'</pre>
    </div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.widget') }}">← Widget</a>
    <a href="{{ route('docs.errors') }}">Errors →</a>
</div>

@endsection
