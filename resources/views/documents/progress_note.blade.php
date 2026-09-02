@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Note d\'Évolution Clinique' : 'Daily Clinical Progress Note' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Évaluation médicale journalière SOAP — PRG' : 'Daily SOAP Medical Assessment — PRG' }}
@endsection

@section('content')
<style>
    :root {
        --prg: #0F4C81;
        --prg-light: #EFF6FF;
        --prg-mid: #BFDBFE;
        --prg-dark: #0A2D50;
    }

    /* ── Note type header ────────────────────────────────────── */
    .prg-header {
        background: linear-gradient(135deg, #0F4C81 0%, #1E3A5F 100%);
        color: #fff; border-radius: 8px; padding: 4mm 6mm;
        margin-bottom: 3mm; display: flex; justify-content: space-between; align-items: center;
    }
    .prg-header-left h2 { margin: 0; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
    .prg-header-left p  { margin: 1mm 0 0; font-size: 9px; opacity: .82; }
    .prg-header-right { text-align: right; font-size: 9px; }
    .prg-header-right .hr { margin-bottom: 1mm; }
    .prg-header-right .hl { opacity: .7; }
    .prg-header-right .hv { font-weight: 700; }

    /* type badge + progress badge */
    .note-type-badge {
        display: inline-block; background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.4); border-radius: 4px;
        padding: .8mm 3mm; font-size: 9px; font-weight: 800;
        letter-spacing: .5px; text-transform: uppercase; margin-bottom: 2mm;
    }
    .day-badge {
        display: inline-block; background: #1D4ED8;
        border-radius: 9999px; padding: .5mm 3mm;
        font-size: 9px; font-weight: 800; letter-spacing: .5px;
        margin-left: 2mm;
    }

    /* ── SOAP section shells ─────────────────────────────────── */
    .soap-section {
        border: 1.5px solid var(--prg-mid); border-radius: 6px;
        margin-bottom: 4mm; overflow: hidden;
    }
    .soap-section-header {
        display: flex; align-items: center; gap: 3mm;
        padding: 2.5mm 4mm;
    }
    .soap-letter {
        width: 9mm; height: 9mm; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 900; flex-shrink: 0;
    }
    .soap-s .soap-section-header { background: #FEF9C3; }
    .soap-s .soap-letter { background: #EAB308; color: #fff; }
    .soap-o .soap-section-header { background: #DBEAFE; }
    .soap-o .soap-letter { background: #2563EB; color: #fff; }
    .soap-a .soap-section-header { background: #D1FAE5; }
    .soap-a .soap-letter { background: #059669; color: #fff; }
    .soap-p .soap-section-header { background: #EDE9FE; }
    .soap-p .soap-letter { background: #7C3AED; color: #fff; }

    .soap-section-title { font-size: 11px; font-weight: 900; }
    .soap-section-sub   { font-size: 8.5px; color: #64748B; margin-top: .5mm; }
    .soap-body { padding: 3mm 4mm; background: #FAFBFF; }

    /* ── Subjective ──────────────────────────────────────────── */
    .complaint-text { font-size: 9.5px; color: #1E293B; line-height: 1.5; margin-bottom: 2mm; }
    .functional-grid {
        display: grid; grid-template-columns: repeat(5, 1fr); gap: 2mm;
        margin-top: 2mm;
    }
    .func-cell { background: #fff; border: 1px solid #E2E8F0; border-radius: 4px; padding: 2mm; text-align: center; }
    .func-lbl { font-size: 7px; text-transform: uppercase; letter-spacing: .3px; color: #94A3B8; font-weight: 600; }
    .func-val { font-size: 9.5px; font-weight: 700; color: #1E293B; margin-top: .5mm; }

    /* Pain bar */
    .pain-bar-wrap { display: flex; align-items: center; gap: 2mm; margin: 1.5mm 0; }
    .pain-bar-track { flex: 1; height: 4mm; background: linear-gradient(to right, #22C55E, #EAB308 40%, #F97316 70%, #DC2626 100%); border-radius: 9999px; position: relative; }
    .pain-bar-marker { position: absolute; top: -1mm; width: 2.5mm; height: 6mm; background: #0F172A; border-radius: 2px; transform: translateX(-50%); }
    .pain-score-lbl { font-size: 11px; font-weight: 900; min-width: 5mm; text-align: center; }

    /* ── Objective ───────────────────────────────────────────── */
    .vitals-row { display: flex; gap: 2mm; flex-wrap: wrap; margin-bottom: 3mm; }
    .vital-pill {
        display: flex; flex-direction: column; align-items: center;
        background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 5px;
        padding: 2mm 3mm; min-width: 14mm;
    }
    .vital-pill.crit { background: #FEF2F2; border-color: #FCA5A5; }
    .vital-pill.warn { background: #FFF7ED; border-color: #FED7AA; }
    .vital-pill-lbl { font-size: 7px; text-transform: uppercase; letter-spacing: .3px; color: #64748B; font-weight: 600; }
    .vital-pill-val { font-size: 12px; font-weight: 900; color: #0F4C81; margin-top: .3mm; }
    .vital-pill.crit .vital-pill-val { color: #DC2626; }
    .vital-pill.warn .vital-pill-val { color: #D97706; }
    .vital-pill-unit { font-size: 7px; color: #94A3B8; margin-top: .2mm; }

    .systems-table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 2mm; }
    .systems-table th { background: var(--prg); color: #fff; padding: 1.5mm 2.5mm; text-align: left; font-size: 7.5px; text-transform: uppercase; letter-spacing: .3px; border: 1px solid var(--prg-dark); }
    .systems-table td { padding: 1.5mm 2.5mm; border: 1px solid #DBEAFE; color: #1E293B; vertical-align: top; }
    .systems-table tr:nth-child(even) td { background: var(--prg-light); }

    .inv-table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
    .inv-table th { background: #1D4ED8; color: #fff; padding: 1.5mm 2mm; text-align: left; font-size: 7.5px; text-transform: uppercase; border: 1px solid #1E40AF; }
    .inv-table td { padding: 1.5mm 2mm; border: 1px solid #BFDBFE; }
    .flag-H { color: #DC2626; font-weight: 800; }
    .flag-L { color: #7C3AED; font-weight: 800; }
    .flag-C { color: #DC2626; font-weight: 900; background: #FEF2F2; padding: .2mm 1.5mm; border-radius: 3px; }

    /* ── Assessment ──────────────────────────────────────────── */
    .dx-primary {
        background: var(--prg-dark); color: #fff;
        border-radius: 5px; padding: 3mm 4mm; margin-bottom: 2.5mm;
        display: flex; justify-content: space-between; align-items: center;
    }
    .dx-primary-name { font-size: 12px; font-weight: 900; }
    .dx-primary-icd  { font-size: 9px; opacity: .8; margin-top: .5mm; }
    .progress-badge {
        display: inline-block; border-radius: 9999px; padding: 1mm 4mm;
        font-size: 10px; font-weight: 900; letter-spacing: .5px; text-transform: uppercase;
    }
    .prog-improving    { background: #D1FAE5; color: #065F46; border: 1.5px solid #6EE7B7; }
    .prog-stable       { background: #DBEAFE; color: #1E3A5F; border: 1.5px solid #93C5FD; }
    .prog-deteriorating{ background: #FEE2E2; color: #7F1D1D; border: 1.5px solid #FCA5A5; }
    .prog-unchanged    { background: #F1F5F9; color: #475569; border: 1.5px solid #CBD5E1; }

    .secondary-dx-list { font-size: 8.5px; color: #475569; }
    .secondary-dx-list li { padding: .5mm 0; border-bottom: 1px dashed #E2E8F0; }
    .clinical-impression { font-size: 9px; color: #1E293B; line-height: 1.5; background: #fff; border-left: 3px solid var(--prg); padding: 2mm 3mm; border-radius: 0 4px 4px 0; margin-top: 2mm; }

    /* ── Plan ────────────────────────────────────────────────── */
    .plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; }
    .plan-card { background: #fff; border: 1px solid var(--prg-mid); border-radius: 5px; overflow: hidden; }
    .plan-card-title { background: #7C3AED; color: #fff; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; padding: 1.5mm 3mm; }
    .plan-card-body  { padding: 2.5mm 3mm; font-size: 8.5px; }
    .plan-card-body ul { margin: 0; padding-left: 3mm; }
    .plan-card-body li { padding: .8mm 0; border-bottom: 1px dashed #E2E8F0; color: #1E293B; }
    .plan-card-body li:last-child { border-bottom: none; }
    .med-action-badge { display: inline-block; border-radius: 9999px; padding: .2mm 2mm; font-size: 7px; font-weight: 700; margin-right: 1mm; }
    .act-continue { background: #DBEAFE; color: #1E40AF; }
    .act-start    { background: #D1FAE5; color: #065F46; }
    .act-stop     { background: #FEE2E2; color: #991B1B; }
    .act-change   { background: #FEF9C3; color: #713F12; }
    .plan-card-full { grid-column: 1 / -1; }

    /* ── Signature block ─────────────────────────────────────── */
    .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-top: 4mm; }
    .sig-box { border: 1px solid var(--prg-mid); border-radius: 5px; padding: 3mm; background: #fff; }
    .sig-lbl { font-size: 8px; text-transform: uppercase; letter-spacing: .4px; color: #64748B; font-weight: 700; margin-bottom: 2mm; }
    .sig-name { font-size: 10px; font-weight: 700; color: var(--prg-dark); }
    .sig-role { font-size: 8px; color: #64748B; margin-top: .5mm; }
    .sig-line { border-top: 1px solid #CBD5E1; margin: 5mm 0 1.5mm; }
    .countersign-box { background: #FFF7ED; border: 1.5px solid #FED7AA; }

    @media print { .soap-section { page-break-inside: avoid; } }
</style>

@php
$noteType = $payload['note_type'] ?? 'Daily Progress Note';
$progress = $payload['assessment']['progress'] ?? 'Stable';
$progClass = match($progress) {
    'Improving'    => 'prog-improving',
    'Deteriorating'=> 'prog-deteriorating',
    'Unchanged'    => 'prog-unchanged',
    default        => 'prog-stable',
};
@endphp

{{-- ── HEADER ──────────────────────────────────────────────────────── --}}
<div class="prg-header">
    <div class="prg-header-left">
        <div class="note-type-badge">{{ $noteType }}</div>
        <span class="day-badge">{{ $payload['day_of_admission'] ?? 'Day 2' }}</span>
        <h2>{{ $language === 'fr' ? 'Note d\'Évolution Clinique' : 'Clinical Progress Note' }}</h2>
        <p>{{ $payload['ward'] ?? 'Internal Medicine — Ward 3B' }} &nbsp;|&nbsp; {{ $payload['bed'] ?? 'BED-3B-12' }}</p>
    </div>
    <div class="prg-header-right">
        <div class="hr"><span class="hl">{{ $language === 'fr' ? 'Date:' : 'Date:' }}</span> <span class="hv">{{ $payload['note_date'] ?? '07 June 2026' }}</span></div>
        <div class="hr"><span class="hl">{{ $language === 'fr' ? 'Heure:' : 'Time:' }}</span> <span class="hv">{{ $payload['note_time'] ?? '09:15 WAT' }}</span></div>
        <div class="hr"><span class="hl">{{ $language === 'fr' ? 'Diagnostic initial:' : 'Admitting Dx:' }}</span> <span class="hv">{{ $payload['admitting_diagnosis'] ?? 'Community-acquired pneumonia' }}</span></div>
        <div class="hr"><span class="hl">{{ $language === 'fr' ? 'Dossier N°:' : 'Doc No:' }}</span> <span class="hv">{{ $document_number }}</span></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- S — SUBJECTIVE                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
$subj = $payload['subjective'] ?? [
    'patient_complaints' => 'Patient reports persistent productive cough with greenish sputum, worsening on exertion. Shortness of breath improved slightly compared to yesterday. Denies haemoptysis. Reports right-sided pleuritic chest pain rated 5/10.',
    'pain_score' => 5,
    'sleep' => 'Poor — interrupted by coughing episodes',
    'appetite' => 'Reduced — tolerating 50% of meals',
    'bowels' => 'Last bowel movement yesterday, soft, normal colour',
    'urine' => 'Adequate — approximately 1.2 L over past 24 h',
    'other' => null,
];
$painScore = $subj['pain_score'] ?? 0;
$painPct   = ($painScore / 10) * 100;
@endphp
<div class="soap-section soap-s">
    <div class="soap-section-header">
        <div class="soap-letter">S</div>
        <div>
            <div class="soap-section-title">{{ $language === 'fr' ? 'Subjectif' : 'Subjective' }}</div>
            <div class="soap-section-sub">{{ $language === 'fr' ? 'Plaintes et ressenti du patient' : 'Patient complaints and reported symptoms' }}</div>
        </div>
    </div>
    <div class="soap-body">
        <div class="complaint-text">{{ $subj['patient_complaints'] ?? '' }}</div>

        <div style="display:flex;align-items:center;gap:3mm;margin:2mm 0 1mm;">
            <span style="font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;">{{ $language === 'fr' ? 'Douleur (EVA):' : 'Pain Score (VAS):' }}</span>
            <div class="pain-bar-wrap" style="flex:1">
                <div class="pain-bar-track" style="position:relative;">
                    <div class="pain-bar-marker" style="left:{{ $painPct }}%;"></div>
                </div>
                <span class="pain-score-lbl" style="color:{{ $painScore <= 3 ? '#059669' : ($painScore <= 6 ? '#D97706' : '#DC2626') }}">{{ $painScore }}/10</span>
            </div>
        </div>

        <div class="functional-grid">
            <div class="func-cell">
                <div class="func-lbl">{{ $language === 'fr' ? 'Sommeil' : 'Sleep' }}</div>
                <div class="func-val">{{ $subj['sleep'] ?? '—' }}</div>
            </div>
            <div class="func-cell">
                <div class="func-lbl">{{ $language === 'fr' ? 'Appétit' : 'Appetite' }}</div>
                <div class="func-val">{{ $subj['appetite'] ?? '—' }}</div>
            </div>
            <div class="func-cell">
                <div class="func-lbl">{{ $language === 'fr' ? 'Selles' : 'Bowels' }}</div>
                <div class="func-val">{{ $subj['bowels'] ?? '—' }}</div>
            </div>
            <div class="func-cell">
                <div class="func-lbl">{{ $language === 'fr' ? 'Diurèse' : 'Urine' }}</div>
                <div class="func-val">{{ $subj['urine'] ?? '—' }}</div>
            </div>
            @if(!empty($subj['other']))
            <div class="func-cell">
                <div class="func-lbl">{{ $language === 'fr' ? 'Autre' : 'Other' }}</div>
                <div class="func-val">{{ $subj['other'] }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- O — OBJECTIVE                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
$obj = $payload['objective'] ?? [
    'vitals' => ['bp'=>'128/82', 'pulse'=>98, 'temp'=>'38.4°C', 'spo2'=>'94%', 'rr'=>22, 'weight_kg'=>72],
    'general_appearance' => 'Alert and oriented. Mild respiratory distress at rest. Not cyanosed. Mild pallor. No jaundice.',
    'systems' => [
        ['system'=>'Respiratory','finding'=>'Tachypnoea (RR 22/min). Dullness to percussion right base. Bronchial breathing and increased vocal resonance right lower zone. Fine inspiratory crackles bilateral lower zones.'],
        ['system'=>'Cardiovascular','finding'=>'Regular rate and rhythm, rate 98/min. No murmurs. JVP not elevated. No peripheral oedema.'],
        ['system'=>'Abdomen','finding'=>'Soft and non-tender. No organomegaly. Bowel sounds present and normal.'],
        ['system'=>'Neurology','finding'=>'GCS 15/15. No focal deficits. No neck stiffness.'],
    ],
    'relevant_investigations' => [
        ['test'=>'WBC','result'=>'14.2 ×10⁹/L','date'=>'07 Jun','flag'=>'H'],
        ['test'=>'CRP','result'=>'87 mg/L','date'=>'07 Jun','flag'=>'H'],
        ['test'=>'Procalcitonin','result'=>'0.82 ng/mL','date'=>'07 Jun','flag'=>'H'],
        ['test'=>'SpO₂ (room air)','result'=>'94%','date'=>'07 Jun','flag'=>'L'],
        ['test'=>'Chest X-Ray','result'=>'Right lower lobe consolidation, no pleural effusion','date'=>'06 Jun','flag'=>null],
    ],
];
$vitals = $obj['vitals'] ?? [];
// Determine critical flags
$hrCrit  = is_numeric($vitals['pulse'] ?? '') && ($vitals['pulse'] > 120 || $vitals['pulse'] < 50);
$rrCrit  = is_numeric($vitals['rr'] ?? '') && ($vitals['rr'] > 25 || $vitals['rr'] < 8);
$tempWarn= !empty($vitals['temp']) && (str_contains($vitals['temp'], '38') || str_contains($vitals['temp'], '39') || str_contains($vitals['temp'], '40'));
@endphp
<div class="soap-section soap-o">
    <div class="soap-section-header">
        <div class="soap-letter">O</div>
        <div>
            <div class="soap-section-title">{{ $language === 'fr' ? 'Objectif' : 'Objective' }}</div>
            <div class="soap-section-sub">{{ $language === 'fr' ? 'Examen clinique et examens complémentaires' : 'Clinical examination and investigation findings' }}</div>
        </div>
    </div>
    <div class="soap-body">
        {{-- Vitals --}}
        <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;margin-bottom:1.5mm;">{{ $language === 'fr' ? 'Signes Vitaux' : 'Vital Signs' }}</div>
        <div class="vitals-row">
            <div class="vital-pill">
                <div class="vital-pill-lbl">BP</div>
                <div class="vital-pill-val">{{ $vitals['bp'] ?? '—' }}</div>
                <div class="vital-pill-unit">mmHg</div>
            </div>
            <div class="vital-pill {{ $hrCrit ? 'crit' : '' }}">
                <div class="vital-pill-lbl">{{ $language === 'fr' ? 'Pouls' : 'Pulse' }}</div>
                <div class="vital-pill-val">{{ $vitals['pulse'] ?? '—' }}</div>
                <div class="vital-pill-unit">bpm</div>
            </div>
            <div class="vital-pill {{ $tempWarn ? 'warn' : '' }}">
                <div class="vital-pill-lbl">{{ $language === 'fr' ? 'Temp' : 'Temp' }}</div>
                <div class="vital-pill-val">{{ $vitals['temp'] ?? '—' }}</div>
                <div class="vital-pill-unit">&nbsp;</div>
            </div>
            <div class="vital-pill {{ (is_numeric(rtrim($vitals['spo2'] ?? '', '%')) && (int)rtrim($vitals['spo2'] ?? '100', '%') < 95) ? 'crit' : '' }}">
                <div class="vital-pill-lbl">SpO₂</div>
                <div class="vital-pill-val">{{ $vitals['spo2'] ?? '—' }}</div>
                <div class="vital-pill-unit">&nbsp;</div>
            </div>
            <div class="vital-pill {{ $rrCrit ? 'crit' : '' }}">
                <div class="vital-pill-lbl">RR</div>
                <div class="vital-pill-val">{{ $vitals['rr'] ?? '—' }}</div>
                <div class="vital-pill-unit">br/min</div>
            </div>
            <div class="vital-pill">
                <div class="vital-pill-lbl">{{ $language === 'fr' ? 'Poids' : 'Weight' }}</div>
                <div class="vital-pill-val">{{ $vitals['weight_kg'] ?? '—' }}</div>
                <div class="vital-pill-unit">kg</div>
            </div>
        </div>

        <div style="font-size:8.5px;color:#1E293B;margin-bottom:2.5mm;background:#EFF6FF;padding:2mm 3mm;border-radius:4px;border-left:3px solid var(--prg);">
            <strong>{{ $language === 'fr' ? 'Aspect général:' : 'General Appearance:' }}</strong> {{ $obj['general_appearance'] ?? '' }}
        </div>

        {{-- Systems --}}
        <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;margin-bottom:1.5mm;">{{ $language === 'fr' ? 'Examen par Systèmes' : 'Systematic Examination' }}</div>
        <table class="systems-table">
            <thead>
                <tr>
                    <th style="width:20%">{{ $language === 'fr' ? 'Système' : 'System' }}</th>
                    <th>{{ $language === 'fr' ? 'Résultats' : 'Findings' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($obj['systems'] ?? [] as $sys)
                <tr>
                    <td style="font-weight:700;color:var(--prg-dark)">{{ $sys['system'] ?? '' }}</td>
                    <td>{{ $sys['finding'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Investigations --}}
        @if(!empty($obj['relevant_investigations']))
        <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;margin:2.5mm 0 1.5mm;">{{ $language === 'fr' ? 'Résultats Pertinents' : 'Relevant Investigation Results' }}</div>
        <table class="inv-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'Examen' : 'Test' }}</th>
                    <th>{{ $language === 'fr' ? 'Résultat' : 'Result' }}</th>
                    <th>{{ $language === 'fr' ? 'Date' : 'Date' }}</th>
                    <th>{{ $language === 'fr' ? 'Drapeau' : 'Flag' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($obj['relevant_investigations'] as $inv)
                <tr>
                    <td style="font-weight:600">{{ $inv['test'] ?? '' }}</td>
                    <td class="{{ !empty($inv['flag']) ? 'flag-'.strtoupper($inv['flag']) : '' }}">{{ $inv['result'] ?? '' }}</td>
                    <td>{{ $inv['date'] ?? '' }}</td>
                    <td style="text-align:center">
                        @if(!empty($inv['flag']))
                            <span class="flag-{{ strtoupper($inv['flag']) }}">{{ strtoupper($inv['flag']) }}</span>
                        @else
                            <span style="color:#9CA3AF">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- A — ASSESSMENT                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
$assess = $payload['assessment'] ?? [
    'primary_diagnosis' => 'Community-acquired pneumonia (CAP), CURB-65 score 2 — moderate severity',
    'icd10' => 'J18.9',
    'secondary_diagnoses' => ['Type 2 Diabetes Mellitus, on oral therapy (E11.9)', 'Hyperglycaemia, medication-related (R73.0)'],
    'clinical_impression' => 'Day 2 of admission for moderate-severity CAP. Patient shows partial response to empirical antibiotic therapy with persisting fever (38.4°C) and tachycardia (98 bpm). Oxygen saturation marginally improved on 2L nasal cannulae (94%). Inflammatory markers remain elevated. Plan to continue current antibiotics and reassess cultures pending. Diabetes management reviewed — hyperglycaemia likely secondary to infection and corticosteroid effect.',
    'progress' => 'Improving',
];
@endphp
<div class="soap-section soap-a">
    <div class="soap-section-header">
        <div class="soap-letter">A</div>
        <div>
            <div class="soap-section-title">{{ $language === 'fr' ? 'Analyse / Évaluation' : 'Assessment' }}</div>
            <div class="soap-section-sub">{{ $language === 'fr' ? 'Diagnostic et impression clinique' : 'Diagnosis and clinical impression' }}</div>
        </div>
    </div>
    <div class="soap-body">
        <div class="dx-primary">
            <div>
                <div style="font-size:7.5px;opacity:.7;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5mm;">{{ $language === 'fr' ? 'Diagnostic Principal' : 'Primary Diagnosis' }}</div>
                <div class="dx-primary-name">{{ $assess['primary_diagnosis'] ?? '' }}</div>
                <div class="dx-primary-icd">ICD-10: {{ $assess['icd10'] ?? '' }}</div>
            </div>
            <div>
                <span class="progress-badge {{ $progClass }}">{{ $progress }}</span>
            </div>
        </div>

        @if(!empty($assess['secondary_diagnoses']))
        <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#64748B;margin-bottom:1mm;">{{ $language === 'fr' ? 'Diagnostics Secondaires' : 'Secondary Diagnoses' }}</div>
        <ul class="secondary-dx-list">
            @foreach($assess['secondary_diagnoses'] as $dx)
            <li>{{ $dx }}</li>
            @endforeach
        </ul>
        @endif

        <div class="clinical-impression">
            <strong>{{ $language === 'fr' ? 'Impression clinique:' : 'Clinical Impression:' }}</strong> {{ $assess['clinical_impression'] ?? '' }}
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- P — PLAN                                                          --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
$plan = $payload['plan'] ?? [
    'investigations' => ['Repeat FBC + CRP in 48h', 'Sputum culture — follow up sensitivity report', 'Blood glucose monitoring QID'],
    'medications' => [
        ['action'=>'continue','drug'=>'Amoxicillin-Clavulanate 1.2 g IV Q8H','detail'=>'Complete 5-day course'],
        ['action'=>'continue','drug'=>'Azithromycin 500 mg PO OD','detail'=>'Day 2 of 5'],
        ['action'=>'start','drug'=>'Salbutamol nebuliser 2.5 mg Q6H','detail'=>'For bronchospasm relief'],
        ['action'=>'change','drug'=>'Metformin 500 mg BD','detail'=>'Hold if contrast CT ordered'],
    ],
    'procedures' => ['Incentive spirometry QID', 'Deep breathing exercises with physiotherapy'],
    'consultations' => ['Pulmonology review — request sent', 'Nutrition/dietetics referral'],
    'nursing_orders' => ['Strict I/O chart', 'Oxygen via nasal cannulae 2L/min, titrate to SpO₂ ≥ 95%', '2-hourly repositioning', 'Adhere to MAR'],
    'diet' => 'High-protein, soft diet. Encourage oral fluids 2L/day.',
    'activity' => 'Bed rest with supervised ambulation to bathroom',
    'discharge_plan' => 'Target discharge Day 5–7 if apyrexial >48h, SpO₂ >95% on room air, tolerating oral antibiotics.',
];
@endphp
<div class="soap-section soap-p">
    <div class="soap-section-header">
        <div class="soap-letter">P</div>
        <div>
            <div class="soap-section-title">{{ $language === 'fr' ? 'Plan de Prise en Charge' : 'Management Plan' }}</div>
            <div class="soap-section-sub">{{ $language === 'fr' ? 'Ordres médicaux et plan thérapeutique' : 'Medical orders and therapeutic plan' }}</div>
        </div>
    </div>
    <div class="soap-body">
        <div class="plan-grid">
            {{-- Investigations --}}
            <div class="plan-card">
                <div class="plan-card-title">&#128300; {{ $language === 'fr' ? 'Examens Complémentaires' : 'Investigations' }}</div>
                <div class="plan-card-body">
                    <ul>
                        @foreach($plan['investigations'] ?? [] as $inv)
                        <li>{{ $inv }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Medications --}}
            <div class="plan-card">
                <div class="plan-card-title">&#128138; {{ $language === 'fr' ? 'Médicaments' : 'Medications' }}</div>
                <div class="plan-card-body">
                    <ul>
                        @foreach($plan['medications'] ?? [] as $m)
                        <li>
                            <span class="med-action-badge act-{{ $m['action'] ?? 'continue' }}">{{ strtoupper($m['action'] ?? 'continue') }}</span>
                            <strong>{{ $m['drug'] ?? '' }}</strong>
                            @if(!empty($m['detail'])) — <em>{{ $m['detail'] }}</em>@endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Procedures --}}
            <div class="plan-card">
                <div class="plan-card-title">&#128137; {{ $language === 'fr' ? 'Procédures' : 'Procedures' }}</div>
                <div class="plan-card-body">
                    <ul>
                        @foreach($plan['procedures'] ?? [] as $proc)
                        <li>{{ $proc }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Consultations --}}
            <div class="plan-card">
                <div class="plan-card-title">&#128101; {{ $language === 'fr' ? 'Consultations' : 'Consultations' }}</div>
                <div class="plan-card-body">
                    <ul>
                        @foreach($plan['consultations'] ?? [] as $cons)
                        <li>{{ $cons }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Nursing --}}
            <div class="plan-card">
                <div class="plan-card-title">&#9874; {{ $language === 'fr' ? 'Prescriptions Infirmières' : 'Nursing Orders' }}</div>
                <div class="plan-card-body">
                    <ul>
                        @foreach($plan['nursing_orders'] ?? [] as $no)
                        <li>{{ $no }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Diet & Activity --}}
            <div class="plan-card">
                <div class="plan-card-title">&#127825; {{ $language === 'fr' ? 'Régime & Activité' : 'Diet & Activity' }}</div>
                <div class="plan-card-body">
                    <ul>
                        <li><strong>{{ $language === 'fr' ? 'Régime:' : 'Diet:' }}</strong> {{ $plan['diet'] ?? '' }}</li>
                        <li><strong>{{ $language === 'fr' ? 'Activité:' : 'Activity:' }}</strong> {{ $plan['activity'] ?? '' }}</li>
                    </ul>
                </div>
            </div>

            {{-- Discharge plan --}}
            @if(!empty($plan['discharge_plan']))
            <div class="plan-card plan-card-full" style="border-color:#7C3AED;">
                <div class="plan-card-title" style="background:#4C1D95;">&#127968; {{ $language === 'fr' ? 'Planification de Sortie' : 'Discharge Planning' }}</div>
                <div class="plan-card-body">{{ $plan['discharge_plan'] }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── SIGNATURES ────────────────────────────────────────────────────── --}}
<div class="sig-grid">
    <div class="sig-box">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Auteur de la Note' : 'Note Author' }}</div>
        <div style="font-size:8.5px;margin-bottom:1.5mm;">
            <strong>{{ $payload['author'] ?? $issuer_name }}</strong> &nbsp;|&nbsp; Reg: {{ $payload['author_reg'] ?? '' }}<br>
            <span style="color:#64748B;">{{ $payload['author_role'] ?? $issuer_role }}</span>
        </div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $payload['author'] ?? $issuer_name }}</div>
        <div class="sig-role">{{ $issued_at }}</div>
    </div>

    @if(!empty($payload['countersigned_by']))
    <div class="sig-box countersign-box">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Contresigné par (Consultant)' : 'Countersigned By (Consultant)' }}</div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $payload['countersigned_by'] }}</div>
        <div class="sig-role">{{ $language === 'fr' ? 'Médecin titulaire' : 'Attending Physician' }} &nbsp;|&nbsp; {{ $issued_at }}</div>
    </div>
    @else
    <div class="sig-box" style="background:#F8FAFC;border-style:dashed;">
        <div class="sig-lbl">{{ $language === 'fr' ? 'Contresignature' : 'Countersignature' }}</div>
        <div style="font-size:8px;color:#94A3B8;font-style:italic;padding-top:2mm;">{{ $language === 'fr' ? 'Non requise — note de médecin senior' : 'Not required — senior physician note' }}</div>
    </div>
    @endif
</div>
@endsection
