<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.health_id_title', [], app()->getLocale()) ?: 'Health ID Verification — OpesCare' }}</title>
    <meta name="description" content="Verify a patient's OpesCare Health ID. For authorised healthcare providers only.">
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

        /* Topbar */
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
        .topbar-badge {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 2rem;
            padding: 0.3rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #BAE6FD;
            letter-spacing: 0.04em;
        }

        /* Layout */
        .page-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 3rem 1.25rem 4rem;
        }

        .verify-card {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(15,76,129,.1);
            overflow: hidden;
        }

        .verify-card-header {
            background: linear-gradient(135deg, #0F2744 0%, #0F4C81 100%);
            padding: 2.25rem 2rem 2rem;
            text-align: center;
            color: #fff;
        }

        .verify-icon-wrap {
            width: 3.5rem;
            height: 3.5rem;
            background: rgba(255,255,255,.12);
            border: 1.5px solid rgba(255,255,255,.25);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .verify-card-header h1 {
            font-size: 1.375rem;
            font-weight: 800;
            margin-bottom: 0.375rem;
        }

        .verify-card-header p {
            font-size: 0.875rem;
            color: #BAE6FD;
            line-height: 1.5;
        }

        .verify-card-body { padding: 2rem; }

        /* Disclaimer */
        .disclaimer {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: #FFF7ED;
            border: 1px solid #FDE68A;
            border-radius: 0.625rem;
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            color: #92400E;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }
        .disclaimer svg { flex-shrink: 0; margin-top: 1px; }

        /* Form */
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 0.375rem;
        }
        .form-label span { color: #DC2626; }

        .form-control {
            width: 100%;
            padding: 0.6875rem 0.875rem;
            border: 1.5px solid #E2E8F0;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            color: #0F172A;
            background: #F8FAFC;
            outline: none;
            transition: border-color .15s;
            font-family: inherit;
        }
        .form-control:focus { border-color: #0F4C81; background: #fff; }
        .form-control.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }

        .form-hint { font-size: 0.75rem; color: #64748B; margin-top: 0.3rem; }
        .form-error { font-size: 0.75rem; color: #DC2626; margin-top: 0.3rem; }

        .form-group { margin-bottom: 1.25rem; }

        .btn-verify {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #0F4C81;
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 700;
            border: none;
            border-radius: 0.625rem;
            padding: 0.875rem 1.5rem;
            cursor: pointer;
            transition: background .2s;
            margin-top: 0.5rem;
        }
        .btn-verify:hover { background: #0A355C; }

        /* Result panels */
        .result-success {
            background: #F0FDF9;
            border: 1.5px solid #6EE7B7;
            border-radius: 0.875rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .result-error {
            background: #FFF1F2;
            border: 1.5px solid #FCA5A5;
            border-radius: 0.875rem;
            padding: 1.25rem;
            margin-top: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #991B1B;
        }

        .result-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.875rem;
        }

        .result-label { font-size: 0.75rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em; }
        .result-value { font-size: 0.9375rem; font-weight: 700; color: #0F172A; }
        .result-id    { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #0F4C81; }

        .status-dot {
            width: 0.625rem;
            height: 0.625rem;
            border-radius: 50%;
            background: #10B981;
            flex-shrink: 0;
        }

        /* Audit trail note */
        .audit-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #64748B;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #E2E8F0;
        }

        /* Footer */
        .verify-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #94A3B8;
            margin-top: 2rem;
        }
        .verify-footer a { color: #0F4C81; text-decoration: none; font-weight: 600; }

        @media (max-width: 600px) {
            .topbar { padding: 0.75rem 1rem; }
            .verify-card-body { padding: 1.5rem 1.25rem; }
            .verify-card-header { padding: 1.75rem 1.25rem 1.5rem; }
            .result-detail-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="{{ url('/') }}" class="topbar-brand">
        <img src="{{ asset('brand/opescare-favicon.png') }}" alt="OpesCare">
        OpesCare
    </a>
    <div class="topbar-badge">
        <i data-lucide="shield-check" style="width:0.75rem;height:0.75rem;"></i>
        {{ __('verify.badge_provider_tool', [], app()->getLocale()) ?: 'Provider Verification Tool' }}
    </div>
</header>

<div class="page-wrap">

    <div class="verify-card">
        <div class="verify-card-header">
            <div class="verify-icon-wrap">
                <i data-lucide="id-card" style="width:1.75rem;height:1.75rem;color:#fff;"></i>
            </div>
            <h1>{{ __('verify.health_id_heading', [], app()->getLocale()) ?: 'Health ID Verification' }}</h1>
            <p>{{ __('verify.health_id_subheading', [], app()->getLocale()) ?: 'Look up a patient\'s verified identity and active record status. For authorised clinical providers only.' }}</p>
        </div>

        <div class="verify-card-body">

            <div class="disclaimer" role="alert">
                <i data-lucide="triangle-alert" style="width:1rem;height:1rem;flex-shrink:0;"></i>
                <span>{{ __('verify.disclaimer', [], app()->getLocale()) ?: 'This tool is for authorised healthcare providers. Every verification is logged and auditable by the patient. Unauthorised access is a criminal offence.' }}</span>
            </div>

            @if($error)
            <div class="result-error" role="alert">
                <i data-lucide="x-circle" style="width:1.125rem;height:1.125rem;flex-shrink:0;color:#DC2626;"></i>
                <span>{{ $error }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('verify.health-id.lookup') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="health_id">
                        {{ __('verify.field_health_id', [], app()->getLocale()) ?: 'Patient Health ID' }}
                        <span>*</span>
                    </label>
                    <input
                        id="health_id"
                        name="health_id"
                        class="form-control mono"
                        value="{{ old('health_id') }}"
                        required
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="OPES-XXXX-XXXX"
                        aria-describedby="health_id_hint"
                    >
                    <div class="form-hint" id="health_id_hint">{{ __('verify.field_health_id_hint', [], app()->getLocale()) ?: 'Enter the alphanumeric Health ID as shown on the patient\'s card or QR code.' }}</div>
                    @error('health_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="purpose">
                        {{ __('verify.field_purpose', [], app()->getLocale()) ?: 'Purpose of Access' }}
                        <span>*</span>
                    </label>
                    <select id="purpose" name="purpose" class="form-control" required aria-describedby="purpose_hint">
                        <option value="">{{ __('verify.field_purpose_placeholder', [], app()->getLocale()) ?: '— Select purpose —' }}</option>
                        <option value="emergency_care"  @selected(old('purpose')==='emergency_care')>{{ __('verify.purpose_emergency', [], app()->getLocale()) ?: 'Emergency Care' }}</option>
                        <option value="scheduled_visit" @selected(old('purpose')==='scheduled_visit')>{{ __('verify.purpose_scheduled', [], app()->getLocale()) ?: 'Scheduled Clinical Visit' }}</option>
                        <option value="lab_result"      @selected(old('purpose')==='lab_result')>{{ __('verify.purpose_lab', [], app()->getLocale()) ?: 'Lab Result Delivery' }}</option>
                        <option value="prescription"    @selected(old('purpose')==='prescription')>{{ __('verify.purpose_prescription', [], app()->getLocale()) ?: 'Prescription Dispensing' }}</option>
                        <option value="insurance_claim" @selected(old('purpose')==='insurance_claim')>{{ __('verify.purpose_insurance', [], app()->getLocale()) ?: 'Insurance Claim Processing' }}</option>
                        <option value="referral"        @selected(old('purpose')==='referral')>{{ __('verify.purpose_referral', [], app()->getLocale()) ?: 'Referral / Transfer' }}</option>
                        <option value="other"           @selected(old('purpose')==='other')>{{ __('verify.purpose_other', [], app()->getLocale()) ?: 'Other' }}</option>
                    </select>
                    <div class="form-hint" id="purpose_hint">{{ __('verify.field_purpose_hint', [], app()->getLocale()) ?: 'This is logged against your staff ID for audit purposes.' }}</div>
                    @error('purpose')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn-verify">
                    <i data-lucide="search" style="width:1rem;height:1rem;"></i>
                    {{ __('verify.btn_verify', [], app()->getLocale()) ?: 'Verify Health ID' }}
                </button>
            </form>

            @if($result)
            <div class="result-success" role="region" aria-label="Verification result">
                <div class="result-row">
                    <div class="status-dot"></div>
                    <span style="font-weight:800;font-size:0.9375rem;color:#065F46;">{{ __('verify.result_verified', [], app()->getLocale()) ?: 'Identity Verified' }}</span>
                </div>
                <div class="result-detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:0.75rem;">
                    <div>
                        <div class="result-label">{{ __('verify.result_name', [], app()->getLocale()) ?: 'Name' }}</div>
                        <div class="result-value">{{ $result->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.result_health_id', [], app()->getLocale()) ?: 'Health ID' }}</div>
                        <div class="result-value result-id">{{ $result->health_id ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.result_dob', [], app()->getLocale()) ?: 'Date of Birth' }}</div>
                        <div class="result-value">{{ isset($result->dob) ? \Carbon\Carbon::parse($result->dob)->format('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="result-label">{{ __('verify.result_blood_type', [], app()->getLocale()) ?: 'Blood Type' }}</div>
                        <div class="result-value">{{ $result->blood_type ?? '—' }}</div>
                    </div>
                </div>
                <div class="audit-note">
                    <i data-lucide="shield-check" style="width:0.875rem;height:0.875rem;color:#0F766E;flex-shrink:0;"></i>
                    {{ __('verify.audit_note', [], app()->getLocale()) ?: 'This access has been logged against your provider credentials and is visible to the patient.' }}
                </div>
            </div>
            @endif

        </div>
    </div>

    <div class="verify-footer">
        <p>
            {{ __('verify.footer_note', [], app()->getLocale()) ?: 'Having trouble?' }}
            <a href="{{ route('public.help') }}">{{ __('verify.footer_help', [], app()->getLocale()) ?: 'Visit Help Center' }}</a>
            &nbsp;·&nbsp;
            <a href="{{ route('public.contact') }}">{{ __('verify.footer_contact', [], app()->getLocale()) ?: 'Contact Support' }}</a>
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
