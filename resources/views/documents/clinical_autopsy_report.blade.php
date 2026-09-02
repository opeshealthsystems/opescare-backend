@extends('documents.base')

@section('title', 'Clinical Autopsy Report / Rapport d\'Autopsie Clinique')

@section('subtitle', 'HOSPITAL POST-MORTEM EXAMINATION — INTERNAL USE')

@section('content')
<style>
    .car-dark-header {
        background: #1E3A5F;
        color: #F0F9FF;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .car-dark-header .dh-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 1mm; }
    .car-dark-header .dh-sub { font-size: 9px; color: #93C5FD; }
    .car-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .cbadge-blue   { background: #DBEAFE; color: #1E3A8A; border: 1px solid #93C5FD; }
    .cbadge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .cbadge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .cbadge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .cbadge-slate  { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
    .cbadge-purple { background: #EDE9FE; color: #5B21B6; border: 1px solid #C4B5FD; }

    .car-card {
        border: 1px solid #1E3A5F;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .car-card .cc-head {
        background: #1E3A5F;
        color: #F0F9FF;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #2563EB;
    }
    .car-card .cc-body { padding: 4mm; }

    .car-card-light {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .car-card-light .cc-head-light {
        background: #F0F9FF;
        color: #1E3A5F;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #BFDBFE;
    }
    .car-card-light .cc-body { padding: 4mm; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }

    .kv { display: flex; justify-content: space-between; margin-bottom: 1.5mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }

    .kv-bilingual { margin-bottom: 2mm; font-size: 10px; }
    .kv-bilingual .kb { color: #64748B; font-size: 9px; }
    .kv-bilingual .vb { color: #0F172A; font-weight: 600; }

    .section-label {
        font-size: 10px; font-weight: 700; color: #1E3A5F;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-left: 3px solid #1E3A5F; padding-left: 2.5mm;
        margin-bottom: 3mm;
    }

    .organ-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .organ-table th {
        background: #1E3A5F; color: #F0F9FF; font-weight: 600;
        text-align: left; padding: 2.5mm 3mm;
        font-size: 9px; text-transform: uppercase;
    }
    .organ-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; vertical-align: top; }
    .organ-table tr:nth-child(even) td { background: #F8FAFC; }

    .histo-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .histo-table th {
        background: #374151; color: #F9FAFB; font-weight: 600;
        text-align: left; padding: 2.5mm 3mm;
        font-size: 9px; text-transform: uppercase;
    }
    .histo-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; vertical-align: top; }
    .histo-table tr:nth-child(even) td { background: #F8FAFC; }

    .cod-section {
        background: #1E3A5F;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        color: #F0F9FF;
    }
    .cod-section .cod-title {
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; margin-bottom: 3mm; color: #93C5FD;
    }
    .cod-line {
        display: flex; gap: 3mm; align-items: flex-start;
        padding: 2mm 0; border-bottom: 1px solid #2563EB;
    }
    .cod-line .cl-alpha { font-size: 13px; font-weight: 800; color: #93C5FD; min-width: 12mm; }
    .cod-line .cl-label { font-size: 9px; color: #BFDBFE; min-width: 26mm; padding-top: 1mm; }
    .cod-line .cl-text { font-size: 10.5px; font-weight: 600; color: #F0F9FF; flex: 1; }
    .cod-ii { margin-top: 2mm; padding-top: 2mm; }
    .cod-ii .cl-alpha { color: #FCD34D; }
    .cod-ii .cl-label { color: #FDE68A; }

    .discrepancy-box {
        border-radius: 6px; padding: 4mm; margin-bottom: 5mm;
        font-size: 10px; line-height: 1.6;
    }
    .disc-yes     { background: #FEF2F2; border: 1px solid #FECACA; border-left: 4px solid #DC2626; }
    .disc-no      { background: #F0FDF4; border: 1px solid #BBF7D0; border-left: 4px solid #16A34A; }
    .disc-partial { background: #FFFBEB; border: 1px solid #FDE68A; border-left: 4px solid #D97706; }

    .conclusions-box {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-left: 4px solid #1E3A5F;
        border-radius: 0 6px 6px 0;
        padding: 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
        line-height: 1.6;
    }

    .sig-stamp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .sig-box { border: 1px solid #CBD5E1; border-radius: 6px; padding: 3mm; text-align: center; }
    .sig-box .sb-lbl { font-size: 9px; color: #64748B; margin-bottom: 8mm; }
    .sig-line { border-top: 1px solid #94A3B8; padding-top: 1mm; font-size: 9px; color: #374151; margin-top: 2mm; }
    .stamp-box {
        border: 2px dashed #94A3B8; border-radius: 6px; min-height: 18mm;
        display: flex; align-items: center; justify-content: center;
        color: #94A3B8; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .internal-use-banner {
        text-align: center; background: #EFF6FF; border: 1px solid #BFDBFE;
        border-radius: 4px; padding: 2mm 4mm; margin-bottom: 5mm;
        font-size: 9.5px; font-weight: 700; color: #1E3A8A;
        text-transform: uppercase; letter-spacing: 1px;
    }
</style>

{{-- Internal Use Banner --}}
<div class="internal-use-banner">
    INTERNAL USE — HOSPITAL AUTOPSY RECORD &nbsp;|&nbsp; USAGE INTERNE — DOSSIER D'AUTOPSIE HOSPITALIÈRE
</div>

{{-- 1. Dark header --}}
<div class="car-dark-header">
    <div>
        <div class="dh-title">Clinical Autopsy Report</div>
        <div class="dh-title" style="font-size:10px; color:#93C5FD;">Rapport d'Autopsie Clinique</div>
        <div class="dh-sub" style="margin-top:1mm;">
            Autopsy No. / N° d'autopsie: <strong>{{ $payload['autopsy_number'] ?? '—' }}</strong>
            &nbsp;|&nbsp; Type: <strong>{{ $payload['autopsy_type'] ?? 'Clinical/Hospital' }}</strong>
        </div>
    </div>
    @php
        $consentObtained = $payload['consent_obtained'] ?? 'No';
        $consentClass = ($consentObtained === 'Yes') ? 'cbadge-green' : 'cbadge-red';
    @endphp
    <div style="text-align:right;">
        <div style="font-size:9px; color:#93C5FD; margin-bottom:1.5mm;">Consent / Consentement</div>
        <span class="car-badge {{ $consentClass }}" style="font-size:9.5px; padding:1.5mm 3.5mm;">
            {{ $consentObtained }}
        </span>
    </div>
</div>

{{-- 2. Decedent & Autopsy Metadata --}}
<div class="two-col">
    <div class="car-card" style="margin-bottom:0;">
        <div class="cc-head">Decedent Information / Informations du Défunt</div>
        <div class="cc-body">
            <div class="kv"><span class="k">Full Name / Nom complet</span><span class="v">{{ $patient_name }}</span></div>
            <div class="kv"><span class="k">Health ID / N° Santé</span><span class="v" style="font-family:monospace;">{{ $health_id }}</span></div>
            <div class="kv"><span class="k">Sex / Sexe</span><span class="v">{{ $patient_sex }}</span></div>
            <div class="kv"><span class="k">Date of Birth / Naissance</span><span class="v">{{ $patient_dob }}</span></div>
            <div class="kv"><span class="k">Date of Death / Décès</span><span class="v">{{ $payload['date_of_death'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Time of Death / Heure du décès</span><span class="v">{{ $payload['time_of_death'] ?? '—' }}</span></div>
        </div>
    </div>
    <div class="car-card" style="margin-bottom:0;">
        <div class="cc-head">Autopsy Details / Détails de l'Autopsie</div>
        <div class="cc-body">
            <div class="kv"><span class="k">Date of Autopsy / Date autopsie</span><span class="v">{{ $payload['date_of_autopsy'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Pathologist / Pathologiste</span><span class="v">{{ $payload['pathologist_name'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Lic. No. / N° licence</span><span class="v" style="font-family:monospace;">{{ $payload['pathologist_license'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Facility / Établissement</span><span class="v">{{ $facility_name }}</span></div>
            <div class="kv"><span class="k">Facility License / Lic. établ.</span><span class="v" style="font-family:monospace;">{{ $facility_license }}</span></div>
            <div class="kv"><span class="k">Consent Type / Type consentement</span><span class="v">{{ $payload['consent_type'] ?? '—' }}</span></div>
        </div>
    </div>
</div>

{{-- 3. Clinical Diagnosis (pre-mortem) --}}
<div class="car-card-light">
    <div class="cc-head-light">Pre-mortem Clinical Diagnosis / Diagnostic Clinique Pré-mortem</div>
    <div class="cc-body">
        <p style="margin:0; font-size:10.5px; line-height:1.6; color:#0F172A;">{{ $payload['clinical_diagnosis'] ?? '—' }}</p>
    </div>
</div>

{{-- 4. External Examination --}}
@php $ext = $payload['external_examination'] ?? []; @endphp
<div class="car-card">
    <div class="cc-head">External Examination / Examen Externe</div>
    <div class="cc-body">
        <div class="three-col" style="margin-bottom:3mm;">
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Body Weight / Poids corporel</div>
                    <div class="vb">{{ $ext['body_weight'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Body Height / Taille</div>
                    <div class="vb">{{ $ext['body_height'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Body Condition / État du corps</div>
                    <div class="vb">{{ $ext['body_condition'] ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div style="margin-bottom:3mm;">
            <div style="font-size:9px; color:#64748B; margin-bottom:1mm;">Identifying Features / Caractéristiques d'identification</div>
            <p style="margin:0; font-size:10px; color:#0F172A; line-height:1.5;">{{ $ext['identifying_features'] ?? '—' }}</p>
        </div>
        <div>
            <div style="font-size:9px; color:#64748B; margin-bottom:1mm;">External Injuries / Lésions externes</div>
            <p style="margin:0; font-size:10px; color:#0F172A; line-height:1.5;">{{ $ext['external_injuries'] ?? 'None noted / Aucune notée' }}</p>
        </div>
    </div>
</div>

{{-- 5. Internal Examination — Organ Systems Table --}}
@php $internal = $payload['internal_examination'] ?? []; @endphp
<div class="section-label">Internal Examination / Examen Interne</div>
@if(!empty($internal))
<div style="margin-bottom:5mm;">
    <table class="organ-table">
        <thead>
            <tr>
                <th style="width:20%;">Organ / Organe</th>
                <th style="width:12%;">Weight (g) / Poids</th>
                <th style="width:28%;">Gross Appearance / Aspect macroscopique</th>
                <th style="width:40%;">Histology Result / Résultat histologique</th>
            </tr>
        </thead>
        <tbody>
            @foreach($internal as $organ)
            <tr>
                <td style="font-weight:600;">{{ $organ['organ'] ?? '—' }}</td>
                <td style="font-family:monospace;">{{ $organ['weight'] ?? '—' }}</td>
                <td>{{ $organ['gross_appearance'] ?? '—' }}</td>
                <td>{{ $organ['histology_result'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:6px; padding:3mm; font-size:10px; color:#94A3B8; margin-bottom:5mm; text-align:center;">
    No internal examination findings recorded / Aucune donnée d'examen interne enregistrée
</div>
@endif

{{-- 6. Cause of Death (Ia / Ib / II) --}}
@php $cod = $payload['cause_of_death'] ?? []; @endphp
<div class="cod-section">
    <div class="cod-title">Cause of Death / Cause du Décès — WHO / OMS Classification</div>
    <div class="cod-line">
        <span class="cl-alpha">I(a)</span>
        <span class="cl-label">Immediate / Immédiate</span>
        <span class="cl-text">{{ $cod['immediate'] ?? '—' }}</span>
    </div>
    <div class="cod-line">
        <span class="cl-alpha">I(b)</span>
        <span class="cl-label">Underlying / Sous-jacente</span>
        <span class="cl-text">{{ $cod['underlying'] ?? '—' }}</span>
    </div>
    <div class="cod-line cod-ii" style="border-bottom:none;">
        <span class="cl-alpha" style="color:#FCD34D;">II</span>
        <span class="cl-label" style="color:#FDE68A;">Contributing / Facteurs contributifs</span>
        <span class="cl-text">{{ $cod['contributing'] ?? '—' }}</span>
    </div>
</div>

{{-- 7. Final Autopsy Diagnosis --}}
<div class="car-card-light">
    <div class="cc-head-light">Final Autopsy Diagnosis / Diagnostic Final d'Autopsie</div>
    <div class="cc-body">
        <p style="margin:0; font-size:10.5px; font-weight:600; color:#0F172A; line-height:1.6;">{{ $payload['final_diagnosis'] ?? '—' }}</p>
    </div>
</div>

{{-- 8. Clinical–Autopsy Discrepancy --}}
@php
    $discrepancy = $payload['discrepancy_with_clinical'] ?? 'No';
    $discClass = match($discrepancy) {
        'Yes' => 'disc-yes',
        'Partial' => 'disc-partial',
        default => 'disc-no',
    };
    $discLabel = match($discrepancy) {
        'Yes' => 'DISCREPANCY IDENTIFIED / DIVERGENCE IDENTIFIÉE',
        'Partial' => 'PARTIAL DISCREPANCY / DIVERGENCE PARTIELLE',
        default => 'NO DISCREPANCY / AUCUNE DIVERGENCE',
    };
@endphp
<div class="discrepancy-box {{ $discClass }}">
    <div style="font-size:10px; font-weight:800; letter-spacing:0.8px; margin-bottom:2mm;">
        {{ $discLabel }}
    </div>
    <div style="font-size:9.5px; color:#374151;">
        <strong>Clinical vs. Autopsy Diagnosis / Diagnostic clinique vs. autopsie:</strong>
        {{ $payload['discrepancy_notes'] ?? ($discrepancy === 'No' ? 'Clinical diagnosis confirmed at autopsy. / Diagnostic clinique confirmé à l\'autopsie.' : '—') }}
    </div>
</div>

{{-- 9. Histology Specimens --}}
@php $histoSpecimens = $payload['histology_specimens'] ?? []; @endphp
<div class="section-label" style="margin-top:1mm;">Histology Specimens / Prélèvements Histologiques</div>
@if(!empty($histoSpecimens))
<div style="margin-bottom:5mm;">
    <table class="histo-table">
        <thead>
            <tr>
                <th style="width:18%;">Label / Étiquette</th>
                <th style="width:20%;">Site / Site</th>
                <th style="width:18%;">Fixative / Fixateur</th>
                <th style="width:44%;">Microscopy Findings / Résultats microscopiques</th>
            </tr>
        </thead>
        <tbody>
            @foreach($histoSpecimens as $spec)
            <tr>
                <td style="font-family:monospace; font-weight:600;">{{ $spec['specimen_label'] ?? '—' }}</td>
                <td>{{ $spec['site'] ?? '—' }}</td>
                <td>{{ $spec['fixative'] ?? '—' }}</td>
                <td>{{ $spec['microscopy_findings'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:6px; padding:3mm; font-size:10px; color:#94A3B8; margin-bottom:5mm; text-align:center;">
    No histology specimens submitted / Aucun prélèvement histologique soumis
</div>
@endif

{{-- 10. Toxicology --}}
<div class="two-col">
    <div class="car-card-light" style="margin-bottom:0;">
        <div class="cc-head-light">Toxicology Requested / Toxicologie demandée</div>
        <div class="cc-body">
            @php $toxReq = $payload['toxicology_requested'] ?? 'No'; @endphp
            <span class="car-badge {{ $toxReq === 'Yes' ? 'cbadge-amber' : 'cbadge-slate' }}" style="font-size:10px; padding:1.5mm 3.5mm;">
                {{ $toxReq }}
            </span>
        </div>
    </div>
    <div class="car-card-light" style="margin-bottom:0;">
        <div class="cc-head-light">Toxicology Results / Résultats toxicologiques</div>
        <div class="cc-body">
            @if(!empty($payload['toxicology_results']))
                <p style="margin:0; font-size:10px; color:#0F172A; line-height:1.5;">{{ $payload['toxicology_results'] }}</p>
            @else
                <p style="font-size:9.5px; color:#94A3B8; margin:0; font-style:italic;">
                    {{ ($toxReq === 'Yes') ? 'Results pending / Résultats en attente' : 'Not requested / Non demandée' }}
                </p>
            @endif
        </div>
    </div>
</div>

{{-- 11. Conclusions --}}
<div class="section-label" style="margin-top:2mm;">Conclusions / Conclusions</div>
<div class="conclusions-box">{{ $payload['conclusions'] ?? '—' }}</div>

{{-- 12. Signature + Stamp + Date --}}
<div class="sig-stamp-grid">
    <div class="sig-box">
        <div class="sb-lbl">Pathologist Signature / Signature du Pathologiste</div>
        <div class="sig-line">
            {{ $payload['pathologist_name'] ?? '—' }}
            &nbsp;|&nbsp; Lic: {{ $payload['pathologist_license'] ?? '—' }}
            &nbsp;|&nbsp; {{ $payload['pathologist_signature_date'] ?? $issued_at }}
        </div>
    </div>
    <div class="stamp-box">Official Stamp / Cachet officiel du Pathologiste</div>
</div>

<div style="text-align:right; font-size:9.5px; color:#64748B; margin-top:2mm;">
    Document No. / N° document: <strong style="font-family:monospace;">{{ $document_number }}</strong>
    &nbsp;|&nbsp; Issued / Émis: <strong>{{ $issued_at }}</strong>
    &nbsp;|&nbsp; By / Par: {{ $issuer_name }} ({{ $issuer_role }})
</div>
@endsection
