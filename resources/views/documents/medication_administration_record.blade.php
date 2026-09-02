@extends('documents.base')

@section('title')
    Medication Administration Record
@endsection

@section('subtitle')
    Bedside Drug Administration Chart — MAR | {{ $payload['chart_date'] ?? '' }}
@endsection

@section('content')
<style>
    .mar-allergy-strip {
        background-color: #FEE2E2;
        border: 2px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        gap: 3mm;
    }
    .mar-allergy-strip.no-allergy {
        background-color: #ECFDF5;
        border-color: #059669;
    }
    .mar-allergy-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #7F1D1D;
        flex-shrink: 0;
    }
    .mar-allergy-label.no-allergy { color: #065F46; }
    .mar-allergy-pill {
        display: inline-block;
        background: #DC2626;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 1mm 2mm;
        border-radius: 4px;
        margin-right: 2mm;
    }

    .mar-patient-strip {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 3mm;
        background: #F0F9FF;
        border: 1px solid #BAE6FD;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .mar-ps-item { font-size: 10px; }
    .mar-ps-label { color: #64748B; font-weight: 500; font-size: 8.5px; text-transform: uppercase; }
    .mar-ps-value { font-weight: 700; color: #0F172A; margin-top: 0.5mm; }

    .section-heading {
        background: #0F4C81;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 4mm;
        border-radius: 4px 4px 0 0;
        margin-top: 5mm;
    }

    .mar-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .mar-table th {
        background: #F8FAFC;
        color: #475569;
        font-weight: 600;
        text-align: center;
        padding: 2mm;
        border: 1px solid #E2E8F0;
        font-size: 8.5px;
        text-transform: uppercase;
    }
    .mar-table th.left { text-align: left; }
    .mar-table td {
        padding: 2mm;
        border: 1px solid #E2E8F0;
        vertical-align: top;
        text-align: center;
    }
    .mar-table td.left { text-align: left; }
    .mar-table tr.high-alert { background-color: #FFF7ED; }
    .mar-table tr.controlled { border-left: 3px solid #D97706; }
    .mar-table tr.high-alert.controlled { background-color: #FEF3C7; border-left: 3px solid #DC2626; }

    .dose-given   { color: #059669; font-weight: 800; font-size: 11px; }
    .dose-held    { color: #D97706; font-weight: 800; font-size: 11px; }
    .dose-refused { color: #DC2626; font-weight: 800; font-size: 11px; }
    .dose-prn     { color: #7C3AED; font-weight: 700; font-size: 9px; }
    .dose-na      { color: #CBD5E1; font-size: 10px; }

    .badge-high-alert {
        display: inline-block;
        background: #DC2626;
        color: #fff;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
        text-transform: uppercase;
    }
    .badge-controlled {
        display: inline-block;
        background: #D97706;
        color: #fff;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
    }
    .drug-name { font-weight: 700; color: #0F172A; font-size: 10px; }
    .drug-meta { font-size: 8.5px; color: #64748B; margin-top: 0.5mm; }
    .drug-instruction { font-size: 8px; color: #9333EA; font-style: italic; margin-top: 0.5mm; }

    .prn-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        margin-bottom: 3mm;
        overflow: hidden;
    }
    .prn-card-header {
        background: #F8FAFC;
        padding: 2mm 3mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #E2E8F0;
    }
    .prn-card-drug { font-weight: 700; font-size: 10.5px; color: #0F172A; }
    .prn-card-meta { font-size: 8.5px; color: #64748B; }
    .prn-card-body { padding: 2mm 3mm; }
    .prn-admin-row {
        display: grid;
        grid-template-columns: 20mm 1fr 30mm 30mm;
        gap: 2mm;
        font-size: 9px;
        padding: 1.5mm 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .prn-admin-row:last-child { border-bottom: none; }
    .prn-col-head { font-weight: 600; color: #64748B; text-transform: uppercase; font-size: 8px; }

    .iv-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .iv-table th {
        background: #F8FAFC; color: #475569; font-weight: 600;
        padding: 2mm; border: 1px solid #E2E8F0; text-align: left;
        font-size: 8.5px; text-transform: uppercase;
    }
    .iv-table td { padding: 2mm; border: 1px solid #E2E8F0; vertical-align: top; }

    .summary-strip {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 3mm;
        margin-top: 5mm;
    }
    .summary-box {
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
        border: 1px solid;
    }
    .summary-box-label { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.75; }
    .summary-box-value { font-size: 18px; font-weight: 900; margin-top: 1mm; }
    .box-total   { background: #F8FAFC; border-color: #CBD5E1; color: #0F172A; }
    .box-given   { background: #ECFDF5; border-color: #6EE7B7; color: #065F46; }
    .box-held    { background: #FFFBEB; border-color: #FCD34D; color: #92400E; }
    .box-refused { background: #FEF2F2; border-color: #FCA5A5; color: #7F1D1D; }

    .dual-sig {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
        margin-top: 5mm;
    }
    .sig-box {
        border-top: 1px solid #94A3B8;
        padding-top: 2mm;
        text-align: center;
        font-size: 9.5px;
        color: #475569;
    }
    .sig-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8; margin-bottom: 6mm; }
    .sig-name { font-weight: 600; color: #0F172A; }
</style>

{{-- ALLERGY STRIP --}}
@php $allergies = $payload['allergies'] ?? []; @endphp
<div class="mar-allergy-strip {{ empty($allergies) ? 'no-allergy' : '' }}">
    @if(!empty($allergies))
        <div class="mar-allergy-label">&#9888; ALLERGIES:</div>
        <div>
            @foreach($allergies as $a)
                <span class="mar-allergy-pill">{{ $a['allergen'] ?? '' }}</span>
                <span style="font-size:9px; color:#7F1D1D; margin-right:4mm;">
                    {{ $a['reaction'] ?? '' }}
                    @if(!empty($a['severity'])) — <strong>{{ $a['severity'] }}</strong>@endif
                </span>
            @endforeach
        </div>
    @else
        <div class="mar-allergy-label no-allergy">&#10003; NO KNOWN ALLERGIES</div>
    @endif
</div>

{{-- PATIENT STRIP --}}
<div class="mar-patient-strip">
    <div class="mar-ps-item">
        <div class="mar-ps-label">Ward</div>
        <div class="mar-ps-value">{{ $payload['ward'] ?? '—' }}</div>
    </div>
    <div class="mar-ps-item">
        <div class="mar-ps-label">Bed</div>
        <div class="mar-ps-value">{{ $payload['bed_number'] ?? '—' }}</div>
    </div>
    <div class="mar-ps-item">
        <div class="mar-ps-label">Weight (kg)</div>
        <div class="mar-ps-value">{{ $payload['weight_kg'] ?? '—' }}</div>
    </div>
    <div class="mar-ps-item">
        <div class="mar-ps-label">Admitting Diagnosis</div>
        <div class="mar-ps-value" style="font-size:9px;">{{ $payload['admitting_diagnosis'] ?? '—' }}</div>
    </div>
</div>

{{-- SCHEDULED MEDICATIONS --}}
<div class="section-heading">Scheduled Medications</div>
<div style="border:1px solid #E2E8F0; border-top:none; border-radius:0 0 4px 4px; overflow:hidden; margin-bottom:5mm;">
    @php
        $scheduled = $payload['scheduled_medications'] ?? [];
        $allTimes = ['06:00','08:00','12:00','14:00','18:00','22:00'];
    @endphp
    <table class="mar-table">
        <thead>
            <tr>
                <th class="left" style="width:32%;">Drug / Dose / Route</th>
                <th class="left" style="width:12%;">Freq / Indication</th>
                @foreach($allTimes as $t)
                    <th>{{ $t }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($scheduled as $med)
            @php
                $rowClass = '';
                if(($med['high_alert'] ?? false) && ($med['controlled'] ?? false)) $rowClass = 'high-alert controlled';
                elseif($med['high_alert'] ?? false) $rowClass = 'high-alert';
                elseif($med['controlled'] ?? false) $rowClass = 'controlled';
                $timeMap = [];
                foreach($med['times'] ?? [] as $te) {
                    $timeMap[$te['scheduled_time'] ?? ''] = $te;
                }
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="left">
                    <div class="drug-name">{{ $med['drug_name'] ?? '—' }}</div>
                    <div class="drug-meta">{{ $med['dose'] ?? '' }} | {{ $med['route'] ?? '' }}</div>
                    @if($med['high_alert'] ?? false)
                        <span class="badge-high-alert">High Alert</span>
                    @endif
                    @if($med['controlled'] ?? false)
                        <span class="badge-controlled">Controlled</span>
                    @endif
                    @if(!empty($med['special_instructions']))
                        <div class="drug-instruction">{{ $med['special_instructions'] }}</div>
                    @endif
                </td>
                <td class="left">
                    <div style="font-size:9px; font-weight:600;">{{ $med['frequency'] ?? '—' }}</div>
                    <div style="font-size:8.5px; color:#64748B;">{{ $med['indication'] ?? '' }}</div>
                </td>
                @foreach($allTimes as $t)
                @php $te = $timeMap[$t] ?? null; $st = $te['status'] ?? 'Not Due'; @endphp
                <td>
                    @if($te)
                        @if($st === 'Given')
                            <div class="dose-given">&#10003;</div>
                            <div style="font-size:7.5px; color:#059669;">{{ $te['given_time'] ?? '' }}</div>
                            <div style="font-size:7.5px; color:#64748B;">{{ $te['given_by_initials'] ?? '' }}</div>
                        @elseif($st === 'Held')
                            <div class="dose-held">H</div>
                        @elseif($st === 'Refused')
                            <div class="dose-refused">R</div>
                        @elseif($st === 'PRN-Given')
                            <div class="dose-prn">PRN</div>
                        @else
                            <div class="dose-na">—</div>
                        @endif
                    @else
                        <div class="dose-na">—</div>
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#94A3B8; font-style:italic; padding:4mm;">No scheduled medications.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- PRN MEDICATIONS --}}
@php $prns = $payload['prn_medications'] ?? []; @endphp
@if(!empty($prns))
<div class="section-heading">PRN (As-Required) Medications</div>
<div style="border:1px solid #E2E8F0; border-top:none; border-radius:0 0 4px 4px; padding:3mm; margin-bottom:5mm;">
    @foreach($prns as $prn)
    <div class="prn-card">
        <div class="prn-card-header">
            <div>
                <div class="prn-card-drug">{{ $prn['drug_name'] ?? '—' }} — {{ $prn['dose'] ?? '' }} {{ $prn['route'] ?? '' }}</div>
                <div class="prn-card-meta">Indication: {{ $prn['indication'] ?? '—' }} | Max Frequency: {{ $prn['max_frequency'] ?? '—' }}</div>
            </div>
        </div>
        @if(!empty($prn['administrations']))
        <div class="prn-card-body">
            <div class="prn-admin-row">
                <div class="prn-col-head">Time</div>
                <div class="prn-col-head">Reason</div>
                <div class="prn-col-head">Given By</div>
                <div class="prn-col-head">Outcome</div>
            </div>
            @foreach($prn['administrations'] as $adm)
            <div class="prn-admin-row">
                <div>{{ $adm['time'] ?? '—' }}</div>
                <div>{{ $adm['reason'] ?? '—' }}</div>
                <div>{{ $adm['given_by'] ?? '—' }}</div>
                <div>{{ $adm['outcome'] ?? '—' }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="prn-card-body" style="color:#94A3B8; font-style:italic; font-size:9px;">No administrations recorded.</div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ONCE-ONLY MEDICATIONS --}}
@php $once = $payload['once_only_medications'] ?? []; @endphp
@if(!empty($once))
<div class="section-heading">Once-Only Medications</div>
<div style="border:1px solid #E2E8F0; border-top:none; border-radius:0 0 4px 4px; margin-bottom:5mm; overflow:hidden;">
    <table class="mar-table">
        <thead>
            <tr>
                <th class="left">Drug</th>
                <th class="left">Dose / Route</th>
                <th>Time Ordered</th>
                <th>Time Given</th>
                <th class="left">Given By</th>
                <th class="left">Indication</th>
            </tr>
        </thead>
        <tbody>
            @foreach($once as $o)
            <tr>
                <td class="left"><span class="drug-name">{{ $o['drug_name'] ?? '—' }}</span></td>
                <td class="left">{{ $o['dose'] ?? '' }} {{ $o['route'] ?? '' }}</td>
                <td>{{ $o['time_ordered'] ?? '—' }}</td>
                <td>{{ $o['time_given'] ?? '—' }}</td>
                <td class="left">{{ $o['given_by'] ?? '—' }}</td>
                <td class="left" style="font-size:9px;">{{ $o['indication'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- IV INFUSIONS --}}
@php $ivs = $payload['iv_infusions'] ?? []; @endphp
@if(!empty($ivs))
<div class="section-heading">IV Infusions</div>
<div style="border:1px solid #E2E8F0; border-top:none; border-radius:0 0 4px 4px; margin-bottom:5mm; overflow:hidden;">
    <table class="iv-table">
        <thead>
            <tr>
                <th>Fluid + Additives</th>
                <th>Volume (ml)</th>
                <th>Rate (ml/hr)</th>
                <th>Start &#8594; End</th>
                <th>Cannula Site</th>
                <th>Bag #</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ivs as $iv)
            <tr>
                <td>
                    <div style="font-weight:700;">{{ $iv['fluid'] ?? '—' }}</div>
                    @if(!empty($iv['additives']))
                    <div style="font-size:8.5px; color:#64748B;">+ {{ $iv['additives'] }}</div>
                    @endif
                </td>
                <td style="text-align:center;">{{ $iv['volume_ml'] ?? '—' }}</td>
                <td style="text-align:center;">{{ $iv['rate_ml_hr'] ?? '—' }}</td>
                <td style="text-align:center;">{{ $iv['start_time'] ?? '—' }} &#8594; {{ $iv['end_time'] ?? '—' }}</td>
                <td>{{ $iv['cannula_site'] ?? '—' }}</td>
                <td style="text-align:center;">{{ $iv['bag_number'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- SUMMARY STRIP --}}
<div class="summary-strip">
    <div class="summary-box box-total">
        <div class="summary-box-label">Total Doses Scheduled</div>
        <div class="summary-box-value">{{ $payload['total_scheduled_doses'] ?? '0' }}</div>
    </div>
    <div class="summary-box box-given">
        <div class="summary-box-label">Doses Given</div>
        <div class="summary-box-value">{{ $payload['doses_given'] ?? '0' }}</div>
    </div>
    <div class="summary-box box-held">
        <div class="summary-box-label">Doses Held</div>
        <div class="summary-box-value">{{ $payload['doses_held'] ?? '0' }}</div>
    </div>
    <div class="summary-box box-refused">
        <div class="summary-box-label">Doses Refused</div>
        <div class="summary-box-value">{{ $payload['doses_refused'] ?? '0' }}</div>
    </div>
</div>

{{-- DUAL SIGNATURE --}}
<div class="dual-sig">
    <div class="sig-box">
        <div class="sig-label">Day Nurse Signature</div>
        <div class="sig-name">{{ $payload['nurse_day'] ?? '—' }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-label">Night Nurse Signature</div>
        <div class="sig-name">{{ $payload['nurse_night'] ?? '—' }}</div>
    </div>
</div>
@endsection
