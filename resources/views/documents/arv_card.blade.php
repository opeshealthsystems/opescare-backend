@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Carte de Traitement ARV' : 'HIV/ART Treatment Card' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Fiche de suivi ARV — MINSANTE / Confidentiel — ARV' : 'ART Patient Follow-up Card — MINSANTE Tracking — ARV' }}
@endsection

@section('content')
<style>
    /* CONFIDENTIAL banner */
    .arv-confidential-banner {
        background-color: #0F766E;
        color: #FFFFFF;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .arv-conf-title {
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .arv-conf-sub {
        font-size: 8.5px;
        color: #CCFBF1;
        margin-top: 0.5mm;
        font-style: italic;
    }
    .arv-conf-badge {
        background-color: #FFFFFF;
        color: #0F766E;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-radius: 4px;
        padding: 1.5mm 3mm;
        flex-shrink: 0;
    }

    /* Enrollment summary grid */
    .arv-enroll-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .arv-stat-cell {
        background-color: #F0FDFA;
        border: 1px solid #99F6E4;
        border-radius: 6px;
        padding: 2.5mm 3mm;
        text-align: center;
    }
    .arv-stat-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #0F766E;
        margin-bottom: 1mm;
    }
    .arv-stat-value {
        font-size: 12px;
        font-weight: 800;
        color: #134E4A;
    }
    .arv-stat-sub {
        font-size: 8px;
        color: #64748B;
        margin-top: 0.5mm;
    }

    /* Current regimen box */
    .arv-regimen-box {
        background-color: #F0FDFA;
        border: 2px solid #0F766E;
        border-radius: 8px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
    }
    .arv-regimen-label {
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #0F766E;
        margin-bottom: 2mm;
        border-bottom: 1px solid #99F6E4;
        padding-bottom: 1.5mm;
    }
    .arv-regimen-line-badge {
        display: inline-block;
        background-color: #CCFBF1;
        border: 1px solid #5EEAD4;
        border-radius: 9999px;
        padding: 0.5mm 2.5mm;
        font-size: 8.5px;
        font-weight: 700;
        color: #0F766E;
        margin-right: 2mm;
    }
    .arv-regimen-name {
        font-size: 12px;
        font-weight: 800;
        color: #134E4A;
        margin: 1.5mm 0;
        line-height: 1.4;
    }
    .undetectable-badge {
        display: inline-block;
        background-color: #DCFCE7;
        border: 1.5px solid #4ADE80;
        border-radius: 9999px;
        padding: 1mm 3mm;
        font-size: 9px;
        font-weight: 800;
        color: #15803D;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Two-column info grid */
    .arv-two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }

    /* OI / coinfection flags */
    .arv-flag-row {
        display: flex;
        align-items: center;
        gap: 2mm;
        padding: 1.5mm 0;
        font-size: 10px;
        color: #334155;
        border-bottom: 1px solid #F1F5F9;
    }
    .arv-flag-row:last-child { border-bottom: none; }
    .arv-flag-dot {
        width: 2.5mm;
        height: 2.5mm;
        border-radius: 50%;
        background-color: #0F766E;
        flex-shrink: 0;
    }

    /* Visit log table */
    .arv-visit-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .arv-visit-table th {
        background-color: #CCFBF1;
        color: #0F766E;
        font-weight: 700;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 2.5mm;
        border-bottom: 2px solid #5EEAD4;
        text-align: left;
        white-space: nowrap;
    }
    .arv-visit-table td {
        padding: 2.5mm 2.5mm;
        border-bottom: 1px solid #F0FDFA;
        color: #1E293B;
        font-size: 9px;
    }
    .arv-visit-table tr:nth-child(even) td { background-color: #F0FDFA; }
    .arv-visit-table tr:last-child td { border-bottom: none; }
    .adherence-good  { color: #15803D; font-weight: 700; }
    .adherence-fair  { color: #D97706; font-weight: 700; }
    .adherence-poor  { color: #DC2626; font-weight: 700; }

    /* Counselling dates */
    .counsel-date-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2mm;
        padding: 3mm;
    }
    .counsel-date-chip {
        background-color: #F0FDFA;
        border: 1px solid #5EEAD4;
        border-radius: 4px;
        padding: 1mm 2.5mm;
        font-size: 9.5px;
        font-weight: 600;
        color: #0F766E;
    }

    /* Case manager / next appt box */
    .arv-appt-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
    }
    .arv-appt-cell {
        background-color: #F0FDFA;
        border: 1.5px solid #5EEAD4;
        border-radius: 6px;
        padding: 3mm 4mm;
    }
    .arv-appt-cell-label {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0F766E;
        margin-bottom: 1.5mm;
    }
    .arv-appt-cell-value {
        font-size: 12px;
        font-weight: 800;
        color: #134E4A;
    }

    /* Previous regimens table */
    .prev-reg-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .prev-reg-table th {
        background-color: #F0FDFA;
        color: #0F766E;
        font-weight: 700;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 2.5mm;
        border-bottom: 1px solid #99F6E4;
        text-align: left;
    }
    .prev-reg-table td {
        padding: 2.5mm 2.5mm;
        border-bottom: 1px solid #F0FDFA;
        color: #334155;
        font-size: 9px;
    }
    .prev-reg-table tr:last-child td { border-bottom: none; }
</style>

{{-- 1. CONFIDENTIAL banner --}}
<div class="arv-confidential-banner">
    <div>
        <div class="arv-conf-title">
            {{ $language === 'fr' ? 'CARTE DE TRAITEMENT ARV / VIH — STRICTEMENT CONFIDENTIEL' : 'HIV/ART TREATMENT CARD — STRICTLY CONFIDENTIAL' }}
        </div>
        <div class="arv-conf-sub">
            {{ $language === 'fr'
                ? 'CONFIDENTIEL — Diffusion restreinte — MINSANTE — Cameroun'
                : 'Restricted Distribution — MINSANTE Cameroon — Authorised Personnel Only' }}
        </div>
    </div>
    <div class="arv-conf-badge">CONFIDENTIEL</div>
</div>

{{-- 2. Enrollment summary --}}
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'RÉCAPITULATIF DE LA PRISE EN CHARGE' : 'ENROLLMENT SUMMARY' }}
    </div>
    <div class="card-body">
        <div class="arv-enroll-grid">
            <div class="arv-stat-cell">
                <div class="arv-stat-label">{{ $language === 'fr' ? 'Test VIH' : 'HIV Test Date' }}</div>
                <div class="arv-stat-value" style="font-size: 10px;">{{ $payload['hiv_test_date'] ?? '' }}</div>
            </div>
            <div class="arv-stat-cell">
                <div class="arv-stat-label">{{ $language === 'fr' ? 'Confirmation' : 'Confirmation Date' }}</div>
                <div class="arv-stat-value" style="font-size: 10px;">{{ $payload['hiv_confirmation_date'] ?? '' }}</div>
            </div>
            <div class="arv-stat-cell">
                <div class="arv-stat-label">{{ $language === 'fr' ? 'Stade OMS init.' : 'WHO Stage (Enroll.)' }}</div>
                <div class="arv-stat-value">{{ $payload['who_stage_at_enrollment'] ?? '' }}</div>
                <div class="arv-stat-sub">{{ $language === 'fr' ? 'CD4 :' : 'CD4:' }} {{ $payload['cd4_at_enrollment'] ?? '' }}</div>
            </div>
            <div class="arv-stat-cell">
                <div class="arv-stat-label">{{ $language === 'fr' ? 'Stade OMS actuel' : 'WHO Stage (Current)' }}</div>
                <div class="arv-stat-value">{{ $payload['who_stage_current'] ?? '' }}</div>
                <div class="arv-stat-sub">{{ $language === 'fr' ? 'CD4 :' : 'CD4:' }} {{ $payload['cd4_current'] ?? '' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Current Regimen box --}}
