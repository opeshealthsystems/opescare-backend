<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.qr_title', [], app()->getLocale()) ?: 'QR Verification — OpesCare' }}</title>
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

        /* Loading state (server-side renders loading if no result yet) */
        .qr-card {
            width: 100%;
            max-width: 520px;
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

        /* Token display */
        .token-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #F0F4F8;
            border: 1px solid #E2E8F0;
            border-radius: 0.5rem;
            padding: 0.625rem 1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0F4C81;
            letter-spacing: 0.06em;
            word-break: break-all;
            margin-bottom: 1.75rem;
        }

        /* Status states */
        .state-loading {
            text-align: center;
            padding: 2rem 0;
        }
        .spinner {
            width: 2.5rem;
            height: 2.5rem;
            border: 3px solid #E2E8F0;
            border-top-color: #0F4C81;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .state-verified {
            background: #F0FDF9;
            border: 1.5px solid #6EE7B7;
            border-radius: 0.875rem;
            padding: 1.5rem;
        }

        .state-expired {
            background: #FFF7ED;
            border: 1.5px solid #FDE68A;
            border-radius: 0.875rem;
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #92400E;
        }

        .state-invalid {
            background: #FFF1F2;
            border: 1.5px solid #FCA5A5;
            border-radius: 0.875rem;
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #991B1B;
        }

        .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        .result-cell {}
        .result-label { font-size: 0.7rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
        .result-value { font-size: 0.9375rem; font-weight: 700; color: #0F172A; }
        .result-value.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #0F4C81; }

        .verified-header {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-weight: 800;
            font-size: 0.9375rem;
            color: #065F46;
            margin-bottom: 1rem;
        }
        .verified-dot {
            width: 0.625rem;
            height: 0.625rem;
            background: #10B981;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .audit-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #64748B;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #D1FAE5;
        }

        /* Allergy strip */
        .allergy-strip {
            background: #FFF7ED;
            border: 1px solid #FDE68A;
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
            font-size: 0.8125rem;
            color: #92400E;
            font-weight: 600;
            margin-top: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Footer */
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
                <i data-lucide="qr-code" style="width:1.625rem;height:1.625rem;color:#fff;"></i>
            </div>
            <h1>{{ __('verify.qr_heading', [], app()->getLocale()) ?: 'QR Health ID Verification' }}</h1>
            <p>{{ __('verify.qr_subheading', [], app()->getLocale()) ?: 'Scanning this token will reveal the patient\'s verified identity for clinical purposes only.' }}</p>
        </div>

        <div class="qr-card-body">

            <div class="token-display">
                <i data-lucide="key" style="width:0.875rem;height:0.875rem;flex-shrink:0;"></i>
                {{ Str::limit($token, 36, '…') }}
            </div>

            @if($error ?? null)
                <div class="state-invalid" role="alert">
                    <i data-lucide="x-circle" style="width:1.125rem;height:1.125rem;flex-shrink:0;"></i>
                    <div>
                        <strong style="display:block;margin-bottom:0.2rem;">{{ __('verify.qr_invalid_title', [], app()->getLocale()) ?: 'Invalid Token' }}</strong>
                        {{ $error }}
                    </div>
                </div>
            @elseif($result === null)
                {{-- Stub / pending state --}}
                <div class="state-loading" role="status" aria-live="polite">
                    <div class="spinner" aria-hidden="true"></div>
                    <p style="font-size:0.9rem;color:#475569;font-weight:600;">{{ __('verify.qr_processing', [], app()->getLocale()) ?: 'Validating token…' }}</p>
                    <p style="font-size:0.8rem;color:#94A3B8;margin-top:0.375rem;">{{ __('verify.qr_processing_note', [], app()->getLocale()) ?: 'This usually takes less than a second.' }}</p>
                </div>
            @elseif(isset($result->expired) && $result->expired)
                <div class="state-expired" role="alert">
                    <i data-lucide="clock" style="width:1.125rem;height:1.125rem;flex-shrink:0;"></i>
                    <div>
                        <strong style="display:block;margin-bottom:0.2rem;">{{ __('verify.qr_expired_title', [], app()->getLocale()) ?: 'QR Code Expired' }}</strong>
                        {{ __('verify.qr_expired_body', [], app()->getLocale()) ?: 'This QR code has expired. Ask the patient to generate a new one from their patient portal.' }}
                    </div>
                </div>
            @elseif(isset($result->valid) && !$result->valid)
                <div class="state-invalid" role="alert">
                    <i data-lucide="x-circle" style="width:1.125rem;height:1.125rem;flex-shrink:0;"></i>
                    <div>
                        <strong style="display:block;margin-bottom:0.2rem;">{{ __('verify.qr_invalid_title', [], app()->getLocale()) ?: 'Invalid Token' }}</strong>
                        {{ __('verify.qr_invalid_body', [], app()->getLocale()) ?: 'This token is not recognised. It may have been tampered with or already used. Contact support if this persists.' }}
                    </div>
                </div>
            @else
                <div class="state-verified" role="region" aria-label="Verification result">
                    <div class="verified-header">
                        <div class="verified-dot"></div>
                        {{ __('verify.result_verified', [], app()->getLocale()) ?: 'Identity Verified' }}
                    </div>
                    <div class="result-grid">
                        <div class="result-cell">
                            <div class="result-label">{{ __('verify.result_name', [], app()->getLocale()) ?: 'Name' }}</div>
                            <div class="result-value">{{ $result->name ?? '—' }}</div>
                        </div>
                        <div class="result-cell">
                            <div class="result-label">{{ __('verify.result_health_id', [], app()->getLocale()) ?: 'Health ID' }}</div>
                            <div class="result-value mono">{{ $result->health_id ?? '—' }}</div>
                        </div>
                        <div class="result-cell">
                            <div class="result-label">{{ __('verify.result_dob', [], app()->getLocale()) ?: 'Date of Birth' }}</div>
                            <div class="result-value">{{ isset($result->dob) ? \Carbon\Carbon::parse($result->dob)->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="result-cell">
                            <div class="result-label">{{ __('verify.result_blood_type', [], app()->getLocale()) ?: 'Blood Type' }}</div>
                            <div class="result-value">{{ $result->blood_type ?? '—' }}</div>
                        </div>
                    </div>
                    @if(!empty($result->allergies))
                    <div class="allergy-strip">
                        <i data-lucide="alert-triangle" style="width:0.875rem;height:0.875rem;flex-shrink:0;"></i>
                        <span><strong>{{ __('verify.allergies', [], app()->getLocale()) ?: 'Allergies:' }}</strong> {{ $result->allergies }}</span>
                    </div>
                    @endif
                    <div class="audit-note">
                        <i data-lucide="shield-check" style="width:0.875rem;height:0.875rem;color:#0F766E;flex-shrink:0;"></i>
                        {{ __('verify.audit_note', [], app()->getLocale()) ?: 'This access has been logged and is visible to the patient in their portal.' }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    <div class="verify-footer">
        <p>
            <a href="{{ route('verify.health-id') }}">{{ __('verify.footer_manual_verify', [], app()->getLocale()) ?: 'Manual ID Lookup' }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.help') }}">{{ __('verify.footer_help', [], app()->getLocale()) ?: 'Help Center' }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.contact') }}">{{ __('verify.footer_contact', [], app()->getLocale()) ?: 'Contact Support' }}</a>
        </p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Auto-reload spinner after 1.5s if result is still null and no error
    // (in production this would be a real API call from the controller)
    @if($result === null && !($error ?? null))
    setTimeout(function() {
        var spinner = document.querySelector('.state-loading');
        if (spinner) {
            spinner.innerHTML = '<p style="font-size:0.875rem;color:#94A3B8;">{{ __('verify.qr_token_expired_ui', [], app()->getLocale()) ?: "Token not found or expired. Please ask the patient to regenerate their QR code." }}</p><div style="margin-top:1.25rem;"><a href="{{ route('verify.health-id') }}" style="display:inline-flex;align-items:center;gap:0.5rem;background:#0F4C81;color:#fff;font-size:0.875rem;font-weight:700;border-radius:0.5rem;padding:0.625rem 1.25rem;text-decoration:none;">{{ __('verify.footer_manual_verify', [], app()->getLocale()) ?: 'Manual ID Lookup' }}</a></div>';
        }
    }, 3000);
    @endif
});
</script>
</body>
</html>
