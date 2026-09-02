@extends('documents.base')

@section('title', 'MALARIA DIAGNOSTIC REPORT')

@section('subtitle', 'RDT + Microscopy | Code: MAL | {{ $document_number }}')

@section('content')
<style>
    .mal-overall-neg { background-color:#D1FAE5; border:3px solid #059669; border-radius:8px; text-align:center; padding:5mm; margin-bottom:6mm; }
    .mal-overall-pos { background-color:#FEE2E2; border:3px solid #DC2626; border-radius:8px; text-align:center; padding:5mm; margin-bottom:6mm; }
    .mal-overall-label { font-size:28px; font-weight:800; letter-spacing:2px; text-transform:uppercase; }
    .mal-overall-neg .mal-overall-label { color:#065F46; }
    .mal-overall-pos .mal-overall-label { color:#7F1D1D; }
    .mal-overall-species { font-size:13px; font-weight:600; margin-top:1mm; }
    .mal-overall-neg .mal-overall-species { color:#047857; }
    .mal-overall-pos .mal-overall-species { color:#991B1B; }
    .rdt-card { border:2px solid #DC2626; border-radius:6px; margin-bottom:6mm; overflow:hidden; }
    .rdt-hdr  { background-color:#DC2626; color:#FFF; padding:2.5mm 4mm; font-size:11px; font-weight:700; text-transform:uppercase; }
    .mic-card { border:2px solid #059669; border-radius:6px; margin-bottom:6mm; overflow:hidden; }
    .mic-hdr  { background-color:#059669; color:#FFF; padding:2.5mm 4mm; font-size:11px; font-weight:700; text-transform:uppercase; }
    .rdt-result-neg { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#D1FAE5; color:#065F46; }
    .rdt-result-pos { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#FEE2E2; color:#991B1B; }
    .rdt-result-mix { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#FEF3C7; color:#92400E; }
    .ctrl-valid { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .ctrl-invalid { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; }
    .density-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:10px; font-weight:700; background-color:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }
    .gametocyte-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; }
    .gametocyte-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .severity-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; }
    .sev-uncomplicated { background-color:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }
    .sev-severe        { background-color:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
    .treatment-box { background-color:#EFF6FF; border:2px solid #2563EB; border-radius:6px; padding:4mm; margin-bottom:6mm; }
    .treatment-box-hdr { font-size:11px; font-weight:700; color:#1D4ED8; text-transform:uppercase; margin-bottom:2mm; }
    .temp-badge { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; }
    .rdt-note { background-color:#F8FAFC; border:1px solid #E2E8F0; border-radius:4px; padding:2mm 3mm; font-size:9px; color:#64748B; margin-top:2mm; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5mm; }
    .sig-line { border-top:1px solid #374151; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

@php
    $overall = $payload['overall_result'] ?? 'NEGATIVE';
    $isPositive = str_starts_with($overall, 'POSITIVE');
    $severity = $payload['severity_classification'] ?? null;
    $sevClass = !empty($severity) && str_contains($severity, 'Severe') ? 'sev-severe' : 'sev-uncomplicated';
    $rdtDone = !empty($payload['rdt_performed']);
    $micDone = !empty($payload['microscopy_performed']);
    $rdtResult = $payload['rdt_result'] ?? null;
    $rdtClass = match($rdtResult) {
        'Negative'             => 'rdt-result-neg',
        'Mixed infection'      => 'rdt-result-mix',
        default                => (!empty($rdtResult) ? 'rdt-result-pos' : 'rdt-result-neg'),
    };
@endphp

{{-- 1. Patient + clinical details --}}
<div class="content-card">
    <div class="card-header">Patient &amp; Clinical Presentation</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Patient:</span><span class="dv">{{ $patient_name }}</span></div>
                <div class="dr"><span class="dl">Health ID:</span><span class="dv">{{ $health_id ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Sex / DOB:</span><span class="dv">{{ $patient_sex ?? 'N/A' }} / {{ $patient_dob ?? 'N/A' }}</span></div>
            </div>
            <div>
                <div class="dr">
                    <span class="dl">Temperature:</span>
                    <span class="dv"><span class="temp-badge">{{ $payload['temperature_at_presentation'] ?? 'N/A' }}</span></span>
                </div>
                <div class="dr"><span class="dl">Specimen:</span><span class="dv">{{ $payload['specimen_type'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Collection Time:</span><span class="dv">{{ $payload['collection_time'] ?? 'N/A' }} (Test: {{ $payload['test_date'] ?? 'N/A' }} {{ $payload['test_time'] ?? '' }})</span></div>
            </div>
        </div>
        <div class="dr" style="margin-top:2mm;">
            <span class="dl">Clinical Indication:</span>
            <span class="dv">{{ $payload['clinical_indication'] ?? 'N/A' }}</span>
        </div>
    </div>
</div>

{{-- 3. RDT result card --}}
@if($rdtDone)
<div class="rdt-card">
    <div class="rdt-hdr">Rapid Diagnostic Test (RDT)</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Brand:</span><span class="dv">{{ $payload['rdt_brand'] ?? 'N/A' }}</span></div>
                <div class="dr">
                    <span class="dl">Control Line:</span>
                    <span class="dv">
                        @if(($payload['rdt_control_line'] ?? '') === 'Valid')
                            <span class="ctrl-valid">Valid</span>
                        @else
                            <span class="ctrl-invalid">{{ $payload['rdt_control_line'] ?? 'N/A' }}</span>
                        @endif
                    </span>
                </div>
                <div class="dr"><span class="dl">Performed By:</span><span class="dv">{{ $payload['rdt_performed_by'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Time:</span><span class="dv">{{ $payload['rdt_time'] ?? 'N/A' }}</span></div>
            </div>
            <div style="display:flex; align-items:center; justify-content:center;">
                <span class="{{ $rdtClass }}">{{ $rdtResult ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="rdt-note">
            Note: RDT detects HRP2 antigen — false positives may persist up to 2 weeks after successful treatment.
        </div>
    </div>
</div>
@endif

{{-- 4. Microscopy result card --}}
@if($micDone)
<div class="mic-card">
    <div class="mic-hdr">Blood Film Microscopy (Thick &amp; Thin)</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Result:</span><span class="dv">{{ $payload['microscopy_result'] ?? 'N/A' }}</span></div>
                @if(!empty($payload['parasite_species']))
                <div class="dr"><span class="dl">Species:</span><span class="dv" style="color:#065F46; font-style:italic;">{{ $payload['parasite_species'] }}</span></div>
                @endif
                @if(!empty($payload['malaria_stage']))
                <div class="dr"><span class="dl">Stage:</span><span class="dv">{{ $payload['malaria_stage'] }}</span></div>
                @endif
                <div class="dr"><span class="dl">Performed By:</span><span class="dv">{{ $payload['microscopy_performed_by'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Time:</span><span class="dv">{{ $payload['microscopy_time'] ?? 'N/A' }}</span></div>
            </div>
            <div>
                @if(!empty($payload['parasite_density']))
                <div class="dr">
                    <span class="dl">Parasite Density:</span>
                    <span class="dv"><span class="density-badge">{{ $payload['parasite_density'] }}</span></span>
                </div>
                @endif
                @if(!empty($payload['parasitaemia_pct']))
                <div class="dr"><span class="dl">Parasitaemia:</span><span class="dv">{{ $payload['parasitaemia_pct'] }}%</span></div>
                @endif
                @if(isset($payload['gametocytes_present']))
                <div class="dr">
                    <span class="dl">Gametocytes:</span>
                    <span class="dv">
                        <span class="{{ $payload['gametocytes_present'] ? 'gametocyte-yes' : 'gametocyte-no' }}">
                            {{ $payload['gametocytes_present'] ? 'Present' : 'Not seen' }}
                        </span>
                    </span>
                </div>
                @endif
                @if(!empty($payload['wbc_for_count']))
                <div class="dr"><span class="dl">WBC Count Used:</span><span class="dv">{{ $payload['wbc_for_count'] }}/µL</span></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- 5. Overall result -- very prominent --}}
<div class="{{ $isPositive ? 'mal-overall-pos' : 'mal-overall-neg' }}">
    <div class="mal-overall-label">{{ $isPositive ? 'POSITIVE' : 'NEGATIVE' }}</div>
    <div class="mal-overall-species">{{ $overall }}</div>
</div>

{{-- 6. Severity classification --}}
@if($isPositive && !empty($severity))
<div style="margin-bottom:5mm; text-align:center;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Severity Classification:</span>
    <span class="severity-badge {{ $sevClass }}">{{ $severity }}</span>
</div>
@endif

{{-- 7. Treatment recommendation --}}
<div class="treatment-box">
    <div class="treatment-box-hdr">Treatment Recommendation</div>
    <p style="font-size:11px; color:#1E3A5F; margin:0;">{{ $payload['treatment_recommendation'] ?? 'Refer to clinician for management.' }}</p>
    @if(!empty($payload['actnote']))
    <p style="font-size:9.5px; color:#374151; margin:2mm 0 0 0; font-style:italic;">{{ $payload['actnote'] }}</p>
    @endif
</div>

{{-- 8. Lab signatures --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Lab Technician:</span><span class="dv">{{ $payload['lab_technician'] ?? $issuer_name }}</span></div>
        @if(!empty($payload['lab_supervisor']))
        <div class="dr"><span class="dl">Lab Supervisor:</span><span class="dv">{{ $payload['lab_supervisor'] }}</span></div>
        @endif
        <div class="dr"><span class="dl">Report Date:</span><span class="dv">{{ $issued_at }}</span></div>
    </div>
    <div><div class="sig-line">Authorising Signature</div></div>
</div>
@endsection
