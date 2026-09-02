@extends('documents.base')

@section('title')
    Surgical Safety Checklist
@endsection

@section('subtitle')
    WHO Surgical Safety Checklist — SSC | {{ $payload['procedure_date'] ?? '' }}
@endsection

@section('content')
<style>
    .ssc-procedure-banner {
        background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
        border: 1px solid #86EFAC;
        border-left: 5px solid #059669;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ssc-proc-name { font-size: 14px; font-weight: 800; color: #0F172A; }
    .ssc-proc-meta { font-size: 9.5px; color: #475569; margin-top: 1mm; }
    .ssc-theatre-badge {
        background: #059669;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2mm 4mm;
        border-radius: 4px;
        text-align: center;
    }

    .team-strip {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .team-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 2.5mm 3mm;
    }
    .team-box-role { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; }
    .team-box-name { font-size: 11px; font-weight: 700; color: #0F172A; margin-top: 0.5mm; }

    .phase-block {
        border-radius: 6px;
        margin-bottom: 6mm;
        overflow: hidden;
        border: 2px solid;
    }
    .phase-sign-in  { border-color: #3B82F6; }
    .phase-time-out { border-color: #D97706; }
    .phase-sign-out { border-color: #059669; }

    .phase-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 3mm 4mm;
        color: #fff;
    }
    .phase-header-sign-in  { background: #3B82F6; }
    .phase-header-time-out { background: #D97706; }
    .phase-header-sign-out { background: #059669; }
    .phase-number { font-size: 11px; font-weight: 900; opacity: 0.6; }
    .phase-title  { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
    .phase-subtitle { font-size: 9px; opacity: 0.85; margin-top: 0.5mm; }
    .phase-time { font-size: 9px; font-weight: 600; opacity: 0.9; }

    .checklist-body { padding: 4mm; background: #fff; }

    .check-item {
        display: flex;
        align-items: flex-start;
        gap: 3mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 10.5px;
    }
    .check-item:last-child { border-bottom: none; }
    .check-box {
        width: 5mm;
        height: 5mm;
        border-radius: 2px;
        border: 1.5px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 900;
        flex-shrink: 0;
        margin-top: 0.5mm;
    }
    .check-yes  { background: #ECFDF5; border-color: #059669; color: #059669; }
    .check-no   { background: #FEF2F2; border-color: #DC2626; color: #DC2626; }
    .check-na   { background: #F8FAFC; border-color: #CBD5E1; color: #CBD5E1; }
    .check-text { color: #0F172A; line-height: 1.5; }
    .check-detail { font-size: 9px; color: #64748B; margin-top: 0.5mm; }

    .checklist-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 6mm;
    }

    .phase-sig-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #F8FAFC;
        border-top: 1px solid #E2E8F0;
        padding: 2.5mm 4mm;
        font-size: 9.5px;
    }
    .phase-sig-label { color: #64748B; font-size: 8px; text-transform: uppercase; }
    .phase-sig-name  { font-weight: 700; color: #0F172A; }

    .deviations-box {
        background: #FFFBEB;
        border: 1.5px solid #FCD34D;
        border-left: 5px solid #DC2626;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .deviations-title { font-size: 10px; font-weight: 800; color: #DC2626; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2mm; }
    .deviation-item { font-size: 10px; color: #78350F; padding: 1mm 0; border-bottom: 1px solid #FEF3C7; }
    .deviation-item:last-child { border-bottom: none; }

    .sig-footer {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 6mm;
        margin-top: 5mm;
    }
    .sig-col { border-top: 1px solid #94A3B8; padding-top: 2mm; text-align: center; }
    .sig-col-label { font-size: 8px; text-transform: uppercase; color: #94A3B8; margin-bottom: 5mm; }
    .sig-col-name  { font-weight: 700; font-size: 10px; color: #0F172A; }
    .sig-col-role  { font-size: 8.5px; color: #64748B; }
</style>

{{-- PROCEDURE BANNER --}}
<div class="ssc-procedure-banner">
    <div>
        <div class="ssc-proc-name">{{ $payload['procedure_name'] ?? '—' }}</div>
        <div class="ssc-proc-meta">Date: {{ $payload['procedure_date'] ?? '—' }}</div>
    </div>
    <div class="ssc-theatre-badge">{{ $payload['theatre'] ?? '—' }}</div>
</div>

{{-- TEAM STRIP --}}
<div class="team-strip">
    <div class="team-box">
        <div class="team-box-role">Surgeon</div>
        <div class="team-box-name">{{ $payload['surgeon'] ?? '—' }}</div>
    </div>
    <div class="team-box">
        <div class="team-box-role">Anaesthetist</div>
        <div class="team-box-name">{{ $payload['anaesthetist'] ?? '—' }}</div>
    </div>
    <div class="team-box">
        <div class="team-box-role">Scrub Nurse</div>
        <div class="team-box-name">{{ $payload['scrub_nurse'] ?? '—' }}</div>
    </div>
</div>

@php
    $si = $payload['sign_in']  ?? [];
    $to = $payload['time_out'] ?? [];
    $so = $payload['sign_out'] ?? [];

    function sscBox(bool $val): string {
        return $val ? '<span class="check-box check-yes">&#10003;</span>' : '<span class="check-box check-no">&#10007;</span>';
    }
@endphp

{{-- PHASE 1: SIGN-IN --}}
<div class="phase-block phase-sign-in">
    <div class="phase-header phase-header-sign-in">
        <div>
            <div class="phase-number">PHASE 1 OF 3</div>
            <div class="phase-title">Sign-In</div>
            <div class="phase-subtitle">Before induction of anaesthesia — performed by anaesthetist</div>
        </div>
        <div class="phase-time">{{ $si['time'] ?? '—' }}</div>
    </div>
    <div class="checklist-body">
        <div class="checklist-grid">
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($si['patient_confirmed_identity'] ?? false)) !!}
                    <div class="check-text">Patient confirmed identity, site &amp; procedure</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['site_confirmed'] ?? false)) !!}
                    <div class="check-text">Surgical site confirmed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['consent_confirmed'] ?? false)) !!}
                    <div class="check-text">Consent confirmed and signed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['site_marked'] ?? false)) !!}
                    <div class="check-text">Surgical site marked (if applicable)</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['anaesthesia_machine_checked'] ?? false)) !!}
                    <div class="check-text">Anaesthesia machine &amp; medication check complete</div>
                </div>
            </div>
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($si['pulse_oximeter_functioning'] ?? false)) !!}
                    <div class="check-text">Pulse oximeter on patient &amp; functioning</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['known_allergies'] ?? false)) !!}
                    <div class="check-text">Known allergies reviewed
                        @if(!empty($si['allergy_detail']))
                        <div class="check-detail">&#8594; {{ $si['allergy_detail'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['difficult_airway_risk'] ?? false)) !!}
                    <div class="check-text">Difficult airway / aspiration risk assessed
                        @if(!empty($si['airway_plan']))
                        <div class="check-detail">Plan: {{ $si['airway_plan'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['blood_loss_risk'] ?? false)) !!}
                    <div class="check-text">Risk of &gt;500 ml blood loss (7 ml/kg in children) anticipated</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($si['iv_access_adequate'] ?? false)) !!}
                    <div class="check-text">IV access &amp; fluids adequate</div>
                </div>
            </div>
        </div>
    </div>
    <div class="phase-sig-row">
        <div><div class="phase-sig-label">Completed By</div><div class="phase-sig-name">{{ $si['completed_by'] ?? '—' }}</div></div>
        <div><div class="phase-sig-label">Completed At</div><div class="phase-sig-name">{{ $si['completed_at'] ?? '—' }}</div></div>
    </div>
