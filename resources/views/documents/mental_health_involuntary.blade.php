@extends('documents.base')

@section('title', 'INVOLUNTARY ADMISSION ORDER — ORDRE D\'ADMISSION INVOLONTAIRE')
@section('subtitle', 'Mental Health Act Detention Order | OpesCare Mental Health Services')

@section('content')
@php
    $accentColor = '#4F46E5';
    $accentLight = '#EEF2FF';
    $accentMid   = '#C7D2FE';

    $orderType    = $payload['order_type']    ?? 'Emergency Involuntary Admission (72h)';
    $orderDate    = $payload['order_date']    ?? '—';
    $orderTime    = $payload['order_time']    ?? '—';
    $legalGrounds = $payload['legal_grounds'] ?? [];
    $mentalDisorder    = $payload['mental_disorder_suspected']  ?? '—';
    $clinicalJustification = $payload['clinical_justification'] ?? '—';
    $capacityAssessment    = $payload['capacity_assessment']    ?? [];
    $riskAssessment        = $payload['risk_assessment']        ?? [];
    $lessRestrictive       = $payload['less_restrictive_options'] ?? '—';
    $patientInformed   = $payload['patient_informed']   ?? false;
    $patientObjected   = $payload['patient_objected']   ?? false;
    $nokInformed       = $payload['nok_informed']       ?? false;
    $nokName           = $payload['nok_name']           ?? null;
    $nokInformedTime   = $payload['nok_informed_time']  ?? null;
    $admittingFacility = $payload['admitting_facility'] ?? 'Psychiatric Unit — OpesCare Central Hospital';
    $admittingWard     = $payload['admitting_ward']     ?? '—';
    $reviewDate        = $payload['review_date']        ?? '—';
    $certifyingPhysician = $payload['certifying_physician'] ?? '—';
    $physicianReg        = $payload['physician_reg']        ?? '—';
    $physicianSpecialty  = $payload['physician_specialty']  ?? '—';
    $secondOpinion       = $payload['second_opinion']       ?? null;
    $judicialRequired    = $payload['judicial_notification_required'] ?? false;
    $magistrateRef       = $payload['magistrate_ref']       ?? null;
    $patientRightsGiven  = $payload['patient_rights_given'] ?? false;

    $overallRisk = $riskAssessment['overall'] ?? 'Medium';
    $riskBgClass = match($overallRisk) {
        'Very High' => '#7F1D1D',
        'High'      => '#991B1B',
        'Medium'    => '#92400E',
        default     => '#065F46',
    };
    $riskTextColor = '#FFFFFF';

    $orderDuration = match(true) {
        str_contains($orderType, '72')   => '72 hours',
        str_contains($orderType, '28')   => '28 days',
        str_contains($orderType, 'Renewal') => '28 days (renewal)',
        default => 'as specified',
    };

    $capacityItems = [
        'understands'  => 'Patient understands the nature of their mental disorder and proposed admission',
        'retains'      => 'Patient retains relevant information long enough to make a decision',
        'weighs'       => 'Patient can weigh information to arrive at a decision',
        'communicates' => 'Patient can communicate their decision',
    ];

    $allGrounds = [
        'Risk of serious harm to self',
        'Risk of serious harm to others',
        'Patient unable to make informed decision due to mental disorder',
        'Urgent treatment necessary to prevent deterioration',
    ];
@endphp

