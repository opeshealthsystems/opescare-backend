@extends('documents.base')

@section('title', 'Forensic Autopsy Report / Rapport d\'Autopsie Médico-Légale')

@section('subtitle', 'JUDICIAL / MEDICOLEGAL — STRICTLY CONFIDENTIAL')

@section('content')
<style>
    .far-confidential-banner {
        background: #7F1D1D;
        color: #FEF2F2;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 5mm;
        text-align: center;
    }
    .far-confidential-banner .cb-main {
        font-size: 13px; font-weight: 900; text-transform: uppercase;
        letter-spacing: 3px; color: #FCA5A5;
    }
    .far-confidential-banner .cb-sub {
        font-size: 9px; color: #FECACA; margin-top: 1mm;
        letter-spacing: 1px;
    }

    .far-dark-header {
        background: #1C1917;
        color: #FAFAF9;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .far-dark-header .dh-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 1mm; }
    .far-dark-header .dh-sub { font-size: 9px; color: #A8A29E; }

    .far-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .fbadge-red      { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .fbadge-orange   { background: #FFEDD5; color: #9A3412; border: 1px solid #FED7AA; }
    .fbadge-green    { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .fbadge-amber    { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .fbadge-purple   { background: #EDE9FE; color: #5B21B6; border: 1px solid #C4B5FD; }
    .fbadge-slate    { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
    .fbadge-dark     { background: #292524; color: #FAFAF9; border: 1px solid #57534E; }

    .far-card {
        border: 1px solid #292524;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .far-card .fc-head {
        background: #292524;
        color: #FAFAF9;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #57534E;
    }
    .far-card .fc-body { padding: 4mm; }

    .far-card-light {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .far-card-light .fc-head-light {
        background: #FAFAF9;
        color: #1C1917;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #D6D3D1;
    }
    .far-card-light .fc-body { padding: 4mm; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }

    .kv { display: flex; justify-content: space-between; margin-bottom: 1.5mm; font-size: 10px; }
    .kv .k { color: #78716C; }
    .kv .v { color: #0F172A; font-weight: 600; }

    .kv-bilingual { margin-bottom: 2mm; font-size: 10px; }
    .kv-bilingual .kb { color: #78716C; font-size: 9px; }
    .kv-bilingual .vb { color: #0F172A; font-weight: 600; }

    .section-label {
        font-size: 10px; font-weight: 700; color: #1C1917;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-left: 3px solid #1C1917; padding-left: 2.5mm;
        margin-bottom: 3mm;
    }

    .injury-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .injury-table th {
        background: #7F1D1D; color: #FEF2F2; font-weight: 600;
        text-align: left; padding: 2.5mm 3mm;
        font-size: 9px; text-transform: uppercase;
    }
    .injury-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; vertical-align: top; }
    .injury-table tr:nth-child(even) td { background: #FFF7F7; }

    .internal-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .internal-table th {
        background: #292524; color: #FAFAF9; font-weight: 600;
        text-align: left; padding: 2.5mm 3mm;
        font-size: 9px; text-transform: uppercase;
    }
    .internal-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; vertical-align: top; }
    .internal-table tr:nth-child(even) td { background: #F8FAFC; }

    .cod-section {
        background: #1C1917;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        color: #FAFAF9;
    }
    .cod-section .cod-title {
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; margin-bottom: 3mm; color: #FCA5A5;
    }
    .cod-line {
        display: flex; gap: 3mm; align-items: flex-start;
        padding: 2mm 0; border-bottom: 1px solid #44403C;
    }
    .cod-line:last-of-type { border-bottom: none; }
    .cod-line .cl-alpha { font-size: 13px; font-weight: 800; color: #FCA5A5; min-width: 12mm; }
    .cod-line .cl-label { font-size: 9px; color: #D6D3D1; min-width: 30mm; padding-top: 1mm; }
    .cod-line .cl-text { font-size: 10.5px; font-weight: 600; color: #FAFAF9; flex: 1; }

    .manner-section {
        margin-top: 3mm; padding-top: 3mm; border-top: 1px solid #44403C;
        display: flex; align-items: center; gap: 4mm;
    }
    .manner-section .ms-lbl { font-size: 9.5px; color: #A8A29E; font-weight: 600; letter-spacing: 0.5px; }

    .opinion-box {
        background: #FAFAF9;
        border: 1px solid #D6D3D1;
        border-left: 4px solid #7F1D1D;
        border-radius: 0 6px 6px 0;
        padding: 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
        line-height: 1.6;
    }

    .chain-custody-box {
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-left: 4px solid #D97706;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10px;
        color: #92400E;
    }

    .sig-stamp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .sig-box { border: 1px solid #CBD5E1; border-radius: 6px; padding: 3mm; text-align: center; }
    .sig-box .sb-lbl { font-size: 9px; color: #78716C; margin-bottom: 8mm; }
    .sig-line { border-top: 1px solid #94A3B8; padding-top: 1mm; font-size: 9px; color: #374151; margin-top: 2mm; }
    .stamp-box {
        border: 2px dashed #94A3B8; border-radius: 6px; min-height: 18mm;
        display: flex; align-items: center; justify-content: center;
        color: #94A3B8; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
    }

    .court-footer-banner {
        background: #7F1D1D;
        color: #FEF2F2;
        border-radius: 4px;
        padding: 2.5mm 4mm;
        text-align: center;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-top: 4mm;
    }
</style>

{{-- CONFIDENTIAL Banner --}}
<div class="far-confidential-banner">
    <div class="cb-main">CONFIDENTIAL — FOR COURT / JUDICIAL USE ONLY</div>
    <div class="cb-sub">CONFIDENTIEL — RÉSERVÉ À L'USAGE JUDICIAIRE / MÉDICO-LÉGAL UNIQUEMENT</div>
</div>

{{-- 1. Dark forensic header --}}
<div class="far-dark-header">
    <div>
        <div class="dh-title">Forensic Autopsy Report</div>
        <div class="dh-title" style="font-size:10px; color:#A8A29E;">Rapport d'Autopsie Médico-Légale</div>
        <div class="dh-sub" style="margin-top:1mm;">
            Case No. / N° dossier: <strong>{{ $payload['case_number'] ?? '—' }}</strong>
            &nbsp;|&nbsp; Authority / Autorité: <strong>{{ $payload['requesting_authority'] ?? '—' }}</strong>
        </div>
    </div>
    @php
        $manner = $payload['manner_of_death'] ?? 'Undetermined';
        $mannerClass = match($manner) {
            'Natural'       => 'fbadge-green',
            'Accident'      => 'fbadge-amber',
            'Homicide'      => 'fbadge-red',
            'Suicide'       => 'fbadge-purple',
            'Undetermined'  => 'fbadge-slate',
            default         => 'fbadge-dark',
        };
    @endphp
    <div style="text-align:right;">
        <div style="font-size:9px; color:#A8A29E; margin-bottom:1.5mm;">Manner of Death / Cause de la mort</div>
        <span class="far-badge {{ $mannerClass }}" style="font-size:10px; padding:2mm 4mm;">{{ $manner }}</span>
    </div>
</div>

{{-- 2. Decedent + Authority --}}
<div class="two-col">
    <div class="far-card" style="margin-bottom:0;">
        <div class="fc-head">Decedent Information / Informations du Défunt</div>
        <div class="fc-body">
            <div class="kv"><span class="k">Full Name / Nom complet</span><span class="v">{{ $patient_name }}</span></div>
            <div class="kv"><span class="k">Health ID / N° Santé</span><span class="v" style="font-family:monospace;">{{ $health_id }}</span></div>
            <div class="kv"><span class="k">Sex / Sexe</span><span class="v">{{ $patient_sex }}</span></div>
            <div class="kv"><span class="k">Date of Birth / Naissance</span><span class="v">{{ $patient_dob }}</span></div>
            <div class="kv"><span class="k">Est. Date of Death / Date de décès estim.</span><span class="v">{{ $payload['date_of_death_estimated'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">PMI / Intervalle post-mortem</span><span class="v">{{ $payload['death_interval_estimate'] ?? '—' }}</span></div>
        </div>
    </div>
    <div class="far-card" style="margin-bottom:0;">
        <div class="fc-head">Judicial Reference / Référence Judiciaire</div>
        <div class="fc-body">
            <div class="kv"><span class="k">Case No. / N° dossier</span><span class="v" style="font-family:monospace;">{{ $payload['case_number'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Requesting Authority</span><span class="v">{{ $payload['requesting_authority'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Investigating Officer</span><span class="v">{{ $payload['investigating_officer'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Warrant No. / N° mandat</span><span class="v" style="font-family:monospace;">{{ $payload['warrant_number'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Warrant Date / Date mandat</span><span class="v">{{ $payload['warrant_date'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Facility / Établissement</span><span class="v">{{ $facility_name }}</span></div>
        </div>
    </div>
</div>

{{-- 3. Scene Information --}}
@php $scene = $payload['scene_information'] ?? []; @endphp
<div class="far-card-light">
    <div class="fc-head-light">Scene Information / Informations de Scène</div>
    <div class="fc-body">
        <div class="three-col" style="margin-bottom:3mm;">
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Scene Type / Type de scène</div>
                    <div class="vb">{{ $scene['scene_type'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Scene Investigators / Enquêteurs</div>
                    <div class="vb">{{ $scene['scene_investigators'] ?? '—' }}</div>
                </div>
            </div>
            <div></div>
        </div>
        <div>
            <div style="font-size:9px; color:#78716C; margin-bottom:1mm;">Scene Description / Description de la scène</div>
            <p style="margin:0; font-size:10px; color:#0F172A; line-height:1.5;">{{ $scene['scene_description'] ?? '—' }}</p>
        </div>
    </div>
</div>

{{-- 4. Body Identification --}}
@php $bodyId = $payload['body_identification'] ?? []; @endphp
<div class="far-card-light">
    <div class="fc-head-light">Body Identification / Identification du Corps</div>
    <div class="fc-body">
        <div class="three-col" style="margin-bottom:0;">
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Method / Méthode</div>
                    <div class="vb">{{ $bodyId['method'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Identified By / Identifié par</div>
                    <div class="vb">{{ $bodyId['identified_by'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Identification Date / Date d'identification</div>
                    <div class="vb">{{ $bodyId['identification_date'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 5. External Injuries --}}
@php $extInjuries = $payload['external_injuries'] ?? []; @endphp
<div class="section-label">External Injuries / Lésions Externes</div>
@if(!empty($extInjuries))
<div style="margin-bottom:5mm;">
    <table class="injury-table">
        <thead>
            <tr>
                <th style="width:15%;">Type / Type</th>
                <th style="width:18%;">Location / Localisation</th>
                <th style="width:15%;">Dimensions</th>
                <th style="width:27%;">Characteristics / Caractéristiques</th>
                <th style="width:25%;">Interpretation / Interprétation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extInjuries as $inj)
            <tr>
                <td style="font-weight:600;">{{ $inj['injury_type'] ?? '—' }}</td>
                <td>{{ $inj['location'] ?? '—' }}</td>
                <td style="font-family:monospace; font-size:9px;">{{ $inj['dimensions'] ?? '—' }}</td>
                <td>{{ $inj['characteristics'] ?? '—' }}</td>
                <td style="font-style:italic; color:#374151;">{{ $inj['interpretation'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="background:#FFF7F7; border:1px dashed #FECACA; border-radius:6px; padding:3mm; font-size:10px; color:#94A3B8; margin-bottom:5mm; text-align:center;">
    No external injuries recorded / Aucune lésion externe enregistrée
</div>
@endif

{{-- 6. Internal Findings --}}
@php $internalFindings = $payload['internal_findings'] ?? []; @endphp
<div class="section-label">Internal Findings / Constatations Internes</div>
@if(!empty($internalFindings))
<div style="margin-bottom:5mm;">
    <table class="internal-table">
        <thead>
            <tr>
                <th style="width:20%;">Organ / Organe</th>
                <th style="width:80%;">Findings / Constatations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($internalFindings as $finding)
            <tr>
                <td style="font-weight:600;">{{ $finding['organ'] ?? (is_string($finding) ? '—' : '—') }}</td>
                <td>{{ $finding['findings'] ?? (is_string($finding) ? $finding : '—') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:6px; padding:3mm; font-size:10px; color:#94A3B8; margin-bottom:5mm; text-align:center;">
    No internal findings recorded / Aucune constatation interne enregistrée
</div>
@endif

{{-- 7. Cause of Death --}}
@php $cod = $payload['cause_of_death'] ?? []; @endphp
<div class="cod-section">
    <div class="cod-title">Cause of Death / Cause du Décès</div>
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
    <div class="cod-line">
        <span class="cl-alpha">M</span>
        <span class="cl-label">Mechanism / Mécanisme</span>
        <span class="cl-text">{{ $cod['mechanism'] ?? '—' }}</span>
    </div>
    <div class="manner-section">
        <span class="ms-lbl">Manner of Death / Cause de la mort:</span>
        <span class="far-badge {{ $mannerClass }}" style="font-size:10px; padding:1.5mm 4mm;">{{ $manner }}</span>
    </div>
</div>

{{-- 8. Toxicology --}}
@php $tox = $payload['toxicology'] ?? []; @endphp
<div class="far-card">
    <div class="fc-head">Toxicology / Toxicologie</div>
    <div class="fc-body">
        <div class="two-col" style="margin-bottom:3mm;">
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Specimens Collected / Prélèvements effectués</div>
                    <div class="vb">{{ $tox['specimens_collected'] ?? '—' }}</div>
                </div>
                <div class="kv-bilingual" style="margin-top:2mm;">
                    <div class="kb">Blood Alcohol Level / Alcoolémie</div>
                    <div class="vb">{{ $tox['blood_alcohol_level'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Substances Detected / Substances détectées</div>
                    <div class="vb">{{ $tox['substances_detected'] ?? 'None detected / Aucune détectée' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 9. Trace Evidence --}}
@php $trace = $payload['trace_evidence'] ?? []; @endphp
<div class="far-card-light">
    <div class="fc-head-light">Trace Evidence / Traces et Indices</div>
    <div class="fc-body">
        <div class="two-col" style="margin-bottom:0;">
            <div>
                <div class="kv-bilingual">
                    <div class="kb">Evidence Collected / Traces collectées</div>
                    <div class="vb">{{ $trace['collected'] ?? 'No / Non' }}</div>
                </div>
                <div class="kv-bilingual" style="margin-top:2mm;">
                    <div class="kb">Description / Description</div>
                    <div class="vb">{{ $trace['description'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                @if(!empty($trace['chain_of_custody_number']))
                <div class="chain-custody-box">
                    <strong>Chain of Custody No. / N° chaîne de garde:</strong><br>
                    <span style="font-family:monospace; font-size:11px;">{{ $trace['chain_of_custody_number'] }}</span>
                </div>
                @else
                <div style="background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:6px; padding:3mm; font-size:9.5px; color:#94A3B8; font-style:italic;">
                    No chain-of-custody number assigned / Aucun numéro de chaîne de garde attribué
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 10. Expert Opinion & Conclusions --}}
<div class="section-label">Expert Opinion &amp; Conclusions / Opinion d'Expert et Conclusions</div>
<div class="opinion-box">{{ $payload['opinion_and_conclusions'] ?? '—' }}</div>

{{-- 11. Signature block --}}
<div class="sig-stamp-grid">
    <div class="sig-box">
        <div class="sb-lbl">
            Forensic Pathologist Signature / Signature du Médecin Légiste
        </div>
        <div class="sig-line">
            {{ $payload['forensic_pathologist_name'] ?? $issuer_name }}
            &nbsp;|&nbsp; Lic: {{ $payload['forensic_pathologist_license'] ?? $facility_license }}
            &nbsp;|&nbsp; {{ $payload['report_date'] ?? $issued_at }}
        </div>
    </div>
    <div class="stamp-box">Official Forensic Seal / Cachet Médico-Légal Officiel</div>
</div>

<div style="text-align:right; font-size:9.5px; color:#78716C; margin-top:2mm;">
    Document No. / N° document: <strong style="font-family:monospace;">{{ $document_number }}</strong>
    &nbsp;|&nbsp; Issued / Émis: <strong>{{ $issued_at }}</strong>
    &nbsp;|&nbsp; By / Par: {{ $issuer_name }} ({{ $issuer_role }})
</div>

{{-- Court Use Footer Banner --}}
<div class="court-footer-banner">
    FOR COURT / JUDICIAL USE ONLY — CONFIDENTIEL — TOUTE DIVULGATION NON AUTORISÉE EST INTERDITE PAR LA LOI
</div>
@endsection
