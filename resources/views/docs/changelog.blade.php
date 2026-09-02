@extends('layouts.docs')
@section('title', 'Changelog')
@section('content')

<h1>Changelog</h1>
<p class="docs-lead">
    All notable changes to the OpesCare APIs and developer platform, newest first.
    We follow <a href="https://semver.org" target="_blank" rel="noopener noreferrer">semantic versioning</a>.
</p>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>Breaking changes will be announced via the developer mailing list at least <strong>60 days</strong> in advance. Subscribe at <a href="{{ route('public.developers') }}">the Developer Hub</a>.</div>
</div>

<h2 id="v1-0-0">v1.0.0 — 2026-05-20</h2>
<p><strong>Initial public release.</strong> All five integration types are now available in the sandbox.</p>

<h3>Connect API (16 endpoints)</h3>
<ul>
    <li><code>POST /connect/auth/token</code> — OAuth 2.0 client_credentials grant</li>
    <li><code>GET /connect/patient/search</code> — patient lookup by Health ID</li>
    <li><code>GET/POST /connect/patient/{id}/consent</code> — consent status and grant</li>
    <li><code>GET /connect/patient/{id}/records</code> — medical records with type filter</li>
    <li><code>GET /connect/inventory/pharmacy/{id}</code> — pharmacy stock levels</li>
    <li><code>GET /connect/inventory/blood/{id}</code> — blood bank inventory</li>
    <li><code>GET/POST/DELETE /connect/webhooks/subscriptions</code> — subscription management</li>
    <li><code>GET /connect/reconciliation</code> — payment reconciliation by date</li>
</ul>

<h3>SDK (9 endpoints)</h3>
<ul>
    <li><code>GET /sdk/patient/{health_id}</code> — patient profile</li>
    <li><code>GET /sdk/facility/{facility_id}</code> — facility details</li>
    <li><code>GET /sdk/appointments</code> — appointment list</li>
    <li><code>POST /sdk/webhooks</code> — webhook subscription via SDK token</li>
    <li><code>GET /sdk/introspect</code> — token introspection</li>
</ul>

<h3>Bridge Agent (3 endpoints)</h3>
<ul>
    <li><code>POST /bridge/sync</code> — push HIS records (visits, vitals, diagnoses, lab results, prescriptions)</li>
    <li><code>POST /bridge/heartbeat</code> — agent keepalive signal</li>
    <li><code>GET /bridge/status</code> — agent status and last sync info</li>
</ul>

<h3>Widget</h3>
<ul>
    <li>Embeddable script + iframe with <code>OpesCareWidget.init()</code></li>
    <li>Events: <code>opescare:loaded</code>, <code>opescare:consent-granted</code>, <code>opescare:error</code></li>
    <li>Themes: <code>light</code> / <code>dark</code></li>
    <li>Locale support: <code>en</code>, <code>fr</code>, <code>sw</code>, <code>ar</code></li>
</ul>

<h3>Webhooks</h3>
<ul>
    <li>7 event types: <code>appointment.created</code>, <code>appointment.updated</code>, <code>lab_result.ready</code>, <code>prescription.ready</code>, <code>consent.granted</code>, <code>payment.completed</code>, <code>patient.registered</code></li>
    <li>HMAC-SHA256 signature verification</li>
    <li>5-attempt exponential back-off retry policy</li>
    <li>Delivery logs in developer portal</li>
</ul>

<h3>Developer Portal</h3>
<ul>
    <li>Sandbox environment open — no approval required</li>
    <li>Integration client management (OAuth 2.0 credentials)</li>
    <li>SDK token generation</li>
    <li>Bridge agent registration</li>
    <li>Webhook delivery log viewer</li>
    <li>Production access request workflow</li>
</ul>

<h3>Interactive Playground</h3>
<ul>
    <li>Redoc-powered OpenAPI 3.1 explorer at <a href="{{ route('docs.playground') }}">/docs/playground</a></li>
    <li>Full machine-readable spec at <a href="{{ asset('openapi.json') }}" target="_blank">/openapi.json</a> (all endpoints)</li>
</ul>

<div class="docs-page-nav">
    <a href="{{ route('docs.playground') }}">← Playground</a>
    <span></span>
</div>

@endsection
