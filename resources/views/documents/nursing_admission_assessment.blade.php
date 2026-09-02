@extends('documents.base')

@section('title')
    Nursing Admission Assessment
@endsection

@section('subtitle')
    Nursing Structured Assessment on Admission — NAA | {{ $payload['assessment_date'] ?? '' }}
@endsection

@section('content')
<style>
    .naa-top-banner {
        background: linear-gradient(135deg, #F0FDFA 0%, #CCFBF1 100%);
        border: 1px solid #5EEAD4;
        border-left: 5px solid #0F766E;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .naa-banner-left { }
    .naa-banner-title { font-size: 13px; font-weight: 800; color: #0F766E; }
    .naa-banner-meta  { font-size: 9.5px; color: #475569; margin-top: 0.5mm; }
    .arrival-badge {
        background: #0F766E;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 1.5mm 3mm;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .side-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }
    .naa-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
    }
    .naa-card-header {
        background: #0F766E;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 3mm;
    }
    .naa-card-body { padding: 3mm; }
    .naa-row { display: flex; justify-content: space-between; font-size: 9.5px; padding: 1mm 0; border-bottom: 1px solid #F1F5F9; }
    .naa-row:last-child { border-bottom: none; }
    .naa-key { color: #64748B; font-weight: 500; }
    .naa-val { font-weight: 700; color: #0F172A; }

    .bool-yes { color: #059669; font-weight: 800; }
    .bool-no  { color: #DC2626; font-weight: 800; }
    .bool-yes::before { content: '✓ '; }
    .bool-no::before  { content: '✗ '; }

    .adl-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .adl-item {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 2.5mm;
        text-align: center;
    }
    .adl-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; margin-bottom: 1mm; }
    .adl-badge {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        padding: 1mm 2mm;
        border-radius: 4px;
        text-transform: capitalize;
    }
    .adl-independent { background: #ECFDF5; color: #065F46; }
    .adl-assisted    { background: #FFFBEB; color: #92400E; }
    .adl-dependent   { background: #FEF2F2; color: #7F1D1D; }

    .scored-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .scored-table th {
        background: #F8FAFC;
        color: #475569;
        font-weight: 600;
        padding: 2mm 3mm;
        border: 1px solid #E2E8F0;
        text-align: left;
        font-size: 8.5px;
        text-transform: uppercase;
    }
    .scored-table td { padding: 2mm 3mm; border: 1px solid #E2E8F0; }
    .score-col { text-align: center; font-weight: 800; font-size: 11px; color: #0F172A; }

    .risk-badge {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        padding: 1.5mm 3mm;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .risk-low      { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; }
    .risk-medium   { background: #FFFBEB; color: #92400E; border: 1px solid #FCD34D; }
    .risk-high     { background: #FEF2F2; color: #7F1D1D; border: 1px solid #FCA5A5; }
    .risk-none     { background: #F8FAFC; color: #475569; border: 1px solid #CBD5E1; }
    .risk-mild     { background: #F0F9FF; color: #0369A1; border: 1px solid #BAE6FD; }
    .risk-moderate { background: #FFFBEB; color: #92400E; border: 1px solid #FCD34D; }
    .risk-very-high { background: #7F1D1D; color: #fff; }

    .score-total-row td { background: #F8FAFC; font-weight: 700; }

    .pain-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        margin-bottom: 5mm;
        overflow: hidden;
    }

    .vas-bar-wrap { margin: 2mm 0; }
    .vas-bar-bg {
        background: linear-gradient(to right, #059669, #EAB308, #DC2626);
        height: 4mm;
        border-radius: 2px;
        position: relative;
    }
    .vas-label-row { display: flex; justify-content: space-between; font-size: 8px; color: #64748B; margin-bottom: 0.5mm; }

    .nutrition-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .nutrition-item {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 2.5mm;
        text-align: center;
    }
    .nutrition-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; margin-bottom: 1mm; }
    .nutrition-value { font-size: 11px; font-weight: 700; color: #0F172A; }

    .psychosocial-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .ps-item {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 4px;
        padding: 2.5mm;
    }
    .ps-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 0.5mm; letter-spacing: 0.3px; }
    .ps-value { font-size: 10px; color: #0F172A; }

    .safety-checklist {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .safety-item {
        border: 1px solid;
        border-radius: 6px;
        padding: 2.5mm;
        text-align: center;
    }
    .safety-done   { background: #ECFDF5; border-color: #6EE7B7; color: #065F46; }
    .safety-undone { background: #FEF2F2; border-color: #FCA5A5; color: #7F1D1D; }
    .safety-icon { font-size: 14px; font-weight: 900; }
    .safety-text { font-size: 8.5px; font-weight: 600; margin-top: 0.5mm; }

    .education-pill {
        display: inline-block;
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 12px;
        padding: 0.5mm 2mm;
        font-size: 9px;
        font-weight: 600;
        color: #1E40AF;
        margin: 0.5mm;
    }

    .naa-sig-box { border-top: 1px solid #94A3B8; padding-top: 2mm; margin-top: 5mm; }
    .naa-sig-label { font-size: 8px; text-transform: uppercase; color: #94A3B8; margin-bottom: 5mm; }
    .naa-sig-name  { font-weight: 700; color: #0F172A; font-size: 10px; }
</style>

{{-- TOP BANNER --}}
<div class="naa-top-banner">
    <div class="naa-banner-left">
        <div class="naa-banner-title">Nursing Admission Assessment</div>
        <div class="naa-banner-meta">
            Date: <strong>{{ $payload['assessment_date'] ?? '—' }}</strong> at <strong>{{ $payload['assessment_time'] ?? '—' }}</strong>
            &nbsp;|&nbsp; Nurse: <strong>{{ $payload['admitting_nurse'] ?? '—' }}</strong>
        </div>
        <div class="naa-banner-meta">
            Accompanied by: {{ $payload['accompanied_by'] ?? '—' }}
        </div>
    </div>
    <div class="arrival-badge">{{ $payload['mode_of_arrival'] ?? '—' }}</div>
</div>

{{-- COMMUNICATION & ORIENTATION --}}
<div class="side-cards">
    @php $comm = $payload['communication'] ?? []; @endphp
    <div class="naa-card">
        <div class="naa-card-header">Communication</div>
        <div class="naa-card-body">
            <div class="naa-row"><span class="naa-key">Language</span><span class="naa-val">{{ $comm['language'] ?? '—' }}</span></div>
            <div class="naa-row"><span class="naa-key">Hearing</span><span class="naa-val">{{ $comm['hearing'] ?? '—' }}</span></div>
            <div class="naa-row"><span class="naa-key">Vision</span><span class="naa-val">{{ $comm['vision'] ?? '—' }}</span></div>
            <div class="naa-row"><span class="naa-key">Speech</span><span class="naa-val">{{ $comm['speech'] ?? '—' }}</span></div>
            <div class="naa-row">
                <span class="naa-key">Interpreter Needed</span>
                <span class="{{ ($comm['interpreter_needed'] ?? false) ? 'bool-yes' : 'bool-no' }}">
                    {{ ($comm['interpreter_needed'] ?? false) ? 'YES' : 'No' }}
                </span>
            </div>
        </div>
    </div>
    @php $ori = $payload['orientation'] ?? []; @endphp
    <div class="naa-card">
        <div class="naa-card-header">Orientation</div>
        <div class="naa-card-body">
            <div class="naa-row">
                <span class="naa-key">To Person</span>
                <span class="{{ ($ori['person'] ?? false) ? 'bool-yes' : 'bool-no' }}">{{ ($ori['person'] ?? false) ? 'Oriented' : 'Disoriented' }}</span>
            </div>
            <div class="naa-row">
                <span class="naa-key">To Place</span>
                <span class="{{ ($ori['place'] ?? false) ? 'bool-yes' : 'bool-no' }}">{{ ($ori['place'] ?? false) ? 'Oriented' : 'Disoriented' }}</span>
            </div>
            <div class="naa-row">
                <span class="naa-key">To Time</span>
                <span class="{{ ($ori['time'] ?? false) ? 'bool-yes' : 'bool-no' }}">{{ ($ori['time'] ?? false) ? 'Oriented' : 'Disoriented' }}</span>
            </div>
            <div class="naa-row">
                <span class="naa-key">Confused</span>
                <span class="{{ ($ori['confused'] ?? false) ? 'bool-no' : 'bool-yes' }}">{{ ($ori['confused'] ?? false) ? 'YES' : 'No' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ACTIVITIES OF DAILY LIVING --}}
@php
    $adl = $payload['activities_of_daily_living'] ?? [];
    $adlItems = [
        'bathing'    => 'Bathing',
        'dressing'   => 'Dressing',
        'toileting'  => 'Toileting',
        'mobility'   => 'Mobility',
        'feeding'    => 'Feeding',
        'continence' => 'Continence',
    ];
@endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Activities of Daily Living</div>
    <div class="card-body">
        <div class="adl-grid">
            @foreach($adlItems as $key => $label)
            @php
                $val = $adl[$key] ?? '—';
                $adlClass = 'adl-independent';
                if (stripos($val, 'assist') !== false || stripos($val, 'partial') !== false) $adlClass = 'adl-assisted';
                elseif (stripos($val, 'dependent') !== false || stripos($val, 'total') !== false) $adlClass = 'adl-dependent';
            @endphp
            <div class="adl-item">
                <div class="adl-label">{{ $label }}</div>
                <span class="adl-badge {{ $adlClass }}">{{ $val }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- FALL RISK — MORSE --}}
@php
    $morse = $payload['fall_risk_morse'] ?? [];
    $morseRisk = $morse['risk_level'] ?? 'Low (<25)';
    $morseRiskClass = 'risk-low';
    if (stripos($morseRisk, 'medium') !== false || stripos($morseRisk, '25') !== false) $morseRiskClass = 'risk-medium';
    elseif (stripos($morseRisk, 'high') !== false || stripos($morseRisk, '45') !== false) $morseRiskClass = 'risk-high';
@endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Fall Risk Assessment — Morse Fall Scale</div>
    <div class="card-body" style="padding:0;">
        <table class="scored-table">
            <thead>
                <tr>
                    <th style="width:50%;">Risk Factor</th>
                    <th>Value</th>
                    <th style="width:15%;">Score</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>History of Falls (within 3 months)</td>
                    <td><span class="{{ ($morse['history_of_falls'] ?? false) ? 'bool-no' : 'bool-yes' }}">{{ ($morse['history_of_falls'] ?? false) ? 'Yes' : 'No' }}</span></td>
                    <td class="score-col">{{ ($morse['history_of_falls'] ?? false) ? 25 : 0 }}</td>
                </tr>
                <tr>
                    <td>Secondary Diagnosis</td>
                    <td><span class="{{ ($morse['secondary_diagnosis'] ?? false) ? 'bool-no' : 'bool-yes' }}">{{ ($morse['secondary_diagnosis'] ?? false) ? 'Yes' : 'No' }}</span></td>
                    <td class="score-col">{{ ($morse['secondary_diagnosis'] ?? false) ? 15 : 0 }}</td>
                </tr>
                <tr>
                    <td>Ambulatory Aid</td>
                    <td>{{ $morse['ambulatory_aid'] ?? '—' }}</td>
                    <td class="score-col">
                        @php
                            $aid = $morse['ambulatory_aid'] ?? '';
                            echo match($aid) {
                                'Crutches/Cane/Walker' => 15,
                                'Furniture'            => 30,
                                default                => 0,
                            };
                        @endphp
                    </td>
                </tr>
                <tr>
                    <td>IV / Heparin Lock</td>
                    <td><span class="{{ ($morse['iv_access'] ?? false) ? 'bool-no' : 'bool-yes' }}">{{ ($morse['iv_access'] ?? false) ? 'Yes' : 'No' }}</span></td>
                    <td class="score-col">{{ ($morse['iv_access'] ?? false) ? 20 : 0 }}</td>
                </tr>
                <tr>
                    <td>Gait</td>
                    <td>{{ $morse['gait'] ?? '—' }}</td>
                    <td class="score-col">
                        @php
                            $gait = $morse['gait'] ?? '';
                            echo match($gait) {
                                'Weak'     => 10,
                                'Impaired' => 20,
                                default    => 0,
                            };
                        @endphp
                    </td>
                </tr>
                <tr>
                    <td>Mental Status</td>
                    <td>{{ $morse['mental_status'] ?? '—' }}</td>
                    <td class="score-col">{{ ($morse['mental_status'] ?? '') === 'Forgets limitations' ? 15 : 0 }}</td>
                </tr>
                <tr class="score-total-row">
                    <td colspan="2" style="text-align:right; font-weight:700;">Total Morse Score</td>
                    <td class="score-col" style="font-size:14px; color:#0F766E;">{{ $morse['total_score'] ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        <div style="padding:3mm; background:#F8FAFC; border-top:1px solid #E2E8F0; display:flex; align-items:center; gap:3mm;">
            <span style="font-size:9px; font-weight:600; color:#475569;">Risk Level:</span>
            <span class="risk-badge {{ $morseRiskClass }}">{{ $morseRisk }}</span>
        </div>
    </div>
</div>

{{-- PRESSURE ULCER RISK — BRADEN --}}
@php
    $braden = $payload['pressure_ulcer_braden'] ?? [];
    $bradenRisk = $braden['risk_level'] ?? 'No risk (19-23)';
    $bradenClass = 'risk-none';
    if (stripos($bradenRisk, 'mild') !== false)      $bradenClass = 'risk-mild';
    elseif (stripos($bradenRisk, 'moderate') !== false) $bradenClass = 'risk-moderate';
    elseif (stripos($bradenRisk, 'very high') !== false) $bradenClass = 'risk-very-high';
    elseif (stripos($bradenRisk, 'high') !== false)  $bradenClass = 'risk-high';
@endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Pressure Ulcer Risk — Braden Scale</div>
    <div class="card-body" style="padding:0;">
        <table class="scored-table">
            <thead>
                <tr>
                    <th style="width:40%;">Domain</th>
                    <th style="width:15%; text-align:center;">Score (1–4)</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['sensory' => 'Sensory Perception', 'moisture' => 'Moisture', 'activity' => 'Activity', 'mobility' => 'Mobility', 'nutrition' => 'Nutrition', 'friction' => 'Friction &amp; Shear'] as $key => $label)
                <tr>
                    <td>{!! $label !!}</td>
                    <td class="score-col">{{ $braden[$key] ?? '—' }}</td>
                </tr>
                @endforeach
                <tr class="score-total-row">
                    <td style="text-align:right; font-weight:700;">Total Braden Score</td>
                    <td class="score-col" style="font-size:14px; color:#0F766E;">{{ $braden['total_score'] ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        <div style="padding:3mm; background:#F8FAFC; border-top:1px solid #E2E8F0; display:flex; align-items:center; gap:3mm;">
            <span style="font-size:9px; font-weight:600; color:#475569;">Risk Level:</span>
            <span class="risk-badge {{ $bradenClass }}">{{ $bradenRisk }}</span>
        </div>
    </div>
</div>

{{-- SKIN ASSESSMENT --}}
@php $skin = $payload['skin_assessment'] ?? []; @endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Skin Assessment</div>
    <div class="card-body">
        @php
            $integ = $skin['integrity'] ?? 'Intact';
            $integBadge = $integ === 'Intact' ? 'risk-low' : 'risk-high';
        @endphp
        <div style="display:flex; align-items:center; gap:3mm; margin-bottom:2mm;">
            <span style="font-size:9px; font-weight:600; color:#475569;">Skin Integrity:</span>
            <span class="risk-badge {{ $integBadge }}">{{ $integ }}</span>
        </div>
        @if(!empty($skin['existing_wounds']))
        <div style="margin-bottom:1.5mm; font-size:9px; font-weight:600; color:#475569; text-transform:uppercase;">Existing Wounds:</div>
        @foreach($skin['existing_wounds'] as $wound)
        <div style="font-size:9.5px; color:#334155; padding:1mm 0; border-bottom:1px solid #F1F5F9;">&#8594; {{ $wound }}</div>
        @endforeach
        @endif
        @if(!empty($skin['pressure_areas']))
        <div style="margin-top:1.5mm; font-size:9.5px; color:#334155;"><strong>Pressure Areas:</strong> {{ $skin['pressure_areas'] }}</div>
        @endif
    </div>
</div>

{{-- PAIN ASSESSMENT --}}
@php $pain = $payload['pain_assessment'] ?? []; @endphp
<div class="pain-card">
    <div class="naa-card-header" style="background:#0F766E;">Pain Assessment</div>
    <div class="card-body" style="padding:3mm;">
        @if($pain['present'] ?? false)
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:3mm; margin-bottom:3mm;">
            <div><div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Location</div><div style="font-weight:700;">{{ $pain['location'] ?? '—' }}</div></div>
            <div><div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Character</div><div style="font-weight:700;">{{ $pain['character'] ?? '—' }}</div></div>
            <div><div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">VAS Score</div><div style="font-weight:800; font-size:14px; color:#DC2626;">{{ $pain['score_vas'] ?? '—' }} / 10</div></div>
        </div>
        @if($pain['score_vas'] !== null)
        <div class="vas-bar-wrap">
            <div class="vas-label-row"><span>0 — No Pain</span><span>10 — Worst Pain</span></div>
            <div class="vas-bar-bg"></div>
        </div>
        @endif
        @if(!empty($pain['interventions']))
        <div style="margin-top:2mm; font-size:9.5px; color:#334155;"><strong>Interventions:</strong> {{ $pain['interventions'] }}</div>
        @endif
        @else
        <div style="color:#059669; font-weight:700; font-size:10.5px;">&#10003; No pain reported on admission</div>
        @endif
    </div>
</div>

{{-- NUTRITIONAL SCREEN --}}
@php
    $nutri = $payload['nutritional_screen'] ?? [];
    $nutriRisk = $nutri['nutrition_risk'] ?? 'Low';
    $nutriRiskClass = match($nutriRisk) { 'High' => 'risk-high', 'Medium' => 'risk-medium', default => 'risk-low' };
@endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Nutritional Screen</div>
    <div class="card-body">
        <div class="nutrition-grid">
            <div class="nutrition-item">
                <div class="nutrition-label">BMI</div>
                <div class="nutrition-value">{{ $nutri['bmi'] ?? '—' }}</div>
            </div>
            <div class="nutrition-item {{ ($nutri['recent_weight_loss'] ?? false) ? 'risk-high' : '' }}" style="{{ ($nutri['recent_weight_loss'] ?? false) ? 'border-color:#FCA5A5;' : '' }}">
                <div class="nutrition-label">Recent Weight Loss</div>
                <div class="nutrition-value {{ ($nutri['recent_weight_loss'] ?? false) ? 'flag-red' : 'flag-green' }}">{{ ($nutri['recent_weight_loss'] ?? false) ? 'YES' : 'No' }}</div>
            </div>
            <div class="nutrition-item {{ ($nutri['poor_appetite'] ?? false) ? 'risk-high' : '' }}" style="{{ ($nutri['poor_appetite'] ?? false) ? 'border-color:#FCA5A5;' : '' }}">
                <div class="nutrition-label">Poor Appetite</div>
                <div class="nutrition-value {{ ($nutri['poor_appetite'] ?? false) ? 'flag-red' : 'flag-green' }}">{{ ($nutri['poor_appetite'] ?? false) ? 'YES' : 'No' }}</div>
            </div>
            <div class="nutrition-item">
                <div class="nutrition-label">Nutrition Risk</div>
                <div style="margin-top:1mm;"><span class="risk-badge {{ $nutriRiskClass }}">{{ $nutriRisk }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- PSYCHOSOCIAL --}}
@php $ps = $payload['psychosocial'] ?? []; @endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Psychosocial Assessment</div>
    <div class="card-body">
        <div class="psychosocial-grid">
            <div class="ps-item"><div class="ps-label">Emotional State</div><div class="ps-value">{{ $ps['emotional_state'] ?? '—' }}</div></div>
            <div class="ps-item"><div class="ps-label">Anxiety Level</div><div class="ps-value">{{ $ps['anxiety_level'] ?? '—' }}</div></div>
            <div class="ps-item"><div class="ps-label">Support System</div><div class="ps-value">{{ $ps['support_system'] ?? '—' }}</div></div>
            <div class="ps-item"><div class="ps-label">Advance Directive</div><div class="ps-value">{{ $ps['advance_directive'] ?? '—' }}</div></div>
            @if(!empty($ps['cultural_needs']))
            <div class="ps-item"><div class="ps-label">Cultural Needs</div><div class="ps-value">{{ $ps['cultural_needs'] }}</div></div>
            @endif
            @if(!empty($ps['religious_needs']))
            <div class="ps-item"><div class="ps-label">Religious Needs</div><div class="ps-value">{{ $ps['religious_needs'] }}</div></div>
            @endif
        </div>
    </div>
</div>

{{-- SAFETY EQUIPMENT --}}
@php $saf = $payload['safety_equipment'] ?? []; @endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Safety Equipment Checklist</div>
    <div class="card-body">
        <div class="safety-checklist">
            @php
                $safetyItems = [
                    'call_bell_explained'  => 'Call Bell Explained',
                    'bed_rails_raised'     => 'Bed Rails Raised',
                    'bed_lowest_position'  => 'Bed Lowest Position',
                    'non_slip_footwear'    => 'Non-Slip Footwear',
                ];
            @endphp
            @foreach($safetyItems as $key => $label)
            @php $done = (bool)($saf[$key] ?? false); @endphp
            <div class="safety-item {{ $done ? 'safety-done' : 'safety-undone' }}">
                <div class="safety-icon">{{ $done ? '✓' : '✗' }}</div>
                <div class="safety-text">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- PATIENT EDUCATION --}}
@php $edu = $payload['patient_education'] ?? []; @endphp
<div class="content-card" style="margin-bottom:5mm;">
    <div class="naa-card-header" style="background:#0F766E;">Patient Education</div>
    <div class="card-body">
        @if(!empty($edu['education_given']))
        <div style="margin-bottom:2mm;">
            @foreach($edu['education_given'] as $topic)
            <span class="education-pill">{{ $topic }}</span>
            @endforeach
        </div>
        @endif
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3mm; font-size:9.5px;">
            @if(!empty($edu['learning_barriers']))
            <div><strong style="color:#64748B;">Learning Barriers:</strong> {{ $edu['learning_barriers'] }}</div>
            @endif
            <div><strong style="color:#64748B;">Teaching Method:</strong> {{ $edu['method'] ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- VALUABLES --}}
@php $val = $payload['valuables'] ?? []; @endphp
<div style="display:flex; align-items:center; gap:4mm; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; padding:2.5mm 4mm; margin-bottom:5mm; font-size:9.5px;">
    <span style="font-weight:700; color:#475569;">Valuables Deposited:</span>
    <span class="{{ ($val['deposited'] ?? false) ? 'bool-yes' : 'bool-no' }}">{{ ($val['deposited'] ?? false) ? 'YES' : 'No' }}</span>
    @if(!empty($val['description']))
    <span style="color:#334155;">| {{ $val['description'] }}</span>
    @endif
</div>

{{-- NURSE SIGNATURE --}}
<div class="naa-sig-box">
    <div class="naa-sig-label">Admitting Nurse Signature</div>
    <div class="naa-sig-name">{{ $payload['signature'] ?? $payload['admitting_nurse'] ?? $issuer_name }}</div>
</div>
@endsection
