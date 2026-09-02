@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Feuille de Surveillance Infirmière' : 'Nursing Observation Chart' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Feuille de signes vitaux 24h — NRS' : '24-Hour Vital Signs Sheet — NRS' }}
@endsection

@section('content')
<style>
    :root {
        --nrs: #0891B2;
        --nrs-light: #ECFEFF;
        --nrs-mid: #A5F3FC;
        --nrs-dark: #0E4F6B;
    }

    /* ── Chart header ───────────────────────────────────────── */
    .nrs-header {
        background: linear-gradient(135deg, #0891B2 0%, #0369A1 100%);
        color: #fff;
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 4mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nrs-header h2 { margin: 0; font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
    .nrs-header p { margin: 1mm 0 0; font-size: 10px; opacity: .85; }
    .nrs-header-meta { text-align: right; font-size: 10px; }
    .nrs-header-meta .meta-row { margin-bottom: 1mm; }
    .nrs-header-meta .meta-lbl { opacity: .7; }
    .nrs-header-meta .meta-val { font-weight: 700; }

    /* ── Allergies strip ────────────────────────────────────── */
    .allergies-strip-none {
        background: #F0FDF4;
        border: 1px solid #A7F3D0;
        border-radius: 5px;
        padding: 2mm 4mm;
        font-size: 9.5px;
        color: #065F46;
        margin-bottom: 4mm;
        font-weight: 600;
    }
    .allergies-strip-alert {
        background: #FEF2F2;
        border: 2px solid #FCA5A5;
        border-radius: 5px;
        padding: 2mm 4mm;
        font-size: 9.5px;
        color: #7F1D1D;
        margin-bottom: 4mm;
        font-weight: 600;
    }
    .allergy-tag {
        display: inline-block;
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
        border-radius: 9999px;
        padding: .5mm 2.5mm;
        font-size: 9px;
        font-weight: 700;
        margin-left: 1.5mm;
    }

    /* ── Section shells ────────────────────────────────────── */
    .nrs-section-title {
        background: var(--nrs);
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
    .nrs-section-body {
        border: 1.5px solid var(--nrs-mid);
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 3.5mm;
        background: #FAFEFF;
    }

    /* ── Vital signs table ──────────────────────────────────── */
    .vs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    .vs-table th {
        background: var(--nrs);
        color: #fff;
        padding: 2mm 1.5mm;
        text-align: center;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
        border: 1px solid var(--nrs-dark);
        white-space: nowrap;
    }
    .vs-table th.col-time { text-align: left; }
    .vs-table td {
        padding: 1.5mm 1.5mm;
        border: 1px solid #E0F7FA;
        text-align: center;
        color: #1F2937;
        font-family: monospace;
        font-size: 9px;
    }
    .vs-table td.col-time { text-align: left; font-family: sans-serif; font-weight: 600; color: var(--nrs-dark); }
    .vs-table td.col-notes { text-align: left; font-family: sans-serif; font-size: 8.5px; color: #374151; }
    .vs-table tr:nth-child(even) td { background: var(--nrs-light); }

    /* Critical value highlights */
    .crit-high { color: #DC2626 !important; font-weight: 800 !important; background: #FEF2F2 !important; }
    .crit-low  { color: #7C3AED !important; font-weight: 800 !important; background: #EDE9FE !important; }

    /* ── Fluid balance card ─────────────────────────────────── */
    .fluid-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
    }
    .fluid-col-title {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--nrs-dark);
        margin-bottom: 2mm;
    }
    .fluid-entry-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .fluid-entry-table th {
        background: var(--nrs-mid);
        color: var(--nrs-dark);
        padding: 1.5mm 2mm;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: .3px;
        text-align: left;
        border-bottom: 1px solid var(--nrs);
    }
    .fluid-entry-table td {
        padding: 1.5mm 2mm;
        border-bottom: 1px solid #E0F7FA;
        color: #1F2937;
    }
    .fluid-total-row {
        display: flex;
        justify-content: space-between;
        padding: 2mm;
        background: var(--nrs-mid);
        border-radius: 4px;
        margin-top: 2mm;
        font-size: 10px;
        font-weight: 700;
        color: var(--nrs-dark);
    }
    .fluid-balance-box {
        margin-top: 3mm;
        text-align: center;
        border: 2px solid var(--nrs);
        border-radius: 6px;
        padding: 2.5mm;
    }
    .fluid-balance-box .fblbl { font-size: 8px; text-transform: uppercase; color: var(--nrs); font-weight: 700; letter-spacing: .4px; }
    .fluid-balance-box .fbval { font-size: 18px; font-weight: 900; font-family: monospace; }
    .fb-positive { color: #DC2626; }
    .fb-negative { color: #059669; }

    /* ── Medications given table ───────────────────────────── */
    .med-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .med-table th {
        background: var(--nrs);
        color: #fff;
        padding: 2mm 2.5mm;
        text-align: left;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
        border: 1px solid var(--nrs-dark);
    }
    .med-table td {
        padding: 2mm 2.5mm;
        border: 1px solid #E0F7FA;
        color: #0F172A;
    }
    .med-table tr:nth-child(even) td { background: var(--nrs-light); }

    /* ── Nursing notes ──────────────────────────────────────── */
    .notes-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .notes-table th {
        background: var(--nrs);
        color: #fff;
        padding: 2mm 2.5mm;
        text-align: left;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
        border: 1px solid var(--nrs-dark);
    }
    .notes-table td {
        padding: 2mm 2.5mm;
        border: 1px solid #E0F7FA;
        color: #0F172A;
        vertical-align: top;
    }
    .notes-table tr:nth-child(even) td { background: var(--nrs-light); }
    .notes-table .note-time { font-family: monospace; font-weight: 700; color: var(--nrs-dark); white-space: nowrap; }
    .notes-table .note-nurse { font-size: 8.5px; color: #64748B; font-style: italic; }

    /* ── Signature ──────────────────────────────────────────── */
    .nrs-sig-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 5mm;
        gap: 6mm;
    }
    .nrs-sig-block { flex: 1; }
    .nrs-sig-block label {
        display: block;
        font-size: 8px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: .3px;
        font-weight: 700;
        margin-bottom: 1mm;
    }
    .nrs-sig-line { border-bottom: 1.5px solid #94A3B8; height: 10mm; margin-bottom: 1.5mm; }
    .nrs-sig-name { font-size: 10px; font-weight: 700; color: #0F172A; }
    .nrs-sig-role { font-size: 9px; color: #64748B; }
</style>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- 1. Chart header                                            --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="nrs-header">
    <div>
        <h2>Nursing Observation Chart</h2>
        <p>
            Patient: {{ $patient_name }}
            &nbsp;|&nbsp; Health ID: {{ $health_id }}
            &nbsp;|&nbsp; DOB: {{ $patient_dob }}
        </p>
    </div>
    <div class="nrs-header-meta">
        <div class="meta-row"><span class="meta-lbl">Date: </span><span class="meta-val">{{ $payload['chart_date'] }}</span></div>
        <div class="meta-row"><span class="meta-lbl">Ward: </span><span class="meta-val">{{ $payload['ward'] }}</span></div>
        <div class="meta-row"><span class="meta-lbl">Bed: </span><span class="meta-val">{{ $payload['bed_number'] }}</span></div>
        <div class="meta-row"><span class="meta-lbl">Shift: </span><span class="meta-val">{{ $payload['shift'] }}</span></div>
        <div class="meta-row"><span class="meta-lbl">Diagnosis: </span><span class="meta-val">{{ $payload['admitting_diagnosis'] }}</span></div>
    </div>
</div>

{{-- 2. Allergies strip --}}
@if(empty($payload['allergies']))
    <div class="allergies-strip-none">&#10003; No Known Drug Allergies (NKDA)</div>
@else
    <div class="allergies-strip-alert">
        &#9888; ALLERGIES:
        @foreach($payload['allergies'] as $allergy)
            <span class="allergy-tag">{{ $allergy }}</span>
        @endforeach
    </div>
@endif

{{-- 3. VITAL SIGNS TABLE --}}
<div class="nrs-section-title">Vital Signs / Signes Vitaux — 24h Observations</div>
<div class="nrs-section-body" style="padding:0;">
    <table class="vs-table">
        <thead>
            <tr>
                <th class="col-time" style="min-width:14mm;">Time</th>
                <th>BP<br>(mmHg)</th>
                <th>Pulse<br>(/min)</th>
                <th>Temp<br>(°C)</th>
                <th>SpO2<br>(%)</th>
                <th>RR<br>(/min)</th>
                <th>GCS<br>E+V+M=T</th>
                <th>Pain<br>/10</th>
                <th>Urine<br>(mL)</th>
                <th class="col-notes" style="min-width:30mm;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['observations'] as $obs)
                @php
                    /* Parse systolic for highlighting */
                    $sysParts = explode('/', $obs['bp'] ?? '');
                    $sys = (int) ($sysParts[0] ?? 0);

                    /* Classify BP */
                    $bpCls = '';
                    if ($sys > 180) $bpCls = 'crit-high';
                    elseif ($sys > 0 && $sys < 90) $bpCls = 'crit-low';

                    /* Pulse */
                    $pulseCls = '';
                    $pv = (int) ($obs['pulse'] ?? 0);
                    if ($pv > 120) $pulseCls = 'crit-high';
                    elseif ($pv > 0 && $pv < 50) $pulseCls = 'crit-low';

                    /* Temp */
                    $tempCls = '';
                    $tv = (float) ($obs['temp'] ?? 0);
                    if ($tv > 38.5) $tempCls = 'crit-high';
                    elseif ($tv > 0 && $tv < 36.0) $tempCls = 'crit-low';

                    /* SpO2 */
                    $spo2Cls = '';
                    $sv = (int) ($obs['spo2'] ?? 100);
                    if ($sv < 90) $spo2Cls = 'crit-high';

                    /* GCS */
                    $gcse = $obs['gcs_e'] ?? '';
                    $gcsv = $obs['gcs_v'] ?? '';
                    $gcsm = $obs['gcs_m'] ?? '';
                    $gcst = $obs['gcs_total'] ?? '';
                    $gcsStr = ($gcse !== '' && $gcsv !== '' && $gcsm !== '')
                        ? "{$gcse}+{$gcsv}+{$gcsm}={$gcst}"
                        : ($gcst ?: '—');
                @endphp
                <tr>
                    <td class="col-time">{{ $obs['time'] ?? '—' }}</td>
                    <td class="{{ $bpCls }}">{{ $obs['bp'] ?? '—' }}</td>
                    <td class="{{ $pulseCls }}">{{ $obs['pulse'] ?? '—' }}</td>
                    <td class="{{ $tempCls }}">{{ $obs['temp'] ?? '—' }}</td>
                    <td class="{{ $spo2Cls }}">{{ $obs['spo2'] ?? '—' }}</td>
                    <td>{{ $obs['rr'] ?? '—' }}</td>
                    <td>{{ $gcsStr }}</td>
                    <td>{{ $obs['pain_score'] ?? '—' }}</td>
                    <td>{{ $obs['urine_output_ml'] ?? '—' }}</td>
                    <td class="col-notes">{{ $obs['notes'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="font-size:7.5px;color:#9CA3AF;padding:1.5mm 2mm;background:#F8FAFC;border-top:1px solid #E0F7FA;">
        &#9888; Critical value thresholds — Red: SpO2&lt;90%, BP Sys&gt;180 or &lt;90, Temp&gt;38.5°C, Pulse&gt;120; Purple: Temp&lt;36°C, Pulse&lt;50
    </div>
</div>

{{-- 4. FLUID BALANCE --}}
<div class="nrs-section-title">Fluid Balance / Bilan Hydrique</div>
<div class="nrs-section-body">
    <div class="fluid-grid">
        <div>
            <div class="fluid-col-title">&#8595; Intake / Apports</div>
            @if(!empty($payload['fluid_balance']['intake']))
                <table class="fluid-entry-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type / Route</th>
                            <th style="text-align:right;">mL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payload['fluid_balance']['intake'] as $entry)
                            <tr>
                                <td style="white-space:nowrap;font-family:monospace;">{{ $entry['time'] }}</td>
                                <td>{{ $entry['type'] }}</td>
                                <td style="text-align:right;font-family:monospace;font-weight:600;">{{ $entry['amount_ml'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="fluid-total-row">
                <span>Total Intake</span>
                <span>{{ $payload['fluid_balance']['total_intake_ml'] }} mL</span>
            </div>
        </div>
        <div>
            <div class="fluid-col-title">&#8593; Output / Pertes</div>
            @if(!empty($payload['fluid_balance']['output']))
                <table class="fluid-entry-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th style="text-align:right;">mL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payload['fluid_balance']['output'] as $entry)
                            <tr>
                                <td style="white-space:nowrap;font-family:monospace;">{{ $entry['time'] }}</td>
                                <td>{{ $entry['type'] }}</td>
                                <td style="text-align:right;font-family:monospace;font-weight:600;">{{ $entry['amount_ml'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="fluid-total-row">
                <span>Total Output</span>
                <span>{{ $payload['fluid_balance']['total_output_ml'] }} mL</span>
            </div>
        </div>
    </div>
    @php
        $balance = $payload['fluid_balance']['balance_ml'];
        $balCls  = $balance > 0 ? 'fb-positive' : 'fb-negative';
        $balSign = $balance > 0 ? '+' : '';
    @endphp
    <div class="fluid-balance-box" style="margin-top:4mm;">
        <div class="fblbl">Net Fluid Balance (Intake &minus; Output)</div>
        <div class="fbval {{ $balCls }}">{{ $balSign }}{{ $balance }} mL</div>
        @if($balance > 1000)
            <div style="font-size:8.5px;color:#92400E;margin-top:1mm;font-weight:600;">&#9888; Significantly positive balance — review fluid orders</div>
        @elseif($balance < -500)
            <div style="font-size:8.5px;color:#7C3AED;margin-top:1mm;font-weight:600;">&#9888; Negative balance — monitor for dehydration</div>
        @endif
    </div>
</div>

{{-- 5. MEDICATIONS GIVEN --}}
@if(!empty($payload['medications_given']))
    <div class="nrs-section-title">Medications Given / Médicaments Administrés</div>
    <div class="nrs-section-body" style="padding:0;">
        <table class="med-table">
            <thead>
                <tr>
                    <th style="width:14mm;">Time</th>
                    <th>Drug / Médicament</th>
                    <th>Dose</th>
                    <th>Route</th>
                    <th>Given By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['medications_given'] as $med)
                    <tr>
                        <td style="font-family:monospace;font-weight:600;color:var(--nrs-dark);">{{ $med['time'] }}</td>
                        <td style="font-weight:600;">{{ $med['drug'] }}</td>
                        <td>{{ $med['dose'] }}</td>
                        <td>{{ $med['route'] }}</td>
                        <td style="font-size:9px;color:#64748B;">{{ $med['given_by'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- 6. NURSING NOTES --}}
@if(!empty($payload['nursing_notes']))
    <div class="nrs-section-title">Nursing Notes / Notes Infirmières</div>
    <div class="nrs-section-body" style="padding:0;">
        <table class="notes-table">
            <thead>
                <tr>
                    <th style="width:14mm;">Time</th>
                    <th>Note</th>
                    <th style="width:30mm;">Nurse</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['nursing_notes'] as $note)
                    <tr>
                        <td class="note-time">{{ $note['time'] }}</td>
                        <td style="line-height:1.5;">{{ $note['note'] }}</td>
                        <td class="note-nurse">{{ $note['nurse'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- 7. Ward nurse signature --}}
<div class="nrs-sig-row">
    <div class="nrs-sig-block">
        <label>Ward Nurse / Infirmier(e) de Service</label>
        <div class="nrs-sig-line"></div>
        <div class="nrs-sig-name">{{ $payload['ward_nurse'] }}</div>
        <div class="nrs-sig-role">Ward Nurse — {{ $payload['ward'] }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:.5mm;">Shift: {{ $payload['shift'] }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:.5mm;">Date: {{ $payload['chart_date'] }}</div>
    </div>
    <div style="text-align:right;font-size:9px;color:#9CA3AF;max-width:80mm;line-height:1.4;">
        This chart is a legal medical record. All entries must be legible, timed, signed, and accurate.
        Alterations must be struck through once with initials — do not erase.
    </div>
</div>
@endsection
