<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.rx_title') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0F4C81">
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F0F4F8;
            color: #0F172A;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: #0F2744;
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            font-size: 1.0625rem;
        }
        .topbar-brand img { width: 26px; height: 26px; flex-shrink: 0; }

        .page-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem 4rem;
        }

        .qr-card {
            width: 100%;
            max-width: 680px;
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(15,76,129,.12);
            overflow: hidden;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

        .qr-card-header {
            background: linear-gradient(135deg, #0F2744 0%, #0F4C81 100%);
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
        .qr-pulse-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: rgba(255,255,255,.12);
            border: 1.5px solid rgba(255,255,255,.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .qr-card-header h1 { font-size: 1.25rem; font-weight: 800; margin-bottom: 0.25rem; }
        .qr-card-header p  { font-size: 0.8125rem; color: #BAE6FD; line-height: 1.5; }

        .qr-card-body { padding: 2rem; }

        /* The reference is the thing a pharmacist reads aloud — it gets the size. */
        .rx-reference {
            text-align: center;
            background: #F0F4F8;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .rx-reference-label {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.375rem;
        }
        .rx-reference-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 1.5rem;
            font-weight: 800;
            color: #0F4C81;
            letter-spacing: 0.08em;
            word-break: break-all;
        }

        /* Banners — one per state, none of them a small grey badge. */
        .rx-banner {
            border-radius: 0.875rem;
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .rx-banner strong { display: block; font-size: 1rem; margin-bottom: 0.3rem; }
        .rx-banner p { font-size: 0.875rem; line-height: 1.5; }
        .rx-banner-icon { flex-shrink: 0; margin-top: 0.1rem; }

        .rx-banner.is-stop     { background: #FFF1F2; border: 2px solid #EF4444; color: #991B1B; }
        .rx-banner.is-expired  { background: #FFF7ED; border: 2px solid #F59E0B; color: #92400E; }
        .rx-banner.is-done     { background: #F8FAFC; border: 1.5px solid #CBD5E1; color: #334155; }
        .rx-banner.is-valid    { background: #F0FDF9; border: 1.5px solid #6EE7B7; color: #065F46; }
        .rx-banner.is-invalid  { background: #FFF1F2; border: 1.5px solid #FCA5A5; color: #991B1B; }

        .rx-section-title {
            font-size: 0.6875rem;
            font-weight: 800;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 1.75rem 0 0.75rem;
        }

        .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.25rem;
        }
        .result-label { font-size: 0.7rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
        .result-value { font-size: 0.9375rem; font-weight: 700; color: #0F172A; }
        .result-value.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #0F4C81; }
        .result-value.muted { font-weight: 600; color: #94A3B8; }

        .rx-table-wrap { overflow-x: auto; border: 1px solid #E2E8F0; border-radius: 0.75rem; }
        table.rx-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; min-width: 520px; }
        table.rx-table th {
            text-align: left;
            font-size: 0.6875rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #F8FAFC;
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }
        table.rx-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: top;
            color: #334155;
        }
        table.rx-table tr:last-child td { border-bottom: none; }
        table.rx-table td.drug { font-weight: 700; color: #0F172A; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.6875rem;
            font-weight: 800;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            white-space: nowrap;
        }
        .pill-dispensed { background: #D1FAE5; color: #065F46; }
        .pill-pending   { background: #E0F2FE; color: #075985; }
        .pill-stopped   { background: #FEE2E2; color: #991B1B; }
        .pill-neutral   { background: #F1F5F9; color: #475569; }

        .rx-empty {
            padding: 1.25rem;
            text-align: center;
            font-size: 0.875rem;
            color: #64748B;
        }

        .audit-note {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.75rem;
            line-height: 1.5;
            color: #64748B;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #E2E8F0;
        }

        .verify-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #94A3B8;
            margin-top: 1.75rem;
        }
        .verify-footer a { color: #0F4C81; text-decoration: none; font-weight: 600; }

        @media (max-width: 540px) {
            .topbar { padding: 0.75rem 1rem; }
            .qr-card-body { padding: 1.5rem 1.25rem; }
            .result-grid { grid-template-columns: 1fr; }
            .rx-reference-value { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="{{ url('/') }}" class="topbar-brand">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="OpesCare">
        OpesCare
    </a>
</header>

<div class="page-wrap">

    <div class="qr-card">
        <div class="qr-card-header">
            <div class="qr-pulse-icon">
                <i data-lucide="pill" style="width:1.625rem;height:1.625rem;color:#fff;"></i>
            </div>
            <h1>{{ __('verify.rx_heading') }}</h1>
            <p>{{ __('verify.rx_subheading') }}</p>
        </div>

        <div class="qr-card-body">

            @if($error ?? null)
                <div class="rx-banner is-invalid" role="alert">
                    <i data-lucide="x-circle" class="rx-banner-icon" style="width:1.25rem;height:1.25rem;"></i>
                    <div>
                        <strong>{{ __('verify.rx_invalid_title') }}</strong>
                        <p>{{ $error }}</p>
                    </div>
                </div>
            @elseif($rx === null)
                <div class="rx-banner is-invalid" role="alert">
                    <i data-lucide="x-circle" class="rx-banner-icon" style="width:1.25rem;height:1.25rem;"></i>
                    <div>
                        <strong>{{ __('verify.rx_invalid_title') }}</strong>
                        <p>{{ __('verify.rx_invalid_body') }}</p>
                    </div>
                </div>
            @else

                <div class="rx-reference">
                    <div class="rx-reference-label">{{ __('verify.rx_reference_label') }}</div>
                    <div class="rx-reference-value">{{ $rx->reference ?: __('verify.rx_no_reference') }}</div>
                </div>

                {{-- The verdict comes first and it is never a small grey badge. --}}
                @if($rx->state === 'retracted')
                    <div class="rx-banner is-stop" role="alert">
                        <i data-lucide="ban" class="rx-banner-icon" style="width:1.5rem;height:1.5rem;"></i>
                        <div>
                            <strong>{{ __('verify.rx_do_not_dispense') }}</strong>
                            <p>{{ __('verify.rx_retracted_body', ['status' => $rx->status_label]) }}</p>
                        </div>
                    </div>
                @elseif($rx->state === 'expired')
                    <div class="rx-banner is-expired" role="alert">
                        <i data-lucide="clock-alert" class="rx-banner-icon" style="width:1.5rem;height:1.5rem;"></i>
                        <div>
                            <strong>{{ __('verify.rx_do_not_dispense') }}</strong>
                            <p>{{ __('verify.rx_expired_body') }}</p>
                        </div>
                    </div>
                @elseif($rx->state === 'dispensed')
                    <div class="rx-banner is-done" role="status">
                        <i data-lucide="package-check" class="rx-banner-icon" style="width:1.5rem;height:1.5rem;"></i>
                        <div>
                            <strong>{{ __('verify.rx_dispensed_title') }}</strong>
                            <p>{{ __('verify.rx_dispensed_body') }}</p>
                        </div>
                    </div>
                @else
                    <div class="rx-banner is-valid" role="status">
                        <i data-lucide="shield-check" class="rx-banner-icon" style="width:1.5rem;height:1.5rem;"></i>
                        <div>
                            <strong>{{ __('verify.rx_valid_title') }}</strong>
                            <p>{{ __('verify.rx_valid_body') }}</p>
                        </div>
                    </div>
                @endif

                <div class="rx-section-title">{{ __('verify.rx_section_patient') }}</div>
                <div class="result-grid">
                    <div>
                        <div class="result-label">{{ __('verify.rx_patient') }}</div>
                        <div class="result-value">{{ $rx->patient_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_health_id') }}</div>
                        <div class="result-value mono">{{ $rx->patient_health_id ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                </div>

                <div class="rx-section-title">{{ __('verify.rx_section_order') }}</div>
                <div class="result-grid">
                    <div>
                        <div class="result-label">{{ __('verify.rx_facility') }}</div>
                        <div class="result-value">{{ $rx->facility_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_prescriber') }}</div>
                        <div class="result-value">{{ $rx->prescriber_name ?: __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_prescribed_at') }}</div>
                        <div class="result-value">{{ $rx->prescribed_at ? $rx->prescribed_at->format('d M Y H:i') : __('verify.rx_not_recorded') }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_expires_at') }}</div>
                        <div class="result-value">{{ $rx->expires_at ? $rx->expires_at->format('d M Y') : __('verify.rx_no_expiry') }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_status') }}</div>
                        <div class="result-value">{{ $rx->status_label }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.rx_dispensed_at') }}</div>
                        <div class="result-value {{ $rx->dispensed_at ? '' : 'muted' }}">
                            {{ $rx->dispensed_at ? $rx->dispensed_at->format('d M Y H:i') : __('verify.rx_not_dispensed') }}
                        </div>
                    </div>
                </div>

                <div class="rx-section-title">{{ __('verify.rx_section_medicines') }}</div>
                <div class="rx-table-wrap">
                    @if($rx->items->isEmpty())
                        <div class="rx-empty">{{ __('verify.rx_no_items') }}</div>
                    @else
                        <table class="rx-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('verify.rx_col_medicine') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_dose') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_frequency') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_route') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_duration') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_quantity') }}</th>
                                    <th scope="col">{{ __('verify.rx_col_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rx->items as $item)
                                    <tr>
                                        <td class="drug">{{ $item->drug_name ?: __('verify.rx_not_recorded') }}</td>
                                        <td>{{ $item->dose ?: '—' }}</td>
                                        <td>{{ $item->frequency ?: '—' }}</td>
                                        <td>{{ $item->route ?: '—' }}</td>
                                        <td>{{ $item->duration_days ? __('verify.rx_days', ['count' => $item->duration_days]) : '—' }}</td>
                                        <td>{{ $item->quantity ?: '—' }}</td>
                                        <td>
                                            @php
                                                $pill = match ($item->status) {
                                                    'dispensed' => 'pill-dispensed',
                                                    'pending'   => 'pill-pending',
                                                    'voided', 'cancelled', 'entered_in_error' => 'pill-stopped',
                                                    default     => 'pill-neutral',
                                                };
                                            @endphp
                                            <span class="pill {{ $pill }}">{{ $item->status_label }}</span>
                                            @if($item->dispensed_at)
                                                <div style="font-size:0.6875rem;color:#94A3B8;margin-top:0.25rem;white-space:nowrap;">
                                                    {{ $item->dispensed_at->format('d M Y') }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="audit-note">
                    <i data-lucide="shield-check" style="width:0.875rem;height:0.875rem;color:#0F766E;flex-shrink:0;margin-top:0.1rem;"></i>
                    <span>{{ __('verify.rx_audit_note') }}</span>
                </div>

            @endif

        </div>
    </div>

    <div class="verify-footer">
        <p>
            <a href="{{ route('verify.health-id') }}">{{ __('verify.footer_manual_verify') }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.help') }}">{{ __('verify.footer_help') }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.contact') }}">{{ __('verify.footer_contact') }}</a>
        </p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
