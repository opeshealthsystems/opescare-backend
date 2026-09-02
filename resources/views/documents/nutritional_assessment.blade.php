@extends('documents.base')

@section('title', 'NUTRITIONAL ASSESSMENT')
@section('subtitle', 'MUST Malnutrition Screening | Full Dietitian Assessment | NRS-2002')

@section('content')
@php
    $accentColor = '#059669';
    $accentLight = '#ECFDF5';
    $accentMid   = '#6EE7B7';

    $assessmentDate    = $payload['assessment_date']    ?? '—';
    $assessmentType    = $payload['assessment_type']    ?? 'Full Nutritional Assessment';
    $dietitian         = $payload['dietitian']          ?? '—';
    $dietitianReg      = $payload['dietitian_reg']      ?? '—';
    $referringPhysician = $payload['referring_physician'] ?? '—';

    $mustScore       = $payload['must_score']       ?? [];
    $anthropometrics = $payload['anthropometrics']  ?? [];
    $dietaryIntake   = $payload['dietary_intake']   ?? [];
    $biochemistry    = $payload['biochemistry']     ?? [];
    $energyReqs      = $payload['energy_requirements'] ?? [];
    $currentNutrition = $payload['current_nutrition'] ?? [];
    $nutritionDiagnosis = $payload['nutrition_diagnosis'] ?? '—';
    $interventions   = $payload['interventions']    ?? [];
    $supplementsPrescribed = $payload['supplement_prescribed'] ?? null;
    $monitoringPlan  = $payload['monitoring_plan']  ?? '—';
    $goals           = $payload['goals']            ?? [];
    $reviewDate      = $payload['review_date']      ?? '—';

    // MUST risk badge
    $mustRisk      = $mustScore['risk'] ?? 'Low (0)';
    $mustTotal     = (int)($mustScore['total'] ?? 0);
    $mustRiskClass = match(true) {
        str_contains($mustRisk, 'High')   => 'must-high',
        str_contains($mustRisk, 'Medium') => 'must-medium',
        default                            => 'must-low',
    };

    // Appetite badge
    $appetite      = $dietaryIntake['appetite'] ?? '—';
    $appetiteClass = match($appetite) {
        'Good'  => 'badge-green',
        'Fair'  => 'badge-amber',
        'Poor'  => 'badge-red',
        'None'  => 'badge-red',
        default => 'badge-gray',
    };

    // Energy deficit
    $targetKcal   = (int)($energyReqs['total_kcal_day'] ?? 0);
    $actualKcal   = (int)($currentNutrition['actual_kcal_achieved'] ?? 0);
    $deficitKcal  = (int)($currentNutrition['deficit_kcal'] ?? ($targetKcal - $actualKcal));
    $targetProtein = (int)($energyReqs['protein_g_day'] ?? 0);
    $actualProtein = (int)($currentNutrition['actual_protein_achieved'] ?? 0);

    $mustCriteria = [
        ['label' => 'BMI Score', 'score_key' => 'bmi_score', 'value_key' => 'bmi_value', 'value_suffix' => ' kg/m²',
         'desc0' => 'BMI > 20 (score 0)', 'desc1' => 'BMI 18.5–20 (score 1)', 'desc2' => 'BMI < 18.5 (score 2)'],
        ['label' => 'Weight Loss Score', 'score_key' => 'weight_loss_score', 'value_key' => 'weight_loss_pct', 'value_suffix' => '%',
         'desc0' => '< 5% loss (score 0)', 'desc1' => '5–10% loss (score 1)', 'desc2' => '> 10% loss (score 2)'],
        ['label' => 'Acute Disease Score', 'score_key' => 'acute_disease_score', 'value_key' => null, 'value_suffix' => '',
         'desc0' => 'No acute disease effect (score 0)', 'desc1' => null, 'desc2' => 'Acutely ill, no/very little intake > 5 days (score 2)'],
    ];
@endphp

