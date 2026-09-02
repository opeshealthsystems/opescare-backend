@extends('documents.base')

@section('title', 'DO NOT ATTEMPT RESUSCITATION ORDER')
@section('subtitle', 'DNAR / AND Order — Clinical Decision Document | OpesCare')

@section('content')
@php
    $accentColor = '#1F2937';
    $accentLight = '#F9FAFB';
    $accentMid   = '#D1D5DB';

    $orderType     = $payload['order_type']    ?? 'DNAR — Do Not Attempt Resuscitation';
    $orderDate     = $payload['order_date']    ?? '—';
    $orderTime     = $payload['order_time']    ?? '—';
    $clinicalBasis = $payload['clinical_basis'] ?? '—';
    $prognosis     = $payload['prognosis']      ?? '—';
    $cprWouldBe    = $payload['cpr_would_be']   ?? [];

    $patientCapacity        = $payload['patient_capacity']             ?? false;
    $patientInvolved        = $payload['patient_involved_in_decision'] ?? false;
    $patientWishes          = $payload['patient_wishes']               ?? null;
    $nokConsulted           = $payload['nok_consulted']                ?? false;
    $nokName                = $payload['nok_name']                     ?? null;
    $nokRelationship        = $payload['nok_relationship']             ?? null;
    $nokInformedDate        = $payload['nok_informed_date']            ?? null;
    $nokResponse            = $payload['nok_response']                 ?? null;

    $careWillContinue    = $payload['care_that_will_continue']       ?? [];
    $careWillNotProvide  = $payload['care_that_will_not_be_provided'] ?? [];
    $transferStatus      = $payload['transfer_status']               ?? 'DNAR order applies during any transfer';
    $reviewDate          = $payload['review_date']                   ?? '—';

    $certifyingPhysician  = $payload['certifying_physician'] ?? '—';
    $physicianReg         = $payload['physician_reg']        ?? '—';
    $consultant           = $payload['consultant']           ?? null;
    $palliativeInvolved   = $payload['palliative_team_involved'] ?? false;

    $stampText = match(true) {
        str_contains($orderType, 'AND')     => 'AND',
        str_contains($orderType, 'Comfort') => 'CCO',
        default                              => 'DNAR',
    };
@endphp

