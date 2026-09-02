@extends('layouts.docs')
@section('title', 'Widget')
@section('content')

<h1>Embeddable Widget</h1>
<p class="docs-lead">
    The OpesCare Widget lets you embed a patient health summary panel into any web page
    with three lines of code. The widget loads via a secure iframe and requires the patient
    to authenticate with their Health ID.
</p>

<h2 id="embed">Embed Code</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab active" data-lang="html">HTML</button>
    </div>
    <div class="docs-code-pane active" data-lang="html">
<pre>&lt;!-- 1. Mount point --&gt;
&lt;div id="opescare-widget"&gt;&lt;/div&gt;

&lt;!-- 2. Loader script --&gt;
&lt;script src="https://opescare.test/widget/v1/loader.js"&gt;&lt;/script&gt;

&lt;!-- 3. Initialise --&gt;
&lt;script&gt;
OpesCareWidget.init({
  container:  '#opescare-widget',
  sdkToken:   'YOUR_SDK_TOKEN',     // Must be server-rendered — never hardcode in public JS
  facilityId: 'FAC-001',
  theme:      'light',              // 'light' | 'dark'
  locale:     'en',
  width:      '100%',
  height:     '600px',
});
&lt;/script&gt;</pre>
    </div>
</div>

<div class="docs-callout warning">
    <i data-lucide="alert-triangle" class="docs-icon"></i>
    <div>
        <strong>Server-rendered pages only.</strong> The SDK token must never appear in publicly accessible
        JavaScript bundles or client-side code. Render this snippet on the server (PHP, Node SSR, etc.).
    </div>
</div>

<h2 id="config">Configuration Options</h2>

<table class="docs-table">
    <thead><tr><th>Option</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>container</code></td><td>string</td><td>—</td><td>CSS selector for the widget mount point (required)</td></tr>
        <tr><td><code>sdkToken</code></td><td>string</td><td>—</td><td>Your SDK Bearer token (required)</td></tr>
        <tr><td><code>facilityId</code></td><td>string</td><td>—</td><td>Your registered facility ID (required)</td></tr>
        <tr><td><code>patientId</code></td><td>string</td><td><code>null</code></td><td>Pre-fill Health ID if known (optional — patient enters it themselves if omitted)</td></tr>
        <tr><td><code>theme</code></td><td>string</td><td><code>'light'</code></td><td><code>'light'</code> or <code>'dark'</code></td></tr>
        <tr><td><code>locale</code></td><td>string</td><td><code>'en'</code></td><td>UI language code (e.g. <code>'fr'</code>, <code>'sw'</code>, <code>'ar'</code>)</td></tr>
        <tr><td><code>width</code></td><td>string</td><td><code>'100%'</code></td><td>CSS width of the iframe</td></tr>
        <tr><td><code>height</code></td><td>string</td><td><code>'600px'</code></td><td>CSS height of the iframe</td></tr>
    </tbody>
</table>

<h2 id="events">JavaScript Events</h2>

<p>Listen for widget events on <code>window</code> using the standard <code>addEventListener</code>:</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab active" data-lang="js">JavaScript</button>
    </div>
    <div class="docs-code-pane active" data-lang="js">
<pre>// Widget has fully loaded and is ready
window.addEventListener('opescare:loaded', function(e) {
  console.log('Widget ready', e.detail);
});

// Patient has granted consent to your facility
// You can now call the Connect API for this patient's records
window.addEventListener('opescare:consent-granted', function(e) {
  console.log('Consent granted for Health ID:', e.detail.health_id);
  // Optionally fetch records via your backend now
  myApp.fetchPatientData(e.detail.health_id);
});

// An error occurred inside the widget
window.addEventListener('opescare:error', function(e) {
  console.error('Widget error', e.detail.code, e.detail.message);
  // Show a user-friendly message
  document.getElementById('error-banner').textContent = e.detail.message;
});</pre>
    </div>
</div>

<h3>Event Reference</h3>

<table class="docs-table">
    <thead><tr><th>Event</th><th>Payload (<code>e.detail</code>)</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>opescare:loaded</code></td><td><code>{ version }</code></td><td>Widget script and iframe loaded</td></tr>
        <tr><td><code>opescare:consent-granted</code></td><td><code>{ health_id, granted_at }</code></td><td>Patient tapped "Grant consent" inside the widget</td></tr>
        <tr><td><code>opescare:error</code></td><td><code>{ code, message }</code></td><td>Widget encountered an error (invalid token, network failure, etc.)</td></tr>
    </tbody>
</table>

<h2 id="styling">Styling</h2>

<p>The widget iframe is intentionally sandboxed — you cannot inject CSS into it. You can control its outer dimensions via the <code>width</code> and <code>height</code> config options, and its colour scheme via <code>theme: 'light'</code> or <code>'dark'</code>.</p>

<p>To match your brand's colour exactly, contact <a href="{{ route('public.contact') }}">developer support</a> about custom theme options available to approved production integrations.</p>

<h2 id="security">Security</h2>

<p>The widget iframe uses these attributes to prevent clickjacking and cross-site script injection:</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="html">Generated iframe attributes</button></div>
    <div class="docs-code-pane active" data-lang="html">
<pre>&lt;iframe
  src="https://opescare.test/widget/v1/frame?token=..."
  sandbox="allow-scripts allow-same-origin allow-forms"
  allow="camera 'none'; microphone 'none'"
  referrerpolicy="no-referrer"
  loading="lazy"
&gt;&lt;/iframe&gt;</pre>
    </div>
</div>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>OpesCare does not allow QR codes or the widget to expose full medical records. The widget shows a summary view only. Full record access requires the Connect API with patient consent.</div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.bridge') }}">← Bridge Agent</a>
    <a href="{{ route('docs.webhooks') }}">Webhooks →</a>
</div>

@endsection