<style>
    .ntr-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
    }
    .ntr-header-strip h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 4px 0;
    }
    .ntr-header-strip .ntr-sub { font-size: 9.5px; opacity: 0.8; }

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
    .badge-teal   { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    /* MUST Table */
    .must-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .must-table th {
        background: {{ $accentLight }};
        color: {{ $accentColor }};
        font-weight: 700;
        text-align: left;
        padding: 4px 8px;
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .must-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .must-table tr:last-child td { border-bottom: none; }
    .must-criterion { font-weight: 600; color: #374151; width: 30%; background: #FAFAFA; }
    .must-score-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 11px;
    }
    .score-0 { background: #D1FAE5; color: #065F46; }
    .score-1 { background: #FEF3C7; color: #92400E; }
    .score-2 { background: #FEE2E2; color: #991B1B; }

    .must-low    { background: #D1FAE5; color: #065F46; border: 2px solid #6EE7B7; }
    .must-medium { background: #FEF3C7; color: #92400E; border: 2px solid #FDE68A; }
    .must-high   { background: #FEE2E2; color: #991B1B; border: 2px solid #FCA5A5; }

    .must-total-row {
        border-radius: 4px;
        padding: 6px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 10px;
    }

    /* Anthropometrics */
    .anthro-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .anthro-item {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        padding: 6px 8px;
        text-align: center;
    }
    .anthro-label { font-size: 8.5px; color: #6B7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
    .anthro-value { font-size: 12px; font-weight: 700; color: #1F2937; }
    .anthro-unit  { font-size: 8px; color: #9CA3AF; }

    /* Energy comparison */
    .energy-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .energy-table th {
        background: #F9FAFB;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        color: #374151;
        padding: 4px 8px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }
    .energy-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .energy-table tr:last-child td { border-bottom: none; }
    .deficit-cell { color: #991B1B; font-weight: 700; }
    .met-cell     { color: #065F46; font-weight: 700; }

    /* Biochemistry */
    .bio-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .bio-table td {
        padding: 4px 8px;
        border-bottom: 1px solid #F3F4F6;
    }
    .bio-table tr:last-child td { border-bottom: none; }
    .bio-label { font-weight: 600; color: #374151; width: 45%; }

    .bullet-list { margin: 0; padding-left: 14px; }
    .bullet-list li { font-size: 10px; margin-bottom: 3px; color: #1F2937; }

    .goals-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .goals-table th {
        background: {{ $accentLight }};
        color: {{ $accentColor }};
        font-weight: 700;
        text-align: left;
        padding: 4px 8px;
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .goals-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
    }
    .goals-table tr:last-child td { border-bottom: none; }

    .supp-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .supp-table th {
        background: #F9FAFB;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        color: #374151;
        padding: 4px 8px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }
    .supp-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
    }
    .supp-table tr:last-child td { border-bottom: none; }

    .sig-line {
        border-top: 1px solid #9CA3AF;
        padding-top: 4px;
        margin-top: 12mm;
        font-size: 9px;
        color: #374151;
    }
</style>

{{-- ── HEADER STRIP ─────────────────────────────────────────────── --}}
<div class="ntr-header-strip">
    <h2>Nutritional Assessment</h2>
    <div class="ntr-sub">
        {{ $facility_name }} &nbsp;|&nbsp; {{ $assessmentDate }}
    </div>
    <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap;">
        <span class="badge badge-teal" style="font-size:10px; padding:3px 10px;">{{ $assessmentType }}</span>
        <span style="font-size:9px; opacity:0.75;">Ref: {{ $referringPhysician }}</span>
    </div>
</div>

{{-- ── MUST SCREENING ───────────────────────────────────────────── --}}
<div class="section-card" style="margin-top:8px; border-color:{{ $accentColor }};">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        MUST Malnutrition Universal Screening Tool
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="must-table">
            <thead>
                <tr>
                    <th style="width:30%;">Criterion</th>
                    <th>Description</th>
                    <th style="width:18%;">Measured Value</th>
                    <th style="width:12%; text-align:center;">Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mustCriteria as $crit)
                @php
                    $cScore = (int)($mustScore[$crit['score_key']] ?? 0);
                    $cValue = $crit['value_key'] ? ($mustScore[$crit['value_key']] ?? null) : null;
                    $descText = match($cScore) {
                        0 => $crit['desc0'],
                        2 => $crit['desc2'],
                        default => $crit['desc1'] ?? '—',
                    };
                @endphp
                <tr>
                    <td class="must-criterion">{{ $crit['label'] }}</td>
                    <td style="font-size:9.5px; color:#374151;">{{ $descText }}</td>
                    <td>
                        @if($cValue !== null)
                        <span style="font-weight:600;">{{ $cValue }}{{ $crit['value_suffix'] }}</span>
                        @else
                        <span style="color:#9CA3AF;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <span class="must-score-pill score-{{ $cScore }}">{{ $cScore }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="must-total-row {{ $mustRiskClass }}">
            <span style="font-size:9px; font-weight:700; text-transform:uppercase;">Total MUST Score:</span>
            <span style="font-size:18px; font-weight:800;">{{ $mustTotal }}</span>
            <span style="font-size:10px; font-weight:700; margin-left:6px;">Risk: {{ $mustRisk }}</span>
        </div>
    </div>
</div>

{{-- ── ANTHROPOMETRICS ──────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Anthropometrics</div>
    <div class="section-card-body">
        @php $anthro = is_array($anthropometrics) ? $anthropometrics : []; @endphp
        <div class="anthro-grid">
            @if(isset($anthro['weight_kg']))
            <div class="anthro-item">
                <div class="anthro-label">Weight</div>
                <div class="anthro-value">{{ $anthro['weight_kg'] }}</div>
                <div class="anthro-unit">kg</div>
            </div>
            @endif
            @if(isset($anthro['height_cm']))
            <div class="anthro-item">
                <div class="anthro-label">Height</div>
                <div class="anthro-value">{{ $anthro['height_cm'] }}</div>
                <div class="anthro-unit">cm</div>
            </div>
            @endif
            @if(isset($anthro['bmi']))
            <div class="anthro-item">
                <div class="anthro-label">BMI</div>
                <div class="anthro-value">{{ $anthro['bmi'] }}</div>
                <div class="anthro-unit">kg/m²</div>
            </div>
            @endif
            @if(isset($anthro['ideal_body_weight_kg']))
            <div class="anthro-item">
                <div class="anthro-label">Ideal Body Wt</div>
                <div class="anthro-value">{{ $anthro['ideal_body_weight_kg'] }}</div>
                <div class="anthro-unit">kg</div>
            </div>
            @endif
            @if(isset($anthro['usual_weight_kg']))
            <div class="anthro-item">
                <div class="anthro-label">Usual Weight</div>
                <div class="anthro-value">{{ $anthro['usual_weight_kg'] }}</div>
                <div class="anthro-unit">kg</div>
            </div>
            @endif
            @if(isset($anthro['weight_change_kg']))
            <div class="anthro-item">
                <div class="anthro-label">Weight Change</div>
                <div class="anthro-value" style="color:{{ $anthro['weight_change_kg'] < 0 ? '#991B1B' : '#065F46' }};">
                    {{ $anthro['weight_change_kg'] > 0 ? '+' : '' }}{{ $anthro['weight_change_kg'] }}
                </div>
                <div class="anthro-unit">kg{{ !empty($anthro['weight_change_timeframe']) ? ' / '.$anthro['weight_change_timeframe'] : '' }}</div>
            </div>
            @endif
            @if(isset($anthro['mid_arm_circumference_cm']))
            <div class="anthro-item">
                <div class="anthro-label">MUAC</div>
                <div class="anthro-value">{{ $anthro['mid_arm_circumference_cm'] }}</div>
                <div class="anthro-unit">cm</div>
            </div>
            @endif
            @if(isset($anthro['calf_circumference_cm']))
            <div class="anthro-item">
                <div class="anthro-label">Calf Circ.</div>
                <div class="anthro-value">{{ $anthro['calf_circumference_cm'] }}</div>
                <div class="anthro-unit">cm</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── DIETARY INTAKE ───────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Dietary Intake Assessment</div>
    <div class="section-card-body">
        @php $diet = is_array($dietaryIntake) ? $dietaryIntake : []; @endphp
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
            <span class="badge {{ $appetiteClass }}">Appetite: {{ $appetite }}</span>
            @if(!empty($diet['oral_intake_pct']))
            <span class="badge badge-gray">Oral intake: {{ $diet['oral_intake_pct'] }}</span>
            @endif
            @if(!empty($diet['dysphagia']) && $diet['dysphagia'])
            <span class="badge badge-amber">Dysphagia present</span>
            @endif
            @if(!empty($diet['nausea_vomiting']) && $diet['nausea_vomiting'])
            <span class="badge badge-amber">Nausea/vomiting</span>
            @endif
        </div>
        @if(!empty($diet['food_preferences']))
        <div style="font-size:9.5px; color:#374151; margin-bottom:3px;"><span style="font-weight:600;">Food preferences:</span> {{ $diet['food_preferences'] }}</div>
        @endif
        @if(!empty($diet['food_allergies']))
        <div style="font-size:9.5px; color:#991B1B; margin-bottom:3px;"><span style="font-weight:600;">Allergies:</span> {{ implode(', ', $diet['food_allergies']) }}</div>
        @endif
        @if(!empty($diet['religious_dietary']))
        <div style="font-size:9.5px; color:#374151; margin-bottom:3px;"><span style="font-weight:600;">Dietary/religious requirements:</span> {{ $diet['religious_dietary'] }}</div>
        @endif
        @if(!empty($diet['problems_affecting_intake']))
        <div style="margin-top:4px;">
            <span style="font-size:9px; font-weight:600; color:#374151; text-transform:uppercase;">Problems affecting intake:</span>
            <ul class="bullet-list" style="margin-top:2px;">
                @foreach($diet['problems_affecting_intake'] as $prob)
                <li>{{ $prob }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

{{-- ── BIOCHEMISTRY ─────────────────────────────────────────────── --}}
@php
    $bio = is_array($biochemistry) ? $biochemistry : [];
    $hasAnyBio = !empty($bio['albumin']) || !empty($bio['prealbumin']) || !empty($bio['haemoglobin']) || !empty($bio['total_lymphocyte_count']);
@endphp
@if($hasAnyBio)
<div class="section-card">
    <div class="section-card-title">Biochemistry Markers</div>
    <div class="section-card-body" style="padding:0;">
        <table class="bio-table">
            @if(!empty($bio['albumin']))
            <tr><td class="bio-label">Albumin</td><td>{{ $bio['albumin'] }}</td></tr>
            @endif
            @if(!empty($bio['prealbumin']))
            <tr><td class="bio-label">Pre-albumin</td><td>{{ $bio['prealbumin'] }}</td></tr>
            @endif
            @if(!empty($bio['haemoglobin']))
            <tr><td class="bio-label">Haemoglobin</td><td>{{ $bio['haemoglobin'] }}</td></tr>
            @endif
            @if(!empty($bio['total_lymphocyte_count']))
            <tr><td class="bio-label">Total Lymphocyte Count</td><td>{{ $bio['total_lymphocyte_count'] }}</td></tr>
            @endif
        </table>
    </div>
</div>
@endif

{{-- ── ENERGY/PROTEIN REQUIREMENTS vs CURRENT ──────────────────── --}}
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Energy, Protein &amp; Fluid Requirements vs Current Intake
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="energy-table">
            <thead>
                <tr>
                    <th>Nutrient</th>
                    <th style="text-align:right;">Target (Requirement)</th>
                    <th style="text-align:right;">Current Intake</th>
                    <th style="text-align:right;">Deficit / Status</th>
                    <th style="width:28%;">Method / Feed</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:600;">Energy</td>
                    <td style="text-align:right;">
                        @if($targetKcal)
                        <span style="font-weight:600;">{{ $targetKcal }}</span> kcal/day
                        @else
                        —
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($actualKcal)
                        {{ $actualKcal }} kcal/day
                        @else
                        <span style="color:#9CA3AF;">Not recorded</span>
                        @endif
                    </td>
                    <td style="text-align:right;" class="{{ $deficitKcal > 0 ? 'deficit-cell' : 'met-cell' }}">
                        @if($actualKcal && $targetKcal)
                            @if($deficitKcal > 0)
                            &#x2212;{{ $deficitKcal }} kcal
                            @else
                            Target met
                            @endif
                        @else
                        —
                        @endif
                    </td>
                    <td style="font-size:9px; color:#6B7280;">
                        {{ $energyReqs['method'] ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:600;">Protein</td>
                    <td style="text-align:right;">
                        @if($targetProtein)
                        <span style="font-weight:600;">{{ $targetProtein }}</span> g/day
                        @else
                        —
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($actualProtein)
                        {{ $actualProtein }} g/day
                        @else
                        <span style="color:#9CA3AF;">Not recorded</span>
                        @endif
                    </td>
                    <td style="text-align:right;" class="{{ ($targetProtein && $actualProtein && $actualProtein < $targetProtein) ? 'deficit-cell' : 'met-cell' }}">
                        @if($targetProtein && $actualProtein)
                            @if($actualProtein < $targetProtein)
                            &#x2212;{{ $targetProtein - $actualProtein }} g
                            @else
                            Target met
                            @endif
                        @else
                        —
                        @endif
                    </td>
                    <td style="font-size:9px; color:#6B7280;">
                        {{ $currentNutrition['route'] ?? '—' }}
                    </td>
                </tr>
                @if(!empty($energyReqs['fluid_ml_day']))
                <tr>
                    <td style="font-weight:600;">Fluid</td>
                    <td style="text-align:right;"><span style="font-weight:600;">{{ $energyReqs['fluid_ml_day'] }}</span> ml/day</td>
                    <td style="text-align:right;"><span style="color:#9CA3AF;">—</span></td>
                    <td style="text-align:right;"><span style="color:#9CA3AF;">—</span></td>
                    <td style="font-size:9px; color:#6B7280;">
                        @if(!empty($currentNutrition['feed_name'])){{ $currentNutrition['feed_name'] }}@endif
                        @if(!empty($currentNutrition['rate']))  @ {{ $currentNutrition['rate'] }}@endif
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── NUTRITION DIAGNOSIS ──────────────────────────────────────── --}}
<div class="section-card" style="border-color:{{ $accentColor }};">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Nutrition Diagnosis
    </div>
    <div class="section-card-body" style="font-weight:600; font-size:11px;">{{ $nutritionDiagnosis }}</div>
</div>

{{-- ── INTERVENTIONS + SUPPLEMENTS ─────────────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Interventions</div>
        <div class="section-card-body">
            @if(!empty($interventions))
            <ul class="bullet-list">
                @foreach($interventions as $intv)
                <li>{{ $intv }}</li>
                @endforeach
            </ul>
            @else
            <span style="color:#9CA3AF; font-style:italic;">None recorded</span>
            @endif
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Supplements Prescribed</div>
        <div class="section-card-body" style="padding:0;">
            @if(!empty($supplementsPrescribed))
            <table class="supp-table">
                <thead><tr><th>Supplement</th><th>Dose</th><th>Frequency</th></tr></thead>
                <tbody>
                    @foreach($supplementsPrescribed as $supp)
                    <tr>
                        <td style="font-weight:600;">{{ $supp['name'] ?? '—' }}</td>
                        <td>{{ $supp['dose'] ?? '—' }}</td>
                        <td>{{ $supp['frequency'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding:8px 10px; color:#9CA3AF; font-style:italic; font-size:10px;">No supplements prescribed</div>
            @endif
        </div>
    </div>
</div>

{{-- ── MONITORING PLAN + GOALS ──────────────────────────────────── --}}
<div class="section-card" style="margin-top:0;">
    <div class="section-card-title">Monitoring Plan</div>
    <div class="section-card-body">{{ $monitoringPlan }}</div>
</div>

@if(!empty($goals))
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">Goals</div>
    <div class="section-card-body" style="padding:0;">
        <table class="goals-table">
            <thead><tr><th>Goal</th><th style="width:25%;">Target Date</th></tr></thead>
            <tbody>
                @foreach($goals as $goal)
                <tr>
                    <td>{{ $goal['goal'] ?? '—' }}</td>
                    <td>{{ $goal['target_date'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── REVIEW DATE + DIETITIAN SIGNATURE ───────────────────────── --}}
<div class="two-col" style="margin-bottom:0;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Next Review</div>
        <div class="section-card-body">
            <span style="font-weight:600; color:{{ $accentColor }}; font-size:12px;">{{ $reviewDate }}</span>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Registered Dietitian</div>
        <div class="section-card-body">
            <div style="font-weight:600; font-size:11px;">{{ $dietitian }}</div>
            <div style="color:#6B7280; font-size:9.5px; margin-top:2px;">Reg: {{ $dietitianReg }}</div>
            <div class="sig-line" style="margin-top:10mm;"><span style="color:#9CA3AF;">Signature</span></div>
        </div>
    </div>
</div>
@endsection
