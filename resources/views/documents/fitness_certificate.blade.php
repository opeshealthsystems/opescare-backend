@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Certificat d\'Aptitude Médicale' : 'Medical Fitness Certificate' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Certificat officiel d\'aptitude — FIT' : 'Official Medical Fitness Clearance — FIT' }}
@endsection

@section('content')
<style>
    :root {
        --fit: #059669;
        --fit-light: #ECFDF5;
        --fit-mid: #A7F3D0;
        --fit-dark: #064E3B;
    }

    /* ── Header strip ───────────────────────────────────────── */
    .fit-header-strip {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: #fff;
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 4mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .fit-header-strip h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .fit-header-strip p { margin: 1mm 0 0; font-size: 10px; opacity: .85; }
    .fit-purpose-badge {
        background: rgba(255,255,255,.2);
        border: 1.5px solid rgba(255,255,255,.5);
        border-radius: 6px;
        padding: 2mm 4mm;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        white-space: nowrap;
    }

    /* ── Section shells ────────────────────────────────────── */
    .fit-section-title {
        background: var(--fit);
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
    .fit-section-body {
        border: 1.5px solid var(--fit-mid);
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 3.5mm;
        background: #FAFAFA;
    }

    /* ── Vitals pills ───────────────────────────────────────── */
    .vitals-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2.5mm;
    }
    .vital-pill {
        border: 1px solid #D1FAE5;
        border-radius: 5px;
        background: var(--fit-light);
        padding: 2mm 2.5mm;
        text-align: center;
    }
    .vital-pill label {
        display: block;
        font-size: 7.5px;
        color: var(--fit);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .2px;
        margin-bottom: 1mm;
    }
    .vital-pill span {
        font-size: 12px;
        font-weight: 700;
        color: var(--fit-dark);
    }

    /* ── Tables ─────────────────────────────────────────────── */
    .fit-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    .fit-table th {
        background: #D1FAE5;
        color: #065F46;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 2.5mm;
        text-align: left;
        border-bottom: 2px solid var(--fit-mid);
    }
    .fit-table td {
        padding: 2.5mm;
        border-bottom: 1px solid #E2E8F0;
        color: #0F172A;
    }
    .fit-table tr:last-child td { border-bottom: none; }
    .badge-normal {
        display: inline-block;
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #6EE7B7;
        border-radius: 9999px;
        padding: .5mm 2.5mm;
        font-size: 8.5px;
        font-weight: 700;
    }
    .badge-abnormal {
        display: inline-block;
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
        border-radius: 9999px;
        padding: .5mm 2.5mm;
        font-size: 8.5px;
        font-weight: 700;
    }

    /* ── VERDICT ────────────────────────────────────────────── */
    .verdict-container { margin: 6mm 0; text-align: center; }
    .verdict-box {
        display: inline-block;
        padding: 5mm 12mm;
        border-radius: 10px;
        border: 3px solid;
        min-width: 80mm;
    }
    .verdict-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 2mm;
        opacity: .75;
    }
    .verdict-text {
        font-size: 28px;
        font-weight: 900;
        letter-spacing: 3px;
        text-transform: uppercase;
        line-height: 1;
    }
    .v-fit         { background: linear-gradient(135deg,#ECFDF5,#D1FAE5); border-color:#059669; color:#065F46; }
    .v-restricted  { background: linear-gradient(135deg,#FFFBEB,#FEF3C7); border-color:#D97706; color:#92400E; }
    .v-unfit       { background: linear-gradient(135deg,#FFF1F2,#FEE2E2); border-color:#DC2626; color:#7F1D1D; }

    .restrictions-list {
        margin-top: 3mm;
        text-align: left;
        display: inline-block;
        background: rgba(255,255,255,.6);
        border-radius: 5px;
        padding: 2mm 3mm;
        list-style: disc;
        padding-left: 5mm;
    }
    .restrictions-list li { font-size: 10px; font-weight: 600; color: #78350F; margin-bottom: .5mm; }

    /* ── Fit-for highlighted box ───────────────────────────── */
    .fit-for-box {
        border: 2px solid var(--fit);
        background: var(--fit-light);
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-top: 4mm;
        font-size: 11px;
        font-weight: 700;
        color: var(--fit-dark);
    }
    .fit-for-box .fflbl {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--fit);
        letter-spacing: .4px;
        display: block;
        margin-bottom: 1mm;
    }

    /* ── Validity + recipient ──────────────────────────────── */
    .validity-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1.5px solid var(--fit-mid);
        border-radius: 6px;
        background: var(--fit-light);
        padding: 3mm 4mm;
        margin-top: 4mm;
        font-size: 10px;
    }
    .validity-row .vlbl { font-size: 8.5px; color: var(--fit); font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .validity-row .vdate { font-size: 14px; font-weight: 800; color: var(--fit-dark); }
    .review-notice {
        background: #FEF3C7;
        border: 1.5px solid #FCD34D;
        border-radius: 5px;
        padding: 2mm 3mm;
        font-size: 9.5px;
        color: #92400E;
        font-weight: 600;
        margin-top: 3mm;
    }
    .recipient-box {
        border-left: 3px solid var(--fit);
        padding: 2mm 3mm;
        background: var(--fit-light);
        border-radius: 0 4px 4px 0;
        font-size: 10px;
        color: var(--fit-dark);
        margin-top: 3mm;
    }
    .recipient-box .rlbl {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--fit);
        letter-spacing: .3px;
        display: block;
        margin-bottom: .5mm;
    }

    /* ── Signature area ─────────────────────────────────────── */
    .fit-footer-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 6mm;
        margin-top: 4mm;
    }
    .fit-sig-block { flex: 1; font-size: 10px; }
    .fit-sig-block label {
        display: block;
        font-size: 8px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: .3px;
        font-weight: 700;
        margin-bottom: 1mm;
    }
    .fit-sig-line { border-bottom: 1.5px solid #94A3B8; height: 10mm; margin-bottom: 1.5mm; }
    .fit-sig-name { font-size: 10px; font-weight: 700; color: #0F172A; }
    .fit-sig-role { font-size: 9px; color: #64748B; }
    .certified-seal {
        width: 26mm;
        height: 26mm;
        background: var(--fit-light);
        border: 3px solid var(--fit);
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--fit-dark);
        font-size: 7px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        line-height: 1.5;
        flex-shrink: 0;
    }
</style>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- 1. Green header with purpose badge                         --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="fit-header-strip">
    <div>
        <h2>Medical Fitness Certificate</h2>
        <p>
            Examination Date: {{ $payload['examination_date'] }}
            &nbsp;|&nbsp; Patient: {{ $patient_name }}
            &nbsp;|&nbsp; DOB: {{ $patient_dob }}
        </p>
    </div>
    <div class="fit-purpose-badge">{{ $payload['certificate_purpose'] }}</div>
</div>

{{-- 2. Vitals summary --}}
<div class="fit-section-title">Vital Signs / Signes Vitaux</div>
<div class="fit-section-body">
    <div class="vitals-grid">
        <div class="vital-pill">
            <label>Blood Pressure</label>
            <span>{{ $payload['vitals']['bp'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Pulse (bpm)</label>
            <span>{{ $payload['vitals']['pulse'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Weight (kg)</label>
            <span>{{ $payload['vitals']['weight_kg'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Height (cm)</label>
            <span>{{ $payload['vitals']['height_cm'] }}</span>
        </div>
        <div class="vital-pill">
            <label>BMI</label>
            <span>{{ $payload['vitals']['bmi'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Vision Right</label>
            <span>{{ $payload['vitals']['vision_right'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Vision Left</label>
            <span>{{ $payload['vitals']['vision_left'] }}</span>
        </div>
        <div class="vital-pill">
            <label>Hearing</label>
            <span>{{ $payload['vitals']['hearing'] }}</span>
        </div>
    </div>
</div>

{{-- 3. Systems examined --}}
<div class="fit-section-title">Systems Examined / Systèmes Examinés</div>
<div class="fit-section-body" style="padding:0;">
    <table class="fit-table">
        <thead>
            <tr>
                <th style="width:30%;">System</th>
                <th>Finding</th>
                <th style="width:22%;text-align:center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['systems_examined'] as $sys)
                <tr>
                    <td style="font-weight:600;">{{ $sys['system'] }}</td>
                    <td>{{ $sys['finding'] }}</td>
                    <td style="text-align:center;">
                        @if($sys['normal'])
                            <span class="badge-normal">&#10003; Normal</span>
                        @else
                            <span class="badge-abnormal">&#10007; Abnormal</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 4. Investigations done --}}
@if(!empty($payload['investigations_done']))
    <div class="fit-section-title">Investigations / Examens Complémentaires</div>
    <div class="fit-section-body" style="padding:0;">
        <table class="fit-table">
            <thead>
                <tr>
                    <th style="width:35%;">Test</th>
                    <th>Result</th>
                    <th style="width:22%;text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['investigations_done'] as $inv)
                    <tr>
                        <td style="font-weight:600;">{{ $inv['test'] }}</td>
                        <td>{{ $inv['result'] }}</td>
                        <td style="text-align:center;">
                            @if($inv['normal'])
                                <span class="badge-normal">&#10003; Normal</span>
                            @else
                                <span class="badge-abnormal">&#10007; Abnormal</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- 5. Examination findings narrative --}}
@if(!empty($payload['examination_findings']))
    <div class="fit-section-title">Examination Findings</div>
    <div class="fit-section-body" style="font-size:10px;color:#374151;line-height:1.5;">
        {{ $payload['examination_findings'] }}
    </div>
@endif

{{-- CENTRAL VERDICT --}}
<div class="verdict-container">
    @php
        $verdict = $payload['fitness_verdict'];
        $vCls = match($verdict) {
            'FIT'                  => 'v-fit',
            'FIT WITH RESTRICTIONS' => 'v-restricted',
            default                => 'v-unfit',
        };
    @endphp
    <div class="verdict-box {{ $vCls }}">
        <div class="verdict-label">Medical Fitness Verdict / Verdict d'Aptitude</div>
        <div class="verdict-text">
            @if($verdict === 'FIT')&#10003;&nbsp;@endif
            @if(in_array($verdict, ['TEMPORARILY UNFIT', 'UNFIT']))&#10007;&nbsp;@endif
            @if($verdict === 'FIT WITH RESTRICTIONS')&#9888;&nbsp;@endif
            {{ $verdict }}
        </div>
        @if($verdict === 'FIT WITH RESTRICTIONS' && !empty($payload['restrictions']))
            <ul class="restrictions-list">
                @foreach($payload['restrictions'] as $r)
                    <li>{{ $r }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- 6. Restrictions (if any, displayed below verdict too) --}}
{{-- 7. Fit For highlighted text --}}
@if(!empty($payload['fit_for']))
    <div class="fit-for-box">
        <span class="fflbl">Certified Fit For / Apte à exercer:</span>
        {{ $payload['fit_for'] }}
    </div>
@endif

{{-- 8. Valid until + review required + intended recipient --}}
<div class="validity-row">
    <div>
        <div class="vlbl">Certificate Valid Until / Valide Jusqu'au</div>
        <div class="vdate">{{ $payload['valid_until'] }}</div>
    </div>
    <div style="text-align:right;font-size:10px;color:#064E3B;">
        <div style="font-weight:600;">Examination Date: {{ $payload['examination_date'] }}</div>
        <div style="font-size:9px;color:#059669;">Document No: {{ $document_number }}</div>
    </div>
</div>

@if($payload['review_required'])
    <div class="review-notice">
        &#9888; Review Required
        @if($payload['review_date'])
            — Review Date: <strong>{{ $payload['review_date'] }}</strong>
        @endif
    </div>
@endif

@if(!empty($payload['intended_recipient']))
    <div class="recipient-box">
        <span class="rlbl">Intended Recipient / Destinataire</span>
        {{ $payload['intended_recipient'] }}
    </div>
@endif

{{-- 9. Physician signature + medical stamp --}}
<div class="fit-footer-row">
    <div class="fit-sig-block">
        <label>Examining Physician / Médecin Examinateur</label>
        <div class="fit-sig-line"></div>
        <div class="fit-sig-name">{{ $issuer_name }}</div>
        <div class="fit-sig-role">{{ $issuer_role }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">{{ $facility_name }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:.5mm;">Date: {{ $payload['examination_date'] }}</div>
    </div>
    <div class="certified-seal">
        <div>OPESCARE</div>
        <div>CERTIFIED</div>
        <div>FITNESS</div>
        <div style="font-size:5.5px;margin-top:1mm;">{{ $facility_name }}</div>
    </div>
</div>
@endsection
