@extends('documents.base')

@section('title', 'LEAVE AGAINST MEDICAL ADVICE')

@section('subtitle', 'LAMA / Départ Contre Avis Médical — LAMA')

@section('content')
<style>
    .lama-warning-banner {
        background: #DC2626;
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        text-align: center;
    }
    .lama-warning-banner .warn-title {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 1mm;
    }
    .lama-warning-banner .warn-sub {
        font-size: 9.5px;
        opacity: 0.9;
        font-style: italic;
    }
    .lama-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .lbadge-red { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .lbadge-green { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .lbadge-amber { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .lbadge-slate { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .lama-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .lama-card.amber-border { border-color: #F59E0B; }
    .lama-card.red-border { border-color: #DC2626; }
    .lc-head {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #E2E8F0;
    }
    .lc-head-amber { background: #FFFBEB; color: #92400E; border-bottom-color: #FDE68A; }
    .lc-head-red { background: #FEF2F2; color: #991B1B; border-bottom-color: #FECACA; }
    .lc-head-default { background: #F8FAFC; color: #374151; }
    .lc-body { padding: 4mm; }
    .kv { display: flex; justify-content: space-between; margin-bottom: 1.5mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .risk-list { list-style: none; padding: 0; margin: 0; counter-reset: risk-counter; }
    .risk-list li {
        counter-increment: risk-counter;
        display: flex;
        gap: 2mm;
        align-items: flex-start;
        padding: 1.5mm 0;
        border-bottom: 1px solid #FEE2E2;
        font-size: 10px;
        color: #374151;
    }
    .risk-list li::before {
        content: counter(risk-counter) ".";
        color: #DC2626;
        font-weight: 800;
        min-width: 5mm;
        font-size: 9.5px;
    }
    .capacity-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        margin-bottom: 2mm;
    }
    .cap-box {
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
    }
    .cap-yes { background: #D1FAE5; }
    .cap-no { background: #FEF2F2; }
    .cap-box .cb-val { font-size: 12px; font-weight: 800; margin-bottom: 0.5mm; }
    .cap-yes .cb-val { color: #065F46; }
    .cap-no .cb-val { color: #991B1B; }
    .cap-box .cb-lbl { font-size: 8.5px; text-transform: uppercase; color: #64748B; }
    .declaration-box {
        background: #FEF2F2;
        border: 2px solid #DC2626;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
    }
    .declaration-box .decl-title {
        font-size: 10px;
        font-weight: 700;
        color: #991B1B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2mm;
    }
    .declaration-box .decl-text {
        font-size: 10.5px;
        color: #1F2937;
        line-height: 1.6;
        font-style: italic;
    }
    .sig-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }
    .sig-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
        min-height: 22mm;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .sig-box .sb-title { font-size: 9px; font-weight: 700; color: #374151; text-transform: uppercase; }
    .sig-line { border-top: 1px solid #94A3B8; padding-top: 1mm; font-size: 8.5px; color: #64748B; margin-top: 2mm; }
    .physician-cert {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 4mm;
        font-size: 10px;
        color: #374151;
        font-style: italic;
        line-height: 1.6;
        margin-bottom: 5mm;
    }
    .med-list { list-style: none; padding: 0; margin: 0; }
    .med-list li { padding: 1mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; }
    .med-list li::before { content: "✓ "; color: #0F4C81; font-weight: 700; }
</style>

{{-- 1. Warning Banner --}}
<div class="lama-warning-banner">
    <div class="warn-title">⚠ LEAVE AGAINST MEDICAL ADVICE ⚠</div>
    <div class="warn-title" style="font-size:12px;">DÉPART CONTRE AVIS MÉDICAL</div>
    <div class="warn-sub">Date: {{ $payload['lama_date'] ?? '—' }} &nbsp;|&nbsp; Time: {{ $payload['lama_time'] ?? '—' }}</div>
</div>

{{-- 2. Patient details + reason for admission --}}
<div class="lama-card">
    <div class="lc-head lc-head-default">Reason for Admission / Hospitalization</div>
    <div class="lc-body">
        <p style="margin:0; font-size:10.5px; color:#0F172A;">{{ $payload['reason_for_admission'] ?? '—' }}</p>
    </div>
</div>

{{-- 3. Medical Advice Given --}}
<div class="lama-card amber-border">
    <div class="lc-head lc-head-amber">Medical Advice Given</div>
    <div class="lc-body">
        <p style="margin:0; font-size:10.5px; color:#0F172A;">{{ $payload['medical_advice_given'] ?? '—' }}</p>
    </div>
</div>

{{-- 4. Risks Communicated --}}
<div class="lama-card red-border">
    <div class="lc-head lc-head-red">Risks Communicated to Patient</div>
    <div class="lc-body">
        @if(!empty($payload['risks_explained']))
            <ol class="risk-list">
                @foreach($payload['risks_explained'] as $risk)
                    <li>{{ $risk }}</li>
                @endforeach
            </ol>
        @else
            <p style="font-size:10px; color:#64748B; margin:0;">No specific risks documented.</p>
        @endif
        <div style="margin-top:2.5mm;">
            <span style="font-size:9.5px; color:#64748B; margin-right:2mm;">Patient Understands Risks:</span>
            @if(!empty($payload['patient_understands_risks']))
                <span class="lama-badge lbadge-green">Yes — Confirmed</span>
            @else
                <span class="lama-badge lbadge-red">Not Confirmed</span>
            @endif
        </div>
    </div>
</div>

{{-- 5. Capacity Assessment --}}
<div class="lama-card">
    <div class="lc-head lc-head-default">Mental Capacity Assessment</div>
    <div class="lc-body">
        <div class="capacity-grid">
            <div class="{{ !empty($payload['mental_capacity_assessed']) ? 'cap-box cap-yes' : 'cap-box cap-no' }}">
                <div class="cb-val">{{ !empty($payload['mental_capacity_assessed']) ? 'Yes' : 'No' }}</div>
                <div class="cb-lbl">Capacity Assessed</div>
            </div>
            <div class="{{ !empty($payload['has_capacity']) ? 'cap-box cap-yes' : 'cap-box cap-no' }}">
                <div class="cb-val">{{ !empty($payload['has_capacity']) ? 'Has Capacity' : 'Lacks Capacity' }}</div>
                <div class="cb-lbl">Decision-Making Capacity</div>
            </div>
        </div>
        @if(!empty($payload['capacity_notes']))
            <p style="font-size:10px; color:#374151; margin:2mm 0 0 0; font-style:italic;">Notes: {{ $payload['capacity_notes'] }}</p>
        @endif
    </div>
</div>

{{-- 6. Patient's Stated Reason --}}
<div class="lama-card">
    <div class="lc-head lc-head-default">Patient's Stated Reason for Leaving</div>
    <div class="lc-body">
        <p style="margin:0; font-size:10.5px; color:#0F172A; font-style:italic;">"{{ $payload['reason_for_leaving'] ?? '—' }}"</p>
    </div>
</div>

{{-- 7. Medications Dispensed --}}
@if(!empty($payload['medications_dispensed']))
<div class="lama-card">
    <div class="lc-head lc-head-default">Medications Dispensed for Home</div>
    <div class="lc-body">
        <ul class="med-list">
            @foreach($payload['medications_dispensed'] as $med)
                <li>{{ $med }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 8. Follow-up --}}
<div class="lama-card">
    <div class="lc-head lc-head-default">Follow-up Arrangement</div>
    <div class="lc-body" style="display:flex; align-items:center; gap:3mm; flex-wrap:wrap;">
        @if(!empty($payload['follow_up_arranged']))
            <span class="lama-badge lbadge-green">Follow-up Arranged</span>
            @if(!empty($payload['follow_up_details']))
                <span style="font-size:10px; color:#374151;">{{ $payload['follow_up_details'] }}</span>
            @endif
        @else
            <span class="lama-badge lbadge-red">No Follow-up Arranged</span>
            <span style="font-size:9.5px; color:#DC2626;">Patient left without scheduled follow-up.</span>
        @endif
    </div>
</div>

{{-- 9. Patient Declaration --}}
<div class="declaration-box">
    <div class="decl-title">Patient Declaration / Déclaration du Patient</div>
    <div class="decl-text">
        I, <strong>{{ $patient_name }}</strong> (Health ID: {{ $health_id ?? 'N/A' }}), hereby declare that I am leaving this healthcare facility against the advice of the attending medical team. I understand that leaving against medical advice may result in serious harm, permanent disability, or death. I have been fully informed of the risks outlined above and I choose to leave of my own free will, accepting full responsibility for any consequences arising from my decision.
    </div>
    <div class="decl-text" style="margin-top:2mm; font-size:9.5px;">
        Je, <strong>{{ $patient_name }}</strong>, déclare par la présente quitter cet établissement de santé contre l'avis de l'équipe médicale et en accepte toutes les conséquences.
    </div>
</div>

{{-- 10. Signature boxes --}}
<div class="sig-grid">
    <div class="sig-box">
        <div class="sb-title">Patient Signature</div>
        <div class="sig-line">{{ $patient_name }}</div>
    </div>
    @if(!empty($payload['next_of_kin_present']))
    <div class="sig-box">
        <div class="sb-title">Next of Kin Signature</div>
        <div class="sig-line">{{ $payload['nok_name'] ?? '—' }} ({{ $payload['nok_relationship'] ?? '—' }})</div>
    </div>
    @else
    <div class="sig-box" style="background:#F8FAFC;">
        <div class="sb-title" style="color:#94A3B8;">Next of Kin</div>
        <div style="font-size:9px; color:#94A3B8; margin-top:4mm;">Not Present</div>
        <div class="sig-line" style="border-color:#E2E8F0;">N/A</div>
    </div>
    @endif
    <div class="sig-box">
        <div class="sb-title">Witness</div>
        <div class="sig-line">{{ $payload['witness_name'] ?? '—' }} &nbsp;|&nbsp; {{ $payload['witness_designation'] ?? '—' }}</div>
    </div>
    <div class="sig-box">
        <div class="sb-title">Attending Physician</div>
        <div class="sig-line">{{ $issuer_name }} &nbsp;|&nbsp; {{ $issuer_role }}</div>
    </div>
</div>

{{-- 11. Physician Certification --}}
<div class="physician-cert">
    I certify that the above patient, <strong>{{ $patient_name }}</strong>, has been fully counselled regarding their medical condition, the recommended treatment plan, and the specific risks of leaving against medical advice. The patient has made this decision with apparent decision-making capacity and of their own free will. All reasonable efforts have been made to persuade the patient to remain under medical care.
</div>
@endsection
