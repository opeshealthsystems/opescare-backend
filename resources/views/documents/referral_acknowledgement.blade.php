@extends('documents.base')

@section('title', 'Referral Acknowledgement Letter')
@section('subtitle', 'RAL — Specialist Response to Referring Clinician')

@section('content')
@php
    $accentColor       = '#0369A1';
    $origRefNum        = $payload['original_referral_number']   ?? '—';
    $origRefDate       = $payload['original_referral_date']     ?? '—';
    $referringDoctor   = $payload['referring_doctor']           ?? '—';
    $referringFacility = $payload['referring_facility']         ?? '—';
    $referringSpec     = $payload['referring_specialty']        ?? '—';
    $receivedDate      = $payload['patient_received_date']      ?? '—';
    $receivedTime      = $payload['patient_received_time']      ?? '—';
    $specialist        = $payload['specialist']                 ?? '—';
    $specialty         = $payload['specialty']                  ?? '—';
    $assessSummary     = $payload['assessment_summary']         ?? '';
    $diagnoses         = $payload['specialist_diagnosis']       ?? [];
    $mgmtPlan          = $payload['management_plan']            ?? [];
    $investigations    = $payload['investigations_requested']   ?? [];
    $medsChanged       = $payload['medications_changed']        ?? [];
    $followUp          = $payload['follow_up_plan']             ?? '—';
    $sharedCare        = $payload['shared_care_recommendations']?? [];
    $urgentConcerns    = $payload['urgent_concerns']            ?? null;
    $thankYou          = $payload['thank_you_note']             ?? 'Thank you for this referral. We will continue to keep you updated on this patient\'s progress.';
@endphp

{{-- ── Letterhead date + RE block ── --}}
<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
    {{-- To --}}
    <div>
        <p style="font-size:12px;margin:0 0 2px;font-weight:700;color:#374151;">TO:</p>
        <p style="font-size:13px;margin:0 0 2px;font-weight:700;">Dr. {{ $referringDoctor }}</p>
        <p style="font-size:12px;margin:0;color:#374151;">{{ $referringFacility }}</p>
        <p style="font-size:12px;margin:0;color:#6b7280;">{{ $referringSpec }}</p>
    </div>
    {{-- Date + From --}}
    <div style="text-align:right;">
        <p style="font-size:12px;margin:0 0 2px;color:#374151;"><strong>Date:</strong> {{ $issued_at ?? '—' }}</p>
        <p style="font-size:12px;margin:0 0 2px;font-weight:700;">From: {{ $specialist }}</p>
        <p style="font-size:12px;margin:0;color:#6b7280;">{{ $specialty }}</p>
        <p style="font-size:12px;margin:0;color:#6b7280;">{{ $facility_name ?? '' }}</p>
    </div>
</div>

{{-- RE block --}}
<div style="background:#eff6ff;border-left:4px solid {{ $accentColor }};padding:10px 14px;border-radius:0 4px 4px 0;margin-bottom:20px;">
    <p style="font-size:12px;margin:0 0 3px;">
        <strong>RE:</strong> {{ $patient_name ?? '—' }} &nbsp;|&nbsp;
        Health ID: {{ $health_id ?? '—' }}
    </p>
    <p style="font-size:12px;margin:0;">
        <strong>Original Referral:</strong> {{ $origRefNum }} &nbsp;({{ $origRefDate }})
    </p>
    <p style="font-size:12px;margin:4px 0 0;">
        <strong>Patient Received:</strong> {{ $receivedDate }} at {{ $receivedTime }}
    </p>
</div>

{{-- Salutation --}}
<p style="font-size:13px;margin:0 0 14px;">Dear Dr. {{ $referringDoctor }},</p>

{{-- Opening paragraph --}}
<p style="font-size:13px;line-height:1.6;margin:0 0 16px;">
    Thank you for referring <strong>{{ $patient_name ?? 'this patient' }}</strong> on <strong>{{ $origRefDate }}</strong>.
    We reviewed this patient on <strong>{{ $receivedDate }}</strong> and provide this summary of our assessment and initial management plan.
</p>

