@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Fiche de Transfert' : 'Patient Transfer Letter' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Fiche de transfert inter-établissement — TRF' : 'Inter-Facility Patient Transfer Document — TRF' }}
@endsection

@section('content')
<style>
    /* Transfer urgency badge */
    .trf-urgency-badge {
        display: inline-block;
        padding: 2.5mm 5mm;
        border-radius: 6px;
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .trf-urgency-immediate { background-color: #FEE2E2; color: #991B1B; border: 2px solid #FCA5A5; }
    .trf-urgency-urgent    { background-color: #FFFBEB; color: #B45309; border: 1.5px solid #FCD34D; }
    .trf-urgency-routine   { background-color: #F1F5F9; color: #475569; border: 1.5px solid #CBD5E1; }

    /* Transfer type badge */
    .trf-type-badge {
        display: inline-block;
        padding: 0.5mm 2.5mm;
        border-radius: 9999px;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #BFDBFE;
    }

    /* FROM → TO strip */
    .trf-route-strip {
        display: flex;
        align-items: center;
        gap: 4mm;
        background-color: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 8px;
        padding: 3mm 5mm;
        margin-bottom: 5mm;
    }
    .trf-facility-box {
        flex: 1;
        font-size: 10px;
        line-height: 1.5;
    }
    .trf-facility-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #3B82F6;
        margin-bottom: 0.5mm;
    }
    .trf-facility-name {
        font-weight: 700;
        color: #0F172A;
        font-size: 11px;
    }
    .trf-facility-ward {
        color: #475569;
        font-size: 9.5px;
    }
    .trf-arrow {
        font-size: 18px;
        color: #0369A1;
        font-weight: 900;
        flex-shrink: 0;
    }

    /* Vitals grid */
    .vitals-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        padding: 4mm;
    }
    .vital-cell {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 2.5mm 3mm;
        text-align: center;
        background-color: #FAFAFA;
    }
    .vital-cell.critical {
        background-color: #FEF2F2;
        border-color: #FECACA;
    }
    .vital-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-bottom: 1mm;
    }
    .vital-value {
        font-size: 13px;
        font-weight: 800;
        color: #0F172A;
    }
    .vital-value.critical-value {
        color: #DC2626;
    }

    /* Two-column layout */
    .two-col-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }

    /* List items */
    .trf-list-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 1.5mm 0;
        font-size: 10.5px;
        color: #334155;
        border-bottom: 1px solid #F1F5F9;
    }
    .trf-list-item:last-child { border-bottom: none; }
    .trf-bullet { color: #0369A1; font-weight: 900; flex-shrink: 0; }

    /* Checklist */
    .checklist-item {
        display: flex;
        align-items: center;
        gap: 2mm;
        padding: 1.5mm 0;
        font-size: 10.5px;
        color: #334155;
    }
    .check-box {
        width: 4mm;
        height: 4mm;
        border: 1.5px solid #0369A1;
        border-radius: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background-color: #EFF6FF;
    }
    .check-mark { color: #0369A1; font-weight: 900; font-size: 9px; }

    /* Special instructions box */
    .special-instructions-box {
        background-color: #FFFBEB;
        border: 1.5px solid #F59E0B;
        border-left: 5px solid #D97706;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
        line-height: 1.7;
    }
    .special-instructions-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #D97706;
        margin-bottom: 1.5mm;
    }

    /* Transport info row */
    .transport-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 3mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 10.5px;
    }
    .transport-row:last-child { border-bottom: none; }
    .transport-label {
        font-weight: 600;
        color: #475569;
        min-width: 35mm;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .transport-value { color: #0F172A; font-weight: 500; }

    /* Signature area */
    .sig-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
        margin-top: 4mm;
    }
    .sig-block {
        border-top: 1.5px solid #CBD5E1;
        padding-top: 2mm;
        font-size: 9px;
        color: #475569;
    }
    .sig-name { font-weight: 700; color: #0F172A; font-size: 10px; margin-top: 6mm; }
    .sig-role { color: #64748B; font-style: italic; }
    .sig-space { height: 10mm; }
</style>

@php
    $urgency = strtolower($payload['transfer_urgency'] ?? 'routine');
    $urgencyMap = ['immediate' => 'IMMEDIATE', 'urgent' => 'URGENT', 'routine' => 'ROUTINE'];
    $vitals = $payload['vitals_at_transfer'] ?? [];
@endphp

{{-- 1. Transfer header strip: URGENT badge + FROM → TO + date/time --}}
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5mm; padding: 3mm 5mm; background-color: #F0F9FF; border: 1.5px solid #BAE6FD; border-radius: 8px;">
    <div>
        <div style="font-size: 13px; font-weight: 900; color: #0369A1; text-transform: uppercase; letter-spacing: 1px;">
            {{ $language === 'fr' ? 'FICHE DE TRANSFERT PATIENT' : 'PATIENT TRANSFER LETTER' }}
        </div>
        <div style="font-size: 9px; color: #0284C7; margin-top: 0.5mm;">
            <span class="trf-type-badge">{{ $payload['transfer_type'] ?? 'Inter-facility' }}</span>
            &nbsp;|&nbsp;
            {{ $language === 'fr' ? 'Date :' : 'Transfer Date:' }} <strong>{{ $payload['transfer_date'] ?? '' }}</strong>
            &nbsp;|&nbsp;
            {{ $language === 'fr' ? 'Heure :' : 'Time:' }} <strong>{{ $payload['transfer_time'] ?? '' }}</strong>
        </div>
    </div>
    <div>
        <span class="trf-urgency-badge trf-urgency-{{ $urgency }}">
            {{ $urgencyMap[$urgency] ?? strtoupper($urgency) }}
        </span>
    </div>
</div>

{{-- FROM → TO facility route --}}
<div class="trf-route-strip">
    <div class="trf-facility-box">
        <div class="trf-facility-label">{{ $language === 'fr' ? 'ÉTABLISSEMENT D\'ORIGINE' : 'FROM FACILITY' }}</div>
        <div class="trf-facility-name">{{ $payload['from_facility'] ?? '' }}</div>
        <div class="trf-facility-ward">{{ $payload['from_ward'] ?? '' }}</div>
    </div>
    <div class="trf-arrow">&#10142;</div>
    <div class="trf-facility-box" style="text-align: right;">
        <div class="trf-facility-label" style="text-align: right;">{{ $language === 'fr' ? 'ÉTABLISSEMENT RECEVEUR' : 'TO FACILITY' }}</div>
        <div class="trf-facility-name">{{ $payload['to_facility'] ?? '' }}</div>
        <div class="trf-facility-ward">{{ $payload['to_department'] ?? '' }}</div>
        @if(!empty($payload['receiving_physician']))
        <div style="font-size: 9px; color: #1D4ED8; margin-top: 0.5mm;">
            {{ $language === 'fr' ? 'Médecin receveur :' : 'Receiving Physician:' }} {{ $payload['receiving_physician'] }}
        </div>
        @endif
    </div>
</div>

{{-- 2. Vitals at Transfer card --}}
<div class="content-card" style="border-color: #BAE6FD;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'CONSTANTES AU MOMENT DU TRANSFERT' : 'VITALS AT TIME OF TRANSFER' }}
    </div>
    <div class="vitals-grid">
        @php
            $vitalDefs = [
                ['key' => 'bp',   'label' => 'BP / TA', 'unit' => 'mmHg'],
                ['key' => 'pulse','label' => 'Pulse', 'unit' => 'bpm'],
                ['key' => 'spo2', 'label' => 'SpO₂', 'unit' => '%'],
                ['key' => 'temp', 'label' => 'Temp', 'unit' => '°C'],
                ['key' => 'rr',   'label' => 'RR', 'unit' => '/min'],
                ['key' => 'gcs',  'label' => 'GCS', 'unit' => '/15'],
            ];
        @endphp
        @foreach($vitalDefs as $vd)
        @php
            $val = $vitals[$vd['key']] ?? 'N/A';
            $isCritical = false;
            if ($vd['key'] === 'spo2' && is_numeric($val) && (float)$val < 90) $isCritical = true;
            if ($vd['key'] === 'gcs'  && is_numeric($val) && (float)$val < 9)  $isCritical = true;
            if ($vd['key'] === 'temp' && is_numeric($val) && ((float)$val > 39.5 || (float)$val < 35)) $isCritical = true;
        @endphp
        <div class="vital-cell {{ $isCritical ? 'critical' : '' }}">
            <div class="vital-label">{{ $vd['label'] }}</div>
            <div class="vital-value {{ $isCritical ? 'critical-value' : '' }}">{{ $val }}</div>
            <div style="font-size: 8px; color: #94A3B8;">{{ $vd['unit'] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- 3. Transport Details --}}
<div class="content-card" style="border-color: #BAE6FD;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'DÉTAILS DU TRANSPORT' : 'TRANSPORT DETAILS' }}
    </div>
    <div class="card-body">
        <div class="transport-row">
            <span class="transport-label">{{ $language === 'fr' ? 'Mode de transport' : 'Transport Mode' }}</span>
            <span class="transport-value">{{ $payload['transport_mode'] ?? '' }}</span>
        </div>
        <div class="transport-row">
            <span class="transport-label">{{ $language === 'fr' ? 'Escorte médicale' : 'Medical Escort' }}</span>
            <span class="transport-value">{{ $payload['escort'] ?? '' }}</span>
        </div>
        <div class="transport-row">
            <span class="transport-label">{{ $language === 'fr' ? 'Voie d\'abord IV' : 'IV Access' }}</span>
            <span class="transport-value">{{ $payload['iv_access'] ?? '' }}</span>
        </div>
    </div>
</div>

{{-- 4. Active Medications & Ongoing Treatments (two-column) --}}
<div class="two-col-grid">
    <div class="content-card" style="margin-bottom: 0; border-color: #BAE6FD;">
        <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
            {{ $language === 'fr' ? 'MÉDICAMENTS EN COURS' : 'ACTIVE MEDICATIONS' }}
        </div>
        <div class="card-body">
            @forelse($payload['active_medications'] ?? [] as $med)
            <div class="trf-list-item">
                <span class="trf-bullet">•</span>
                <span>{{ $med }}</span>
            </div>
            @empty
            <div style="font-size: 10px; color: #94A3B8; font-style: italic;">{{ $language === 'fr' ? 'Aucun médicament actif' : 'No active medications' }}</div>
            @endforelse
        </div>
    </div>
    <div class="content-card" style="margin-bottom: 0; border-color: #BAE6FD;">
        <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
            {{ $language === 'fr' ? 'TRAITEMENTS EN COURS' : 'ONGOING TREATMENTS' }}
        </div>
        <div class="card-body">
            @forelse($payload['ongoing_treatments'] ?? [] as $tx)
            <div class="trf-list-item">
                <span class="trf-bullet">•</span>
                <span>{{ $tx }}</span>
            </div>
            @empty
            <div style="font-size: 10px; color: #94A3B8; font-style: italic;">{{ $language === 'fr' ? 'Aucun traitement en cours' : 'No ongoing treatments' }}</div>
            @endforelse
        </div>
    </div>
</div>

{{-- 5. Clinical Summary --}}
<div class="content-card" style="border-color: #BAE6FD; margin-top: 0;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'RÉSUMÉ CLINIQUE' : 'CLINICAL SUMMARY' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155; text-align: justify;">
        {{ $payload['clinical_summary'] ?? '' }}
    </div>
</div>

{{-- 6. Reason for Transfer --}}
<div class="content-card" style="border-color: #BAE6FD;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'MOTIF DU TRANSFERT' : 'REASON FOR TRANSFER' }}
    </div>
    <div class="card-body" style="font-size: 10.5px; line-height: 1.7; color: #334155; text-align: justify;">
        {{ $payload['reason_for_transfer'] ?? '' }}
    </div>
</div>

{{-- 7. Documents Accompanying checklist --}}
@if(!empty($payload['documents_accompanying']))
<div class="content-card" style="border-color: #BAE6FD;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'DOCUMENTS JOINTS' : 'DOCUMENTS ACCOMPANYING' }}
    </div>
    <div class="card-body">
        @foreach($payload['documents_accompanying'] as $doc)
        <div class="checklist-item">
            <div class="check-box"><span class="check-mark">&#10003;</span></div>
            <span style="font-size: 10.5px; color: #334155;">{{ $doc }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- 8. Special Instructions (amber border) --}}
@if(!empty($payload['special_instructions']))
<div class="special-instructions-box">
    <div class="special-instructions-label">
        {{ $language === 'fr' ? 'INSTRUCTIONS PARTICULIÈRES' : 'SPECIAL INSTRUCTIONS' }}
    </div>
    {{ $payload['special_instructions'] }}
</div>
@endif

{{-- 9. Signature block --}}
<div class="content-card" style="border-color: #BAE6FD;">
    <div class="card-header" style="background-color: #E0F2FE; color: #0369A1;">
        {{ $language === 'fr' ? 'SIGNATURES' : 'SIGNATURES' }}
    </div>
    <div class="card-body">
        <div class="sig-grid">
            <div>
                <div style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 1mm;">
                    {{ $language === 'fr' ? 'MÉDECIN TRANSFÉRANT' : 'TRANSFERRING PHYSICIAN' }}
                </div>
                <div class="sig-space"></div>
                <div class="sig-block">
                    <div class="sig-name">{{ $issuer_name }}</div>
                    <div class="sig-role">{{ $issuer_role }}</div>
                    <div style="font-size: 9px; color: #64748B; margin-top: 0.5mm;">{{ $payload['from_facility'] ?? '' }}</div>
                </div>
            </div>
            <div>
                <div style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 1mm;">
                    {{ $language === 'fr' ? 'ACCUSÉ DE RÉCEPTION — MÉDECIN RECEVEUR' : 'RECEIVING PHYSICIAN ACKNOWLEDGEMENT' }}
                </div>
                <div class="sig-space"></div>
                <div class="sig-block">
                    <div style="border-top: 1px dashed #CBD5E1; padding-top: 1mm; color: #94A3B8; font-style: italic; font-size: 9px;">
                        {{ $language === 'fr' ? 'Signature &amp; cachet du médecin receveur' : 'Signature &amp; stamp of receiving physician' }}
                    </div>
                    @if(!empty($payload['receiving_physician']))
                    <div class="sig-name">{{ $payload['receiving_physician'] }}</div>
                    <div class="sig-role">{{ $payload['to_department'] ?? '' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
