@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Fiche de Transfusion Sanguine' : 'Blood Transfusion Record' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Dossier médico-légal de transfusion — BTR' : 'Medico-Legal Transfusion Record — BTR' }}
@endsection

@section('content')
<style>
    :root {
        --btr: #DC2626;
        --btr-light: #FEF2F2;
        --btr-mid: #FECACA;
        --btr-dark: #7F1D1D;
    }

    /* ── Alert header ───────────────────────────────────────── */
    .btr-header {
        background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
        color: #fff;
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 4mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btr-header h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .btr-header p { margin: 1mm 0 0; font-size: 10px; opacity: .85; }
    .btr-badges { display: flex; gap: 3mm; flex-direction: column; align-items: flex-end; }
    .btr-badge {
        background: rgba(255,255,255,.2);
        border: 1.5px solid rgba(255,255,255,.5);
        border-radius: 6px;
        padding: 1.5mm 4mm;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .8px;
        white-space: nowrap;
    }
    .btr-compat-ok   { background: rgba(16,185,129,.35); border-color: #6EE7B7; }
    .btr-compat-fail { background: rgba(239,68,68,.35);  border-color: #FCA5A5; }

    /* ── Section shells ────────────────────────────────────── */
    .btr-section-title {
        background: var(--btr);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 2mm 4mm;
        border-radius: 4px 4px 0 0;
        margin-top: 4mm;
        margin-bottom: 0;
    }
    .btr-section-body {
        border: 1.5px solid var(--btr-mid);
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 3.5mm;
        background: #FFFAFA;
    }

    /* ── Info grid ─────────────────────────────────────────── */
    .btr-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2mm 5mm;
    }
    .btr-info-item label {
        display: block;
        font-size: 8px;
        color: #64748B;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-bottom: .5mm;
    }
    .btr-info-item span {
        font-size: 10.5px;
        font-weight: 600;
        color: #0F172A;
    }

    /* ── Consent row ────────────────────────────────────────── */
    .consent-row {
        display: flex;
        align-items: center;
        gap: 3mm;
        background: var(--btr-light);
        border: 1.5px solid var(--btr-mid);
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 4mm;
        flex-wrap: wrap;
    }
    .consent-badge-yes {
        display: inline-block;
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #6EE7B7;
        border-radius: 9999px;
        padding: 1mm 3mm;
        font-size: 9px;
        font-weight: 800;
    }
    .consent-badge-no {
        display: inline-block;
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
        border-radius: 9999px;
        padding: 1mm 3mm;
        font-size: 9px;
        font-weight: 800;
    }

    /* ── Hb progress arrow ─────────────────────────────────── */
    .hb-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5mm;
        background: var(--btr-light);
        border: 1.5px solid var(--btr-mid);
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 4mm;
    }
    .hb-cell {
        text-align: center;
    }
    .hb-cell .hb-lbl {
        font-size: 8px;
        color: #9CA3AF;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 1mm;
    }
    .hb-cell .hb-val {
        font-size: 22px;
        font-weight: 900;
        color: var(--btr);
        font-family: monospace;
    }
    .hb-arrow {
        font-size: 28px;
        color: var(--btr);
        font-weight: 900;
    }
    .hb-cell.post .hb-val { color: #059669; }

    /* ── Blood bank reference card ─────────────────────────── */
    .bb-ref-card {
        display: inline-flex;
        align-items: center;
        gap: 3mm;
        border: 2px solid var(--btr);
        border-radius: 6px;
        background: var(--btr-light);
        padding: 2mm 4mm;
        margin-bottom: 4mm;
    }
    .bb-ref-card .icon { font-size: 18px; }
    .bb-ref-card .lbl { font-size: 8px; color: var(--btr); font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
    .bb-ref-card .val { font-size: 13px; font-weight: 800; color: var(--btr-dark); font-family: monospace; }

    /* ── Unit cards ─────────────────────────────────────────── */
    .unit-card {
        border: 1.5px solid var(--btr-mid);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .unit-card-head {
        background: var(--btr);
        color: #fff;
        padding: 2mm 3.5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 9.5px;
        font-weight: 700;
    }
    .unit-card-body { padding: 3mm 3.5mm; background: #FFFAFA; }
    .unit-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2mm 4mm;
        font-size: 9.5px;
        margin-bottom: 3mm;
    }
    .unit-meta-grid .lbl { font-size: 7.5px; color: #9CA3AF; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: .3mm; }
    .unit-meta-grid .val { font-weight: 600; color: #0F172A; }

    /* ── Observations table ─────────────────────────────────── */
    .obs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .obs-table th {
        background: #FEE2E2;
        color: var(--btr-dark);
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1.5mm 2mm;
        text-align: center;
        border: 1px solid var(--btr-mid);
    }
    .obs-table td {
        padding: 1.5mm 2mm;
        border: 1px solid #F3F4F6;
        text-align: center;
        color: #374151;
    }
    .obs-table .obs-phase {
        font-weight: 700;
        color: var(--btr);
        background: #FFF5F5;
        text-align: left;
        padding-left: 2.5mm;
    }

    /* ── Reaction section ───────────────────────────────────── */
    .reaction-ok {
        display: inline-flex;
        align-items: center;
        gap: 2mm;
        background: #D1FAE5;
        color: #065F46;
        border: 1.5px solid #6EE7B7;
        border-radius: 6px;
        padding: 2mm 4mm;
        font-size: 10px;
        font-weight: 700;
    }
    .reaction-alert {
        background: #FEE2E2;
        border: 1.5px solid var(--btr-mid);
        border-radius: 6px;
        padding: 3mm 4mm;
        font-size: 10px;
        color: var(--btr-dark);
    }
    .reaction-alert li { margin-bottom: 1mm; }
    .management-box {
        border-left: 3px solid var(--btr);
        padding: 2mm 3mm;
        background: var(--btr-light);
        border-radius: 0 4px 4px 0;
        font-size: 10px;
        color: #374151;
        margin-top: 3mm;
    }

    /* ── Dual signature ─────────────────────────────────────── */
    .dual-sig {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6mm;
        margin-top: 5mm;
    }
    .sig-box {
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        padding: 3mm;
        background: #FAFAFA;
    }
    .sig-box label {
        display: block;
        font-size: 8px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: .3px;
        font-weight: 700;
        margin-bottom: 1mm;
    }
    .sig-line { border-bottom: 1.5px solid #94A3B8; height: 10mm; margin-bottom: 1.5mm; }
    .sig-name { font-size: 10px; font-weight: 700; color: #0F172A; }
    .sig-role { font-size: 9px; color: #64748B; }
</style>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- 1. Red alert header: blood group + crossmatch badges       --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="btr-header">
    <div>
        <h2>Blood Transfusion Record</h2>
        <p>
            Patient: {{ $patient_name }}
            &nbsp;|&nbsp; Health ID: {{ $health_id }}
            &nbsp;|&nbsp; Date: {{ $payload['transfusion_date'] }}
        </p>
    </div>
    <div class="btr-badges">
        <span class="btr-badge">{{ $payload['patient_blood_group'] }}</span>
        @php
            $xmOk = strtolower($payload['patient_crossmatch_result']) === 'compatible';
        @endphp
        <span class="btr-badge {{ $xmOk ? 'btr-compat-ok' : 'btr-compat-fail' }}">
            Crossmatch: {{ $payload['patient_crossmatch_result'] }}
        </span>
    </div>
</div>

{{-- 2. Indication + consent row --}}
<div class="consent-row">
    <div style="flex:1;font-size:10px;color:#374151;">
        <span style="font-size:8px;font-weight:700;text-transform:uppercase;color:#9CA3AF;display:block;margin-bottom:.5mm;">Transfusion Indication</span>
        <strong>{{ $payload['transfusion_indication'] }}</strong>
    </div>
    <div style="display:flex;align-items:center;gap:2mm;">
        @if($payload['consent_obtained'])
            <span class="consent-badge-yes">&#10003; Consent Obtained</span>
        @else
            <span class="consent-badge-no">&#10007; No Consent Recorded</span>
        @endif
    </div>
    <div style="font-size:9.5px;color:#374151;text-align:right;">
        <div><span style="color:#9CA3AF;">By:</span> <strong>{{ $payload['consent_by'] }}</strong></div>
        <div style="font-size:9px;color:#9CA3AF;">{{ $payload['consent_datetime'] }}</div>
    </div>
</div>

{{-- 3. Pre → Post Hb progress --}}
<div class="hb-progress">
    <div class="hb-cell">
        <div class="hb-lbl">Pre-Transfusion Hb</div>
        <div class="hb-val">{{ $payload['pre_transfusion_hb'] }}</div>
    </div>
    <div class="hb-arrow">&#8594;</div>
    <div class="hb-cell post">
        <div class="hb-lbl">Post-Transfusion Hb</div>
        <div class="hb-val">
            @if($payload['post_transfusion_hb'])
                {{ $payload['post_transfusion_hb'] }}
            @else
                <span style="font-size:14px;color:#9CA3AF;">Pending</span>
            @endif
        </div>
    </div>
</div>

{{-- 4. Blood bank reference --}}
<div class="bb-ref-card">
    <span class="icon">&#128197;</span>
    <div>
        <div class="lbl">Blood Bank Reference</div>
        <div class="val">{{ $payload['blood_bank_ref'] }}</div>
    </div>
</div>

{{-- 5 + 6. Units transfused with observations per unit --}}
<div class="btr-section-title">Units Transfused / Unités Transfusées</div>
<div class="btr-section-body">
    @foreach($payload['units'] as $i => $unit)
        <div class="unit-card">
            <div class="unit-card-head">
                <span>Unit {{ $i + 1 }}: {{ $unit['unit_number'] }}</span>
                <span>{{ $unit['product_type'] }} &mdash; {{ $unit['volume_ml'] }} mL &mdash; {{ $unit['blood_group'] }}</span>
            </div>
            <div class="unit-card-body">
                <div class="unit-meta-grid">
                    <div>
                        <span class="lbl">Expiry Date</span>
                        <span class="val">{{ $unit['expiry_date'] }}</span>
                    </div>
                    <div>
                        <span class="lbl">Start Time</span>
                        <span class="val">{{ $unit['start_time'] }}</span>
                    </div>
                    <div>
                        <span class="lbl">End Time</span>
                        <span class="val">{{ $unit['end_time'] }}</span>
                    </div>
                    <div>
                        <span class="lbl">Duration</span>
                        <span class="val">{{ $unit['duration_mins'] }} min</span>
                    </div>
                    <div>
                        <span class="lbl">Rate (mL/hr)</span>
                        <span class="val">{{ $unit['rate_ml_hr'] }}</span>
                    </div>
                    <div style="grid-column:span 3;">
                        <span class="lbl">Administered By</span>
                        <span class="val">{{ $unit['administered_by'] }}</span>
                    </div>
                </div>

                {{-- Observations: Pre / Mid / Post --}}
                <table class="obs-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;width:18%;">Phase</th>
                            <th>BP (mmHg)</th>
                            <th>Pulse (/min)</th>
                            <th>Temp (°C)</th>
                            <th>SpO2 (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['pre_obs' => 'Pre-Transfusion', 'mid_obs' => 'Mid-Transfusion', 'post_obs' => 'Post-Transfusion'] as $obsKey => $obsLabel)
                            @if(isset($unit[$obsKey]) && $unit[$obsKey])
                                <tr>
                                    <td class="obs-phase">{{ $obsLabel }}</td>
                                    <td>{{ $unit[$obsKey]['bp'] ?? '—' }}</td>
                                    <td>{{ $unit[$obsKey]['pulse'] ?? '—' }}</td>
                                    <td>{{ $unit[$obsKey]['temp'] ?? '—' }}</td>
                                    <td>{{ $unit[$obsKey]['spo2'] ?? '—' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

{{-- 7. Transfusion reactions --}}
<div class="btr-section-title">Transfusion Reactions / Réactions Transfusionnelles</div>
<div class="btr-section-body">
    @php
        $hasReaction = !empty($payload['reactions']) &&
            !(count($payload['reactions']) === 1 && strtolower(trim($payload['reactions'][0])) === 'none');
    @endphp
    @if(!$hasReaction)
        <div class="reaction-ok">&#10003; No Adverse Reactions Observed</div>
    @else
        <div class="reaction-alert">
            <strong style="color:var(--btr);">&#9888; Adverse Reaction(s) Noted:</strong>
            <ul style="margin:1.5mm 0 0;padding-left:5mm;">
                @foreach($payload['reactions'] as $rxn)
                    <li>{{ $rxn }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 8. Reaction management --}}
    @if(!empty($payload['reaction_management']))
        <div class="management-box">
            <strong style="font-size:8.5px;text-transform:uppercase;color:var(--btr);letter-spacing:.3px;">Reaction Management:</strong>
            <div style="margin-top:.5mm;">{{ $payload['reaction_management'] }}</div>
        </div>
    @endif
</div>

{{-- 9. Dual signatures --}}
<div class="btr-section-title">Authorisation &amp; Verification</div>
<div class="btr-section-body">
    <div class="dual-sig">
        <div class="sig-box">
            <label>Ordering Physician / Médecin Prescripteur</label>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $payload['ordering_physician'] }}</div>
            <div class="sig-role">Ordering Physician</div>
            <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">Date: {{ $payload['transfusion_date'] }}</div>
        </div>
        <div class="sig-box">
            <label>Blood Bank Technician / Technicien Banque de Sang</label>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $payload['blood_bank_technician'] }}</div>
            <div class="sig-role">Blood Bank Technician</div>
            <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">Ref: {{ $payload['blood_bank_ref'] }}</div>
        </div>
    </div>
</div>
@endsection
