@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Certificat de Vaccination' : 'Immunization Certificate' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Registre officiel de vaccination — OpesCare' : 'Official Vaccination Record — OpesCare' }}
@endsection

@section('content')
<style>
    /* IMM accent: #7C3AED violet */
    .imm-who-header {
        border: 3px solid #7C3AED;
        border-radius: 8px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        gap: 4mm;
        background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
    }
    .imm-who-logo {
        background: #1D4ED8;
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 900;
        width: 14mm; height: 14mm;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        letter-spacing: 1px;
    }
    .imm-who-titles .who-title-en {
        font-size: 12px; font-weight: 800; color: #1D4ED8; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .imm-who-titles .who-title-fr {
        font-size: 10px; font-weight: 600; color: #5B21B6; font-style: italic;
    }
    .imm-who-titles .who-ref {
        font-size: 8px; color: #6D28D9; margin-top: 0.5mm;
    }

    .imm-status-badge {
        display: inline-block;
        border-radius: 6px; padding: 2mm 5mm;
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        margin-bottom: 5mm;
    }
    .imm-up-to-date { background: #DCFCE7; color: #166534; border: 2px solid #16A34A; }
    .imm-incomplete  { background: #FEF3C7; color: #92400E; border: 2px solid #D97706; }
    .imm-overdue     { background: #FEE2E2; color: #7F1D1D; border: 2px solid #DC2626; }

    .imm-yf-block {
        background: linear-gradient(135deg, #FFF7ED 0%, #FEF3C7 100%);
        border: 2px solid #D97706; border-radius: 8px;
        padding: 4mm; margin-bottom: 5mm;
    }
    .imm-yf-title { font-size: 11px; font-weight: 800; color: #92400E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2mm; }
    .imm-yf-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3mm; }
    .imm-yf-item .yf-label { font-size: 8px; color: #92400E; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
    .imm-yf-item .yf-value { font-size: 11px; font-weight: 700; color: #78350F; }
    .imm-yf-note { font-size: 8.5px; color: #92400E; font-style: italic; margin-top: 2mm; padding-top: 2mm; border-top: 1px solid #FDE68A; }

    .imm-section-card { border: 1px solid #E2E8F0; border-radius: 6px; margin-bottom: 5mm; overflow: hidden; }
    .imm-section-header {
        background: #F5F3FF; border-bottom: 2px solid #DDD6FE; color: #5B21B6;
        font-weight: 700; font-size: 10px; padding: 2mm 4mm; text-transform: uppercase; letter-spacing: 0.6px;
    }

    .imm-vaccine-table { width: 100%; border-collapse: collapse; font-size: 9px; }
    .imm-vaccine-table th {
        background: #F5F3FF; color: #5B21B6; font-weight: 700; text-align: left;
        padding: 2mm 2.5mm; border-bottom: 2px solid #DDD6FE;
        text-transform: uppercase; font-size: 8px;
    }
    .imm-vaccine-table td { padding: 2.5mm; border-bottom: 1px solid #F1F5F9; font-size: 9px; }
    .imm-row-complete { background: #F0FDF4; }
    .imm-row-due { background: #FFFBEB; }
    .imm-row-missed { background: #FEF2F2; }
    .imm-dose-badge-complete {
        background: #DCFCE7; color: #166534; font-size: 8px; font-weight: 700;
        padding: 0.5mm 2mm; border-radius: 10px; display: inline-block;
    }
    .imm-dose-badge-due {
        background: #FEF3C7; color: #92400E; font-size: 8px; font-weight: 700;
        padding: 0.5mm 2mm; border-radius: 10px; display: inline-block;
    }
    .imm-dose-badge-missed {
        background: #FEE2E2; color: #B91C1C; font-size: 8px; font-weight: 700;
        padding: 0.5mm 2mm; border-radius: 10px; display: inline-block;
    }

    .imm-stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .imm-stat-card {
        border-radius: 8px; padding: 4mm; text-align: center;
        border: 1px solid #E2E8F0;
    }
    .imm-stat-card.imm-doses-card { background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%); }
    .imm-stat-card.imm-doses-card .stat-num { font-size: 36px; font-weight: 900; color: #FFFFFF; }
    .imm-stat-card.imm-doses-card .stat-lbl { font-size: 9px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px; }
    .imm-stat-card.imm-nextdue-card { background: #FFFBEB; border-color: #FDE68A; }
    .imm-stat-card.imm-nextdue-card .stat-num { font-size: 13px; font-weight: 800; color: #92400E; }
    .imm-stat-card.imm-nextdue-card .stat-lbl { font-size: 9px; color: #92400E; text-transform: uppercase; letter-spacing: 0.5px; }

    .imm-certify-statement {
        background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 6px;
        padding: 4mm; margin-bottom: 5mm; font-size: 9.5px; color: #3730A3;
        font-style: italic; line-height: 1.7; text-align: justify;
    }

    .imm-provider-block {
        border-top: 1px solid #E2E8F0; padding-top: 4mm;
        display: flex; justify-content: space-between; align-items: flex-end;
        margin-bottom: 4mm;
    }
    .imm-sig-line {
        border-top: 1px solid #94A3B8; padding-top: 1mm; margin-top: 7mm;
        font-size: 9px; color: #475569; width: 55mm; display: inline-block; text-align: center;
    }
    .imm-stamp-box {
        border: 2px dashed #DDD6FE; border-radius: 6px;
        width: 30mm; height: 20mm; display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: #A78BFA; font-weight: 600; text-align: center;
    }
</style>

@php
    $isIntl    = !empty($payload['international_certificate']);
    $immStatus = $payload['immunization_status'] ?? 'up_to_date';
    $statusMap = [
        'up_to_date' => ['class' => 'imm-up-to-date', 'en' => 'UP TO DATE',  'fr' => 'A JOUR'],
        'incomplete'  => ['class' => 'imm-incomplete',  'en' => 'INCOMPLETE',  'fr' => 'INCOMPLET'],
        'overdue'     => ['class' => 'imm-overdue',      'en' => 'OVERDUE',     'fr' => 'EN RETARD'],
    ];
    $statusInfo = $statusMap[$immStatus] ?? $statusMap['up_to_date'];
    $totalDoses = $payload['total_doses_given'] ?? count($payload['vaccines'] ?? []);
@endphp

{{-- 1. WHO INTERNATIONAL CERTIFICATE HEADER (conditional) --}}
@if($isIntl)
<div class="imm-who-header">
    <div class="imm-who-logo">WHO</div>
    <div class="imm-who-titles">
        <div class="who-title-en">INTERNATIONAL CERTIFICATE OF VACCINATION OR PROPHYLAXIS</div>
        <div class="who-title-fr">Certificat International de Vaccination ou de Prophylaxie</div>
        <div class="who-ref">
            {{ $language === 'fr'
                ? 'Conforme au Reglement Sanitaire International (RSI 2005) de l\'OMS'
                : 'In accordance with WHO International Health Regulations (IHR 2005)' }}
        </div>
    </div>
</div>
@endif

{{-- 2. STATUS BADGE --}}
<div style="margin-bottom:5mm;">
    <span class="imm-status-badge {{ $statusInfo['class'] }}">
        &#9679; {{ $statusInfo[$language] }}
    </span>
    @if(!empty($payload['certificate_purpose']))
    <span style="margin-left:3mm;background:#F5F3FF;border:1px solid #DDD6FE;color:#5B21B6;padding:1.5mm 3mm;border-radius:4px;font-size:9px;font-weight:600;text-transform:uppercase;">
        {{ strtoupper($payload['certificate_purpose']) }}
    </span>
    @endif
</div>

{{-- 3. YELLOW FEVER CERTIFICATE BLOCK --}}
@if(!empty($payload['yellow_fever_certificate_number']))
<div class="imm-yf-block">
    <div class="imm-yf-title">
        &#9733; {{ $language === 'fr' ? 'CERTIFICAT FIEVRE JAUNE / Yellow Fever Certificate' : 'YELLOW FEVER CERTIFICATE / Certificat Fievre Jaune' }}
    </div>
    <div class="imm-yf-grid">
        <div class="imm-yf-item">
            <div class="yf-label">{{ $language === 'fr' ? 'N° Certificat' : 'Certificate No.' }}</div>
            <div class="yf-value">{{ $payload['yellow_fever_certificate_number'] }}</div>
        </div>
        <div class="imm-yf-item">
            <div class="yf-label">{{ $language === 'fr' ? 'Valide du' : 'Valid From' }}</div>
            <div class="yf-value">{{ $payload['yellow_fever_valid_from'] ?? '—' }}</div>
        </div>
        <div class="imm-yf-item">
            <div class="yf-label">{{ $language === 'fr' ? 'Valide jusqu\'au' : 'Valid Until' }}</div>
            <div class="yf-value">{{ $payload['yellow_fever_valid_until'] ?? 'Lifetime' }}</div>
        </div>
    </div>
    <div class="imm-yf-note">
        {{ $language === 'fr'
            ? 'Ce certificat est valable pour toute la vie du vacciné conformément au RSI OMS 2005.'
            : 'This certificate is valid for the lifetime of the vaccinee per WHO IHR 2005.' }}
    </div>
</div>
@endif

{{-- 4. IMMUNIZATION RECORD TABLE --}}
<div class="imm-section-card">
    <div class="imm-section-header">{{ $language === 'fr' ? 'CARNET DE VACCINATION' : 'IMMUNIZATION RECORD' }}</div>
    <table class="imm-vaccine-table">
        <thead>
            <tr>
                <th>{{ $language === 'fr' ? 'Vaccin' : 'Vaccine' }}</th>
                <th>{{ $language === 'fr' ? 'Antigene' : 'Antigen' }}</th>
                <th>{{ $language === 'fr' ? 'Dose N°' : 'Dose #' }}</th>
                <th>{{ $language === 'fr' ? 'Date' : 'Date' }}</th>
                <th>{{ $language === 'fr' ? 'N° Lot' : 'Batch No.' }}</th>
                <th>{{ $language === 'fr' ? 'Voie/Site' : 'Route/Site' }}</th>
                <th>{{ $language === 'fr' ? 'Administré par' : 'Given By' }}</th>
                <th>{{ $language === 'fr' ? 'Prochaine dose' : 'Next Due' }}</th>
                <th>{{ $language === 'fr' ? 'Statut' : 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payload['vaccines'] ?? [] as $vac)
            @php
                $isDue    = empty($vac['date_given']) && !empty($vac['next_dose_due']);
                $isMissed = empty($vac['date_given']) && empty($vac['next_dose_due']);
                $rowClass = $isDue ? 'imm-row-due' : ($isMissed ? 'imm-row-missed' : 'imm-row-complete');
                $badgeClass = $isDue ? 'imm-dose-badge-due' : ($isMissed ? 'imm-dose-badge-missed' : 'imm-dose-badge-complete');
                $badgeText  = $isDue ? ($language === 'fr' ? 'DUE' : 'DUE') : ($isMissed ? ($language === 'fr' ? 'MANQUE' : 'MISSED') : ($language === 'fr' ? 'DONNE' : 'GIVEN'));
            @endphp
            <tr class="{{ $rowClass }}">
                <td><strong>{{ $vac['vaccine_name'] ?? '—' }}</strong></td>
                <td>{{ $vac['antigen'] ?? '—' }}</td>
                <td style="text-align:center;"><strong>{{ $vac['dose_number'] ?? '—' }}</strong></td>
                <td>{{ $vac['date_given'] ?? '—' }}</td>
                <td style="font-family:monospace;font-size:8.5px;">{{ $vac['batch_number'] ?? '—' }}</td>
                <td>{{ $vac['route'] ?? '—' }}{{ !empty($vac['site']) ? ' / '.$vac['site'] : '' }}</td>
                <td>{{ $vac['given_by'] ?? '—' }}</td>
                <td>{{ $vac['next_dose_due'] ?? '—' }}</td>
                <td><span class="{{ $badgeClass }}">{{ $badgeText }}</span></td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:#94A3B8;font-style:italic;padding:4mm;">
                {{ $language === 'fr' ? 'Aucune vaccination enregistree' : 'No vaccines recorded' }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 5. SUMMARY STATS --}}
<div class="imm-stats-row">
    <div class="imm-stat-card imm-doses-card">
        <div class="stat-num">{{ $totalDoses }}</div>
        <div class="stat-lbl">{{ $language === 'fr' ? 'Doses Administrees' : 'Total Doses Given' }}</div>
    </div>
    <div class="imm-stat-card imm-nextdue-card">
        @php
            $nextDue = null;
            foreach (($payload['vaccines'] ?? []) as $v) {
                if (!empty($v['next_dose_due'])) { $nextDue = $v['next_dose_due']; break; }
            }
        @endphp
        <div class="stat-lbl" style="margin-bottom:2mm;">{{ $language === 'fr' ? 'Prochaine Dose' : 'Next Dose Due' }}</div>
        <div class="stat-num">{{ $nextDue ?? ($language === 'fr' ? 'Aucune' : 'None') }}</div>
    </div>
</div>

{{-- 6. CERTIFYING STATEMENT --}}
<div class="imm-certify-statement">
    {{ $language === 'fr'
        ? '"Le present certificat atteste que l\'individu nomme ci-dessus a recu les vaccins indiques conformement au programme national de vaccination et/ou aux prescriptions cliniques en vigueur."'
        : '"This record certifies that the named individual has received the vaccines listed above in accordance with the national immunization programme and/or clinical requirements."' }}
</div>

{{-- 7. PROVIDER SIGNATURE + FACILITY STAMP --}}
<div class="imm-provider-block">
    <div style="font-size:10px;color:#475569;">
        <div style="font-size:9px;text-transform:uppercase;color:#94A3B8;letter-spacing:0.4px;margin-bottom:1mm;">
            {{ $language === 'fr' ? 'Prestataire certifiant' : 'Certifying Provider' }}
        </div>
        <div style="font-weight:700;color:#0F172A;font-size:11px;">{{ $issuer_name }}</div>
        <div style="font-size:9px;color:#475569;margin-top:0.5mm;">{{ $issuer_role }}</div>
        <div style="font-size:9px;color:#475569;">{{ $facility_name }}</div>
        <div class="imm-sig-line">{{ $issuer_name }}</div>
        <div style="font-size:8px;color:#94A3B8;margin-top:1mm;">
            {{ $language === 'fr' ? 'Signature autorisee' : 'Authorized Signature' }}
        </div>
    </div>
    <div style="text-align:center;">
        <div class="imm-stamp-box">
            {{ $language === 'fr' ? 'CACHET\nDE L\'ETABLISSEMENT' : 'FACILITY\nSTAMP' }}
        </div>
        <div style="font-size:8px;color:#94A3B8;margin-top:1mm;">
            {{ $language === 'fr' ? 'Sceau officiel' : 'Official seal' }}
        </div>
    </div>
</div>
@endsection