<div class="arv-regimen-box">
    <div class="arv-regimen-label">
        {{ $language === 'fr' ? 'TRAITEMENT ARV EN COURS' : 'CURRENT ART REGIMEN' }}
    </div>
    <div style="margin-bottom: 1.5mm;">
        <span class="arv-regimen-line-badge">{{ $payload['regimen_line'] ?? '' }}</span>
        <span style="font-size: 8.5px; color: #64748B;">
            {{ $language === 'fr' ? 'Début :' : 'Started:' }} <strong>{{ $payload['regimen_start_date'] ?? '' }}</strong>
        </span>
    </div>
    <div class="arv-regimen-name">{{ $payload['current_regimen'] ?? '' }}</div>
    <div style="display: flex; align-items: center; gap: 3mm; margin-top: 1.5mm;">
        <div>
            <span style="font-size: 8.5px; color: #64748B;">{{ $language === 'fr' ? 'Charge virale :' : 'Viral Load:' }}</span>
            <span style="font-size: 10.5px; font-weight: 700; color: #0F172A; margin-left: 1mm;">{{ $payload['viral_load_current'] ?? '' }}</span>
            @if(!empty($payload['viral_load_date']))
            <span style="font-size: 8.5px; color: #94A3B8; margin-left: 1mm;">({{ $payload['viral_load_date'] }})</span>
            @endif
        </div>
        @php
            $vl = $payload['viral_load_current'] ?? '';
            $isUndetectable = (stripos($vl, '< 50') !== false || stripos($vl, 'undetectable') !== false || stripos($vl, 'indétectable') !== false);
        @endphp
        @if($isUndetectable)
        <span class="undetectable-badge">&#10003; {{ $language === 'fr' ? 'INDÉTECTABLE' : 'UNDETECTABLE' }}</span>
        @endif
    </div>
