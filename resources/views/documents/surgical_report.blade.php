@extends('documents.base')

@section('title', 'OPERATIVE / SURGICAL REPORT')
@section('subtitle', 'Compte-Rendu Opératoire — OpesCare Surgical Suite')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:6mm;margin-bottom:6mm;">
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Procedure Details</div>
        <div class="card-body">
            <div class="meta-row"><span class="meta-label">Procedure:</span><span class="meta-value">{{ $procedure_name ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">ICD Code:</span><span class="meta-value">{{ $icd_procedure_code ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Date:</span><span class="meta-value">{{ $operation_date ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Start:</span><span class="meta-value">{{ $operation_start ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">End:</span><span class="meta-value">{{ $operation_end ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Duration:</span><span class="meta-value">{{ $duration_minutes ?? 'N/A' }} min</span></div>
            <div class="meta-row"><span class="meta-label">Theatre:</span><span class="meta-value">{{ $theatre ?? 'N/A' }}</span></div>
        </div>
    </div>
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Theatre Team</div>
        <div class="card-body">
            <div class="meta-row"><span class="meta-label">Surgeon:</span><span class="meta-value">{{ $surgeon ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Assistant:</span><span class="meta-value">{{ $assistant ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Anaesthetist:</span><span class="meta-value">{{ $anaesthetist ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Anaesthesia:</span><span class="meta-value">{{ $anaesthesia_type ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Scrub Nurse:</span><span class="meta-value">{{ $scrub_nurse ?? 'N/A' }}</span></div>
            <div class="meta-row"><span class="meta-label">Circ. Nurse:</span><span class="meta-value">{{ $circulating_nurse ?? 'N/A' }}</span></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:6mm;margin-bottom:6mm;">
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Pre-op Diagnosis</div>
        <div class="card-body" style="font-size:10.5px;">{{ $pre_op_diagnosis ?? 'N/A' }}</div>
    </div>
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Post-op Diagnosis</div>
        <div class="card-body" style="font-size:10.5px;font-weight:600;color:#0F172A;">{{ $post_op_diagnosis ?? 'N/A' }}</div>
    </div>
</div>

@if(!empty($findings))
<div class="content-card">
    <div class="card-header">Intraoperative Findings</div>
    <div class="card-body">
        @foreach($findings as $f)
        <div style="display:flex;align-items:flex-start;gap:8px;padding:2px 0;font-size:10.5px;">
            <span style="color:#0F4C81;flex-shrink:0;margin-top:1px;">▸</span> {{ $f }}
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="content-card">
    <div class="card-header">Operative Technique / Déroulement Opératoire</div>
    <div class="card-body" style="font-size:10.5px;line-height:1.7;color:#1E293B;">
        {{ $procedure_details ?? 'Not documented.' }}
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6mm;margin-bottom:6mm;">
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Blood Loss</div>
        <div class="card-body" style="font-size:11px;font-weight:600;">{{ $estimated_blood_loss ?? 'N/A' }}</div>
    </div>
    @if(!empty($specimens))
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Specimens</div>
        <div class="card-body">
            @foreach($specimens as $s)
            <div style="font-size:10px;padding:1px 0;">🧪 {{ $s }}</div>
            @endforeach
        </div>
    </div>
    @endif
    @if(!empty($drains))
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Drains</div>
        <div class="card-body">
            @foreach($drains as $d)
            <div style="font-size:10px;padding:1px 0;">💧 {{ $d }}</div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@if(!empty($post_op_instructions))
<div class="content-card">
    <div class="card-header">Post-operative Instructions</div>
    <div class="card-body">
        @foreach($post_op_instructions as $i => $inst)
        <div style="display:flex;gap:8px;padding:2px 0;font-size:10.5px;">
            <span style="color:#0F4C81;font-weight:700;flex-shrink:0;">{{ $i+1 }}.</span> {{ $inst }}
        </div>
        @endforeach
    </div>
</div>
@endif

@if(!empty($complications))
<div style="background:{{ $complications === 'None intraoperatively' ? '#F0FDF4' : '#FEF2F2' }};border:1px solid {{ $complications === 'None intraoperatively' ? '#BBF7D0' : '#FECACA' }};border-radius:6px;padding:4mm 5mm;margin-bottom:6mm;">
    <span style="font-weight:600;color:{{ $complications === 'None intraoperatively' ? '#059669' : '#B91C1C' }};font-size:11px;">Complications: </span>
    <span style="font-size:11px;">{{ $complications }}</span>
</div>
@endif
@endsection