<style>
    .dnr-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dnr-header-left h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 3px 0;
    }
    .dnr-header-left .dnr-sub { font-size: 9.5px; opacity: 0.75; }
    .dnr-stamp {
        width: 22mm;
        height: 22mm;
        border-radius: 50%;
        border: 3px solid #DC2626;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        background: #FEF2F2;
        flex-shrink: 0;
    }
    .dnr-stamp .stamp-code  { font-size: 16px; font-weight: 800; color: #DC2626; line-height: 1; }
    .dnr-stamp .stamp-label { font-size: 7px;  font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 0.3px; }

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
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .badge-dark   { background: #1F2937; color: #F9FAFB; border: 1px solid #374151; }
    .badge-blue   { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }

    .cpr-reasons-list { margin: 0; padding: 0; list-style: none; }
    .cpr-reasons-list li {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        padding: 5px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
        color: #1F2937;
    }
    .cpr-reasons-list li:last-child { border-bottom: none; }
    .cpr-check {
        width: 14px; height: 14px;
        border-radius: 2px;
        background: #991B1B;
        color: #FFFFFF;
        display: flex; align-items: center; justify-content: center;
        font-size: 9px; font-weight: 700;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .care-list { margin: 0; padding: 0; list-style: none; }
    .care-list li {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        padding: 4px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
    }
    .care-list li:last-child { border-bottom: none; }
    .care-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .care-dot-green { background: #10B981; }
    .care-dot-red   { background: #EF4444; }

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

    .transfer-banner {
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 9.5px;
        color: #1E40AF;
        font-weight: 600;
        margin-bottom: 8px;
        text-align: center;
    }

    .care-note {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-left: 4px solid #1F2937;
        border-radius: 0 4px 4px 0;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 10px;
        color: #374151;
        font-style: italic;
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
<div class="dnr-header-strip">
    <div class="dnr-header-left">
        <h2>Do Not Attempt Resuscitation Order</h2>
        <div class="dnr-sub">
            {{ $facility_name }} &nbsp;|&nbsp; {{ $orderDate }} at {{ $orderTime }}
        </div>
        <div style="margin-top:5px;">
            <span class="badge badge-dark" style="font-size:10px; padding:3px 10px;">{{ $orderType }}</span>
        </div>
    </div>
    <div class="dnr-stamp">
        <div class="stamp-code">{{ $stampText }}</div>
        <div class="stamp-label">Do Not<br>Resuscitate</div>
    </div>
</div>

{{-- ── CLINICAL BASIS + PROGNOSIS ───────────────────────────────── --}}
<div class="two-col" style="margin-top:8px;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Clinical Basis</div>
        <div class="section-card-body">{{ $clinicalBasis }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Prognosis</div>
        <div class="section-card-body" style="font-weight:600;">{{ $prognosis }}</div>
    </div>
</div>

{{-- ── WHY CPR IS NOT APPROPRIATE ───────────────────────────────── --}}
<div class="section-card" style="margin-top:8px; border-color:#FCA5A5;">
    <div class="section-card-title" style="background:#FEE2E2; color:#991B1B;">
        Why CPR is Not Appropriate
    </div>
    <div class="section-card-body" style="padding:4px 10px;">
        <ul class="cpr-reasons-list">
            @foreach($cprWouldBe as $reason)
            <li>
                <span class="cpr-check">✓</span>
                <span style="font-weight:500;">{{ $reason }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- ── DECISION PROCESS ─────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Decision-Making Process</div>
    <div class="section-card-body">
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
            <span class="badge {{ $patientCapacity ? 'badge-green' : 'badge-red' }}">
                Patient capacity: {{ $patientCapacity ? 'Confirmed' : 'Lacks capacity' }}
            </span>
            <span class="badge {{ $patientInvolved ? 'badge-green' : 'badge-amber' }}">
                Patient involved: {{ $patientInvolved ? 'Yes' : 'No' }}
            </span>
            <span class="badge {{ $nokConsulted ? 'badge-green' : 'badge-amber' }}">
                NOK consulted: {{ $nokConsulted ? 'Yes' : 'No' }}
            </span>
        </div>

        @if($patientWishes)
        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:4px; padding:6px 10px; margin-bottom:6px;">
            <div style="font-size:9px; font-weight:600; color:#065F46; text-transform:uppercase; margin-bottom:2px;">Patient's Expressed Wishes</div>
            <div style="font-size:10px; color:#1F2937; font-style:italic;">"{{ $patientWishes }}"</div>
        </div>
        @endif

        @if($nokConsulted)
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:4px;">
            @if($nokName)
            <span class="badge badge-gray">NOK: {{ $nokName }}</span>
            @endif
            @if($nokRelationship)
            <span class="badge badge-gray">Relationship: {{ $nokRelationship }}</span>
            @endif
            @if($nokInformedDate)
            <span class="badge badge-gray">Informed: {{ $nokInformedDate }}</span>
            @endif
        </div>
        @if($nokResponse)
        <div style="margin-top:6px; font-size:9.5px; color:#374151;">
            <span style="font-weight:600;">NOK Response:</span> {{ $nokResponse }}
        </div>
        @endif
        @endif
    </div>
</div>

{{-- ── CARE THAT WILL CONTINUE vs WILL NOT BE PROVIDED ────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0; border-color:#6EE7B7;">
        <div class="section-card-title" style="background:#D1FAE5; color:#065F46;">
            &#10003; Care That WILL Continue
        </div>
        <div class="section-card-body" style="padding:6px 10px;">
            <ul class="care-list">
                @foreach($careWillContinue as $item)
                <li>
                    <span class="care-dot care-dot-green"></span>
                    <span style="color:#065F46;">{{ $item }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0; border-color:#FCA5A5;">
        <div class="section-card-title" style="background:#FEE2E2; color:#991B1B;">
            &#10007; Will NOT Be Provided
        </div>
        <div class="section-card-body" style="padding:6px 10px;">
            <ul class="care-list">
                @foreach($careWillNotProvide as $item)
                <li>
                    <span class="care-dot care-dot-red"></span>
                    <span style="color:#991B1B;">{{ $item }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- ── TRANSFER NOTE ────────────────────────────────────────────── --}}
<div class="transfer-banner">
    &#8646; {{ $transferStatus }}
</div>

{{-- ── REVIEW DATE ──────────────────────────────────────────────── --}}
<div class="review-deadline-box">
    <div class="dl-label">This Order Must Be Reviewed Before — Cette ordonnance doit être révisée avant:</div>
    <div class="dl-date">{{ $reviewDate }}</div>
</div>

{{-- ── CERTIFYING PHYSICIAN + CONSULTANT ───────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Certifying Physician</div>
        <div class="section-card-body">
            <div style="font-weight:600; font-size:11px;">{{ $certifyingPhysician }}</div>
            <div style="color:#6B7280; font-size:9.5px; margin-top:2px;">Reg: {{ $physicianReg }}</div>
            <div class="sig-line" style="margin-top:10mm;">
                <span style="color:#9CA3AF;">Signature</span>
            </div>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Countersigning Consultant</div>
        <div class="section-card-body">
            @if($consultant)
            <div style="font-weight:600; font-size:11px;">{{ $consultant }}</div>
            <span class="badge badge-green" style="margin-top:4px;">Countersigned</span>
            @else
            <span class="badge badge-gray">No countersignature recorded</span>
            @endif
            <div class="sig-line" style="margin-top:10mm;">
                <span style="color:#9CA3AF;">Signature</span>
            </div>
        </div>
    </div>
</div>

{{-- ── PALLIATIVE TEAM + CARE NOTE ─────────────────────────────── --}}
<div style="display:flex; align-items:center; gap:8px; margin:6px 0;">
    <span class="badge {{ $palliativeInvolved ? 'badge-blue' : 'badge-gray' }}">
        Palliative team: {{ $palliativeInvolved ? 'Involved' : 'Not recorded' }}
    </span>
</div>

<div class="care-note">
    This order does not mean withdrawal of care. It means that cardiopulmonary resuscitation (CPR)
    will not be attempted as it is not in the patient's best interests. All other appropriate care,
    comfort measures, and symptom management will continue. This decision has been made following
    thorough clinical assessment and, where possible, in consultation with the patient and their family.
</div>
@endsection