</div>

{{-- 4. TB Co-infection + PMTCT --}}
@if(!empty($payload['tb_coinfection']) || !empty($payload['pmtct']))
<div class="arv-two-col">
    @if(!empty($payload['tb_coinfection']))
    <div class="content-card" style="margin-bottom: 0; border-color: #5EEAD4;">
        <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
            {{ $language === 'fr' ? 'CO-INFECTION TB' : 'TB CO-INFECTION' }}
        </div>
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 2mm; margin-bottom: 2mm;">
                <span style="font-size: 11px; font-weight: 800; color: #B45309;">&#9888;</span>
                <span style="font-size: 10.5px; font-weight: 700; color: #0F172A;">
                    {{ $language === 'fr' ? 'Co-infection TB active' : 'Active TB Co-infection' }}
                </span>
            </div>
            @if(!empty($payload['tb_treatment_status']))
            <div style="font-size: 10px; color: #334155;">
                <span style="font-weight: 600;">{{ $language === 'fr' ? 'Statut traitement TB :' : 'TB Treatment Status:' }}</span>
                {{ $payload['tb_treatment_status'] }}
            </div>
            @endif
        </div>
    </div>
    @endif
    @if(!empty($payload['pmtct']))
    <div class="content-card" style="margin-bottom: 0; border-color: #5EEAD4;">
        <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
            {{ $language === 'fr' ? 'PTME' : 'PMTCT' }}
        </div>
        <div class="card-body" style="font-size: 10.5px; color: #0F172A;">
            <span style="font-weight: 700;">
                {{ $language === 'fr'
                    ? 'Patiente inscrite au programme PTME (Prévention de la Transmission Mère-Enfant).'
                    : 'Patient enrolled in PMTCT (Prevention of Mother-to-Child Transmission) programme.' }}
            </span>
        </div>
    </div>
    @endif
</div>
@endif

{{-- 5. OI History --}}
@if(!empty($payload['ois_history']))
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'ANTÉCÉDENTS D\'INFECTIONS OPPORTUNISTES' : 'OPPORTUNISTIC INFECTIONS HISTORY' }}
    </div>
    <div class="card-body">
        @foreach($payload['ois_history'] as $oi)
        <div class="arv-flag-row">
            <div class="arv-flag-dot"></div>
            <span>{{ $oi }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- 6. Visit Log table --}}
