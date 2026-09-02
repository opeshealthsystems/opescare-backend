@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Formulaire de Demande de Remboursement' : 'Insurance Claim Form' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Demande officielle de remboursement — CLM' : 'Official Insurance Claim Submission — CLM' }}
@endsection

@section('content')
<style>
    :root {
        --clm: #0369A1;
        --clm-light: #E0F2FE;
        --clm-dark: #075985;
        --clm-mid: #BAE6FD;
    }

    /* ── Section shells ────────────────────────────────────── */
    .clm-section-title {
        background: var(--clm);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 2mm 4mm;
        border-radius: 4px 4px 0 0;
        margin-top: 5mm;
        margin-bottom: 0;
    }
    .clm-section-body {
        border: 1.5px solid var(--clm-mid);
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 3.5mm;
        background: #FAFAFA;
        margin-bottom: 0;
    }

    /* ── Two-column header ─────────────────────────────────── */
    .clm-header-cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 4mm;
    }
    .clm-panel {
        border: 1.5px solid var(--clm-mid);
        border-radius: 6px;
        overflow: hidden;
    }
    .clm-panel-head {
        background: var(--clm);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 2mm 3.5mm;
    }
    .clm-panel-body {
        background: var(--clm-light);
        padding: 3mm 3.5mm;
        font-size: 10px;
        color: #0C4A6E;
    }
    .clm-panel-body .r { margin-bottom: 1mm; }
    .clm-panel-body .lbl { color: #0369A1; font-weight: 500; }
    .clm-panel-body .val { font-weight: 700; font-family: monospace; font-size: 10.5px; }

    /* ── Status badge ──────────────────────────────────────── */
    .clm-status-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #F0F9FF;
        border: 1.5px solid var(--clm-mid);
        border-radius: 6px;
        padding: 2mm 4mm;
        margin-bottom: 4mm;
        font-size: 10px;
    }
    .status-badge {
        display: inline-block;
        padding: 1.5mm 4mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .s-submitted   { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .s-review      { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
    .s-approved    { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .s-partial     { background: #FEF9C3; color: #854D0E; border: 1px solid #FDE68A; }
    .s-rejected    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

    /* ── Info grid ─────────────────────────────────────────── */
    .clm-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2mm 5mm;
    }
    .clm-info-item label {
        display: block;
        font-size: 8px;
        color: #64748B;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-bottom: .5mm;
    }
    .clm-info-item span {
        font-size: 10.5px;
        font-weight: 600;
        color: #0F172A;
    }

    /* ── Encounter badge ────────────────────────────────────── */
    .enc-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .enc-inpatient  { background: #EDE9FE; color: #5B21B6; border: 1px solid #C4B5FD; }
    .enc-outpatient { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .enc-emergency  { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

    /* ── ICD-10 highlight ───────────────────────────────────── */
    .icd-primary-box {
        background: var(--clm-light);
        border-left: 4px solid var(--clm);
        padding: 2.5mm 3.5mm;
        border-radius: 3px;
        margin-bottom: 2mm;
    }
    .icd-primary-box .icd-lbl {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--clm-dark);
        letter-spacing: .4px;
    }
    .icd-primary-box .icd-val {
        font-size: 11px;
        font-weight: 700;
        color: #0C4A6E;
        margin-top: 1mm;
    }
    .sec-dx-list { padding-left: 4mm; margin: 1.5mm 0 0; }
    .sec-dx-list li { font-size: 10px; color: #334155; margin-bottom: .5mm; }

    /* ── Services table ─────────────────────────────────────── */
    .clm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .clm-table th {
        background: var(--clm);
        color: #fff;
        padding: 2mm 2.5mm;
        text-align: left;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
        border: 1px solid var(--clm-dark);
    }
    .clm-table td {
        padding: 2.5mm;
        border: 1px solid #E2E8F0;
        color: #0F172A;
        vertical-align: top;
    }
    .clm-table tr:nth-child(even) td { background: var(--clm-light); }
    .num { text-align: right; font-family: monospace; }
    .pending-badge {
        display: inline-block;
        background: #FEF3C7;
        color: #92400E;
        font-size: 8px;
        padding: .5mm 2mm;
        border-radius: 8px;
        font-weight: 700;
        border: 1px solid #FCD34D;
    }

    /* ── Financial summary ──────────────────────────────────── */
    .clm-financial-box {
        border: 2px solid var(--clm);
        border-radius: 8px;
        overflow: hidden;
        margin: 5mm 0;
    }
    .clm-financial-box-head {
        background: var(--clm);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 2.5mm 4mm;
    }
    .clm-fin-rows { padding: 3mm 4mm; }
    .clm-fin-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5mm 0;
        border-bottom: 1px dashed #E2E8F0;
        font-size: 11px;
    }
    .clm-fin-row:last-child { border-bottom: none; }
    .clm-fin-row.net {
        background: var(--clm);
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        margin: 2mm -4mm -3mm -4mm;
        padding: 3mm 4mm;
    }
    .fin-lbl { color: #334155; }
    .fin-val { font-weight: 700; color: var(--clm); font-family: monospace; font-size: 12px; }
    .clm-fin-row.net .fin-lbl,
    .clm-fin-row.net .fin-val { color: #fff; font-size: 13px; }

    /* ── Supporting docs ────────────────────────────────────── */
    .doc-checklist { list-style: none; padding: 0; margin: 2mm 0 0; }
    .doc-checklist li { font-size: 10px; color: #374151; margin-bottom: 1mm; }
    .doc-checklist li::before { content: "\2610  "; color: var(--clm); font-size: 12px; }

    /* ── Declaration ────────────────────────────────────────── */
    .declaration-box {
        border: 1.5px solid var(--clm-mid);
        border-radius: 6px;
        background: #F8FAFF;
        padding: 3.5mm 4mm;
        font-size: 10px;
        color: #0C4A6E;
        font-style: italic;
        line-height: 1.5;
        margin-top: 3mm;
    }

    /* ── Signature grid ─────────────────────────────────────── */
    .clm-sig-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6mm;
        margin-top: 4mm;
    }
    .sig-box {
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        padding: 3mm;
        background: #FAFAFA;
        font-size: 10px;
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
    .provider-stamp {
        text-align: center;
        border: 2px solid var(--clm);
        border-radius: 50%;
        width: 22mm;
        height: 22mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1mm;
        font-size: 7px;
        color: var(--clm);
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.4;
    }

    /* ── Bank details ────────────────────────────────────────── */
    .bank-box {
        border: 1.5px solid var(--clm-mid);
        border-radius: 6px;
        background: var(--clm-light);
        padding: 3mm 4mm;
        font-size: 10px;
    }
    .bank-box label {
        display: block;
        font-size: 8px;
        color: var(--clm-dark);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-bottom: 1mm;
    }
    .bank-val { font-size: 11px; font-weight: 700; color: #0C4A6E; font-family: monospace; }

    /* ── Insurer-use-only box ───────────────────────────────── */
    .insurer-use-box {
        border: 2px dashed #94A3B8;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-top: 5mm;
    }
    .insurer-use-box .iub-title {
        font-size: 8.5px;
        text-transform: uppercase;
        font-weight: 700;
        color: #9CA3AF;
        letter-spacing: .6px;
        margin-bottom: 3mm;
    }
    .iub-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 3mm 4mm;
    }
    .iub-field {
        font-size: 8.5px;
        color: #6B7280;
        border-bottom: 1px solid #E5E7EB;
        padding-bottom: 8mm;
    }
</style>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- 1. Two-column header: Provider | Insurer                   --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="clm-header-cols">
    <div class="clm-panel">
        <div class="clm-panel-head">Healthcare Provider</div>
        <div class="clm-panel-body">
            <div class="r"><span class="lbl">Facility: </span><span>{{ $facility_name }}</span></div>
            <div class="r"><span class="lbl">Licence: </span><span class="val">{{ $facility_license }}</span></div>
            <div class="r"><span class="lbl">Provider Reg: </span><span class="val">{{ $payload['provider_reg_number'] }}</span></div>
            <div class="r"><span class="lbl">Claim No: </span><span class="val">{{ $payload['claim_number'] }}</span></div>
            <div class="r"><span class="lbl">Submission Date: </span><span>{{ $payload['submission_date'] }}</span></div>
        </div>
    </div>
    <div class="clm-panel">
        <div class="clm-panel-head">Insurer / Assureur</div>
        <div class="clm-panel-body">
            <div class="r"><span class="lbl">Insurer: </span><span style="font-weight:700;">{{ $payload['insurer_name'] }}</span></div>
            <div class="r"><span class="lbl">Branch: </span><span>{{ $payload['insurer_branch'] }}</span></div>
            @if($payload['insurer_address'])
                <div class="r"><span class="lbl">Address: </span><span>{{ $payload['insurer_address'] }}</span></div>
            @endif
            <div class="r"><span class="lbl">Policy No: </span><span class="val">{{ $payload['policy_number'] }}</span></div>
        </div>
    </div>
</div>

{{-- 2. Claim status bar --}}
<div class="clm-status-bar">
    <div>
        <strong style="font-size:11px;color:var(--clm);">INSURANCE CLAIM FORM</strong>
        <span style="color:#64748B;font-size:10px;margin-left:4mm;">Document No: {{ $document_number }}</span>
    </div>
    <div style="display:flex;align-items:center;gap:3mm;">
        @php
            $sMap = [
                'Submitted'          => 's-submitted',
                'Under Review'       => 's-review',
                'Approved'           => 's-approved',
                'Partially Approved' => 's-partial',
                'Rejected'           => 's-rejected',
            ];
            $sCls = $sMap[$payload['claim_status']] ?? 's-submitted';
        @endphp
        <span class="status-badge {{ $sCls }}">{{ $payload['claim_status'] }}</span>
        <span style="font-size:9.5px;color:#475569;">Submitted: {{ $payload['submission_date'] }}</span>
    </div>
</div>

{{-- 3. Patient / Policy --}}
<div class="clm-section-title">Patient &amp; Policy Information</div>
<div class="clm-section-body">
    <div class="clm-info-grid">
        <div class="clm-info-item">
            <label>Policy Holder</label>
            <span>{{ $payload['policy_holder'] }}</span>
        </div>
        <div class="clm-info-item">
            <label>Policy Number</label>
            <span style="font-family:monospace;">{{ $payload['policy_number'] }}</span>
        </div>
        <div class="clm-info-item">
            <label>Employer / Affiliation</label>
            <span>{{ $payload['employer'] }}</span>
        </div>
        <div class="clm-info-item">
            <label>Patient Name</label>
            <span>{{ $patient_name }}</span>
        </div>
        <div class="clm-info-item">
            <label>Date of Birth</label>
            <span>{{ $patient_dob }}</span>
        </div>
        <div class="clm-info-item">
            <label>Sex</label>
            <span>{{ $patient_sex }}</span>
        </div>
        <div class="clm-info-item">
            <label>Health ID</label>
            <span style="font-family:monospace;">{{ $health_id }}</span>
        </div>
    </div>
</div>

{{-- 4. Encounter details --}}
<div class="clm-section-title">Encounter Details</div>
<div class="clm-section-body">
    <div class="clm-info-grid">
        <div class="clm-info-item">
            <label>Encounter Date</label>
            <span>{{ $payload['encounter_date'] }}</span>
        </div>
        <div class="clm-info-item">
            <label>Encounter Type</label>
            <span>
                @php
                    $encCls = [
                        'Inpatient'  => 'enc-inpatient',
                        'Outpatient' => 'enc-outpatient',
                        'Emergency'  => 'enc-emergency',
                    ][$payload['encounter_type']] ?? 'enc-outpatient';
                @endphp
                <span class="enc-badge {{ $encCls }}">{{ $payload['encounter_type'] }}</span>
            </span>
        </div>
        @if($payload['admission_date'])
            <div class="clm-info-item">
                <label>Admission Date</label>
                <span>{{ $payload['admission_date'] }}</span>
            </div>
        @endif
        @if($payload['discharge_date'])
            <div class="clm-info-item">
                <label>Discharge Date</label>
                <span>{{ $payload['discharge_date'] }}</span>
            </div>
        @endif
    </div>
</div>

{{-- 5. Diagnoses --}}
<div class="clm-section-title">Diagnoses / Diagnostics</div>
<div class="clm-section-body">
    <div class="icd-primary-box">
        <div class="icd-lbl">Primary Diagnosis (ICD-10)</div>
        <div class="icd-val">{{ $payload['primary_diagnosis'] }}</div>
    </div>
    @if(!empty($payload['secondary_diagnoses']))
        <div style="font-size:8.5px;font-weight:700;color:#64748B;text-transform:uppercase;margin-top:2mm;">Secondary Diagnoses:</div>
        <ul class="sec-dx-list">
            @foreach($payload['secondary_diagnoses'] as $dx)
                <li>{{ $dx }}</li>
            @endforeach
        </ul>
    @endif
</div>

{{-- 6. Services table --}}
<div class="clm-section-title">Services &amp; Procedures Billed</div>
<div class="clm-section-body" style="padding:0;">
    <table class="clm-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>CPT Code</th>
                <th>ICD-10</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Unit Cost</th>
                <th style="text-align:right;">Claimed</th>
                <th style="text-align:right;">Approved</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['services'] as $svc)
                <tr>
                    <td>{{ $svc['description'] }}</td>
                    <td style="font-family:monospace;font-size:9px;">{{ $svc['cpt_code'] }}</td>
                    <td style="font-family:monospace;font-size:9px;">{{ $svc['icd10_code'] }}</td>
                    <td class="num" style="text-align:center;">{{ $svc['quantity'] }}</td>
                    <td class="num">{{ $svc['unit_cost'] }}</td>
                    <td class="num">{{ $svc['claimed_amount'] }}</td>
                    <td class="num">
                        @if(isset($svc['approved_amount']) && $svc['approved_amount'] !== null)
                            {{ $svc['approved_amount'] }}
                        @else
                            <span class="pending-badge">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 7. Financial summary --}}
<div class="clm-financial-box">
    <div class="clm-financial-box-head">Financial Summary — Récapitulatif Financier</div>
    <div class="clm-fin-rows">
        <div class="clm-fin-row">
            <span class="fin-lbl">Subtotal Claimed</span>
            <span class="fin-val">{{ $payload['subtotal_claimed'] }}</span>
        </div>
        <div class="clm-fin-row">
            <span class="fin-lbl">Patient Co-pay (deducted)</span>
            <span class="fin-val" style="color:#DC2626;">&minus; {{ $payload['patient_copay'] }}</span>
        </div>
        <div class="clm-fin-row net">
            <span class="fin-lbl">NET CLAIMED FROM INSURER</span>
            <span class="fin-val">{{ $payload['net_claimed'] }}</span>
        </div>
    </div>
</div>

{{-- 8. Supporting documents --}}
<div class="clm-section-title">Supporting Documents / Pièces Justificatives</div>
<div class="clm-section-body">
    @if(!empty($payload['supporting_documents']))
        <ul class="doc-checklist">
            @foreach($payload['supporting_documents'] as $doc)
                <li>{{ $doc }}</li>
            @endforeach
        </ul>
    @else
        <p style="font-size:10px;color:#9CA3AF;margin:0;">None listed.</p>
    @endif
</div>

{{-- 9. Provider declaration --}}
<div class="declaration-box">
    <strong style="font-style:normal;color:var(--clm);">Provider Declaration:</strong>
    "I certify that the services listed herein were medically necessary and were provided to the patient named above as described. All information furnished in this claim is true, accurate, and complete to the best of my knowledge and belief. Any false claims or misrepresentation may be subject to prosecution under applicable Cameroonian law."
</div>

{{-- 10. Signatures + bank details --}}
<div class="clm-sig-grid">
    <div class="sig-box">
        <label>Authorized Signatory / Signataire Autorisé</label>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $issuer_name }}</div>
        <div class="sig-role">{{ $issuer_role }} — {{ $facility_name }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">Date: {{ $payload['submission_date'] }}</div>
        <div style="text-align:center;margin-top:3mm;">
            <div class="provider-stamp">
                <div>OFFICIAL</div>
                <div>PROVIDER</div>
                <div>STAMP</div>
                <div style="font-family:monospace;font-size:6px;">{{ $payload['provider_reg_number'] }}</div>
            </div>
        </div>
    </div>
    <div class="sig-box">
        <label>Reimbursement Bank Account / Compte Bancaire</label>
        <div class="bank-box" style="margin-top:2mm;">
            <label>Bank Name</label>
            <div class="bank-val">{{ $payload['bank_name'] }}</div>
        </div>
        <div class="bank-box" style="margin-top:2mm;">
            <label>Account Number</label>
            <div class="bank-val">{{ $payload['bank_account'] }}</div>
        </div>
        <div class="bank-box" style="margin-top:2mm;">
            <label>Beneficiary</label>
            <div class="bank-val">{{ $facility_name }}</div>
        </div>
    </div>
</div>

{{-- 11. Insurer use only --}}
<div class="insurer-use-box">
    <div class="iub-title">For Insurer Use Only — Réservé à l'Assureur — Do Not Complete</div>
    <div class="iub-grid">
        <div class="iub-field">Received By:</div>
        <div class="iub-field">Date Received:</div>
        <div class="iub-field">Reference / File No:</div>
        <div class="iub-field">Assessor Initials:</div>
        <div class="iub-field">Amount Approved (XAF):</div>
        <div class="iub-field">Payment Date:</div>
        <div class="iub-field">Cheque / EFT Reference:</div>
        <div class="iub-field">Reviewer Remarks:</div>
    </div>
</div>
@endsection
