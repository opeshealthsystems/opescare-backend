@extends('documents.base')

@section('title', 'BLOOD BANK REQUEST / CROSSMATCH FORM')

@section('subtitle', 'Transfusion Medicine | Code: BBR | {{ $document_number }}')

@section('content')
<style>
    .bbr-urgency-routine   { background-color:#1D4ED8; color:#FFF; }
    .bbr-urgency-urgent    { background-color:#EA580C; color:#FFF; }
    .bbr-urgency-emergency { background-color:#DC2626; color:#FFF; }
    .bbr-urgency-mhp       { background-color:#7F1D1D; color:#FFF; border:3px solid #EF4444; }
    .bbr-urgency-banner { border-radius:6px; padding:3mm 5mm; margin-bottom:5mm; display:flex; align-items:center; justify-content:space-between; }
    .bbr-urgency-title { font-size:15px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; }
    .bbr-urgency-sub   { font-size:9.5px; opacity:.85; margin-top:1mm; }
    .bg-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:800; background-color:#FEE2E2; color:#991B1B; border:2px solid #DC2626; }
    .flag-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; }
    .flag-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .special-req-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }
    .special-req-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#9CA3AF; }
    .bbr-use-section { border:2px dashed #DC2626; border-radius:6px; margin-bottom:5mm; overflow:hidden; }
    .bbr-use-hdr { background-color:#DC2626; color:#FFF; padding:2.5mm 4mm; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .crossmatch-compatible { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; }
    .crossmatch-incompatible{ display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
    .crossmatch-pending      { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }
    .identity-check-box { background-color:#FFF7ED; border:2px solid #EA580C; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:9.5px; color:#7C2D12; }
    .sample-badge-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .sample-badge-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; }
    .surgery-badge { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; }
    .hb-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:10px; font-weight:700; background-color:#FEE2E2; color:#7F1D1D; border:1px solid #FCA5A5; }
    .prod-item { padding:2mm 0; border-bottom:1px solid #E5E7EB; font-size:10.5px; }
    .prod-item:last-child { border-bottom:none; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5mm; }
    .sig-line { border-top:1px solid #DC2626; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

@php
    $urgency = $payload['urgency'] ?? 'Routine (within 24h)';
    $urgencyClass = match(true) {
        str_contains($urgency, 'Massive')    => 'bbr-urgency-mhp',
        str_contains($urgency, 'Emergency')  => 'bbr-urgency-emergency',
        str_contains($urgency, 'Urgent')     => 'bbr-urgency-urgent',
        default                              => 'bbr-urgency-routine',
    };
    $bloodBankData = $payload['blood_bank_use'] ?? [];
    $crossmatch = $bloodBankData['crossmatch_result'] ?? null;
    $crossmatchClass = match($crossmatch) {
        'Compatible'   => 'crossmatch-compatible',
        'Incompatible' => 'crossmatch-incompatible',
        'Pending'      => 'crossmatch-pending',
        default        => 'crossmatch-pending',
    };
@endphp

{{-- 1. Urgency banner --}}
<div class="bbr-urgency-banner {{ $urgencyClass }}">
    <div>
        <div class="bbr-urgency-title">Blood Bank Request — {{ $urgency }}</div>
        <div class="bbr-urgency-sub">
            Ward/Bed: {{ $payload['ward_bed'] ?? 'N/A' }} &nbsp;|&nbsp;
            Requesting Doctor: {{ $payload['requesting_doctor'] ?? $issuer_name }} &nbsp;|&nbsp;
            Date: {{ $payload['request_date'] ?? 'N/A' }} {{ $payload['request_time'] ?? '' }}
        </div>
    </div>
</div>

{{-- 2. Clinical indication + diagnosis --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Clinical Details</div>
    <div class="card-body">
        <div class="dr"><span class="dl">Diagnosis:</span><span class="dv">{{ $payload['diagnosis'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Clinical Indication for Transfusion:</span><span class="dv">{{ $payload['clinical_indication'] ?? 'N/A' }}</span></div>
    </div>
</div>

{{-- 3. Lab values + bleeding --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Laboratory Values</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                @if(!empty($payload['haemoglobin']))
                <div class="dr">
                    <span class="dl">Haemoglobin:</span>
                    <span class="dv"><span class="hb-badge">{{ $payload['haemoglobin'] }}</span></span>
                </div>
                @endif
                @if(!empty($payload['haematocrit']))
                <div class="dr">
                    <span class="dl">Haematocrit:</span>
                    <span class="dv"><span class="hb-badge">{{ $payload['haematocrit'] }}</span></span>
                </div>
                @endif
            </div>
            <div>
                <div class="dr">
                    <span class="dl">Active Bleeding:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['bleeding_active']) ? 'flag-yes' : 'flag-no' }}">
                            {{ !empty($payload['bleeding_active']) ? 'Yes — Active Bleed' : 'No' }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. Transfusion history + antibodies + pregnancy --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Transfusion History &amp; Risk Factors</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">Previous Transfusions:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['previous_transfusions']) ? 'flag-yes' : 'flag-no' }}">
                            {{ !empty($payload['previous_transfusions']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
                <div class="dr">
                    <span class="dl">Transfusion Reactions History:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['transfusion_reactions_history']) ? 'flag-yes' : 'flag-no' }}">
                            {{ !empty($payload['transfusion_reactions_history']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
                @if(!empty($payload['reaction_details']))
                <div class="dr"><span class="dl">Reaction Details:</span><span class="dv">{{ $payload['reaction_details'] }}</span></div>
                @endif
            </div>
            <div>
                <div class="dr">
                    <span class="dl">Blood Group Known:</span>
                    <span class="dv">
                        @if(!empty($payload['patient_blood_group_known']) && !empty($payload['known_blood_group']))
                            <span class="bg-badge">{{ $payload['known_blood_group'] }}</span>
                        @else
                            <span class="flag-no">Unknown — Type &amp; Screen</span>
                        @endif
                    </span>
                </div>
                @if(!empty($payload['antibodies_known']))
                <div class="dr"><span class="dl">Known Antibodies:</span><span class="dv">{{ $payload['antibodies_known'] }}</span></div>
                @endif
                @if(!empty($payload['pregnancy_history']))
                <div class="dr"><span class="dl">Pregnancy History:</span><span class="dv">{{ $payload['pregnancy_history'] }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 5. Products requested table --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Products Requested</div>
    <div class="card-body" style="padding:0;">
        <table class="doc-table" style="margin:0;">
            <thead>
                <tr>
                    <th>Blood Product</th>
                    <th>Units</th>
                    <th>Special Requirements</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payload['products_requested'] ?? [] as $prod)
                <tr>
                    <td style="font-weight:600;">{{ $prod['product'] ?? 'N/A' }}</td>
                    <td>{{ $prod['units'] ?? 'N/A' }}</td>
                    <td>{{ $prod['special_requirements'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center; color:#6B7280;">No products specified</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 6. Special product requirements --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Special Product Requirements</div>
    <div class="card-body">
        <div style="display:flex; gap:4mm; flex-wrap:wrap;">
            <div>
                <span style="font-size:9.5px; color:#6B7280; margin-right:1mm;">Irradiated:</span>
                <span class="{{ !empty($payload['irradiated_required']) ? 'special-req-yes' : 'special-req-no' }}">
                    {{ !empty($payload['irradiated_required']) ? 'Required' : 'Not required' }}
                </span>
            </div>
            <div>
                <span style="font-size:9.5px; color:#6B7280; margin-right:1mm;">CMV-Negative:</span>
                <span class="{{ !empty($payload['cmv_negative_required']) ? 'special-req-yes' : 'special-req-no' }}">
                    {{ !empty($payload['cmv_negative_required']) ? 'Required' : 'Not required' }}
                </span>
            </div>
            <div>
                <span style="font-size:9.5px; color:#6B7280; margin-right:1mm;">Leukodepleted:</span>
                <span class="{{ !empty($payload['leukodepleted_required']) ? 'special-req-yes' : 'special-req-no' }}">
                    {{ !empty($payload['leukodepleted_required']) ? 'Required' : 'Not required' }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- 7. Sample sent --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Sample</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">Sample Sent to Lab:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['sample_sent']) ? 'sample-badge-yes' : 'sample-badge-no' }}">
                            {{ !empty($payload['sample_sent']) ? 'Yes' : 'Not yet sent' }}
                        </span>
                    </span>
                </div>
            </div>
            <div>
                @if(!empty($payload['sample_time']))
                <div class="dr"><span class="dl">Sample Time:</span><span class="dv">{{ $payload['sample_time'] }}</span></div>
                @endif
                @if(!empty($payload['sample_taken_by']))
                <div class="dr"><span class="dl">Taken By:</span><span class="dv">{{ $payload['sample_taken_by'] }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 8. Surgery planned --}}
@if(!empty($payload['surgery_planned']))
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Surgery Planned:</span>
    <span class="surgery-badge">
        Yes — {{ $payload['surgery_date'] ?? 'Date TBC' }}
    </span>
</div>
@endif

{{-- 9. Blood Bank Use Only --}}
<div class="bbr-use-section">
    <div class="bbr-use-hdr">&#9472;&#9472;&#9472; BLOOD BANK USE ONLY &#9472;&#9472;&#9472;</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">Blood Group Confirmed:</span>
                    <span class="dv">
                        @if(!empty($bloodBankData['blood_group_confirmed']))
                            <span class="bg-badge">{{ $bloodBankData['blood_group_confirmed'] }}</span>
                        @else
                            <span style="color:#9CA3AF;">Pending</span>
                        @endif
                    </span>
                </div>
                <div class="dr">
                    <span class="dl">Crossmatch Result:</span>
                    <span class="dv">
                        @if(!empty($crossmatch))
                            <span class="{{ $crossmatchClass }}">{{ $crossmatch }}</span>
                        @else
                            <span style="color:#9CA3AF;">Pending</span>
                        @endif
                    </span>
                </div>
            </div>
            <div>
                <div class="dr"><span class="dl">Units Available:</span><span class="dv">{{ $bloodBankData['units_available'] ?? '—' }}</span></div>
                <div class="dr"><span class="dl">Units Issued:</span><span class="dv">{{ $bloodBankData['units_issued'] ?? '—' }}</span></div>
                <div class="dr"><span class="dl">Issued By:</span><span class="dv">{{ $bloodBankData['issued_by'] ?? '—' }}</span></div>
                <div class="dr"><span class="dl">Issued Time:</span><span class="dv">{{ $bloodBankData['issued_time'] ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 10. Requesting doctor signature --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Requesting Doctor:</span><span class="dv">{{ $payload['requesting_doctor'] ?? $issuer_name }}</span></div>
        <div class="dr"><span class="dl">Ward / Bed:</span><span class="dv">{{ $payload['ward_bed'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Date / Time:</span><span class="dv">{{ $payload['request_date'] ?? 'N/A' }} {{ $payload['request_time'] ?? '' }}</span></div>
    </div>
    <div><div class="sig-line">Requesting Doctor Signature</div></div>
</div>

{{-- 11. Identity check reminder --}}
<div class="identity-check-box">
    <strong>&#9888; IDENTITY CHECK — BEFORE ISSUING:</strong>
    Verify patient name, health ID, blood group, and crossmatch label against this form.
    <strong>Two-person verification is mandatory before release of any blood product.</strong>
    Check expiry date and label integrity. Do not issue if any discrepancy is found.
</div>
@endsection