{{-- Assessment Summary --}}
@if($assessSummary)
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Assessment Summary
    </h3>
    <p style="font-size:13px;line-height:1.6;margin:0;">{{ $assessSummary }}</p>
</div>
@endif

{{-- Specialist Diagnoses --}}
@if(count($diagnoses) > 0)
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Specialist Diagnoses
    </h3>
    <ol style="margin:0;padding-left:18px;">
        @foreach($diagnoses as $dx)
        <li style="font-size:13px;margin-bottom:5px;">
            {{ $dx['diagnosis'] ?? '—' }}
            @if(!empty($dx['icd10']))
                <span style="background:#dbeafe;color:#1e40af;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:700;margin-left:6px;">
                    {{ $dx['icd10'] }}
                </span>
            @endif
        </li>
        @endforeach
    </ol>
</div>
@endif

{{-- Management Plan --}}
@if(count($mgmtPlan) > 0)
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Management Plan
    </h3>
    <ol style="margin:0;padding-left:18px;">
        @foreach($mgmtPlan as $item)
        <li style="font-size:13px;margin-bottom:5px;">{{ $item }}</li>
        @endforeach
    </ol>
</div>
@endif

{{-- Investigations Requested --}}
@if(count($investigations) > 0)
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Further Investigations Requested
    </h3>
    <ul style="margin:0;padding-left:18px;">
        @foreach($investigations as $inv)
        <li style="font-size:13px;margin-bottom:5px;">{{ $inv }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Medications Changed --}}
@if(count($medsChanged) > 0)
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Medication Changes
    </h3>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#eff6ff;color:#1e40af;">
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;width:55%;">Change</th>
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;">Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($medsChanged as $i => $med)
            <tr style="background:{{ $i % 2 === 0 ? '#f9fafb' : '#fff' }};">
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $med['change'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $med['reason'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Follow-up Plan --}}
<div style="margin-bottom:18px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:1px solid #bfdbfe;padding-bottom:4px;margin-bottom:8px;">
        Specialist Follow-Up Plan
    </h3>
    <p style="font-size:13px;margin:0;">{{ $followUp }}</p>
</div>

{{-- Shared Care Recommendations --}}
@if(count($sharedCare) > 0)
<div style="margin-bottom:18px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;padding:12px 16px;">
    <h3 style="font-size:12px;font-weight:700;color:#0c4a6e;text-transform:uppercase;margin-bottom:8px;">
        Shared Care — Recommendations for Referring Clinician
    </h3>
    <ul style="margin:0;padding-left:18px;">
        @foreach($sharedCare as $rec)
        <li style="font-size:12px;margin-bottom:5px;">{{ $rec }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Urgent Concerns --}}
@if($urgentConcerns)
<div style="border:2px solid #f59e0b;background:#fffbeb;border-radius:6px;padding:12px 16px;margin-bottom:18px;">
    <p style="font-size:12px;font-weight:700;color:#92400e;text-transform:uppercase;margin:0 0 6px;">
        &#9888; Urgent Concerns
    </p>
    <p style="font-size:13px;margin:0;color:#92400e;">{{ $urgentConcerns }}</p>
</div>
@endif

{{-- Closing --}}
<p style="font-size:13px;line-height:1.6;margin:0 0 6px;">{{ $thankYou }}</p>
<p style="font-size:13px;line-height:1.6;margin:0 0 20px;">Please do not hesitate to contact us should you require any further information.</p>

{{-- Signature block --}}
<div style="border-top:2px solid #bfdbfe;padding-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
        <p style="font-size:12px;font-weight:700;margin:0;">{{ $specialist }}</p>
        <p style="font-size:12px;color:#374151;margin:2px 0;">{{ $specialty }}</p>
        <p style="font-size:12px;color:#374151;margin:2px 0;">{{ $facility_name ?? '' }}</p>
        <p style="font-size:11px;color:#6b7280;margin:10px 0 0;">Signature: _______________________________</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px;margin:0;color:#374151;"><strong>Date:</strong> {{ $issued_at ?? '—' }}</p>
        <p style="font-size:12px;margin:4px 0 0;color:#374151;"><strong>Document No.:</strong> {{ $document_number ?? '—' }}</p>
    </div>
</div>
@endsection