</div>

{{-- PHASE 2: TIME-OUT --}}
<div class="phase-block phase-time-out">
    <div class="phase-header phase-header-time-out">
        <div>
            <div class="phase-number">PHASE 2 OF 3</div>
            <div class="phase-title">Time-Out</div>
            <div class="phase-subtitle">Before skin incision — entire team confirms together</div>
        </div>
        <div class="phase-time">{{ $to['time'] ?? '—' }}</div>
    </div>
    <div class="checklist-body">
        <div class="checklist-grid">
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($to['team_introduced'] ?? false)) !!}
                    <div class="check-text">All team members introduced by name and role</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($to['patient_name_confirmed'] ?? false)) !!}
                    <div class="check-text">Patient name confirmed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($to['procedure_confirmed'] ?? false)) !!}
                    <div class="check-text">Procedure confirmed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($to['site_confirmed'] ?? false)) !!}
                    <div class="check-text">Operative site confirmed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($to['imaging_displayed'] ?? false)) !!}
                    <div class="check-text">Relevant imaging displayed</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($to['antibiotic_given'] ?? false)) !!}
                    <div class="check-text">Antibiotic prophylaxis given within 60 min
                        @if(!empty($to['antibiotic_time']))
                        <div class="check-detail">Given at: {{ $to['antibiotic_time'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($to['equipment_sterility_confirmed'] ?? false)) !!}
                    <div class="check-text">Equipment sterility confirmed (including indicator results)</div>
                </div>
                <div class="check-item check-item">
                    <div style="font-size:9px; color:#64748B; font-weight:600; text-transform:uppercase;">Critical Steps Discussed:</div>
                </div>
                <div style="padding:2mm; background:#FFFBEB; border-radius:4px; font-size:9.5px; color:#78350F; margin-bottom:2mm;">{{ $to['critical_steps_discussed'] ?? '—' }}</div>
                <div class="check-item">
                    <div>
                        <div class="check-text"><strong>Duration Estimate:</strong> {{ $to['duration_estimate'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="check-item">
                    <div>
                        <div class="check-text"><strong>Anticipated Blood Loss:</strong> {{ $to['anticipated_blood_loss'] ?? '—' }}</div>
                    </div>
                </div>
                @if(!empty($to['special_concerns']))
                <div class="check-item">
                    <div>
                        <div class="check-text"><strong>Special Concerns:</strong></div>
                        <div class="check-detail" style="color:#DC2626;">{{ $to['special_concerns'] }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="phase-sig-row">
        <div><div class="phase-sig-label">Completed By</div><div class="phase-sig-name">{{ $to['completed_by'] ?? '—' }}</div></div>
        <div><div class="phase-sig-label">Completed At</div><div class="phase-sig-name">{{ $to['completed_at'] ?? '—' }}</div></div>
    </div>
</div>

{{-- PHASE 3: SIGN-OUT --}}
<div class="phase-block phase-sign-out">
    <div class="phase-header phase-header-sign-out">
        <div>
            <div class="phase-number">PHASE 3 OF 3</div>
            <div class="phase-title">Sign-Out</div>
            <div class="phase-subtitle">Before patient leaves operating theatre</div>
        </div>
        <div class="phase-time">{{ $so['time'] ?? '—' }}</div>
    </div>
    <div class="checklist-body">
        <div class="checklist-grid">
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($so['procedure_name_confirmed'] ?? false)) !!}
                    <div class="check-text">Procedure name recorded</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($so['instrument_count_correct'] ?? false)) !!}
                    <div class="check-text">Instrument count correct</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($so['swab_count_correct'] ?? false)) !!}
                    <div class="check-text">Swab count correct</div>
                </div>
                <div class="check-item">
                    {!! sscBox((bool)($so['needle_count_correct'] ?? false)) !!}
                    <div class="check-text">Needle count correct</div>
                </div>
            </div>
            <div>
                <div class="check-item">
                    {!! sscBox((bool)($so['specimen_labeled'] ?? false)) !!}
                    <div class="check-text">Specimen(s) labeled correctly
                        @if(!empty($so['specimen_details']))
                        <div class="check-detail">{{ $so['specimen_details'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="check-item">
                    {!! sscBox(!(bool)($so['equipment_problems'] ?? false)) !!}
                    <div class="check-text">No equipment problems to address
                        @if(!empty($so['equipment_details']))
                        <div class="check-detail" style="color:#DC2626;">{{ $so['equipment_details'] }}</div>
                        @endif
                    </div>
                </div>
                @if(!empty($so['recovery_concerns']))
                <div class="check-item">
                    <span class="check-box check-no">!</span>
                    <div class="check-text">Recovery concerns noted:
                        <div class="check-detail" style="color:#DC2626;">{{ $so['recovery_concerns'] }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="phase-sig-row">
        <div><div class="phase-sig-label">Completed By</div><div class="phase-sig-name">{{ $so['completed_by'] ?? '—' }}</div></div>
        <div><div class="phase-sig-label">Completed At</div><div class="phase-sig-name">{{ $so['completed_at'] ?? '—' }}</div></div>
    </div>
</div>

{{-- DEVIATIONS --}}
@php $devs = $payload['deviations'] ?? []; @endphp
@if(!empty($devs))
<div class="deviations-box">
    <div class="deviations-title">&#9888; Deviations / Non-Conformances Noted</div>
    @foreach($devs as $dev)
    <div class="deviation-item">&#8594; {{ $dev }}</div>
    @endforeach
</div>
@endif

{{-- FINAL SIGNATURES --}}
<div class="sig-footer">
    <div class="sig-col">
        <div class="sig-col-label">Surgeon Signature</div>
        <div class="sig-col-name">{{ $payload['surgeon'] ?? '—' }}</div>
        <div class="sig-col-role">Lead Surgeon</div>
    </div>
    <div class="sig-col">
        <div class="sig-col-label">Anaesthetist Signature</div>
        <div class="sig-col-name">{{ $payload['anaesthetist'] ?? '—' }}</div>
        <div class="sig-col-role">Anaesthetist</div>
    </div>
    <div class="sig-col">
        <div class="sig-col-label">Scrub Nurse Signature</div>
        <div class="sig-col-name">{{ $payload['scrub_nurse'] ?? '—' }}</div>
        <div class="sig-col-role">Scrub Nurse</div>
    </div>
</div>
@endsection
