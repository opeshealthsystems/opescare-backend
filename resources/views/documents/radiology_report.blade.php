@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Rapport de Radiologie' : 'Radiology Report' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Rapport officiel d\'imagerie médicale' : 'Official Medical Imaging Report' }}
@endsection

@section('content')
<style>
    .rad-modality-banner {
        background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
        color: #FFFFFF;
        border-radius: 8px;
        padding: 4mm 6mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .rad-banner-inner { display: flex; align-items: center; gap: 3mm; }
    .rad-banner-title { font-size: 16px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
    .rad-banner-icon {
        width: 10mm; height: 10mm;
        border: 2.5px solid rgba(255,255,255,0.6);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; font-weight: 700; margin-right: 3mm; flex-shrink: 0;
    }
    .rad-banner-code {
        font-size: 9px; background: rgba(255,255,255,0.18);
        padding: 1mm 2.5mm; border-radius: 4px; letter-spacing: 0.5px; font-weight: 600; margin-top: 1mm;
    }
    .rad-stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3mm; margin-bottom: 5mm; }
    .rad-stat-box { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 6px; padding: 3mm; text-align: center; }
    .rad-stat-label { font-size: 8px; color: #1D4ED8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 1mm; }
    .rad-stat-value { font-size: 11px; font-weight: 700; color: #1E3A8A; }
    .rad-critical-alert {
        background: #FEF2F2; border: 2px solid #DC2626; border-radius: 8px;
        padding: 3.5mm 5mm; margin-bottom: 5mm; display: flex; align-items: center; gap: 3mm;
    }
    .rad-alert-icon { font-size: 20px; flex-shrink: 0; color: #DC2626; }
    .rad-alert-title { font-size: 12px; font-weight: 800; color: #B91C1C; text-transform: uppercase; letter-spacing: 0.5px; }
    .rad-alert-detail { font-size: 9.5px; color: #7F1D1D; margin-top: 0.5mm; }
    .rad-section-card { border: 1px solid #E2E8F0; border-radius: 6px; margin-bottom: 5mm; overflow: hidden; }
    .rad-section-header {
        background: #EFF6FF; border-bottom: 2px solid #BFDBFE; color: #1D4ED8;
        font-weight: 700; font-size: 10px; padding: 2mm 4mm; text-transform: uppercase; letter-spacing: 0.6px;
        display: flex; align-items: center; gap: 2mm;
    }
    .rad-section-body { padding: 4mm; font-size: 10.5px; color: #0F172A; line-height: 1.7; }
    .rad-findings-body { padding: 4mm; font-size: 10.5px; color: #0F172A; line-height: 1.9; white-space: pre-line; }
    .rad-impression-card {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border: 2px solid #1D4ED8; border-radius: 8px; margin-bottom: 5mm; overflow: hidden;
    }
    .rad-impression-header {
        background: #1D4ED8; color: #FFFFFF; font-weight: 800;
        font-size: 11px; padding: 2.5mm 4mm; text-transform: uppercase; letter-spacing: 1px;
    }
    .rad-impression-body { padding: 4mm; font-size: 11px; font-weight: 600; color: #1E3A8A; line-height: 1.8; }
    .rad-info-row {
        background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px;
        padding: 2.5mm 4mm; margin-bottom: 4mm; font-size: 10px; color: #475569;
    }
    .rad-dose-note { font-size: 8.5px; color: #94A3B8; text-align: right; margin-bottom: 4mm; font-style: italic; }
    .rad-radiologist-block {
        border-top: 1px solid #E2E8F0; padding-top: 4mm;
        display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4mm;
    }
    .rad-sig-line {
        border-top: 1px solid #94A3B8; padding-top: 1mm; margin-top: 7mm;
        font-size: 9px; color: #475569; width: 55mm; display: inline-block; text-align: center;
    }
    .rad-esign-badge {
        display: inline-block; background: #EFF6FF; border: 1px solid #BFDBFE;
        color: #1D4ED8; font-size: 8px; font-weight: 600; padding: 0.8mm 2mm; border-radius: 4px; margin-top: 1mm;
    }
    .contrast-tag {
        background: #DBEAFE; color: #1D4ED8; padding: 0.3mm 1.5mm;
        border-radius: 3px; font-size: 8px; margin-left: 2mm; font-weight: 600;
    }
</style>

@php
    $modality    = $payload['modality'] ?? 'Imaging';
    $bodyPart    = $payload['body_part'] ?? '—';
    $laterality  = $payload['laterality'] ?? null;
    $repDate     = $payload['reporting_date'] ?? ($issued_at ?? '—');
    $imagesCount = $payload['images_available'] ?? 0;
    $modalityIcons = [
        'X-Ray' => '&#8853;', 'CT' => '&#9678;', 'MRI' => '&#9673;',
        'Ultrasound' => '&#9681;', 'PET' => '&#9672;',
    ];
    $modalityIcon  = $modalityIcons[$modality] ?? '&#8853;';
    $modalityLabels = [
        'X-Ray'      => ['en' => 'X-RAY REPORT',       'fr' => 'RAPPORT RADIOGRAPHIE'],
        'CT'         => ['en' => 'CT SCAN REPORT',      'fr' => 'RAPPORT SCANNER CT'],
        'MRI'        => ['en' => 'MRI REPORT',          'fr' => 'RAPPORT IRM'],
        'Ultrasound' => ['en' => 'ULTRASOUND REPORT',   'fr' => 'RAPPORT ECHOGRAPHIE'],
        'PET'        => ['en' => 'PET SCAN REPORT',     'fr' => 'RAPPORT TEP'],
    ];
    $modalityLabel = $modalityLabels[$modality][$language] ?? strtoupper($modality) . ' REPORT';
@endphp

{{-- 1. MODALITY BANNER --}}
<div class="rad-modality-banner">
    <div class="rad-banner-inner">
        <div class="rad-banner-icon">{!! $modalityIcon !!}</div>
        <div>
            <div class="rad-banner-title">{{ $modalityLabel }}</div>
            <div class="rad-banner-code">CODE: RAD &nbsp;&bull;&nbsp; {{ $document_number }}</div>
        </div>
    </div>
    <div style="text-align:right;font-size:9px;color:rgba(255,255,255,0.8);">
        {{ $language === 'fr' ? 'Rapport de Radiologie Officiel' : 'Official Radiology Report' }}<br>
        OpesCare Imaging Platform
    </div>
</div>

{{-- 2. STUDY DETAILS ROW --}}
<div class="rad-stat-row">
    <div class="rad-stat-box">
        <div class="rad-stat-label">{{ $language === 'fr' ? 'Modalité' : 'Modality' }}</div>
        <div class="rad-stat-value">{{ $modality }}</div>
    </div>
    <div class="rad-stat-box">
        <div class="rad-stat-label">{{ $language === 'fr' ? 'Partie du Corps' : 'Body Part' }}</div>
        <div class="rad-stat-value">{{ $bodyPart }}{{ $laterality ? ' ('.$laterality.')' : '' }}</div>
    </div>
    <div class="rad-stat-box">
        <div class="rad-stat-label">{{ $language === 'fr' ? 'Date Examen' : 'Date Performed' }}</div>
        <div class="rad-stat-value">{{ $repDate }}</div>
    </div>
    <div class="rad-stat-box">
        <div class="rad-stat-label">{{ $language === 'fr' ? 'Images' : 'Images' }}</div>
        <div class="rad-stat-value">{{ $imagesCount }} image(s)</div>
    </div>
</div>

{{-- 3. CRITICAL FINDING ALERT --}}
@if(!empty($payload['critical_finding']))
<div class="rad-critical-alert">
    <div class="rad-alert-icon">&#9888;</div>
    <div>
        <div class="rad-alert-title">
            {{ $language === 'fr' ? 'RESULTAT CRITIQUE' : 'CRITICAL FINDING' }}
        </div>
        @if(!empty($payload['critical_finding_communicated_to']))
        <div class="rad-alert-detail">
            {{ $language === 'fr' ? 'Communique a :' : 'Communicated to:' }}
            <strong>{{ $payload['critical_finding_communicated_to'] }}</strong>
            &mdash; {{ $issued_at }}
        </div>
        @endif
    </div>
</div>
@endif

{{-- 4. CLINICAL INDICATION --}}
@if(!empty($payload['indication']))
<div class="rad-section-card">
    <div class="rad-section-header">{{ $language === 'fr' ? 'INDICATION CLINIQUE' : 'CLINICAL INDICATION' }}</div>
    <div class="rad-section-body">{{ $payload['indication'] }}</div>
</div>
@endif

{{-- 5. CLINICAL HISTORY --}}
@if(!empty($payload['clinical_history']))
<div class="rad-section-card">
    <div class="rad-section-header">{{ $language === 'fr' ? 'HISTOIRE CLINIQUE' : 'CLINICAL HISTORY' }}</div>
    <div class="rad-section-body">{{ $payload['clinical_history'] }}</div>
</div>
@endif

{{-- 6. TECHNIQUE --}}
@if(!empty($payload['technique']))
<div class="rad-section-card">
    <div class="rad-section-header">
        TECHNIQUE
        @if(!empty($payload['contrast_used']))
            <span class="contrast-tag">{{ $language === 'fr' ? 'CONTRASTE UTILISE' : 'CONTRAST USED' }}</span>
        @endif
    </div>
    <div class="rad-section-body">{{ $payload['technique'] }}</div>
</div>
@endif

{{-- 7. FINDINGS --}}
<div class="rad-section-card">
    <div class="rad-section-header">{{ $language === 'fr' ? 'RESULTATS' : 'FINDINGS' }}</div>
    <div class="rad-findings-body">{{ $payload['findings'] ?? '—' }}</div>
</div>

{{-- 8. IMPRESSION --}}
<div class="rad-impression-card">
    <div class="rad-impression-header">{{ $language === 'fr' ? 'IMPRESSION' : 'IMPRESSION' }}</div>
    <div class="rad-impression-body">{{ $payload['impression'] ?? '—' }}</div>
</div>

{{-- 9. RECOMMENDATIONS --}}
@if(!empty($payload['recommendations']))
<div class="rad-section-card">
    <div class="rad-section-header">{{ $language === 'fr' ? 'RECOMMANDATIONS' : 'RECOMMENDATIONS' }}</div>
    <div class="rad-section-body">{{ $payload['recommendations'] }}</div>
</div>
@endif

{{-- 10. COMPARISON STUDY --}}
@if(!empty($payload['comparison_study']))
<div class="rad-info-row">
    <strong>{{ $language === 'fr' ? 'Etude comparee :' : 'Compared with:' }}</strong>
    {{ $payload['comparison_study'] }}
</div>
@endif

@if(!empty($payload['image_location']))
<div class="rad-info-row">
    <strong>{{ $language === 'fr' ? 'Emplacement images :' : 'Image Location:' }}</strong>
    {{ $payload['image_location'] }}
</div>
@endif

{{-- 11. RADIATION DOSE (CT / X-Ray only) --}}
@if(!empty($payload['radiation_dose_mgy']) && in_array($modality, ['CT', 'X-Ray']))
<div class="rad-dose-note">
    {{ $language === 'fr' ? 'Dose effective :' : 'Effective Dose:' }}
    {{ $payload['radiation_dose_mgy'] }} mGy
    &mdash; {{ $language === 'fr' ? 'Pour information dosimetrique uniquement' : 'For dosimetry reference only' }}
</div>
@endif

{{-- 12. RADIOLOGIST SIGNATURE BLOCK --}}
<div class="rad-radiologist-block">
    <div style="font-size:10px;color:#475569;">
        <div style="font-size:9px;text-transform:uppercase;color:#94A3B8;letter-spacing:0.4px;margin-bottom:1mm;">
            {{ $language === 'fr' ? 'Radiologue Rapporteur' : 'Reporting Radiologist' }}
        </div>
        <div style="font-weight:700;color:#0F172A;font-size:11px;">
            {{ $payload['radiologist_name'] ?? $issuer_name }}
        </div>
        @if(!empty($payload['radiologist_registration']))
        <div style="color:#475569;font-size:9px;margin-top:0.5mm;">
            {{ $language === 'fr' ? 'Matricule :' : 'Registration No:' }}
            <strong>{{ $payload['radiologist_registration'] }}</strong>
        </div>
        @endif
    </div>
    <div style="text-align:right;">
        <div style="font-size:8.5px;color:#475569;margin-bottom:1mm;">
            {{ $language === 'fr' ? 'Date du rapport :' : 'Report Date:' }} {{ $repDate }}
        </div>
        <div class="rad-sig-line">{{ $payload['radiologist_name'] ?? $issuer_name }}</div>
        <div>
            <span class="rad-esign-badge">
                &#10003; {{ $language === 'fr' ? 'Signe electroniquement' : 'Electronically Signed' }}
            </span>
        </div>
    </div>
</div>
@endsection
