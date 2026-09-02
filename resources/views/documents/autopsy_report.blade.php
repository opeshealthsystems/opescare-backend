@extends('documents.base')

@section('title', 'POST-MORTEM EXAMINATION REPORT')

@section('subtitle', 'Rapport d\'Autopsie / Pathological Autopsy — PMR')

@section('content')
<style>
    .pmr-solemn-header {
        background: #1F2937;
        color: #F9FAFB;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .pmr-solemn-header .sh-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 1mm; }
    .pmr-solemn-header .sh-sub { font-size: 9px; color: #9CA3AF; }
    .pmr-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .pbadge-dark { background: #374151; color: #F9FAFB; border: 1px solid #4B5563; }
    .pbadge-green { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .pbadge-amber { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .pbadge-red { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .pbadge-blue { background: #DBEAFE; color: #1E3A8A; border: 1px solid #93C5FD; }
    .pbadge-slate { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
    .pbadge-purple { background: #EDE9FE; color: #5B21B6; border: 1px solid #C4B5FD; }
    .pmr-card {
        border: 1px solid #374151;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .pmr-card .pc-head {
        background: #374151;
        color: #F9FAFB;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #4B5563;
    }
    .pmr-card .pc-body { padding: 4mm; }
    .pmr-card-light {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .pmr-card-light .pc-head-light {
        background: #F8FAFC;
        color: #374151;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #E2E8F0;
    }
    .pmr-card-light .pc-body { padding: 4mm; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .sys-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; margin-bottom: 5mm; }
    .sys-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
    }
    .sys-card .sc-head {
        padding: 2mm 3.5mm;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid #E2E8F0;
    }
    .sys-card .sc-body { padding: 3mm; font-size: 10px; color: #0F172A; line-height: 1.5; }
    .sc-cv { background: #FFF1F2; color: #9F1239; }
    .sc-resp { background: #ECFDF5; color: #065F46; }
    .sc-abd { background: #FFFBEB; color: #92400E; }
    .sc-neuro { background: #EFF6FF; color: #1E3A8A; }
    .sc-msk { background: #FAF5FF; color: #5B21B6; }
    .organ-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .organ-table th { background: #1F2937; color: #F9FAFB; font-weight: 600; text-align: left; padding: 2.5mm 3mm; font-size: 9px; text-transform: uppercase; }
    .organ-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; }
    .organ-table tr:nth-child(even) td { background: #F8FAFC; }
    .organ-table .ref { color: #94A3B8; font-size: 9px; }
    .kv { display: flex; justify-content: space-between; margin-bottom: 1.5mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .cod-section {
        background: #1F2937;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        color: #F9FAFB;
    }
    .cod-section .cod-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3mm; color: #FCD34D; }
    .cod-line {
        display: flex;
        gap: 3mm;
        align-items: flex-start;
        padding: 2mm 0;
        border-bottom: 1px solid #374151;
    }
    .cod-line .cl-label { min-width: 18mm; font-size: 9px; color: #9CA3AF; padding-top: 1mm; }
    .cod-line .cl-alpha { font-size: 13px; font-weight: 800; color: #FCD34D; min-width: 5mm; }
    .cod-line .cl-text { font-size: 10.5px; font-weight: 600; color: #F9FAFB; flex: 1; }
    .manner-row { margin-top: 3mm; display: flex; align-items: center; gap: 3mm; }
    .manner-row .mr-lbl { font-size: 9.5px; color: #9CA3AF; }
    .neg-list { list-style: none; padding: 0; margin: 0; }
    .neg-list li { padding: 1.5mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; display: flex; align-items: center; gap: 2mm; }
    .neg-list li::before { content: "✗ "; color: #16A34A; font-weight: 700; }
    .opinion-box {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-left: 4px solid #1F2937;
        border-radius: 0 6px 6px 0;
        padding: 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
        line-height: 1.6;
        font-style: italic;
    }
    .sig-stamp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .sig-box { border: 1px solid #CBD5E1; border-radius: 6px; padding: 3mm; text-align: center; }
    .sig-box .sb-lbl { font-size: 9px; color: #64748B; margin-bottom: 8mm; }
    .sig-line { border-top: 1px solid #94A3B8; padding-top: 1mm; font-size: 9px; color: #374151; margin-top: 2mm; }
    .stamp-box { border: 2px dashed #94A3B8; border-radius: 6px; min-height: 18mm; display: flex; align-items: center; justify-content: center; color: #94A3B8; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
    .histo-tox-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .spec-list { list-style: none; padding: 0; margin: 0; }
    .spec-list li { padding: 1.5mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; }
    .spec-list li::before { content: "⬛ "; font-size: 7px; color: #374151; }
</style>

{{-- 1. Solemn dark header --}}
<div class="pmr-solemn-header">
    <div>
        <div class="sh-title">Post-Mortem Examination Report</div>
        <div class="sh-title" style="font-size:10px; color:#9CA3AF;">Rapport d'Examen Post-Mortem — CONFIDENTIEL</div>
        <div class="sh-sub" style="margin-top:1mm;">
            Date: {{ $payload['autopsy_date'] ?? '—' }} &nbsp;|&nbsp;
            {{ $payload['autopsy_time_start'] ?? '—' }} – {{ $payload['autopsy_time_end'] ?? '—' }}
        </div>
    </div>
    @php
        $atype = $payload['autopsy_type'] ?? '';
        $atypeClass = match(true) {
            str_contains($atype, 'Consent') => 'pbadge-blue',
            str_contains($atype, 'Coroner') => 'pbadge-amber',
            str_contains($atype, 'Forensic') => 'pbadge-red',
            default => 'pbadge-dark',
        };
    @endphp
    <span class="pmr-badge {{ $atypeClass }}" style="font-size:9.5px; padding:1.5mm 3.5mm;">{{ $payload['autopsy_type'] ?? '—' }}</span>
</div>

{{-- 2. Metadata --}}
<div class="pmr-card">
    <div class="pc-head">Autopsy Metadata</div>
    <div class="pc-body">
        <div class="two-col" style="margin-bottom:0;">
            <div>
                <div class="kv"><span class="k">Requesting Authority</span><span class="v">{{ $payload['requesting_authority'] ?? '—' }}</span></div>
                <div class="kv"><span class="k">Pathologist</span><span class="v">{{ $payload['pathologist'] ?? '—' }}</span></div>
                <div class="kv"><span class="k">Registration No.</span><span class="v" style="font-family:monospace;">{{ $payload['pathologist_reg'] ?? '—' }}</span></div>
            </div>
            <div>
                <div class="kv"><span class="k">Assistant</span><span class="v">{{ $payload['assistant'] ?? '—' }}</span></div>
                <div class="kv"><span class="k">Autopsy Type</span><span class="v">{{ $payload['autopsy_type'] ?? '—' }}</span></div>
                <div class="kv"><span class="k">Duration</span><span class="v">{{ $payload['autopsy_time_start'] ?? '—' }} — {{ $payload['autopsy_time_end'] ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 3. External Examination --}}
<div class="pmr-card-light">
    <div class="pc-head-light">External Examination</div>
    <div class="pc-body">
        <p style="margin:0; font-size:10px; line-height:1.6; color:#0F172A;">{{ $payload['external_examination'] ?? '—' }}</p>
    </div>
</div>

{{-- 4. Internal Examination — 5 system cards --}}
@php $ie = $payload['internal_examination'] ?? []; @endphp
<div style="font-size:10px; font-weight:700; color:#1F2937; text-transform:uppercase; letter-spacing:0.5px; border-left:3px solid #1F2937; padding-left:2.5mm; margin-bottom:3mm;">
    Internal Examination
</div>
<div class="sys-grid">
    <div class="sys-card">
        <div class="sc-head sc-cv">Cardiovascular</div>
        <div class="sc-body">{{ $ie['cardiovascular'] ?? '—' }}</div>
    </div>
    <div class="sys-card">
        <div class="sc-head sc-resp">Respiratory</div>
        <div class="sc-body">{{ $ie['respiratory'] ?? '—' }}</div>
    </div>
    <div class="sys-card">
        <div class="sc-head sc-abd">Abdominal</div>
        <div class="sc-body">{{ $ie['abdominal'] ?? '—' }}</div>
    </div>
    <div class="sys-card">
        <div class="sc-head sc-neuro">Neurological</div>
        <div class="sc-body">{{ $ie['neurological'] ?? '—' }}</div>
    </div>
</div>
<div class="sys-card" style="margin-bottom:5mm;">
    <div class="sc-head sc-msk">Musculoskeletal</div>
    <div class="sc-body">{{ $ie['musculoskeletal'] ?? '—' }}</div>
</div>

{{-- 5. Organ Weights --}}
@php $ow = $payload['organ_weights'] ?? []; @endphp
<div class="pmr-card">
    <div class="pc-head">Organ Weights</div>
    <div class="pc-body" style="padding:2mm;">
        <table class="organ-table">
            <thead>
                <tr>
                    <th>Organ</th>
                    <th>Weight (g)</th>
                    <th>Normal Range (g)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Heart</td><td>{{ $ow['heart_g'] ?? '—' }}</td><td class="ref">250 – 350</td></tr>
                <tr><td>Lung (Right)</td><td>{{ $ow['lungs_right_g'] ?? '—' }}</td><td class="ref">400 – 500</td></tr>
                <tr><td>Lung (Left)</td><td>{{ $ow['lungs_left_g'] ?? '—' }}</td><td class="ref">350 – 450</td></tr>
                <tr><td>Liver</td><td>{{ $ow['liver_g'] ?? '—' }}</td><td class="ref">1200 – 1600</td></tr>
                <tr><td>Spleen</td><td>{{ $ow['spleen_g'] ?? '—' }}</td><td class="ref">100 – 250</td></tr>
                <tr><td>Kidney (Right)</td><td>{{ $ow['kidneys_right_g'] ?? '—' }}</td><td class="ref">125 – 175</td></tr>
                <tr><td>Kidney (Left)</td><td>{{ $ow['kidneys_left_g'] ?? '—' }}</td><td class="ref">125 – 175</td></tr>
                <tr><td>Brain</td><td>{{ $ow['brain_g'] ?? '—' }}</td><td class="ref">1200 – 1400</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- 6. Histology + Toxicology --}}
<div class="histo-tox-grid">
    <div class="pmr-card-light" style="margin-bottom:0;">
        <div class="pc-head-light">Histology Samples</div>
        <div class="pc-body">
            @if(!empty($payload['histology_samples']))
                <ul class="spec-list">
                    @foreach($payload['histology_samples'] as $hs)
                        <li>{{ $hs }}</li>
                    @endforeach
                </ul>
            @else
                <p style="font-size:10px; color:#94A3B8; margin:0;">No histology samples collected.</p>
            @endif
        </div>
    </div>
    <div class="pmr-card-light" style="margin-bottom:0;">
        <div class="pc-head-light">Toxicology</div>
        <div class="pc-body">
            <div style="margin-bottom:2mm;">
                <span style="font-size:9.5px; color:#64748B; margin-right:2mm;">Requested:</span>
                @if(!empty($payload['toxicology_requested']))
                    <span class="pmr-badge pbadge-amber">Yes</span>
                @else
                    <span class="pmr-badge pbadge-slate">No</span>
                @endif
            </div>
            @if(!empty($payload['toxicology_results']))
                <p style="margin:0; font-size:10px; color:#0F172A;">{{ $payload['toxicology_results'] }}</p>
            @elseif(!empty($payload['toxicology_requested']))
                <p style="font-size:9.5px; color:#94A3B8; margin:0; font-style:italic;">Results pending.</p>
            @endif
        </div>
    </div>
</div>
<div style="margin-bottom:5mm;"></div>

{{-- 7. Cause of Death (WHO format) + Manner --}}
@php
    $manner = $payload['manner_of_death'] ?? 'Undetermined';
    $mannerClass = match($manner) {
        'Natural' => 'pbadge-green',
        'Accident' => 'pbadge-amber',
        'Homicide' => 'pbadge-red',
        'Suicide' => 'pbadge-purple',
        default => 'pbadge-slate',
    };
@endphp
<div class="cod-section">
    <div class="cod-title">Cause of Death — WHO Classification</div>
    <div class="cod-line">
        <span class="cl-alpha">I(a)</span>
        <span class="cl-label">Immediate Cause</span>
        <span class="cl-text">{{ $payload['cause_of_death_a'] ?? '—' }}</span>
    </div>
    @if(!empty($payload['cause_of_death_b']))
    <div class="cod-line">
        <span class="cl-alpha">I(b)</span>
        <span class="cl-label">Due to</span>
        <span class="cl-text">{{ $payload['cause_of_death_b'] }}</span>
    </div>
    @endif
    @if(!empty($payload['cause_of_death_c']))
    <div class="cod-line">
        <span class="cl-alpha">I(c)</span>
        <span class="cl-label">Due to</span>
        <span class="cl-text">{{ $payload['cause_of_death_c'] }}</span>
    </div>
    @endif
    <div class="manner-row">
        <span class="mr-lbl">Manner of Death:</span>
        <span class="pmr-badge {{ $mannerClass }}" style="font-size:10px; padding:1.5mm 4mm;">{{ $manner }}</span>
    </div>
</div>

{{-- 8. Significant Negative Findings --}}
@if(!empty($payload['significant_negative_findings']))
<div class="pmr-card-light">
    <div class="pc-head-light">Significant Negative Findings</div>
    <div class="pc-body">
        <ul class="neg-list">
            @foreach($payload['significant_negative_findings'] as $nf)
                <li>{{ $nf }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 9. Pathologist Opinion --}}
<div style="font-size:10px; font-weight:700; color:#1F2937; text-transform:uppercase; letter-spacing:0.5px; border-left:3px solid #1F2937; padding-left:2.5mm; margin-bottom:2.5mm;">
    Pathologist's Opinion &amp; Conclusion
</div>
<div class="opinion-box">{{ $payload['pathologist_opinion'] ?? '—' }}</div>

{{-- 10. Signature + stamp + date --}}
<div class="sig-stamp-grid">
    <div class="sig-box">
        <div class="sb-lbl">Pathologist Signature</div>
        <div class="sig-line">{{ $payload['pathologist'] ?? '—' }} &nbsp;|&nbsp; Reg: {{ $payload['pathologist_reg'] ?? '—' }}</div>
    </div>
    <div class="stamp-box">Pathologist's Official Stamp / Cachet du Pathologiste</div>
</div>

<div style="text-align:right; font-size:9.5px; color:#64748B; margin-top:2mm;">
    Report Issued: <strong>{{ $issued_at }}</strong>
</div>
@endsection
