@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Rapport d\'Anatomopathologie' : 'Histopathology Report' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Rapport d\'anatomopathologie officiel — PATH' : 'Official Pathology Laboratory Report — PATH' }}
@endsection

@section('content')
<style>
    /* Critical finding banner */
    .critical-banner {
        background-color: #FEE2E2;
        border: 2px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        gap: 3mm;
    }
    .critical-banner-icon {
        font-size: 18px;
        flex-shrink: 0;
    }
    .critical-banner-text {
        font-size: 12px;
        font-weight: 800;
        color: #991B1B;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .critical-banner-sub {
        font-size: 9px;
        color: #B91C1C;
        font-style: italic;
        margin-top: 0.5mm;
    }

    /* Specimen / request meta cards */
    .spec-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }

    /* Date row in specimen card */
    .date-trio {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 2mm;
        margin-top: 2mm;
    }
    .date-cell {
        background-color: #F3F0FF;
        border: 1px solid #DDD6FE;
        border-radius: 4px;
        padding: 1.5mm 2mm;
        text-align: center;
    }
    .date-cell-label {
        font-size: 7.5px;
        color: #7C3AED;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .date-cell-value {
        font-size: 9.5px;
        font-weight: 700;
        color: #1E1B4B;
    }

    /* Diagnosis box */
    .diagnosis-box {
        background-color: #F5F3FF;
        border: 2px solid #6D28D9;
        border-radius: 8px;
        padding: 5mm 6mm;
        margin-bottom: 5mm;
    }
    .diagnosis-label {
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6D28D9;
        margin-bottom: 2mm;
        border-bottom: 1px solid #DDD6FE;
        padding-bottom: 1.5mm;
    }
    .diagnosis-text {
        font-size: 15px;
        font-weight: 900;
        color: #1E1B4B;
        line-height: 1.4;
        margin-bottom: 2mm;
    }
    .icd-badge {
        display: inline-block;
        background-color: #EDE9FE;
        border: 1px solid #C4B5FD;
        border-radius: 4px;
        padding: 1mm 3mm;
        font-size: 9.5px;
        font-weight: 700;
        color: #5B21B6;
        font-family: monospace;
    }

    /* Staging row */
    .staging-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        padding: 3mm;
    }
    .staging-cell {
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 2mm 3mm;
    }
    .staging-cell-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-bottom: 1mm;
    }
    .staging-cell-value {
        font-size: 10.5px;
        font-weight: 700;
        color: #0F172A;
    }

    /* Table inside card */
    .path-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    .path-table th {
        background-color: #EDE9FE;
        color: #5B21B6;
        font-weight: 700;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 3mm;
        border-bottom: 2px solid #C4B5FD;
        text-align: left;
    }
    .path-table td {
        padding: 2.5mm 3mm;
        border-bottom: 1px solid #F3F0FF;
        color: #1E293B;
    }
    .path-table tr:last-child td { border-bottom: none; }

    /* Pathologist credentials row */
    .pathologist-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 4mm;
        font-size: 10.5px;
    }
    .pathologist-info { flex: 1; }
    .pathologist-reg-badge {
        display: inline-block;
        background-color: #EDE9FE;
        border: 1px solid #C4B5FD;
        border-radius: 4px;
        padding: 0.5mm 2mm;
        font-size: 8.5px;
        font-weight: 700;
        color: #5B21B6;
        font-family: monospace;
        margin-top: 1mm;
    }
</style>

@php $isCritical = !empty($payload['critical_finding']); @endphp

{{-- Critical finding banner at top if applicable --}}
@if($isCritical)
<div class="critical-banner">
    <div class="critical-banner-icon">&#9888;</div>
    <div>
        <div class="critical-banner-text">
            {{ $language === 'fr' ? 'RÉSULTAT CRITIQUE — CLINICIEN NOTIFIÉ' : 'CRITICAL RESULT — CLINICIAN NOTIFIED' }}
        </div>
        <div class="critical-banner-sub">
            {{ $language === 'fr'
                ? 'Ce résultat nécessite une action clinique immédiate. Notification confirmée.'
                : 'This result requires immediate clinical action. Notification confirmed.' }}
        </div>
    </div>
