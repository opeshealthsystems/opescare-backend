@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Feuille d\'Administration des Médicaments' : 'Medication Administration Record' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Enregistrement légal des doses administrées — MAR' : 'Legal Record of Dose Administration — MAR' }}
@endsection

@section('content')
<style>
    :root {
        --mar: #0369A1;
        --mar-light: #EFF6FF;
        --mar-mid: #BAE6FD;
        --mar-dark: #0C2D48;
    }

    /* ── Header ─────────────────────────────────────────────── */
    .mar-header {
        background: linear-gradient(135deg, #0369A1 0%, #0C4A6E 100%);
        color: #fff;
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 3mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .mar-header-left h2 { margin: 0; font-size: 15px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; }
    .mar-header-left p  { margin: 1mm 0 0; font-size: 9.5px; opacity: .82; }
    .mar-header-right   { text-align: right; font-size: 9.5px; }
    .mar-header-right .mhr { margin-bottom: 1mm; }
    .mar-header-right .mhl { opacity: .7; }
    .mar-header-right .mhv { font-weight: 700; }

    /* ── Allergy strip ────────────────────────────────────── */
    .allergy-none {
        background: #F0FDF4; border: 1px solid #6EE7B7; border-radius: 5px;
        padding: 2mm 4mm; font-size: 9px; color: #065F46; font-weight: 700;
        margin-bottom: 3mm; display: flex; align-items: center; gap: 2mm;
    }
    .allergy-alert {
        background: #FEF2F2; border: 2.5px solid #F87171; border-radius: 5px;
        padding: 2mm 4mm; font-size: 9px; color: #7F1D1D; font-weight: 700;
        margin-bottom: 3mm;
    }
    .allergy-alert-title { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 1.5mm; }
    .allergy-tag {
        display: inline-block; background: #FEE2E2; color: #991B1B;
        border: 1px solid #FCA5A5; border-radius: 9999px;
        padding: .4mm 2.5mm; font-size: 8.5px; font-weight: 700; margin: .5mm;
    }
    .sev-mild   { background: #FEF9C3; color: #713F12; border-color: #FDE68A; }
    .sev-moderate { background: #FED7AA; color: #7C2D12; border-color: #FDBA74; }
    .sev-severe { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }

    /* ── Patient info strip ───────────────────────────────── */
    .patient-strip {
        display: grid; grid-template-columns: repeat(5, 1fr); gap: 2.5mm;
        background: var(--mar-light); border: 1.5px solid var(--mar-mid);
        border-radius: 5px; padding: 2.5mm 4mm; margin-bottom: 4mm;
    }
    .ps-cell .ps-lbl { font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; color: #64748B; font-weight: 600; }
    .ps-cell .ps-val { font-size: 9.5px; font-weight: 700; color: var(--mar-dark); margin-top: .5mm; }

    /* ── Section title ────────────────────────────────────── */
    .mar-section-title {
        background: var(--mar); color: #fff; font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .6px; padding: 2mm 4mm;
        border-radius: 4px 4px 0 0; margin-top: 4mm;
    }

    /* ── MAR table ────────────────────────────────────────── */
    .mar-table { width: 100%; border-collapse: collapse; font-size: 8px; }
    .mar-table th {
        background: #0284C7; color: #fff; padding: 2mm 1.5mm;
        text-align: center; font-size: 7.5px; text-transform: uppercase;
        letter-spacing: .3px; border: 1px solid #0369A1; white-space: nowrap;
    }
    .mar-table th.left-align { text-align: left; }
    .mar-table td {
        padding: 1.5mm 1.5mm; border: 1px solid #DBEAFE; vertical-align: middle;
        font-size: 8px; color: #1E293B;
    }
    .mar-table td.drug-cell { font-weight: 700; font-size: 8.5px; color: var(--mar-dark); }
    .mar-table td.drug-generic { font-size: 7.5px; color: #64748B; font-style: italic; }
    .mar-table tr:nth-child(even) td { background: var(--mar-light); }

    /* High-alert / controlled highlights */
    .row-high-alert td { background: #FFF7ED !important; border-color: #FDBA74 !important; }
    .row-controlled    { border-left: 3px solid #D97706 !important; }

    /* Administration status cells */
    .adm-cell { text-align: center; font-size: 10px; font-weight: 800; }
    .adm-given    { color: #059669; }
    .adm-withheld { color: #D97706; }
    .adm-refused  { color: #DC2626; }
    .adm-notdue   { color: #9CA3AF; }

    /* Badges */
    .badge { display: inline-block; border-radius: 9999px; padding: .4mm 2.5mm; font-size: 7.5px; font-weight: 700; letter-spacing: .3px; }
    .badge-high-alert { background: #FFF3CD; color: #92400E; border: 1px solid #F59E0B; }
    .badge-controlled { background: #FEF3C7; color: #78350F; border: 1px solid #D97706; }
    .badge-route { background: var(--mar-mid); color: var(--mar-dark); border: 1px solid #7DD3FC; }

    /* ── PRN table ────────────────────────────────────────── */
    .prn-table { width: 100%; border-collapse: collapse; font-size: 8px; }
    .prn-table th {
        background: #1D4ED8; color: #fff; padding: 2mm 2mm;
        text-align: left; font-size: 7.5px; text-transform: uppercase;
        letter-spacing: .3px; border: 1px solid #1E40AF;
    }
    .prn-table td {
        padding: 2mm 2mm; border: 1px solid #BFDBFE; font-size: 8px;
        color: #1E293B; vertical-align: top;
    }
    .prn-table tr:nth-child(even) td { background: #EFF6FF; }
    .pain-arrow { color: #6B7280; font-size: 9px; }
    .pain-score { display: inline-block; min-width: 5mm; text-align: center; font-weight: 800;
        border-radius: 3px; padding: .3mm 1.5mm; font-size: 9px; }
    .pain-low  { background: #D1FAE5; color: #065F46; }
    .pain-mid  { background: #FEF9C3; color: #713F12; }
    .pain-high { background: #FEE2E2; color: #991B1B; }

    /* ── Summary footer ───────────────────────────────────── */
    .summary-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 3mm;
        margin: 4mm 0; padding: 3mm 4mm;
        background: var(--mar-light); border: 1.5px solid var(--mar-mid); border-radius: 5px;
    }
    .sum-box { text-align: center; }
    .sum-lbl { font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; color: #64748B; font-weight: 600; }
    .sum-val { font-size: 16px; font-weight: 900; margin-top: 1mm; }
    .sum-given    { color: #059669; }
    .sum-withheld { color: #D97706; }
    .sum-refused  { color: #DC2626; }
    .sum-due      { color: var(--mar); }

    /* ── Signature block ──────────────────────────────────── */
    .sig-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm;
        margin-top: 4mm;
    }
    .sig-box {
        border: 1px solid var(--mar-mid); border-radius: 5px;
        padding: 3mm; background: #fff;
    }
    .sig-lbl { font-size: 8px; text-transform: uppercase; letter-spacing: .4px; color: #64748B; font-weight: 700; margin-bottom: 2mm; }
    .sig-name { font-size: 10px; font-weight: 700; color: var(--mar-dark); }
    .sig-role { font-size: 8px; color: #64748B; margin-top: .5mm; }
    .sig-line { border-top: 1px solid #CBD5E1; margin: 5mm 0 1.5mm; }

    /* Period banner */
    .period-banner {
        background: var(--mar-dark); color: #fff; text-align: center;
        font-size: 10px; font-weight: 800; letter-spacing: .8px;
        padding: 2mm 4mm; border-radius: 4px; margin-bottom: 3mm;
        text-transform: uppercase;
    }

    @media print {
        .mar-table { font-size: 7.5px; }
        .mar-section-title { margin-top: 3mm; }
    }
</style>

{{-- ── HEADER ──────────────────────────────────────────────────────── --}}
<div class="mar-header">
    <div class="mar-header-left">
        <h2>{{ $language === 'fr' ? 'Feuille d\'Administration des Médicaments' : 'Medication Administration Record' }}</h2>
        <p>{{ $language === 'fr' ? 'Document légal — conserver dans le dossier patient' : 'Legal document — retain in patient chart' }} &nbsp;|&nbsp; <strong>MAR</strong></p>
    </div>
    <div class="mar-header-right">
        <div class="mhr"><span class="mhl">{{ $language === 'fr' ? 'Date:' : 'Date:' }}</span> <span class="mhv">{{ $payload['chart_date'] ?? '07 June 2026' }}</span></div>
        <div class="mhr"><span class="mhl">{{ $language === 'fr' ? 'Période:' : 'Period:' }}</span> <span class="mhv">{{ $payload['chart_period'] ?? 'Day Shift (07:00–19:00)' }}</span></div>
        <div class="mhr"><span class="mhl">{{ $language === 'fr' ? 'Dossier N°:' : 'Doc No:' }}</span> <span class="mhv">{{ $document_number }}</span></div>
    </div>
</div>

{{-- ── ALLERGY STRIP ────────────────────────────────────────────────── --}}
@php $allergies = $payload['allergies'] ?? []; @endphp
@if(count($allergies) > 0)
<div class="allergy-alert">
    <div class="allergy-alert-title">&#9888; {{ $language === 'fr' ? 'ALERTE ALLERGIE' : 'ALLERGY ALERT' }}</div>
    @foreach($allergies as $allergy)
        <span class="allergy-tag sev-{{ strtolower($allergy['severity'] ?? 'mild') }}">
            {{ $allergy['allergen'] ?? '' }}
            @if(!empty($allergy['reaction'])) &rarr; {{ $allergy['reaction'] }}@endif
            ({{ $allergy['severity'] ?? '' }})
        </span>
    @endforeach
</div>
@else
<div class="allergy-none">&#10003; {{ $language === 'fr' ? 'Aucune allergie connue documentée' : 'No known drug allergies on file' }}</div>
@endif

{{-- ── PATIENT INFO STRIP ───────────────────────────────────────────── --}}
<div class="patient-strip">
    <div class="ps-cell">
        <div class="ps-lbl">{{ $language === 'fr' ? 'Unité / Lit' : 'Ward / Bed' }}</div>
        <div class="ps-val">{{ $payload['ward'] ?? 'Internal Medicine — Ward 3B' }}<br>{{ $payload['bed'] ?? 'BED-3B-12' }}</div>
    </div>
    <div class="ps-cell">
        <div class="ps-lbl">{{ $language === 'fr' ? 'Date Admission' : 'Admission Date' }}</div>
        <div class="ps-val">{{ $payload['admission_date'] ?? '05 June 2026' }}</div>
    </div>
    <div class="ps-cell">
        <div class="ps-lbl">{{ $language === 'fr' ? 'Diagnostic' : 'Admitting Diagnosis' }}</div>
        <div class="ps-val">{{ $payload['admitting_diagnosis'] ?? 'Community-acquired pneumonia, severe' }}</div>
    </div>
    <div class="ps-cell">
        <div class="ps-lbl">{{ $language === 'fr' ? 'Poids' : 'Weight' }}</div>
        <div class="ps-val">{{ $payload['weight_kg'] ?? '72' }} kg</div>
    </div>
    <div class="ps-cell">
        <div class="ps-lbl">{{ $language === 'fr' ? 'Vérifié par' : 'Checked By' }}</div>
        <div class="ps-val">{{ $payload['checked_by'] ?? 'Sr. Nguemo Blaise, RN' }}</div>
    </div>
</div>

<div class="period-banner">{{ $payload['chart_period'] ?? '07 June 2026 — Day Shift (07:00–19:00)' }}</div>

{{-- ── REGULAR MEDICATIONS ──────────────────────────────────────────── --}}
<div class="mar-section-title">{{ $language === 'fr' ? 'Médicaments Réguliers' : 'Regular Scheduled Medications' }}</div>

@php
$meds = $payload['medications'] ?? [
    [
        'drug_name' => 'Amoxicillin-Clavulanate',
        'generic_name' => 'Co-amoxiclav',
        'strength' => '1.2 g',
        'form' => 'Powder for IV infusion',
        'route' => 'IV',
        'indication' => 'Broad-spectrum antibiotic — CAP',
        'scheduled_times' => ['08:00', '16:00', '00:00'],
        'dose' => '1.2 g',
        'special_instructions' => 'Infuse over 30 min in 100 mL NaCl 0.9%',
        'prescriber' => 'Dr. Mbarga F.',
        'prescribed_date' => '05 Jun 2026',
        'high_alert' => false,
        'controlled' => false,
        'administrations' => [
            ['time_due'=>'08:00','time_given'=>'08:05','dose_given'=>'1.2 g','given_by'=>'Nurse Engono','site'=>'R antecubital','status'=>'given','reason_if_not_given'=>null],
            ['time_due'=>'16:00','time_given'=>null,'dose_given'=>null,'given_by'=>null,'site'=>null,'status'=>'not_due','reason_if_not_given'=>null],
        ],
    ],
    [
        'drug_name' => 'Morphine Sulfate',
        'generic_name' => 'Morphine',
        'strength' => '10 mg/mL',
        'form' => 'Solution for injection',
        'route' => 'IV',
        'indication' => 'Moderate-severe pain control',
        'scheduled_times' => ['08:00', '14:00', '20:00'],
        'dose' => '5 mg',
        'special_instructions' => 'Check RR before each dose; hold if RR < 10',
        'prescriber' => 'Dr. Mbarga F.',
        'prescribed_date' => '05 Jun 2026',
        'high_alert' => true,
        'controlled' => true,
        'administrations' => [
            ['time_due'=>'08:00','time_given'=>'08:10','dose_given'=>'5 mg','given_by'=>'Nurse Engono','site'=>'IV line','status'=>'given','reason_if_not_given'=>null],
            ['time_due'=>'14:00','time_given'=>null,'dose_given'=>null,'given_by'=>null,'site'=>null,'status'=>'withheld','reason_if_not_given'=>'RR 9 — holding dose; physician notified'],
        ],
    ],
    [
        'drug_name' => 'Metformin',
        'generic_name' => 'Metformin HCl',
        'strength' => '500 mg',
        'form' => 'Tablet',
        'route' => 'PO',
        'indication' => 'Type 2 diabetes management',
        'scheduled_times' => ['08:00', '18:00'],
        'dose' => '500 mg',
        'special_instructions' => 'Give with food',
        'prescriber' => 'Dr. Mbarga F.',
        'prescribed_date' => '05 Jun 2026',
        'high_alert' => false,
        'controlled' => false,
        'administrations' => [
            ['time_due'=>'08:00','time_given'=>'08:15','dose_given'=>'500 mg','given_by'=>'Nurse Engono','site'=>null,'status'=>'given','reason_if_not_given'=>null],
            ['time_due'=>'18:00','time_given'=>null,'dose_given'=>null,'given_by'=>null,'site'=>null,'status'=>'not_due','reason_if_not_given'=>null],
        ],
    ],
    [
        'drug_name' => 'Furosemide',
        'generic_name' => 'Furosemide',
        'strength' => '40 mg',
        'form' => 'Tablet',
        'route' => 'PO',
        'indication' => 'Loop diuretic — fluid overload',
        'scheduled_times' => ['08:00'],
        'dose' => '40 mg',
        'special_instructions' => 'Monitor urine output; strict I/O chart',
        'prescriber' => 'Dr. Mbarga F.',
        'prescribed_date' => '06 Jun 2026',
        'high_alert' => false,
        'controlled' => false,
        'administrations' => [
            ['time_due'=>'08:00','time_given'=>null,'dose_given'=>null,'given_by'=>null,'site'=>null,'status'=>'refused','reason_if_not_given'=>'Patient refused — counselled; refusal documented'],
        ],
    ],
];
@endphp

<table class="mar-table">
    <thead>
        <tr>
            <th class="left-align" style="width:18%">{{ $language === 'fr' ? 'Médicament' : 'Medication' }}</th>
            <th style="width:5%">{{ $language === 'fr' ? 'Voie' : 'Route' }}</th>
            <th style="width:6%">{{ $language === 'fr' ? 'Dose' : 'Dose' }}</th>
            <th class="left-align" style="width:12%">{{ $language === 'fr' ? 'Indications spéciales' : 'Special Instructions' }}</th>
            <th style="width:5%">08:00</th>
            <th style="width:5%">10:00</th>
            <th style="width:5%">12:00</th>
            <th style="width:5%">14:00</th>
            <th style="width:5%">16:00</th>
            <th style="width:5%">18:00</th>
            <th class="left-align" style="width:12%">{{ $language === 'fr' ? 'Prescripteur' : 'Prescriber' }}</th>
            <th class="left-align" style="width:12%">{{ $language === 'fr' ? 'Remarques' : 'Notes / Reason' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($meds as $med)
        @php
            $rowClass = '';
            if(!empty($med['high_alert'])) $rowClass = 'row-high-alert';
            // Map scheduled times to time columns for display
            $timeCols = ['08:00','10:00','12:00','14:00','16:00','18:00'];
            // Build a map of time_due -> administration
            $admMap = [];
            foreach(($med['administrations'] ?? []) as $adm) {
                $admMap[$adm['time_due']] = $adm;
            }
            $reasonNote = '';
            foreach(($med['administrations'] ?? []) as $adm) {
                if(!empty($adm['reason_if_not_given'])) $reasonNote = $adm['reason_if_not_given'];
            }
        @endphp
        <tr class="{{ $rowClass }}{{ !empty($med['controlled']) ? ' row-controlled' : '' }}">
            <td>
                <div class="drug-cell">{{ $med['drug_name'] ?? '' }}</div>
                <div class="drug-generic">{{ $med['generic_name'] ?? '' }} {{ $med['strength'] ?? '' }} — {{ $med['form'] ?? '' }}</div>
                @if(!empty($med['high_alert']))<div><span class="badge badge-high-alert">&#9888; HIGH ALERT</span></div>@endif
                @if(!empty($med['controlled']))<div><span class="badge badge-controlled">&#128274; CONTROLLED</span></div>@endif
            </td>
            <td style="text-align:center"><span class="badge badge-route">{{ $med['route'] ?? '' }}</span></td>
            <td style="text-align:center;font-weight:700;">{{ $med['dose'] ?? '' }}</td>
            <td>{{ $med['special_instructions'] ?? '' }}</td>
            @foreach($timeCols as $tc)
                @php
                    $adm = $admMap[$tc] ?? null;
                    $inSchedule = in_array($tc, $med['scheduled_times'] ?? []);
                    if($adm) {
                        $st = $adm['status'];
                    } elseif($inSchedule) {
                        $st = 'not_due';
                    } else {
                        $st = 'na';
                    }
                @endphp
                <td class="adm-cell">
                    @if($st === 'given')
                        <span class="adm-given" title="{{ $adm['time_given'] ?? '' }} by {{ $adm['given_by'] ?? '' }}">&#10003;</span>
                    @elseif($st === 'withheld')
                        <span class="adm-withheld" title="{{ $adm['reason_if_not_given'] ?? '' }}">W</span>
                    @elseif($st === 'refused')
                        <span class="adm-refused" title="{{ $adm['reason_if_not_given'] ?? '' }}">R</span>
                    @elseif($st === 'not_due')
                        <span class="adm-notdue">&mdash;</span>
                    @else
                        <span style="color:#E2E8F0">&bull;</span>
                    @endif
                </td>
            @endforeach
            <td>{{ $med['prescriber'] ?? '' }}<br><span style="font-size:7px;color:#94A3B8">{{ $med['prescribed_date'] ?? '' }}</span></td>
            <td style="font-size:7.5px;color:#475569;">{{ $reasonNote }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="font-size:7.5px;color:#64748B;margin-top:1.5mm;padding-left:1mm;">
    {{ $language === 'fr' ? 'Légende:' : 'Legend:' }}
    <span style="color:#059669;font-weight:700">&#10003; {{ $language === 'fr' ? 'Administré' : 'Given' }}</span> &nbsp;
    <span style="color:#D97706;font-weight:700">W {{ $language === 'fr' ? 'Suspendu' : 'Withheld' }}</span> &nbsp;
    <span style="color:#DC2626;font-weight:700">R {{ $language === 'fr' ? 'Refusé' : 'Refused' }}</span> &nbsp;
    <span style="color:#9CA3AF;font-weight:700">&mdash; {{ $language === 'fr' ? 'Non prévu' : 'Not due' }}</span>
</div>

{{-- ── PRN MEDICATIONS ───────────────────────────────────────────────── --}}
@php
$prnMeds = $payload['prn_medications'] ?? [
    [
        'drug_name' => 'Paracetamol',
        'strength' => '1 g IV',
        'dose' => '1 g',
        'route' => 'IV',
        'indication' => 'Fever / mild-moderate pain',
        'max_dose_24h' => '4 g / 24h',
        'administrations' => [
            ['time'=>'09:30','dose_given'=>'1 g','given_by'=>'Nurse Engono','reason_given'=>'Temp 38.8°C','pain_score_before'=>4,'pain_score_after'=>2],
        ],
    ],
    [
        'drug_name' => 'Ondansetron',
        'strength' => '4 mg IV',
        'dose' => '4 mg',
        'route' => 'IV',
        'indication' => 'Nausea / vomiting',
        'max_dose_24h' => '32 mg / 24h',
        'administrations' => [],
    ],
];
@endphp

<div class="mar-section-title" style="background:#1D4ED8;">{{ $language === 'fr' ? 'Médicaments à la Demande (PRN / Si Besoin)' : 'PRN (As Needed) Medications' }}</div>
<table class="prn-table">
    <thead>
        <tr>
            <th>{{ $language === 'fr' ? 'Médicament' : 'Medication' }}</th>
            <th>{{ $language === 'fr' ? 'Indication' : 'Indication' }}</th>
            <th>{{ $language === 'fr' ? 'Dose Max 24h' : 'Max Dose 24h' }}</th>
            <th>{{ $language === 'fr' ? 'Heure' : 'Time' }}</th>
            <th>{{ $language === 'fr' ? 'Dose donnée' : 'Dose Given' }}</th>
            <th>{{ $language === 'fr' ? 'Raison' : 'Reason Given' }}</th>
            <th>{{ $language === 'fr' ? 'Douleur ↓' : 'Pain Before→After' }}</th>
            <th>{{ $language === 'fr' ? 'Administré par' : 'Given By' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($prnMeds as $prn)
            @if(count($prn['administrations'] ?? []) > 0)
                @foreach($prn['administrations'] as $idx => $pa)
                @php
                    $pb = $pa['pain_score_before'] ?? null;
                    $paf = $pa['pain_score_after'] ?? null;
                    $pbClass = is_numeric($pb) ? ($pb <= 3 ? 'pain-low' : ($pb <= 6 ? 'pain-mid' : 'pain-high')) : '';
                    $paClass = is_numeric($paf) ? ($paf <= 3 ? 'pain-low' : ($paf <= 6 ? 'pain-mid' : 'pain-high')) : '';
                @endphp
                <tr>
                    @if($idx === 0)
                    <td rowspan="{{ count($prn['administrations']) }}" style="font-weight:700">{{ $prn['drug_name'] ?? '' }}<br><span style="font-size:7px;color:#64748B">{{ $prn['strength'] ?? '' }} — {{ $prn['route'] ?? '' }}</span></td>
                    <td rowspan="{{ count($prn['administrations']) }}" style="font-size:7.5px">{{ $prn['indication'] ?? '' }}</td>
                    <td rowspan="{{ count($prn['administrations']) }}" style="text-align:center;font-weight:700">{{ $prn['max_dose_24h'] ?? '' }}</td>
                    @endif
                    <td style="text-align:center;font-weight:700">{{ $pa['time'] ?? '' }}</td>
                    <td style="text-align:center">{{ $pa['dose_given'] ?? '' }}</td>
                    <td style="font-size:7.5px">{{ $pa['reason_given'] ?? '' }}</td>
                    <td style="text-align:center">
                        @if(is_numeric($pb) && is_numeric($paf))
                            <span class="pain-score {{ $pbClass }}">{{ $pb }}</span>
                            <span class="pain-arrow"> &rarr; </span>
                            <span class="pain-score {{ $paClass }}">{{ $paf }}</span>
                        @else
                            <span style="color:#CBD5E1">&mdash;</span>
                        @endif
                    </td>
                    <td style="font-size:7.5px">{{ $pa['given_by'] ?? '' }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td style="font-weight:700">{{ $prn['drug_name'] ?? '' }}<br><span style="font-size:7px;color:#64748B">{{ $prn['strength'] ?? '' }} — {{ $prn['route'] ?? '' }}</span></td>
                    <td style="font-size:7.5px">{{ $prn['indication'] ?? '' }}</td>
                    <td style="text-align:center;font-weight:700">{{ $prn['max_dose_24h'] ?? '' }}</td>
                    <td colspan="5" style="text-align:center;color:#9CA3AF;font-style:italic;">{{ $language === 'fr' ? 'Non administré ce quart' : 'Not administered this shift' }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>

{{-- ── SUMMARY ───────────────────────────────────────────────────────── --}}
@php
$totalDue = 0; $totalGiven = 0; $totalWithheld = 0; $totalRefused = 0;
foreach($meds as $med) {
    foreach(($med['administrations'] ?? []) as $adm) {
        if(in_array($adm['time_due'], $med['scheduled_times'] ?? [])) {
            $totalDue++;
            if($adm['status'] === 'given') $totalGiven++;
            if($adm['status'] === 'withheld') $totalWithheld++;
            if($adm['status'] === 'refused') $totalRefused++;
        }
    }
}
@endphp
<div class="summary-grid">
    <div class="sum-box">
        <div class="sum-lbl">{{ $language === 'fr' ? 'Doses prévues' : 'Doses Due (this shift)' }}</div>
        <div class="sum-val sum-due">{{ $totalDue }}</div>
    </div>
    <div class="sum-box">
        <div class="sum-lbl">{{ $language === 'fr' ? 'Administrées' : 'Doses Given' }}</div>
        <div class="sum-val sum-given">{{ $totalGiven }}</div>
    </div>
    <div class="sum-box">
        <div class="sum-lbl">{{ $language === 'fr' ? 'Suspendues' : 'Withheld' }}</div>
        <div class="sum-val sum-withheld">{{ $totalWithheld }}</div>
    </div>
    <div class="sum-box">
        <div class="sum-lbl">{{ $language === 'fr' ? 'Refusées' : 'Refused' }}</div>
        <div class="sum-val sum-refused">{{ $totalRefused }}</div>
    </div>
</div>

{{-- ── SIGNATURES ────────────────────────────────────────────────────── --}}
<div class="sig-grid">
    <div class="sig-box">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Infirmière — Quart de Jour' : 'Nurse — Day Shift' }}</div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $issuer_name }}</div>
        <div class="sig-role">{{ $issuer_role }} &nbsp;|&nbsp; {{ $issued_at }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Infirmière — Quart de Nuit' : 'Nurse — Night Shift' }}</div>
        <div class="sig-line"></div>
        <div class="sig-name" style="color:#9CA3AF;font-style:italic;">{{ $language === 'fr' ? 'À compléter' : 'To be completed' }}</div>
        <div class="sig-role">&nbsp;</div>
    </div>
    <div class="sig-box">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Vérification Stupéfiants' : 'Controlled Drug Check' }}</div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $payload['checked_by'] ?? 'Sr. Nguemo Blaise, RN' }}</div>
        <div class="sig-role">{{ $language === 'fr' ? 'Infirmière de contrôle' : 'Checking Nurse' }} &nbsp;|&nbsp; {{ $issued_at }}</div>
    </div>
</div>

<div style="font-size:7.5px;color:#94A3B8;margin-top:3mm;text-align:center;">
    {{ $language === 'fr'
        ? 'Ce document est un enregistrement médical légal. Toute erreur doit être tracée conformément à la politique de l\'établissement. Ne pas utiliser de correcteur.'
        : 'This document is a legal medical record. Any error must be crossed out and initialled per facility policy. Do not use correction fluid.' }}
</div>
@endsection
