@extends('documents.base')

@section('title')
    Investigation Request Form
@endsection

@section('subtitle')
    Clinical Investigation Request — REQ | {{ $payload['request_number'] ?? '' }}
@endsection

@section('content')
<style>
    .req-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #F0F9FF;
        border: 1px solid #BAE6FD;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .req-type-badge {
        font-size: 12px;
        font-weight: 800;
        color: #0369A1;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .urgency-pill {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        padding: 1.5mm 3mm;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .urg-routine { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; }
    .urg-urgent  { background: #FFFBEB; color: #92400E; border: 1px solid #FCD34D; }
    .urg-stat    { background: #DC2626; color: #fff; }
    .req-meta-right { text-align: right; font-size: 9.5px; color: #475569; }
    .req-meta-right strong { color: #0F172A; display: block; }

    .req-doctor-strip {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .rds-item { font-size: 10px; }
    .rds-label { font-size: 8px; color: #64748B; text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px; }
    .rds-value { font-weight: 700; color: #0F172A; margin-top: 0.5mm; }

    .indication-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-left: 4px solid #0369A1;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .indication-label { font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #0369A1; letter-spacing: 0.5px; margin-bottom: 1mm; }
    .indication-text  { font-size: 10.5px; color: #334155; line-height: 1.6; }

    .tests-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .tests-table th {
        background: #0369A1;
        color: #fff;
        padding: 2.5mm 3mm;
        border: 1px solid #0369A1;
        text-align: left;
        font-size: 8.5px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .tests-table td { padding: 2.5mm 3mm; border: 1px solid #E2E8F0; vertical-align: top; }
    .tests-table tr:nth-child(even) td { background: #F0F9FF; }
    .test-name { font-weight: 700; color: #0F172A; }
    .test-code { font-size: 8.5px; color: #64748B; font-style: italic; }
    .special-inst { font-size: 8.5px; color: #7C3AED; }

    .req-flags-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .req-flag-box {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
    }
    .req-flag-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; margin-bottom: 1mm; }
    .req-flag-yes { background: #FEF2F2; border-color: #FCA5A5; }
    .req-flag-yes .req-flag-label { color: #7F1D1D; }
    .req-flag-no  { background: #ECFDF5; border-color: #6EE7B7; }
    .req-flag-value { font-size: 11px; font-weight: 800; }
    .flag-red  { color: #DC2626; }
    .flag-green { color: #059669; }

    .meds-list { font-size: 9.5px; color: #334155; line-height: 1.8; }

    .specimen-box {
        border: 1.5px dashed #CBD5E1;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .specimen-title { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; margin-bottom: 2mm; }
    .specimen-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3mm; }
    .specimen-item-label { font-size: 8px; color: #94A3B8; text-transform: uppercase; font-weight: 600; }
    .specimen-item-value { font-size: 10px; font-weight: 600; color: #0F172A; margin-top: 0.5mm; }
    .specimen-condition-ok      { color: #059669; }
    .specimen-condition-warn    { color: #D97706; }
    .specimen-condition-problem { color: #DC2626; }

    .req-sig-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
        margin-top: 5mm;
        margin-bottom: 5mm;
    }
    .req-sig-box { border-top: 1px solid #94A3B8; padding-top: 2mm; }
    .req-sig-label { font-size: 8px; text-transform: uppercase; color: #94A3B8; margin-bottom: 5mm; }
    .req-sig-name  { font-weight: 700; color: #0F172A; font-size: 10px; }
    .req-sig-reg   { font-size: 8.5px; color: #64748B; }

    .receipt-stamp {
        border: 2px dashed #CBD5E1;
        border-radius: 6px;
        padding: 4mm;
        text-align: center;
        margin-top: 4mm;
    }
    .receipt-title { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.5px; margin-bottom: 2mm; }
    .receipt-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3mm; }
    .receipt-field {
        border: 1px solid #E2E8F0;
        border-radius: 4px;
        padding: 2mm;
        min-height: 8mm;
        background: #fff;
    }
    .receipt-field-label { font-size: 7.5px; color: #94A3B8; text-transform: uppercase; }
</style>

{{-- TOP BAR --}}
@php
    $urgency = $payload['urgency'] ?? 'Routine';
    $urgClass = match($urgency) {
        'STAT (Critical)' => 'urg-stat',
        'Urgent'          => 'urg-urgent',
        default           => 'urg-routine',
    };
@endphp
<div class="req-top-bar">
    <div>
        <div class="req-type-badge">{{ $payload['request_type'] ?? '—' }}</div>
        <div style="margin-top:1mm;">
            <span class="urgency-pill {{ $urgClass }}">{{ $urgency }}</span>
        </div>
    </div>
    <div class="req-meta-right">
        <strong>{{ $payload['request_number'] ?? '—' }}</strong>
        {{ $payload['request_date'] ?? '—' }} at {{ $payload['request_time'] ?? '—' }}
        <div style="margin-top:0.5mm;">Ward / Clinic: <strong style="display:inline;">{{ $payload['ward_or_clinic'] ?? '—' }}</strong></div>
    </div>
</div>

{{-- DOCTOR STRIP --}}
<div class="req-doctor-strip">
    <div class="rds-item">
        <div class="rds-label">Requesting Doctor</div>
        <div class="rds-value">{{ $payload['requesting_doctor'] ?? '—' }}</div>
        <div style="font-size:8.5px; color:#64748B;">Reg: {{ $payload['requesting_doctor_reg'] ?? '—' }}</div>
    </div>
    <div class="rds-item">
        <div class="rds-label">Contact Number</div>
        <div class="rds-value">{{ $payload['contact_number'] ?? '—' }}</div>
    </div>
</div>

{{-- CLINICAL INDICATION --}}
<div class="indication-box">
    <div class="indication-label">Clinical Indication</div>
    <div class="indication-text">{{ $payload['clinical_indication'] ?? '—' }}</div>
</div>
@if(!empty($payload['relevant_history']))
<div class="indication-box" style="border-left-color:#7C3AED; background:#FAF5FF; border-color:#E9D5FF; margin-bottom:5mm;">
    <div class="indication-label" style="color:#7C3AED;">Relevant History</div>
    <div class="indication-text">{{ $payload['relevant_history'] }}</div>
</div>
@endif

{{-- REQUESTED TESTS --}}
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header" style="background:#0369A1; color:#fff;">Requested Tests</div>
    <div class="card-body" style="padding:0;">
        <table class="tests-table">
            <thead>
                <tr>
                    <th style="width:35%;">Test Name</th>
                    <th style="width:12%;">Code</th>
                    <th style="width:18%;">Specimen Type</th>
                    <th>Special Instructions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payload['requested_tests'] ?? [] as $test)
                <tr>
                    <td><span class="test-name">{{ $test['test_name'] ?? '—' }}</span></td>
                    <td><span class="test-code">{{ $test['code'] ?? '—' }}</span></td>
                    <td>{{ $test['specimen_type'] ?? '—' }}</td>
                    <td><span class="special-inst">{{ $test['special_instructions'] ?? '—' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#94A3B8; font-style:italic; padding:3mm;">No tests specified.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- FLAGS GRID --}}
<div class="req-flags-grid">
    @php
        $fast = (bool)($payload['fasting_required'] ?? false);
        $contrast = $payload['contrast_required'] ?? null;
        $contrastAllergy = $payload['contrast_allergy'] ?? null;
    @endphp
    <div class="req-flag-box {{ $fast ? 'req-flag-yes' : 'req-flag-no' }}">
        <div class="req-flag-label">Fasting Required</div>
        <div class="req-flag-value {{ $fast ? 'flag-red' : 'flag-green' }}">{{ $fast ? 'YES' : 'NO' }}</div>
        @if($fast && !empty($payload['fasting_duration_hours']))
        <div style="font-size:8.5px; color:#64748B; margin-top:0.5mm;">Duration: {{ $payload['fasting_duration_hours'] }}h</div>
        @endif
    </div>
    @if($contrast !== null)
    <div class="req-flag-box {{ $contrast ? 'req-flag-yes' : 'req-flag-no' }}">
        <div class="req-flag-label">Contrast Required</div>
        <div class="req-flag-value {{ $contrast ? 'flag-red' : 'flag-green' }}">{{ $contrast ? 'YES' : 'NO' }}</div>
    </div>
    @endif
    @if(!empty($payload['pregnancy_status']))
    <div class="req-flag-box">
        <div class="req-flag-label">Pregnancy Status</div>
        <div style="font-size:9.5px; font-weight:700; color:#0F172A; margin-top:1mm;">{{ $payload['pregnancy_status'] }}</div>
    </div>
    @endif
</div>

@if(!empty($payload['previous_relevant_results']))
<div class="indication-box" style="border-left-color:#059669; background:#F0FDF4; border-color:#BBF7D0; margin-bottom:5mm;">
    <div class="indication-label" style="color:#065F46;">Previous Relevant Results</div>
    <div class="indication-text">{{ $payload['previous_relevant_results'] }}</div>
</div>
@endif

{{-- CURRENT MEDICATIONS --}}
@php $meds = $payload['current_medications'] ?? []; @endphp
@if(!empty($meds))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Current Medications (Relevant to this Test)</div>
    <div class="card-body">
        <div class="meds-list">
            @foreach($meds as $med)
            <span style="display:inline-block; background:#F1F5F9; border:1px solid #E2E8F0; border-radius:3px; padding:0.5mm 2mm; margin:0.5mm; font-size:9px; font-weight:600;">{{ $med }}</span>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- SPECIMEN COLLECTION --}}
<div class="specimen-box">
    <div class="specimen-title">Specimen Collection (Completed by Phlebotomist / Radiographer)</div>
    <div class="specimen-grid">
        <div>
            <div class="specimen-item-label">Collected By</div>
            <div class="specimen-item-value">{{ $payload['specimen_collected_by'] ?? '—' }}</div>
        </div>
        <div>
            <div class="specimen-item-label">Collection Time</div>
            <div class="specimen-item-value">{{ $payload['specimen_collection_time'] ?? '—' }}</div>
        </div>
        <div>
            @php
                $cond = $payload['specimen_condition'] ?? null;
                $condClass = match($cond) {
                    'Satisfactory'  => 'specimen-condition-ok',
                    'Haemolysed'    => 'specimen-condition-warn',
                    'Insufficient'  => 'specimen-condition-problem',
                    default         => '',
                };
            @endphp
            <div class="specimen-item-label">Condition</div>
            <div class="specimen-item-value {{ $condClass }}">{{ $cond ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- REQUESTING DOCTOR SIGNATURE --}}
<div class="req-sig-row">
    <div class="req-sig-box">
        <div class="req-sig-label">Requesting Doctor Signature</div>
        <div class="req-sig-name">{{ $payload['requesting_doctor'] ?? $issuer_name }}</div>
        <div class="req-sig-reg">Reg No: {{ $payload['requesting_doctor_reg'] ?? '—' }}</div>
    </div>
    @if(!empty($payload['copy_to']))
    <div class="req-sig-box">
        <div class="req-sig-label">Copy To</div>
        @foreach($payload['copy_to'] as $copyName)
        <div class="req-sig-name" style="font-size:9.5px; margin-bottom:1mm;">{{ $copyName }}</div>
        @endforeach
    </div>
    @endif
</div>

{{-- LAB RECEIPT STAMP AREA --}}
<div class="receipt-stamp">
    <div class="receipt-title">Laboratory / Radiology Receipt Stamp</div>
    <div class="receipt-grid">
        <div class="receipt-field">
            <div class="receipt-field-label">Received By</div>
        </div>
        <div class="receipt-field">
            <div class="receipt-field-label">Received Date &amp; Time</div>
        </div>
        <div class="receipt-field">
            <div class="receipt-field-label">Lab Accession No.</div>
        </div>
    </div>
</div>
@endsection
