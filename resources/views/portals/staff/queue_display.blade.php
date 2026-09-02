<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>{{ __('public.stf_queue_title') }}</title>
    <meta name="theme-color" content="#0F2744">
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #0F4C81;
            --teal: #0F766E;
            --bg: #0F1B2E;
            --card: #0F2744;
            --border: rgba(255,255,255,.07);
            --text: #F1F5F9;
            --muted: #94A3B8;
            --called: #10B981;
            --waiting: #3B82F6;
            --serving: #F59E0B;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top bar ─────────────────────────── */
        .topbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-brand img { width: 2rem; height: 2rem; }

        .topbar-brand-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
        }

        .topbar-facility {
            font-size: 0.875rem;
            color: var(--muted);
            font-weight: 600;
        }

        .topbar-clock {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: .03em;
            font-variant-numeric: tabular-nums;
        }

        .topbar-date {
            font-size: 0.8125rem;
            color: var(--muted);
            text-align: right;
        }

        /* ── NOW SERVING banner ──────────────── */
        .now-serving {
            background: linear-gradient(90deg, rgba(16,185,129,.15) 0%, rgba(15,76,129,.1) 100%);
            border-bottom: 1px solid rgba(16,185,129,.2);
            padding: 2rem 2.5rem;
        }

        .now-serving-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--called);
            margin-bottom: 0.5rem;
        }

        .now-serving-number {
            font-size: clamp(3.5rem, 8vw, 6rem);
            font-weight: 900;
            color: #fff;
            line-height: 1;
            letter-spacing: -.02em;
        }

        .now-serving-room {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--called);
            margin-top: 0.5rem;
        }

        /* ── Queue grid ──────────────────────── */
        .queue-section {
            flex: 1;
            padding: 2rem 2.5rem;
        }

        .queue-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--muted);
            margin-bottom: 1.25rem;
        }

        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .ticket {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: border-color .2s;
        }

        .ticket.status-calling {
            border-color: var(--called);
            background: rgba(16,185,129,.08);
        }

        .ticket.status-serving {
            border-color: var(--serving);
            background: rgba(245,158,11,.06);
        }

        .ticket-number {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
        }

        .ticket.status-calling .ticket-number { color: var(--called); }
        .ticket.status-serving .ticket-number { color: var(--serving); }

        .ticket-name {
            font-size: 0.875rem;
            color: var(--muted);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: .25rem .6rem;
            border-radius: 999px;
        }

        .badge-waiting  { background: rgba(59,130,246,.15); color: #60A5FA; }
        .badge-calling  { background: rgba(16,185,129,.15); color: #34D399; }
        .badge-serving  { background: rgba(245,158,11,.15); color: #FCD34D; }
        .badge-done     { background: rgba(100,116,139,.15); color: #94A3B8; }

        .pulse {
            animation: pulseAnim 1.5s infinite;
        }

        @keyframes pulseAnim {
            0%, 100% { opacity: 1; }
            50% { opacity: .4; }
        }

        /* ── Empty state ─────────────────────── */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            gap: 1rem;
            color: var(--muted);
            padding: 4rem;
            text-align: center;
        }

        .empty-state svg { opacity: .25; }

        /* ── Footer ticker ───────────────────── */
        .ticker {
            background: var(--primary);
            padding: .75rem 2.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255,255,255,.85);
            display: flex;
            align-items: center;
            gap: 1rem;
            overflow: hidden;
        }

        .ticker-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            background: #38BDF8;
            flex-shrink: 0;
        }

        /* ── Mobile ──────────────────────────── */
        @media (max-width: 640px) {
            .topbar { padding: 1rem 1.25rem; }
            .topbar-clock { font-size: 1.25rem; }
            .now-serving { padding: 1.5rem 1.25rem; }
            .queue-section { padding: 1.5rem 1.25rem; }
            .queue-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .topbar-clockwrap { text-align: right; }
        .ticket-wait { font-size: .75rem; color: var(--muted); margin-top: .25rem; }
        .empty-state h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-top: .5rem; }
    </style>
</head>
<body>

    {{-- Top bar --}}
    <div class="topbar">
        <div class="topbar-brand">
            <img src="{{ asset('brand/opescare-favicon.png') }}" alt="OpesCare">
            <div>
                <div class="topbar-brand-name">OpesCare</div>
                <div class="topbar-facility">{{ __('public.stf_queue_header') }}</div>
            </div>
        </div>
        <div class="topbar-clockwrap">
            <div class="topbar-clock" id="clock">--:--</div>
            <div class="topbar-date" id="date-display"></div>
        </div>
    </div>

    @php
        $calling = collect($tickets ?? [])->where('status', 'calling')->first();
        $serving = collect($tickets ?? [])->where('status', 'serving');
        $waiting = collect($tickets ?? [])->where('status', 'waiting');
        $allQueued = collect($tickets ?? []);
    @endphp

    {{-- Now serving / calling --}}
    @if($calling)
    <div class="now-serving">
        <div class="now-serving-label pulse">{{ __('public.stf_queue_now_calling') }}</div>
        <div class="now-serving-number">{{ $calling['queue_number'] }}</div>
        <div class="now-serving-room">
            {{ $calling['masked_patient_name'] ?? '—' }}
            @if(!empty($calling['room'])) &nbsp;·&nbsp; {{ $calling['room'] }} @endif
        </div>
    </div>
    @elseif($serving->isNotEmpty())
    <div class="now-serving">
        <div class="now-serving-label">{{ __('public.stf_queue_being_served') }}</div>
        <div class="now-serving-number">{{ $serving->first()['queue_number'] }}</div>
        <div class="now-serving-room">{{ $serving->first()['masked_patient_name'] ?? '—' }}</div>
    </div>
    @endif

    {{-- Queue grid --}}
    @if($allQueued->isNotEmpty())
    <div class="queue-section">
        <div class="queue-section-title">{{ __('public.stf_queue_waiting') }} — {{ __('staff_core.queue_waiting_count', ['count' => $waiting->count()], app()->getLocale()) ?: $waiting->count() . ' patient(s)' }}</div>
        <div class="queue-grid">
            @foreach($allQueued as $ticket)
            @php
                $statusClass = match($ticket['status'] ?? 'waiting') {
                    'calling' => 'status-calling',
                    'serving' => 'status-serving',
                    default   => '',
                };
                $badgeClass = match($ticket['status'] ?? 'waiting') {
                    'calling' => 'badge-calling',
                    'serving' => 'badge-serving',
                    'done'    => 'badge-done',
                    default   => 'badge-waiting',
                };
            @endphp
            <div class="ticket {{ $statusClass }}">
                <div class="ticket-number">{{ $ticket['queue_number'] }}</div>
                <div class="ticket-name">{{ $ticket['masked_patient_name'] ?? (__('staff_core.ticket_patient_fallback', [], app()->getLocale()) ?: 'Patient') }}</div>
                <div class="ticket-badge {{ $badgeClass }}">
                    @if(($ticket['status'] ?? '') === 'calling')
                        <span class="pulse">●</span>
                    @else
                        <span>●</span>
                    @endif
                    @enum($ticket['status'] ?? 'waiting')
                </div>
                @if(!empty($ticket['wait_minutes']))
                <div class="ticket-wait">~{{ $ticket['wait_minutes'] }} {{ __('public.stf_queue_min_wait') }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <h2>{{ __('public.stf_queue_no_patients') }}</h2>
        <p>{{ __('public.stf_queue_empty_msg') }}</p>
    </div>
    @endif

    {{-- Footer ticker --}}
    <div class="ticker">
        <div class="ticker-dot"></div>
        <span>{{ __('public.stf_queue_refresh_msg') }} &nbsp;·&nbsp; {{ __('public.stf_queue_mgmt') }} &nbsp;·&nbsp; {{ now()->format('l, d F Y') }}</span>
    </div>

    <script>
        // Live clock
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2,'0');
            const m = String(now.getMinutes()).padStart(2,'0');
            const s = String(now.getSeconds()).padStart(2,'0');
            const el = document.getElementById('clock');
            if (el) el.textContent = h + ':' + m + ':' + s;

            const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const dateEl = document.getElementById('date-display');
            if (dateEl) dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
