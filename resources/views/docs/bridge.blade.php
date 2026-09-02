@extends('layouts.docs')
@section('title', 'Bridge Agent')
@section('content')

<h1>Bridge Agent</h1>
<p class="docs-lead">
    The OpesCare Bridge Agent is an installable Python daemon you deploy on-premise next to your
    local facility system or Hospital Information System (HIS). It continuously syncs records
    to OpesCare — without requiring your system to be internet-accessible or to implement the
    full Connect API.
</p>

<h2 id="how-it-works">How it Works</h2>

<ol>
    <li>Obtain your <code>agent_id</code> and <code>agent_key</code> from the OpesCare admin panel.</li>
    <li>Install the Bridge Agent: <code>pip install opescare-bridge-agent</code></li>
    <li>Configure it with your credentials in <code>bridge_config.json</code>.</li>
    <li>Point it at a folder where your system exports CSV files.</li>
    <li>Run <code>opescare-bridge --config bridge_config.json</code> — it watches the folder, queues records locally, and syncs to OpesCare when connected.</li>
    <li>Every 60 seconds the agent sends a heartbeat so OpesCare knows it is alive.</li>
</ol>

<div class="docs-callout info">
    <i data-lucide="info" class="docs-icon"></i>
    <div>
        If your system goes offline, records are queued in a local SQLite database and synced automatically
        when the connection is restored. Records are only removed from the queue after OpesCare confirms receipt.
    </div>
</div>

<h2 id="install">Installation</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="shell">Shell</button></div>
    <div class="docs-code-pane active" data-lang="shell">
<pre>pip install opescare-bridge-agent

# Verify installation
opescare-bridge --help</pre>
    </div>
</div>

<h2 id="setup">Configuration</h2>

<p>Create a <code>bridge_config.json</code> file. Set <code>OPESCARE_AGENT_KEY</code> as an environment variable in production — do not put the key in the config file.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">bridge_config.json</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "server_url":   "https://api.opescare.com",
  "agent_id":     "YOUR_AGENT_ID_FROM_OPESCARE_ADMIN",
  "agent_key":    "SET_VIA_OPESCARE_AGENT_KEY_ENV_VAR",
  "facility_id":  "YOUR_FACILITY_UUID",
  "environment":  "production",
  "heartbeat_interval_seconds": 60,
  "sync_interval_seconds": 300,
  "log_level": "INFO",
  "connector": {
    "type":         "csv_folder",
    "watch_folder": "./data/incoming",
    "file_pattern": "*.csv"
  }
}</pre>
    </div>
</div>

<h2 id="run">Running the Agent</h2>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="shell">Shell</button></div>
    <div class="docs-code-pane active" data-lang="shell">
<pre># Set your agent key as an environment variable
export OPESCARE_AGENT_KEY=your_agent_key_here

# Start the agent
opescare-bridge --config bridge_config.json</pre>
    </div>
</div>

<h2 id="csv-convention">CSV File Naming Convention</h2>

<p>The Bridge Agent detects the record type automatically from the CSV filename:</p>

<table class="docs-table">
    <thead><tr><th>Filename Contains</th><th>Record Type</th><th>Key CSV Columns</th></tr></thead>
    <tbody>
        <tr><td><code>patients_</code></td><td>Patient registration</td><td>first_name, last_name, dob, sex, phone</td></tr>
        <tr><td><code>encounters_</code></td><td>Encounter / visit</td><td>health_id, notes, severity, date</td></tr>
        <tr><td><code>lab_results_</code></td><td>Lab result</td><td>health_id, test, result, flagged, date</td></tr>
        <tr><td><code>prescriptions_</code></td><td>Prescription</td><td>health_id, medicine, dose, frequency</td></tr>
    </tbody>
</table>

<p>Example: a file named <code>lab_results_2026-06-01.csv</code> will be detected as lab result records and pushed to <code>/api/v1/bridge/sync</code> automatically.</p>

<h2 id="sync">Sync Endpoint (Direct API)</h2>

<p>If you are building a custom integration rather than using the daemon, call the sync endpoint directly with the <code>X-Bridge-Agent-ID</code> and <code>X-Bridge-Agent-Key</code> headers.</p>

