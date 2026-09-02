@extends('documents.base')

@section('title', 'ADVERSE EVENT FOLLOWING IMMUNIZATION REPORT')

@section('subtitle', 'WHO / MINSANTE Pharmacovigilance | Code: AEF | {{ $document_number }}')

@section('content')
<style>
    .aefi-sev-banner { border-radius:6px; padding:3mm 5mm; margin-bottom:5mm; display:flex; align-items:center; justify-content:space-between; }
    .aefi-sev-fatal      { background-color:#7F1D1D; color:#FFF; }
    .aefi-sev-life       { background-color:#DC2626; color:#FFF; }
    .aefi-sev-serious    { background-color:#EA580C; color:#FFF; }
    .aefi-sev-nonserious { background-color:#CA8A04; color:#FFF; }
    .aefi-sev-label { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .aefi-sev-sub   { font-size:9.5px; opacity:.85; }
    .outcome-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; }
    .outcome-recovered  { background-color:#D1FAE5; color:#065F46; }
    .outcome-recovering { background-color:#DBEAFE; color:#1D4ED8; }
    .outcome-sequelae   { background-color:#FEF3C7; color:#92400E; }
    .outcome-fatal      { background-color:#FEE2E2; color:#991B1B; }
    .outcome-unknown    { background-color:#F3F4F6; color:#4B5563; }
    .causality-badge { display:inline-block; padding:1.5mm 4mm; border-radius:4px; font-size:10px; font-weight:600; background-color:#FFF7ED; color:#C2410C; border:1px solid #FED7AA; }
    .prog-error-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
    .prog-none-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .flag-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; margin-right:1mm; }
    .flag-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; margin-right:1mm; }
    .batch-alert { background-color:#FEE2E2; border:2px solid #DC2626; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:10.5px; color:#7F1D1D; font-weight:600; }
    .reg-badge-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .reg-badge-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .minsante-notice { background-color:#FEF3C7; border:1px solid #FCD34D; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:9.5px; color:#78350F; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .event-type-item { display:inline-block; padding:.5mm 2mm; border-radius:3px; font-size:9.5px; background-color:#FEE2E2; color:#991B1B; margin:1px 2px 1px 0; }
    .treatment-item { display:inline-block; padding:.5mm 2mm; border-radius:3px; font-size:9.5px; background-color:#DBEAFE; color:#1D4ED8; margin:1px 2px 1px 0; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5mm; }
    .sig-line { border-top:1px solid #DC2626; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

@php
    $sev = $payload['severity'] ?? 'Non-serious';
    $sevClass = match(true) {
        str_contains($sev, 'Fatal')            => 'aefi-sev-fatal',
        str_contains($sev, 'life-threatening') => 'aefi-sev-life',
        str_contains($sev, 'Serious')          => 'aefi-sev-serious',
        default                                => 'aefi-sev-nonserious',
    };
    $outcome = $payload['outcome'] ?? 'Unknown';
    $outcomeClass = match($outcome) {
        'Recovered fully' => 'outcome-recovered',
        'Recovering'      => 'outcome-recovering',
        'Sequelae'        => 'outcome-sequelae',
        'Fatal'           => 'outcome-fatal',
        default           => 'outcome-unknown',
    };
@endphp

{{-- 1. Severity banner --}}
<div class="aefi-sev-banner {{ $sevClass }}">
    <div>
        <div class="aefi-sev-label">AEFI Report — {{ $payload['vaccination_programme'] ?? 'EPI' }}</div>
        <div class="aefi-sev-sub">Event Date: {{ $payload['event_date'] ?? 'N/A' }} &nbsp;|&nbsp; Report Date: {{ $payload['report_date'] ?? 'N/A' }}</div>
    </div>
    <div style="font-size:12px; font-weight:800; text-transform:uppercase;">{{ $sev }}</div>
</div>

{{-- 2. Vaccines given table --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Vaccines Administered</div>
    <div class="card-body" style="padding:0;">
        <table class="doc-table" style="margin:0;">
            <thead>
                <tr>
                    <th>Vaccine / Brand</th>
                    <th>Batch No.</th>
                    <th>Expiry</th>
                    <th>Dose</th>
                    <th>Route / Site</th>
                    <th>Administered By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payload['vaccines_given'] ?? [] as $vax)
                <tr>
                    <td>{{ $vax['vaccine_name'] ?? 'N/A' }}<br><span style="font-size:9px; color:#6B7280;">{{ $vax['brand'] ?? '' }}</span></td>
                    <td>{{ $vax['batch_number'] ?? 'N/A' }}</td>
                    <td>{{ $vax['expiry_date'] ?? 'N/A' }}</td>
                    <td>{{ $vax['dose_number'] ?? 'N/A' }}</td>
                    <td>{{ $vax['route'] ?? 'N/A' }} / {{ $vax['site'] ?? 'N/A' }}</td>
                    <td>{{ $vax['administered_by'] ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color:#6B7280;">No vaccine data recorded</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 3. Event description + onset --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Event Description</div>
    <div class="card-body">
        <p style="font-size:10.5px; color:#111827; margin:0 0 2mm 0;">{{ $payload['event_description'] ?? 'N/A' }}</p>
        <div class="dr"><span class="dl">Onset Time After Vaccination:</span><span class="dv">{{ $payload['event_onset_time'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Vaccination Date:</span><span class="dv">{{ $payload['vaccination_date'] ?? 'N/A' }}</span></div>
    </div>
</div>

{{-- 4. Event type checklist --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Event Type(s)</div>
    <div class="card-body">
        @forelse($payload['event_type'] ?? [] as $et)
            <span class="event-type-item">{{ $et }}</span>
        @empty
            <span style="color:#6B7280; font-size:10px;">Not specified</span>
        @endforelse
    </div>
</div>

{{-- 5. Outcome badge --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Outcome:</span>
    <span class="outcome-badge {{ $outcomeClass }}">{{ $outcome }}</span>
    @if(!empty($payload['hospitalised']))
        <span style="margin-left:3mm;" class="flag-yes">Hospitalised — {{ $payload['hospitalisation_duration_days'] ?? '?' }} days</span>
    @endif
</div>

{{-- 6. Treatment given --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Treatment Given</div>
    <div class="card-body">
        @forelse($payload['treatment_given'] ?? [] as $tx)
            <span class="treatment-item">{{ $tx }}</span>
        @empty
            <span style="color:#6B7280; font-size:10px;">None recorded</span>
        @endforelse
    </div>
</div>

{{-- 7. Programme error --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Programme Error Assessment:</span>
    @php $pe = $payload['programme_error'] ?? null; @endphp
    @if(!empty($pe))
        @if(str_contains($pe, 'No programme error'))
            <span class="prog-none-badge">{{ $pe }}</span>
        @else
            <span class="prog-error-badge">{{ $pe }}</span>
        @endif
    @else
        <span class="flag-no">Not assessed</span>
    @endif
</div>

{{-- 8. Cold chain + multi-dose flags --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Cold Chain Breach:</span>
    @if(isset($payload['cold_chain_breach']))
        <span class="{{ $payload['cold_chain_breach'] ? 'flag-yes' : 'flag-no' }}">{{ $payload['cold_chain_breach'] ? 'Yes — Breach Detected' : 'No Breach' }}</span>
    @else
        <span class="flag-no">Not assessed</span>
    @endif
    &nbsp;&nbsp;
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Multi-dose Vial:</span>
    <span class="{{ !empty($payload['multi_dose_vial']) ? 'flag-yes' : 'flag-no' }}">{{ !empty($payload['multi_dose_vial']) ? 'Yes' : 'No' }}</span>
</div>

{{-- 9. Other cases from same batch --}}
@if(!empty($payload['other_cases_same_batch']))
<div class="batch-alert">
    &#9888; Other cases from the same batch reported: {{ $payload['other_cases_count'] ?? 'N/A' }} cases
</div>
@endif

{{-- 10. WHO/MINSANTE Causality Assessment --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">WHO/MINSANTE Causality Assessment:</span>
    <span class="causality-badge">{{ $payload['causality_assessment'] ?? 'Unclassifiable' }}</span>
</div>

{{-- 11. Regulatory actions --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Regulatory Actions</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">MINSANTE Ref:</span>
                    <span class="dv">{{ $payload['minsante_ref'] ?? 'Pending' }}</span>
                </div>
                <div class="dr">
                    <span class="dl">WHO Notified:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['who_notified']) ? 'reg-badge-yes' : 'reg-badge-no' }}">
                            {{ !empty($payload['who_notified']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
            </div>
            <div>
                <div class="dr">
                    <span class="dl">Batch Quarantined:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['batch_quarantined']) ? 'reg-badge-yes' : 'reg-badge-no' }}">
                            {{ !empty($payload['batch_quarantined']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 12. Reporter details + signature --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Reporter:</span><span class="dv">{{ $payload['reporter'] ?? $issuer_name }}</span></div>
        <div class="dr"><span class="dl">Designation:</span><span class="dv">{{ $payload['reporter_designation'] ?? $issuer_role }}</span></div>
        <div class="dr"><span class="dl">Contact:</span><span class="dv">{{ $payload['reporter_contact'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Report Date:</span><span class="dv">{{ $payload['report_date'] ?? $issued_at }}</span></div>
    </div>
    <div><div class="sig-line">Reporter Signature</div></div>
</div>

{{-- 13. MINSANTE notice --}}
<div class="minsante-notice">
    <strong>MINSANTE Pharmacovigilance Notice:</strong>
    This form must be submitted to the MINSANTE Pharmacovigilance Centre within
    <strong>24 hours</strong> for serious events and <strong>72 hours</strong> for non-serious events.
    Failure to report is a violation of MINSANTE pharmacovigilance obligations.
</div>
@endsection