@if(!empty($payload['visit_log']))
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'REGISTRE DES CONSULTATIONS' : 'VISIT LOG' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="arv-visit-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'DATE' : 'DATE' }}</th>
                    <th>{{ $language === 'fr' ? 'POIDS' : 'WEIGHT' }}</th>
                    <th>CD4</th>
                    <th>{{ $language === 'fr' ? 'CHARGE VIRALE' : 'VIRAL LOAD' }}</th>
                    <th>{{ $language === 'fr' ? 'OBSERVANCE' : 'ADHERENCE' }}</th>
                    <th>{{ $language === 'fr' ? 'SCHÉMA' : 'REGIMEN' }}</th>
                    <th>{{ $language === 'fr' ? 'NOTES' : 'NOTES' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['visit_log'] as $visit)
                @php
                    $adh = isset($visit['adherence_pct']) ? (float)$visit['adherence_pct'] : null;
                    $adhClass = '';
                    if ($adh !== null) {
                        if ($adh >= 95) $adhClass = 'adherence-good';
                        elseif ($adh >= 80) $adhClass = 'adherence-fair';
                        else $adhClass = 'adherence-poor';
                    }
                @endphp
                <tr>
                    <td style="white-space: nowrap; font-weight: 600;">{{ $visit['date'] ?? '' }}</td>
                    <td>{{ $visit['weight'] ?? '' }}</td>
                    <td>{{ $visit['cd4'] ?? '' }}</td>
                    <td>{{ $visit['viral_load'] ?? '' }}</td>
                    <td class="{{ $adhClass }}">
                        @if($adh !== null){{ $adh }}%@endif
                    </td>
                    <td style="font-size: 8.5px;">{{ $visit['regimen'] ?? '' }}</td>
                    <td style="font-size: 8.5px; color: #64748B;">{{ $visit['notes'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 7. Adherence Counselling Dates --}}
@if(!empty($payload['adherence_counselling_dates']))
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'DATES DE CONSEIL À L\'OBSERVANCE' : 'ADHERENCE COUNSELLING DATES' }}
    </div>
    <div class="counsel-date-grid">
        @foreach($payload['adherence_counselling_dates'] as $counselDate)
        <span class="counsel-date-chip">{{ $counselDate }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- 8. Case Manager + Next Appointment --}}
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'SUIVI ET PROCHAIN RENDEZ-VOUS' : 'FOLLOW-UP &amp; NEXT APPOINTMENT' }}
    </div>
    <div class="card-body">
        <div class="arv-appt-grid">
            <div class="arv-appt-cell">
                <div class="arv-appt-cell-label">{{ $language === 'fr' ? 'Gestionnaire de cas' : 'Case Manager' }}</div>
                <div class="arv-appt-cell-value">{{ $payload['case_manager'] ?? '' }}</div>
            </div>
            <div class="arv-appt-cell">
                <div class="arv-appt-cell-label">{{ $language === 'fr' ? 'Prochain rendez-vous' : 'Next Appointment' }}</div>
                <div class="arv-appt-cell-value">{{ $payload['next_appointment'] ?? '' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 9. Previous Regimens --}}
@if(!empty($payload['previous_regimens']))
<div class="content-card" style="border-color: #5EEAD4;">
    <div class="card-header" style="background-color: #CCFBF1; color: #0F766E;">
        {{ $language === 'fr' ? 'SCHÉMAS THÉRAPEUTIQUES ANTÉRIEURS' : 'PREVIOUS ART REGIMENS' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="prev-reg-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'SCHÉMA' : 'REGIMEN' }}</th>
                    <th>{{ $language === 'fr' ? 'DÉBUT' : 'START DATE' }}</th>
                    <th>{{ $language === 'fr' ? 'FIN' : 'STOP DATE' }}</th>
                    <th>{{ $language === 'fr' ? 'RAISON DE L\'ARRÊT' : 'REASON STOPPED' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['previous_regimens'] as $prev)
                <tr>
                    <td style="font-weight: 600; color: #0F172A;">{{ $prev['regimen'] ?? '' }}</td>
                    <td>{{ $prev['start_date'] ?? '' }}</td>
                    <td>{{ $prev['stop_date'] ?? '' }}</td>
                    <td style="color: #64748B;">{{ $prev['reason_stopped'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Confidentiality reminder at foot of content --}}
<div style="background-color: #F0FDFA; border: 1px solid #5EEAD4; border-radius: 4px; padding: 2.5mm 4mm; margin-top: 2mm; font-size: 8.5px; color: #0F766E; text-align: center; font-weight: 600;">
    {{ $language === 'fr'
        ? 'Document confidentiel — VIH/SIDA. Accès réservé au personnel médical autorisé. Loi n° 2010/012 du Cameroun.'
        : 'Confidential HIV/AIDS document. Authorised medical personnel access only. Cameroon Law No. 2010/012.' }}
</div>
@endsection