<div class="endpoint-pill">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-path">/api/v1/bridge/sync</span>
</div>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
        <button class="docs-code-tab" data-lang="php">PHP</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl -X POST https://opescare.test/api/v1/bridge/sync \
  -H "X-Bridge-Agent-ID: YOUR_AGENT_ID" \
  -H "X-Bridge-Agent-Key: YOUR_AGENT_KEY" \
  -H "X-Bridge-Timestamp: $(date +%s)" \
  -H "Idempotency-Key: sync-$(uuidgen)" \
  -H "Content-Type: application/json" \
  -d '{
    "agent_id": "YOUR_AGENT_ID",
    "records": [
      {
        "record_type": "lab_result",
        "health_id": "CM-HID-7KQ9-MP42-X8D1",
        "test_name": "HbA1c",
        "result_value": "9.2%",
        "flagged": true,
        "occurred_at": "2026-06-01T08:00:00Z",
        "source_system": "bridge_agent_csv"
      }
    ]
  }'</pre>
    </div>
    <div class="docs-code-pane" data-lang="php">
<pre>Http::withHeaders([
    'X-Bridge-Agent-ID'  =&gt; env('BRIDGE_AGENT_ID'),
    'X-Bridge-Agent-Key' =&gt; env('BRIDGE_AGENT_KEY'),
    'X-Bridge-Timestamp' =&gt; time(),
    'Idempotency-Key'    =&gt; 'sync-' . \Str::uuid(),
])->post('https://opescare.test/api/v1/bridge/sync', [
    'agent_id' =&gt; env('BRIDGE_AGENT_ID'),
    'records'  =&gt; [
        [
            'record_type'  =&gt; 'lab_result',
            'health_id'    =&gt; 'CM-HID-7KQ9-MP42-X8D1',
            'test_name'    =&gt; 'HbA1c',
            'result_value' =&gt; '9.2%',
            'flagged'      =&gt; true,
        ],
    ],
]);</pre>
    </div>
    <div class="docs-code-pane" data-lang="python">
<pre>import requests, os, time, uuid

requests.post(
    'https://opescare.test/api/v1/bridge/sync',
    headers={
        'X-Bridge-Agent-ID':  os.environ['BRIDGE_AGENT_ID'],
        'X-Bridge-Agent-Key': os.environ['BRIDGE_AGENT_KEY'],
        'X-Bridge-Timestamp': str(int(time.time())),
        'Idempotency-Key':    f'sync-{uuid.uuid4().hex}',
    },
    json={
        'agent_id': os.environ['BRIDGE_AGENT_ID'],
        'records': [
            {
                'record_type':  'lab_result',
                'health_id':    'CM-HID-7KQ9-MP42-X8D1',
                'test_name':    'HbA1c',
                'result_value': '9.2%',
                'flagged':      True,
            },
        ],
    }
)</pre>
    </div>
</div>

<p><strong>Response:</strong></p>
<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">JSON</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "status": "ok",
  "accepted": 1,
  "rejected": 0,
  "sync_id": "sync_a8f3k12x"
}</pre>
    </div>
</div>

<h2 id="heartbeat">Heartbeat</h2>

<div class="endpoint-pill">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-path">/api/v1/bridge/heartbeat</span>
</div>

<p>The daemon sends this automatically every 60 seconds. Agents silent for 15+ minutes are marked <code>offline</code> in the admin panel.</p>

<div class="docs-code-block">
    <div class="docs-code-tabs">
        <button class="docs-code-tab" data-lang="curl">cURL</button>
        <button class="docs-code-tab" data-lang="python">Python</button>
    </div>
    <div class="docs-code-pane" data-lang="curl">
<pre>curl -X POST https://opescare.test/api/v1/bridge/heartbeat \
  -H "X-Bridge-Agent-ID: YOUR_AGENT_ID" \
  -H "X-Bridge-Agent-Key: YOUR_AGENT_KEY" \
  -H "X-Bridge-Timestamp: $(date +%s)" \
  -H "Content-Type: application/json" \
  -d '{"agent_id":"YOUR_AGENT_ID","facility_id":"YOUR_FACILITY_ID","version":"1.0.0","environment":"production"}'</pre>
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
        'environment': 'production',
    }
)</pre>
    </div>
</div>

<h2 id="status">Status</h2>

<div class="endpoint-pill">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-path">/api/v1/bridge/status</span>
</div>

<div class="docs-code-block">
    <div class="docs-code-tabs"><button class="docs-code-tab active" data-lang="json">Response</button></div>
    <div class="docs-code-pane active" data-lang="json">
<pre>{
  "agent_id": "YOUR_AGENT_ID",
  "status": "online",
  "last_sync_at": "2026-06-01T09:30:00Z",
  "last_heartbeat_at": "2026-06-01T09:32:00Z",
  "records_synced_today": 127,
  "queue_depth": 0,
  "version": "1.0.0"
}</pre>
    </div>
</div>

<div class="docs-page-nav">
    <a href="{{ route('docs.sdk') }}">← SDK</a>
    <a href="{{ route('docs.widget') }}">Widget →</a>
</div>

@endsection