</div>
@endif

{{-- 1. Specimen details + Request info (two-column) --}}
<div class="spec-meta-grid">
    <div class="content-card" style="margin-bottom: 0; border-color: #C4B5FD;">
        <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
            {{ $language === 'fr' ? 'DÉTAILS DU PRÉLÈVEMENT' : 'SPECIMEN DETAILS' }}
        </div>
        <div class="card-body">
            <div style="margin-bottom: 1mm;">
                <span style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #64748B;">
                    {{ $language === 'fr' ? 'N° de prélèvement' : 'Specimen ID' }}
                </span><br>
                <span style="font-size: 12px; font-weight: 800; color: #1E1B4B; font-family: monospace;">{{ $payload['specimen_id'] ?? '' }}</span>
            </div>
            <div style="margin-bottom: 1mm; font-size: 10px;">
                <span style="font-weight: 600; color: #475569;">{{ $language === 'fr' ? 'Type :' : 'Type:' }}</span>
                <span style="color: #0F172A; font-weight: 700;"> {{ $payload['specimen_type'] ?? '' }}</span>
            </div>
            <div class="date-trio">
                <div class="date-cell">
                    <div class="date-cell-label">{{ $language === 'fr' ? 'Prélèv.' : 'Collected' }}</div>
                    <div class="date-cell-value">{{ $payload['collection_date'] ?? '' }}</div>
                </div>
                <div class="date-cell">
                    <div class="date-cell-label">{{ $language === 'fr' ? 'Reçu' : 'Received' }}</div>
                    <div class="date-cell-value">{{ $payload['received_date'] ?? '' }}</div>
                </div>
                <div class="date-cell">
                    <div class="date-cell-label">{{ $language === 'fr' ? 'Rapport' : 'Reported' }}</div>
                    <div class="date-cell-value">{{ $payload['report_date'] ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="content-card" style="margin-bottom: 0; border-color: #C4B5FD;">
        <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
            {{ $language === 'fr' ? 'INFORMATIONS DE DEMANDE' : 'REQUEST INFORMATION' }}
        </div>
        <div class="card-body">
            <div style="margin-bottom: 2mm;">
                <span style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #64748B;">
                    {{ $language === 'fr' ? 'Médecin prescripteur' : 'Requesting Physician' }}
                </span><br>
                <span style="font-size: 11px; font-weight: 700; color: #0F172A;">{{ $payload['requesting_physician'] ?? '' }}</span>
            </div>
            <div>
                <span style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #64748B;">
                    {{ $language === 'fr' ? 'Pathologiste' : 'Reporting Pathologist' }}
                </span><br>
                <span style="font-size: 11px; font-weight: 700; color: #0F172A;">{{ $payload['pathologist'] ?? '' }}</span><br>
                <span class="pathologist-reg-badge">{{ $payload['pathologist_reg'] ?? '' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- 3. Clinical Indication --}}
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'INDICATION CLINIQUE' : 'CLINICAL INDICATION' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155;">
        {{ $payload['clinical_indication'] ?? '' }}
    </div>
</div>

{{-- 4. Gross Description --}}
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'DESCRIPTION MACROSCOPIQUE' : 'GROSS DESCRIPTION (MACROSCOPIC)' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155; text-align: justify;">
        {{ $payload['gross_description'] ?? '' }}
    </div>
</div>

{{-- 5. Microscopic Description --}}
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'DESCRIPTION MICROSCOPIQUE' : 'MICROSCOPIC DESCRIPTION' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155; text-align: justify;">
        {{ $payload['microscopic_description'] ?? '' }}
    </div>
</div>

{{-- 6. Special Stains table --}}
@if(!empty($payload['special_stains']))
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'COLORATIONS SPÉCIALES' : 'SPECIAL STAINS' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="path-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'COLORATION' : 'STAIN' }}</th>
                    <th>{{ $language === 'fr' ? 'RÉSULTAT' : 'RESULT' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['special_stains'] as $stain)
                <tr>
                    <td style="font-weight: 600;">{{ $stain['stain'] ?? '' }}</td>
                    <td>{{ $stain['result'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 7. IHC table --}}
@if(!empty($payload['immunohistochemistry']))
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'IMMUNOHISTOCHIMIE (IHC)' : 'IMMUNOHISTOCHEMISTRY (IHC)' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="path-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'MARQUEUR' : 'MARKER' }}</th>
                    <th>{{ $language === 'fr' ? 'RÉSULTAT' : 'RESULT' }}</th>
                    <th>{{ $language === 'fr' ? 'INTERPRÉTATION' : 'INTERPRETATION' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['immunohistochemistry'] as $ihc)
                <tr>
                    <td style="font-weight: 700; color: #5B21B6;">{{ $ihc['marker'] ?? '' }}</td>
                    <td style="font-weight: 600;">{{ $ihc['result'] ?? '' }}</td>
                    <td style="color: #475569;">{{ $ihc['interpretation'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 8. DIAGNOSIS — prominent box --}}
<div class="diagnosis-box">
    <div class="diagnosis-label">
        {{ $language === 'fr' ? 'DIAGNOSTIC ANATOMOPATHOLOGIQUE' : 'PATHOLOGICAL DIAGNOSIS' }}
    </div>
    <div class="diagnosis-text">{{ $payload['diagnosis'] ?? '' }}</div>
    @if(!empty($payload['icd10_morphology']))
    <div>
        <span style="font-size: 8.5px; font-weight: 600; color: #6D28D9; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 2mm;">
            {{ $language === 'fr' ? 'Code CIM-10 Morphologie :' : 'ICD-10 Morphology Code:' }}
        </span>
        <span class="icd-badge">{{ $payload['icd10_morphology'] }}</span>
    </div>
    @endif
</div>

{{-- 9. Staging & Margins --}}
@if(!empty($payload['staging']) || !empty($payload['margins']) || !empty($payload['lymph_nodes']))
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'STADIFICATION ET MARGES' : 'STAGING &amp; MARGINS' }}
    </div>
    <div class="staging-grid">
        @if(!empty($payload['staging']))
        <div class="staging-cell">
            <div class="staging-cell-label">{{ $language === 'fr' ? 'Stade pathologique' : 'Pathological Stage' }}</div>
            <div class="staging-cell-value">{{ $payload['staging'] }}</div>
        </div>
        @endif
        @if(!empty($payload['margins']))
        <div class="staging-cell">
            <div class="staging-cell-label">{{ $language === 'fr' ? 'Marges de résection' : 'Resection Margins' }}</div>
            <div class="staging-cell-value">{{ $payload['margins'] }}</div>
        </div>
        @endif
        @if(!empty($payload['lymph_nodes']))
        <div class="staging-cell">
            <div class="staging-cell-label">{{ $language === 'fr' ? 'Ganglions lymphatiques' : 'Lymph Nodes' }}</div>
            <div class="staging-cell-value">{{ $payload['lymph_nodes'] }}</div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- 10. Pathologist Comment --}}
@if(!empty($payload['pathologist_comment']))
<div class="content-card" style="border-color: #C4B5FD;">
    <div class="card-header" style="background-color: #EDE9FE; color: #5B21B6;">
        {{ $language === 'fr' ? 'COMMENTAIRE DU PATHOLOGISTE' : 'PATHOLOGIST COMMENT' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155; text-align: justify;">
        {{ $payload['pathologist_comment'] }}
    </div>
</div>
@endif

{{-- 11. Critical finding banner repeated at bottom --}}
@if($isCritical)
<div class="critical-banner" style="margin-top: 4mm;">
    <div class="critical-banner-icon">&#9888;</div>
    <div>
        <div class="critical-banner-text">
            {{ $language === 'fr' ? 'RÉSULTAT CRITIQUE — CLINICIEN NOTIFIÉ' : 'CRITICAL RESULT — CLINICIAN NOTIFIED' }}
        </div>
        <div class="critical-banner-sub">
            {{ $language === 'fr'
                ? 'Ce rapport contient un résultat anatomopathologique critique exigeant une prise en charge immédiate.'
                : 'This report contains a critical pathological finding requiring immediate clinical management.' }}
        </div>
    </div>
</div>
@endif
@endsection