<style>
    .mhi-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
    }
    .mhi-header-strip h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 3px 0;
    }
    .mhi-header-strip .mhi-sub {
        font-size: 9.5px;
        opacity: 0.85;
    }

    .mhi-warning-banner {
        background: #FEF2F2;
        border: 2px solid #DC2626;
        border-radius: 4px;
        padding: 8px 14px;
        margin: 8px 0;
        text-align: center;
    }
    .mhi-warning-banner .warn-title {
        font-size: 11px;
        font-weight: 700;
        color: #991B1B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .mhi-warning-banner .warn-sub {
        font-size: 9px;
        color: #B91C1C;
        margin-top: 2px;
    }

    .section-card {
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        margin-bottom: 8px;
        overflow: hidden;
    }
    .section-card-title {
        background: #F3F4F6;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .section-card-body {
        padding: 8px 10px;
        font-size: 10px;
        color: #1F2937;
        line-height: 1.5;
    }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-indigo { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    .grounds-list { margin: 0; padding: 0; list-style: none; }
    .grounds-list li {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        padding: 5px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
        color: #1F2937;
    }
    .grounds-list li:last-child { border-bottom: none; }
    .ground-check {
        width: 14px;
        height: 14px;
        border-radius: 2px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        margin-top: 1px;
    }
    .ground-checked   { background: #DC2626; color: #FFFFFF; }
    .ground-unchecked { background: #F3F4F6; color: #9CA3AF; border: 1px solid #D1D5DB; }

    .cap-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-bottom: 1px solid #F3F4F6;
    }
    .cap-row:last-child { border-bottom: none; }
    .cap-dot {
        width: 14px; height: 14px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 9px; font-weight: 700;
        flex-shrink: 0;
    }
    .cap-yes { background: #D1FAE5; color: #065F46; }
    .cap-no  { background: #FEE2E2; color: #991B1B; }
    .cap-label { font-size: 10px; color: #1F2937; flex: 1; }

    .risk-item { padding: 5px 10px; border-bottom: 1px solid #F3F4F6; display:flex; align-items:center; gap:8px; }
    .risk-item:last-child { border-bottom: none; }
    .risk-domain { font-size: 10px; font-weight: 600; color: #374151; width: 45%; }

    .review-deadline-box {
        background: #FFF7ED;
        border: 2px solid #F59E0B;
        border-radius: 4px;
        padding: 8px 14px;
        text-align: center;
        margin-bottom: 8px;
    }
    .review-deadline-box .dl-label { font-size: 9px; color: #92400E; text-transform: uppercase; font-weight: 600; }
    .review-deadline-box .dl-date  { font-size: 14px; font-weight: 700; color: #B45309; margin-top: 2px; }

    .legal-notice {
        background: {{ $accentLight }};
        border: 1px solid {{ $accentMid }};
        border-radius: 4px;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 9px;
        color: #3730A3;
        font-style: italic;
    }

    .notification-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 6px 10px;
    }

    .sig-line {
        border-top: 1px solid #9CA3AF;
        padding-top: 4px;
        margin-top: 12mm;
        font-size: 9px;
        color: #374151;
    }
</style>

{{-- ── HEADER STRIP ─────────────────────────────────────────────── --}}
<div class="mhi-header-strip">
    <h2>Involuntary Admission Order — Ordre d'Admission Involontaire</h2>
    <div class="mhi-sub">
        {{ $facility_name }} &nbsp;|&nbsp; {{ $admittingFacility }} &nbsp;|&nbsp;
        {{ $orderDate }} at {{ $orderTime }}
    </div>
</div>

{{-- ── ORDER TYPE BADGE ─────────────────────────────────────────── --}}
<div style="background:{{ $accentLight }}; border:1px solid {{ $accentMid }}; border-top:none; border-radius:0 0 4px 4px; padding:6px 12px; display:flex; gap:8px; align-items:center; margin-bottom:8px;">
    <span class="badge badge-indigo">{{ $orderType }}</span>
    <span style="font-size:9px; color:#6B7280;">Admitting ward:</span>
    <span style="font-size:10px; font-weight:600; color:#374151;">{{ $admittingWard }}</span>
</div>

{{-- ── WARNING BANNER ───────────────────────────────────────────── --}}
<div class="mhi-warning-banner">
    <div class="warn-title">&#9888; THIS ORDER DEPRIVES THE PATIENT OF LIBERTY — Strict legal compliance required</div>
    <div class="warn-sub">Ce document prive le patient de sa liberté. Conformité légale stricte obligatoire.</div>
</div>

{{-- ── LEGAL GROUNDS CHECKLIST ──────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Legal Grounds for Detention (tick all that apply)
    </div>
    <div class="section-card-body" style="padding: 4px 10px;">
        <ul class="grounds-list">
            @foreach($allGrounds as $ground)
            @php $ticked = in_array($ground, $legalGrounds); @endphp
            <li>
                <span class="ground-check {{ $ticked ? 'ground-checked' : 'ground-unchecked' }}">
                    {{ $ticked ? '✓' : '' }}
                </span>
                <span style="{{ $ticked ? 'font-weight:600;' : 'color:#9CA3AF;' }}">{{ $ground }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- ── MENTAL DISORDER + CLINICAL JUSTIFICATION ────────────────── --}}
<div class="two-col" style="margin-bottom:0;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Mental Disorder Suspected</div>
        <div class="section-card-body">{{ $mentalDisorder }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Clinical Justification for Involuntary Admission</div>
        <div class="section-card-body">{{ $clinicalJustification }}</div>
    </div>
</div>

{{-- ── CAPACITY ASSESSMENT ──────────────────────────────────────── --}}
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Capacity Assessment — 4-Point Test
    </div>
    <div class="section-card-body" style="padding:0;">
        @foreach($capacityItems as $key => $label)
        @php $met = (bool)($capacityAssessment[$key] ?? false); @endphp
        <div class="cap-row">
            <div class="cap-dot {{ $met ? 'cap-yes' : 'cap-no' }}">{{ $met ? '✓' : '✗' }}</div>
            <div class="cap-label">{{ $label }}</div>
            <span class="badge {{ $met ? 'badge-green' : 'badge-red' }}">{{ $met ? 'Met' : 'Not Met' }}</span>
        </div>
        @endforeach
        <div style="padding:6px 10px; border-top:1px solid #E5E7EB; display:flex; align-items:center; gap:8px;">
            @php $hasCapacity = (bool)($capacityAssessment['has_capacity'] ?? false); @endphp
            <span class="badge {{ $hasCapacity ? 'badge-green' : 'badge-red' }}" style="font-size:10px; padding:3px 10px;">
                {{ $hasCapacity ? 'CAPACITY CONFIRMED' : 'LACKS CAPACITY' }}
            </span>
            @if(!empty($capacityAssessment['rationale']))
            <span style="font-size:9.5px; color:#374151; font-style:italic;">{{ $capacityAssessment['rationale'] }}</span>
            @endif
        </div>
    </div>
</div>

{{-- ── RISK ASSESSMENT ──────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title" style="color:#991B1B;">Risk Assessment</div>
    <div class="section-card-body" style="padding:0;">
        @foreach(['suicide_risk' => 'Suicide Risk', 'violence_risk' => 'Violence Risk', 'self_neglect_risk' => 'Self-Neglect Risk'] as $rKey => $rLabel)
        @php
            $rVal = $riskAssessment[$rKey] ?? 'Not assessed';
            $rBadge = match(strtolower($rVal)) {
                'high', 'very high' => 'badge-red',
                'medium'            => 'badge-amber',
                default             => 'badge-green',
            };
        @endphp
        <div class="risk-item">
            <span class="risk-domain">{{ $rLabel }}</span>
            <span class="badge {{ $rBadge }}">{{ $rVal }}</span>
        </div>
        @endforeach
        <div style="padding:6px 10px; border-top:1px solid #E5E7EB;">
            <span style="font-size:9px; color:#374151; font-weight:600;">Overall Risk:</span>
            <span style="display:inline-block; margin-left:6px; padding:3px 12px; border-radius:4px; font-size:10px; font-weight:700; background:{{ $riskBgClass }}; color:{{ $riskTextColor }};">
                {{ $overallRisk }}
            </span>
        </div>
    </div>
</div>

{{-- ── LESS RESTRICTIVE OPTIONS ─────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Less Restrictive Alternatives Considered</div>
    <div class="section-card-body">{{ $lessRestrictive }}</div>
</div>

{{-- ── NOTIFICATIONS ────────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Patient &amp; NOK Notification</div>
    <div class="section-card-body" style="padding:0;">
        <div class="notification-row">
            <span class="badge {{ $patientInformed ? 'badge-green' : 'badge-red' }}">
                Patient informed: {{ $patientInformed ? 'Yes' : 'No' }}
            </span>
            <span class="badge {{ $patientObjected ? 'badge-red' : 'badge-gray' }}">
                Patient objected: {{ $patientObjected ? 'Yes' : 'No' }}
            </span>
            <span class="badge {{ $nokInformed ? 'badge-green' : 'badge-amber' }}">
                NOK informed: {{ $nokInformed ? 'Yes' : 'No' }}
            </span>
            @if($nokName)
            <span class="badge badge-gray">NOK: {{ $nokName }}</span>
            @endif
            @if($nokInformedTime)
            <span class="badge badge-gray">NOK notified at: {{ $nokInformedTime }}</span>
            @endif
        </div>
    </div>
</div>

{{-- ── REVIEW DEADLINE ──────────────────────────────────────────── --}}
<div class="review-deadline-box">
    <div class="dl-label">Order Review Deadline — This order MUST be reviewed before:</div>
    <div class="dl-date">{{ $reviewDate }}</div>
</div>

{{-- ── CERTIFYING PHYSICIAN + SECOND OPINION ───────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Certifying Physician</div>
        <div class="section-card-body">
            <div style="font-weight:600; font-size:11px;">{{ $certifyingPhysician }}</div>
            <div style="color:#6B7280; font-size:9.5px; margin-top:2px;">Reg No: {{ $physicianReg }}</div>
            <div style="color:#6B7280; font-size:9.5px;">Specialty: {{ $physicianSpecialty }}</div>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Second Opinion</div>
        <div class="section-card-body">
            @if($secondOpinion)
            <div style="font-weight:600; font-size:11px;">{{ $secondOpinion }}</div>
            <span class="badge badge-green" style="margin-top:4px;">Second opinion obtained</span>
            @else
            <span class="badge badge-amber">Not obtained</span>
            <div style="font-size:9px; color:#6B7280; margin-top:4px;">Second opinion not recorded</div>
            @endif
        </div>
    </div>
</div>

{{-- ── JUDICIAL NOTIFICATION ───────────────────────────────────── --}}
@if($judicialRequired)
<div class="section-card" style="margin-top:8px; border-color:#DC2626;">
    <div class="section-card-title" style="background:#FEE2E2; color:#991B1B;">
        Judicial Notification Required
    </div>
    <div class="section-card-body">
        <span class="badge badge-red">Judicial notification required under Cameroon Mental Health Act</span>
        @if($magistrateRef)
        <span class="badge badge-gray" style="margin-left:6px;">Magistrate Ref: {{ $magistrateRef }}</span>
        @endif
    </div>
</div>
@endif

{{-- ── PATIENT RIGHTS ───────────────────────────────────────────── --}}
<div class="section-card" style="border-color:{{ $accentColor }};">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Patient Rights
    </div>
    <div class="section-card-body">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <span class="badge {{ $patientRightsGiven ? 'badge-green' : 'badge-red' }}">
                Rights documented: {{ $patientRightsGiven ? 'Yes' : 'No' }}
            </span>
        </div>
        <div style="font-size:10px; color:#1F2937; line-height:1.6;">
            Patient has been informed of: (1) the right to appeal this order to the courts;
            (2) the right to legal representation; (3) the right to an independent medical review;
            (4) the right to have a nominated person notified; and (5) the right to be treated with
            dignity and have their cultural and religious needs respected.
        </div>
    </div>
</div>

{{-- ── SIGNATURE SECTION ───────────────────────────────────────── --}}
<div class="two-col" style="margin-top:10px;">
    <div>
        <div class="sig-line">
            <div style="font-weight:600;">{{ $certifyingPhysician }}</div>
            <div style="color:#6B7280;">{{ $physicianSpecialty }} | Reg: {{ $physicianReg }}</div>
            <div style="color:#6B7280;">Date: {{ $orderDate }} &nbsp;&nbsp; Time: {{ $orderTime }}</div>
        </div>
    </div>
    <div>
        <div class="sig-line">
            <div style="font-weight:600; color:#6B7280;">Countersignature (if required)</div>
            <div style="color:#9CA3AF; font-style:italic; font-size:9px;">Witness / Authorized Officer</div>
        </div>
    </div>
</div>

{{-- ── LEGAL NOTICE ─────────────────────────────────────────────── --}}
<div class="legal-notice">
    Issued under the Cameroon Law on Mental Health and applicable provisions of Law No. 2010/012 on the
    Protection of Personal Data. This order is valid for <strong>{{ $orderDuration }}</strong> from the
    time of signing. It must be reviewed before <strong>{{ $reviewDate }}</strong>. Failure to review
    before the deadline renders this order unlawful. Unauthorised disclosure of this document is a criminal offence.
</div>
@endsection
