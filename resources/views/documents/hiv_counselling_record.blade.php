@extends('documents.base')

@section('title', 'CONFIDENTIAL — HIV COUNSELLING RECORD')

@section('subtitle', 'MINSANTE HIV/AIDS Programme | Code: HCR | {{ $document_number }}')

@section('content')
<style>
    .hcr-conf-banner { background-color:#0F766E; color:#FFF; border-radius:6px; padding:3mm 5mm; margin-bottom:5mm; display:flex; align-items:center; justify-content:space-between; }
    .hcr-conf-text { font-size:13px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
    .hcr-conf-sub  { font-size:9.5px; opacity:.82; margin-top:1mm; }
    .session-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; background-color:#CCFBF1; color:#134E4A; border:1px solid #5EEAD4; }
    .indication-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F0FDFA; color:#0F766E; border:1px solid #99F6E4; }
    .checklist-item { display:flex; align-items:flex-start; padding:1.5mm 0; border-bottom:1px solid #CCFBF1; font-size:10px; }
    .checklist-item:last-child { border-bottom:none; }
    .check-done { color:#059669; font-weight:700; margin-right:2mm; min-width:5mm; }
    .check-text { color:#111827; }
    .risk-badge-low  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .risk-badge-mod  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; }
    .risk-badge-high { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEE2E2; color:#991B1B; }
    .risk-factor-yes { display:inline-block; padding:.5mm 2mm; border-radius:3px; font-size:9px; font-weight:600; background-color:#FEE2E2; color:#991B1B; }
    .risk-factor-no  { display:inline-block; padding:.5mm 2mm; border-radius:3px; font-size:9px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .consent-yes { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; }
    .consent-no  { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:11px; font-weight:700; background-color:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
    .result-neg { background-color:#D1FAE5; border:3px solid #059669; border-radius:8px; text-align:center; padding:4mm; margin-bottom:5mm; }
    .result-pos { background-color:#FEF3C7; border:3px solid #D97706; border-radius:8px; text-align:center; padding:4mm; margin-bottom:5mm; }
    .result-ind { background-color:#EFF6FF; border:3px solid #2563EB; border-radius:8px; text-align:center; padding:4mm; margin-bottom:5mm; }
    .result-label-neg { font-size:22px; font-weight:800; color:#065F46; text-transform:uppercase; }
    .result-label-pos { font-size:22px; font-weight:800; color:#92400E; text-transform:uppercase; }
    .result-label-ind { font-size:22px; font-weight:800; color:#1D4ED8; text-transform:uppercase; }
    .result-sub { font-size:10px; margin-top:1mm; }
    .result-neg .result-sub { color:#047857; }
    .result-pos .result-sub { color:#92400E; }
    .result-ind .result-sub { color:#1D4ED8; }
    .emotion-badge { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; }
    .emotion-calm       { background-color:#D1FAE5; color:#065F46; }
    .emotion-distressed { background-color:#FEE2E2; color:#991B1B; }
    .emotion-denial     { background-color:#FEF3C7; color:#92400E; }
    .emotion-overwhelmed{ background-color:#EDE9FE; color:#5B21B6; }
    .referral-badge { display:inline-block; padding:1mm 3mm; border-radius:9999px; font-size:9.5px; font-weight:700; background-color:#CCFBF1; color:#134E4A; border:1px solid #5EEAD4; }
    .support-item { display:inline-block; padding:.5mm 2mm; border-radius:3px; font-size:9.5px; background-color:#F0FDFA; color:#0F766E; margin:1px 2px 1px 0; }
    .next-appt-box { background-color:#F0FDFA; border:1px solid #5EEAD4; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:10.5px; color:#134E4A; }
    .conf-notice { background-color:#F0FDFA; border:2px solid #0F766E; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:9.5px; color:#134E4A; }
    .client-code-box { background-color:#F8FAFC; border:1px dashed #94A3B8; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; text-align:center; font-size:11px; font-weight:700; color:#374151; letter-spacing:1px; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5mm; }
    .sig-line { border-top:1px solid #0F766E; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

@php
    $sessionType = $payload['session_type'] ?? 'Pre-Test Counselling';
    $finalResult = $payload['final_result'] ?? null;
    $resultClass = match(true) {
        $finalResult === 'HIV Negative'                       => 'result-neg',
        $finalResult === 'HIV Positive'                       => 'result-pos',
        str_starts_with((string)$finalResult, 'Indeterminate') => 'result-ind',
        default => '',
    };
    $resultLabelClass = match(true) {
        $finalResult === 'HIV Negative'                       => 'result-label-neg',
        $finalResult === 'HIV Positive'                       => 'result-label-pos',
        str_starts_with((string)$finalResult, 'Indeterminate') => 'result-label-ind',
        default => '',
    };
    $emotionRaw = $payload['emotional_response'] ?? null;
    $emotionClass = match($emotionRaw) {
        'Calm'        => 'emotion-calm',
        'Distressed'  => 'emotion-distressed',
        'In denial'   => 'emotion-denial',
        'Overwhelmed' => 'emotion-overwhelmed',
        default       => 'emotion-calm',
    };
    $riskAssessment = $payload['risk_assessment'] ?? [];
    $riskLevel = $riskAssessment['risk_level'] ?? 'Low';
    $riskClass = match($riskLevel) {
        'High'     => 'risk-badge-high',
        'Moderate' => 'risk-badge-mod',
        default    => 'risk-badge-low',
    };
@endphp

{{-- 1. Confidential banner + session type --}}
<div class="hcr-conf-banner">
    <div>
        <div class="hcr-conf-text">CONFIDENTIAL — HIV Counselling Record</div>
        <div class="hcr-conf-sub">MINSANTE HIV/AIDS Programme — Do not disclose without written consent</div>
    </div>
    <span class="session-badge">{{ $sessionType }}</span>
</div>

{{-- Client code (not patient name) for privacy --}}
<div class="client-code-box">
    Client Code: {{ $payload['client_code'] ?? 'N/A' }}
    &nbsp;|&nbsp;
    Session: {{ $payload['session_date'] ?? 'N/A' }} at {{ $payload['session_time'] ?? 'N/A' }}
</div>

{{-- 2. Session + counsellor details --}}
<div class="content-card">
    <div class="card-header" style="background-color:#CCFBF1; color:#134E4A;">Session Details</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Counsellor Code:</span><span class="dv">{{ $payload['counsellor_code'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Counsellor:</span><span class="dv">{{ $payload['counsellor'] ?? $issuer_name }}</span></div>
            </div>
            <div>
                <div class="dr"><span class="dl">Facility:</span><span class="dv">{{ $facility_name }}</span></div>
                <div class="dr"><span class="dl">Date / Time:</span><span class="dv">{{ $payload['session_date'] ?? 'N/A' }} {{ $payload['session_time'] ?? '' }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Testing indication --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Testing Indication:</span>
    <span class="indication-badge">{{ $payload['testing_indication'] ?? 'N/A' }}</span>
</div>

{{-- 4. Pre-test counselling checklist --}}
@if(!empty($payload['pre_test_topics_covered']) && is_array($payload['pre_test_topics_covered']))
<div class="content-card">
    <div class="card-header" style="background-color:#CCFBF1; color:#134E4A;">Pre-Test Counselling Topics Covered</div>
    <div class="card-body">
        @foreach($payload['pre_test_topics_covered'] as $topic)
        <div class="checklist-item">
            <span class="check-done">&#10003;</span>
            <span class="check-text">{{ $topic }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- 5. Risk Assessment --}}
<div class="content-card">
    <div class="card-header" style="background-color:#CCFBF1; color:#134E4A;">Risk Assessment</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">Sexual Partners (12 mo):</span>
                    <span class="dv">{{ $riskAssessment['sexual_partners_12mo'] ?? 'N/A' }}</span>
                </div>
                <div class="dr">
                    <span class="dl">Condom Use:</span>
                    <span class="dv">{{ $riskAssessment['condom_use'] ?? 'N/A' }}</span>
                </div>
                <div class="dr">
                    <span class="dl">IV Drug Use:</span>
                    <span class="dv">
                        <span class="{{ !empty($riskAssessment['intravenous_drug_use']) ? 'risk-factor-yes' : 'risk-factor-no' }}">
                            {{ !empty($riskAssessment['intravenous_drug_use']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
            </div>
            <div>
                <div class="dr">
                    <span class="dl">Blood Transfusion History:</span>
                    <span class="dv">
                        <span class="{{ !empty($riskAssessment['blood_transfusion_history']) ? 'risk-factor-yes' : 'risk-factor-no' }}">
                            {{ !empty($riskAssessment['blood_transfusion_history']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
                <div class="dr">
                    <span class="dl">STI History:</span>
                    <span class="dv">
                        <span class="{{ !empty($riskAssessment['sti_history']) ? 'risk-factor-yes' : 'risk-factor-no' }}">
                            {{ !empty($riskAssessment['sti_history']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
                <div class="dr">
                    <span class="dl">Overall Risk Level:</span>
                    <span class="dv"><span class="{{ $riskClass }}">{{ $riskLevel }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 6. Consent --}}
<div style="margin-bottom:5mm; text-align:center;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Informed Consent to Test:</span>
    @if(!empty($payload['consent_given']))
        <span class="consent-yes">Consent Obtained</span>
    @else
        <span class="consent-no">Consent Not Given — Test Not Performed</span>
    @endif
</div>

{{-- 7. Test result --}}
@if(!empty($payload['test_performed']) && !empty($finalResult))
<div class="{{ $resultClass }}">
    <div class="{{ $resultLabelClass }}">{{ $finalResult }}</div>
    @if(!empty($payload['test_result']))
    <div class="result-sub">
        Initial: {{ $payload['test_result'] ?? 'N/A' }}
        @if(!empty($payload['confirmatory_test']))
            &nbsp;|&nbsp; Confirmatory: {{ $payload['confirmatory_test'] }}
        @endif
    </div>
    @endif
</div>
@endif

{{-- 8. Post-test counselling checklist --}}
@if(!empty($payload['post_test_topics_covered']) && is_array($payload['post_test_topics_covered']))
<div class="content-card">
    <div class="card-header" style="background-color:#CCFBF1; color:#134E4A;">Post-Test Counselling Topics Covered</div>
    <div class="card-body">
        @foreach($payload['post_test_topics_covered'] as $topic)
        <div class="checklist-item">
            <span class="check-done">&#10003;</span>
            <span class="check-text">{{ $topic }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- 9. Emotional response + support --}}
<div class="content-card">
    <div class="card-header" style="background-color:#CCFBF1; color:#134E4A;">Emotional Response &amp; Support</div>
    <div class="card-body">
        @if(!empty($emotionRaw))
        <div class="dr">
            <span class="dl">Emotional Response:</span>
            <span class="dv"><span class="emotion-badge {{ $emotionClass }}">{{ $emotionRaw }}</span></span>
        </div>
        @endif
        @if(!empty($payload['support_arranged']) && is_array($payload['support_arranged']))
        <div class="dr">
            <span class="dl">Support Arranged:</span>
            <span class="dv">
                @foreach($payload['support_arranged'] as $sup)
                    <span class="support-item">{{ $sup }}</span>
                @endforeach
            </span>
        </div>
        @endif
    </div>
</div>

{{-- 10. Referral --}}
@if(!empty($payload['referral_made']))
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Referral Made:</span>
    <span class="referral-badge">{{ $payload['referral_made'] }}</span>
</div>
@endif

{{-- 11. Next appointment --}}
@if(!empty($payload['next_appointment']))
<div class="next-appt-box">
    <strong>Next Appointment:</strong> {{ $payload['next_appointment'] }}
</div>
@endif

{{-- Counsellor signature --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Counsellor:</span><span class="dv">{{ $payload['counsellor'] ?? $issuer_name }}</span></div>
        <div class="dr"><span class="dl">Code:</span><span class="dv">{{ $payload['counsellor_code'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Date:</span><span class="dv">{{ $issued_at }}</span></div>
    </div>
    <div><div class="sig-line">Counsellor Signature</div></div>
</div>

{{-- 12. Confidentiality notice --}}
<div class="conf-notice">
    <strong>CONFIDENTIALITY NOTICE:</strong>
    This record is strictly confidential. Disclosure of HIV status without written consent is prohibited under
    <em>Cameroon Law No. 2003/004</em> and the MINSANTE HIV/AIDS Guidelines.
    This document must be stored separately from the general medical record and accessed only by authorised personnel.
</div>
@endsection
