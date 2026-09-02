@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Carnet de Suivi Prénatal' : 'Antenatal Card' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Suivi de la grossesse — Soins maternels officiels' : 'Antenatal Care Record — Official Maternal Health Document' }}
@endsection

@section('content')
<style>
    /* ANC accent: #DB2777 pink */
    .anc-obs-strip {
        background: linear-gradient(135deg, #DB2777 0%, #BE185D 100%);
        color: #FFFFFF;
        border-radius: 8px;
        padding: 3.5mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 2mm;
    }
    .anc-obs-item { text-align: center; }
    .anc-obs-label { font-size: 8px; color: rgba(255,255,255,0.75); text-transform: uppercase; letter-spacing: 0.4px; }
    .anc-obs-value { font-size: 13px; font-weight: 800; color: #FFFFFF; }
    .anc-obs-sep { border-left: 1px solid rgba(255,255,255,0.3); height: 8mm; }

    .anc-risk-badge {
        display: inline-block;
        border-radius: 6px;
        padding: 2mm 5mm;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5mm;
    }
    .anc-risk-low    { background: #DCFCE7; color: #166534; border: 2px solid #16A34A; }
    .anc-risk-medium { background: #FEF3C7; color: #92400E; border: 2px solid #D97706; }
    .anc-risk-high   { background: #FEE2E2; color: #7F1D1D; border: 2px solid #DC2626; }

    .anc-bloods-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .anc-blood-box {
        border-radius: 6px;
        padding: 2.5mm 3mm;
        border: 1px solid #E2E8F0;
        text-align: center;
    }
    .anc-blood-label { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 0.5mm; color: #475569; }
    .anc-blood-value { font-size: 11px; font-weight: 700; }
    .anc-blood-pos   { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    .anc-blood-neg   { background: #F0FDF4; color: #166534; border-color: #BBF7D0; }
    .anc-blood-reactive     { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    .anc-blood-nonreactive  { background: #F0FDF4; color: #166534; border-color: #BBF7D0; }
    .anc-blood-unknown      { background: #F8FAFC; color: #475569; border-color: #E2E8F0; }

    .anc-section-card { border: 1px solid #E2E8F0; border-radius: 6px; margin-bottom: 5mm; overflow: hidden; }
    .anc-section-header {
        background: #FDF2F8; border-bottom: 2px solid #FBCFE8; color: #9D174D;
        font-weight: 700; font-size: 10px; padding: 2mm 4mm; text-transform: uppercase; letter-spacing: 0.6px;
    }
    .anc-section-body { padding: 4mm; font-size: 10.5px; color: #0F172A; line-height: 1.6; }

    .anc-visit-table { width: 100%; border-collapse: collapse; font-size: 9px; }
    .anc-visit-table th {
        background: #FDF2F8; color: #9D174D; font-weight: 700;
        padding: 2mm 2.5mm; border-bottom: 2px solid #FBCFE8;
        text-align: center; text-transform: uppercase; font-size: 8px; white-space: nowrap;
    }
    .anc-visit-table td {
        padding: 2mm 2.5mm; border-bottom: 1px solid #F1F5F9;
        text-align: center; font-size: 9px;
    }
    .anc-visit-table tr:nth-child(even) td { background: #FDF2F8; }
    .anc-abnormal-bp  { color: #B91C1C; font-weight: 700; }
    .anc-abnormal-val { color: #D97706; font-weight: 700; }
    .anc-prot-trace   { color: #D97706; font-weight: 600; }
    .anc-prot-pos     { color: #B91C1C; font-weight: 700; }

    .anc-delivery-box {
        background: #FDF2F8; border: 2px solid #DB2777; border-radius: 8px;
        padding: 4mm; margin-bottom: 5mm;
    }
    .anc-delivery-title { font-size: 11px; font-weight: 800; color: #9D174D; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.5px; }

    .anc-next-appt {
        background: linear-gradient(135deg, #DB2777 0%, #BE185D 100%);
        color: #FFFFFF; border-radius: 8px; padding: 4mm 6mm;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 5mm;
    }
    .anc-next-appt .appt-label { font-size: 9px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px; }
    .anc-next-appt .appt-value { font-size: 15px; font-weight: 800; }

    .anc-midwife-block {
        border-top: 1px solid #E2E8F0; padding-top: 4mm;
        display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4mm;
    }
    .anc-sig-line {
        border-top: 1px solid #94A3B8; padding-top: 1mm; margin-top: 7mm;
        font-size: 9px; color: #475569; width: 55mm; display: inline-block; text-align: center;
    }

    .anc-tetanus-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .anc-tetanus-table th { background: #F8FAFC; color: #475569; font-weight: 600; padding: 2mm 3mm; border-bottom: 1px solid #E2E8F0; text-align: left; font-size: 9px; text-transform: uppercase; }
    .anc-tetanus-table td { padding: 2mm 3mm; border-bottom: 1px solid #F1F5F9; }

    .anc-prophylaxis-list { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 2mm; }
    .anc-prophylaxis-list li {
        background: #FDF2F8; border: 1px solid #FBCFE8; color: #9D174D;
        padding: 1mm 2.5mm; border-radius: 12px; font-size: 9px; font-weight: 600;
    }
</style>

@php
    $gravida       = $payload['gravida'] ?? 'G?';
    $para          = $payload['para'] ?? 'P?';
    $lmp           = $payload['lmp'] ?? '—';
    $edd           = $payload['edd'] ?? '—';
    $gaReg         = $payload['gestational_age_at_registration'] ?? '—';
    $riskCategory  = $payload['risk_category'] ?? 'low';
    $riskBadge     = ['low' => 'anc-risk-low', 'medium' => 'anc-risk-medium', 'high' => 'anc-risk-high'][$riskCategory] ?? 'anc-risk-low';
    $riskLabels    = [
        'low'    => ['en' => 'LOW RISK',    'fr' => 'RISQUE FAIBLE'],
        'medium' => ['en' => 'MEDIUM RISK', 'fr' => 'RISQUE MOYEN'],
        'high'   => ['en' => 'HIGH RISK',   'fr' => 'RISQUE ELEVE'],
    ];
    $riskLabel = $riskLabels[$riskCategory][$language] ?? strtoupper($riskCategory) . ' RISK';

    $hivStatus  = $payload['hiv_status'] ?? 'unknown';
    $hbsag      = $payload['hbsag'] ?? 'neg';
    $vdrl       = $payload['syphilis_vdrl'] ?? 'non-reactive';
    $hb         = $payload['haemoglobin_gdl'] ?? '—';
    $bloodGroup = $payload['blood_group'] ?? '—';
    $rhesus     = $payload['rhesus'] ?? '—';

    $hivClass = ($hivStatus === 'pos') ? 'anc-blood-pos' : (($hivStatus === 'neg') ? 'anc-blood-neg' : 'anc-blood-unknown');
    $hbsagClass = ($hbsag === 'pos') ? 'anc-blood-pos' : 'anc-blood-neg';
    $vdrlClass = ($vdrl === 'reactive') ? 'anc-blood-reactive' : 'anc-blood-nonreactive';
    $rhClass = ($rhesus === 'negative') ? 'anc-blood-pos' : 'anc-blood-neg';
@endphp

{{-- 1. OBSTETRIC HISTORY STRIP --}}
<div class="anc-obs-strip">
    <div class="anc-obs-item">
        <div class="anc-obs-label">Gravida / Para</div>
        <div class="anc-obs-value">G{{ $gravida }} P{{ $para }}</div>
    </div>
    <div class="anc-obs-sep"></div>
    <div class="anc-obs-item">
        <div class="anc-obs-label">{{ $language === 'fr' ? 'DDP (DDR)' : 'LMP' }}</div>
        <div class="anc-obs-value">{{ $lmp }}</div>
    </div>
    <div class="anc-obs-sep"></div>
    <div class="anc-obs-item">
        <div class="anc-obs-label">{{ $language === 'fr' ? 'DPA (Terme)' : 'EDD (Due Date)' }}</div>
        <div class="anc-obs-value">{{ $edd }}</div>
    </div>
    <div class="anc-obs-sep"></div>
    <div class="anc-obs-item">
        <div class="anc-obs-label">{{ $language === 'fr' ? 'AG a l\'inscription' : 'GA at Registration' }}</div>
        <div class="anc-obs-value">{{ $gaReg }} {{ $language === 'fr' ? 'sem.' : 'wks' }}</div>
    </div>
    <div class="anc-obs-sep"></div>
    <div class="anc-obs-item">
        <div class="anc-obs-label">{{ $language === 'fr' ? 'Date d\'inscription' : 'Booking Date' }}</div>
        <div class="anc-obs-value" style="font-size:10px;">{{ $payload['booking_date'] ?? '—' }}</div>
    </div>
</div>

{{-- 2. RISK CATEGORY BADGE --}}
<div style="margin-bottom:5mm;">
    <span class="anc-risk-badge {{ $riskBadge }}">
        &#9679; {{ $riskLabel }}
    </span>
</div>

{{-- 3. BOOKING BLOODS SUMMARY --}}
<div class="anc-section-card">
    <div class="anc-section-header">{{ $language === 'fr' ? 'BILAN SANGUIN D\'INSCRIPTION' : 'BOOKING BLOOD INVESTIGATIONS' }}</div>
    <div class="anc-section-body" style="padding:3mm;">
        <div class="anc-bloods-grid">
            <div class="anc-blood-box">
                <div class="anc-blood-label">{{ $language === 'fr' ? 'Groupe Sanguin' : 'Blood Group' }}</div>
                <div class="anc-blood-value" style="color:#0F172A;">{{ $bloodGroup }}</div>
            </div>
            <div class="anc-blood-box">
                <div class="anc-blood-label">{{ $language === 'fr' ? 'Rhesus' : 'Rhesus Factor' }}</div>
                <div class="anc-blood-value {{ $rhClass }}">{{ strtoupper($rhesus) }}</div>
            </div>
            <div class="anc-blood-box {{ $hivClass }}">
                <div class="anc-blood-label">HIV</div>
                <div class="anc-blood-value">{{ strtoupper($hivStatus) }}</div>
            </div>
            <div class="anc-blood-box {{ $hbsagClass }}">
                <div class="anc-blood-label">HBsAg</div>
                <div class="anc-blood-value">{{ strtoupper($hbsag) }}</div>
            </div>
            <div class="anc-blood-box {{ $vdrlClass }}">
                <div class="anc-blood-label">VDRL / Syphilis</div>
                <div class="anc-blood-value">{{ strtoupper(str_replace('-', ' ', $vdrl)) }}</div>
            </div>
            <div class="anc-blood-box" style="background:#F8FAFC;">
                <div class="anc-blood-label">{{ $language === 'fr' ? 'Hb (g/dL)' : 'Haemoglobin (g/dL)' }}</div>
                <div class="anc-blood-value" style="color:{{ ($hb !== '—' && (float)$hb < 10.5) ? '#B91C1C' : '#0F172A' }};">
                    {{ $hb }} g/dL
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. ANTENATAL VISIT RECORD TABLE --}}
<div class="anc-section-card">
    <div class="anc-section-header">{{ $language === 'fr' ? 'TABLEAU DE SUIVI PRENATAL' : 'ANTENATAL VISIT RECORD' }}</div>
    <div style="overflow-x:auto;">
        <table class="anc-visit-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'Vis.' : 'Visit' }}</th>
                    <th>{{ $language === 'fr' ? 'Date' : 'Date' }}</th>
                    <th>{{ $language === 'fr' ? 'AG' : 'GA' }}</th>
                    <th>{{ $language === 'fr' ? 'Poids' : 'Wt (kg)' }}</th>
                    <th>{{ $language === 'fr' ? 'TA (mmHg)' : 'BP (mmHg)' }}</th>
                    <th>{{ $language === 'fr' ? 'HU (cm)' : 'FH (cm)' }}</th>
                    <th>{{ $language === 'fr' ? 'BCF' : 'FHR' }}</th>
                    <th>{{ $language === 'fr' ? 'Position' : 'Position' }}</th>
                    <th>{{ $language === 'fr' ? 'Oedeme' : 'Edema' }}</th>
                    <th>{{ $language === 'fr' ? 'Prot.' : 'Prot.' }}</th>
                    <th>{{ $language === 'fr' ? 'Notes' : 'Notes' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payload['antenatal_visits'] ?? [] as $visit)
                @php
                    $bpHigh = (!empty($visit['bp_systolic']) && $visit['bp_systolic'] >= 140)
                           || (!empty($visit['bp_diastolic']) && $visit['bp_diastolic'] >= 90);
                    $protClass = '';
                    if (!empty($visit['urine_protein'])) {
                        if (in_array($visit['urine_protein'], ['+', '++', '+++', 'positive'])) {
                            $protClass = 'anc-prot-pos';
                        } elseif ($visit['urine_protein'] === 'trace') {
                            $protClass = 'anc-prot-trace';
                        }
                    }
                @endphp
                <tr>
                    <td><strong>{{ $visit['visit_number'] ?? '#' }}</strong></td>
                    <td>{{ $visit['visit_date'] ?? '—' }}</td>
                    <td>{{ $visit['gestational_age'] ?? '—' }}{{ !empty($visit['gestational_age']) ? ' wk' : '' }}</td>
                    <td>{{ $visit['weight_kg'] ?? '—' }}</td>
                    <td class="{{ $bpHigh ? 'anc-abnormal-bp' : '' }}">
                        {{ $visit['bp_systolic'] ?? '—' }}/{{ $visit['bp_diastolic'] ?? '—' }}
                    </td>
                    <td>{{ $visit['fundal_height_cm'] ?? '—' }}</td>
                    <td>{{ $visit['fetal_heart_rate'] ?? '—' }}</td>
                    <td>{{ $visit['fetal_position'] ?? '—' }}{{ !empty($visit['presentation']) ? ' / '.$visit['presentation'] : '' }}</td>
                    <td>{{ $visit['edema'] ?? '—' }}</td>
                    <td class="{{ $protClass }}">{{ $visit['urine_protein'] ?? '—' }}</td>
                    <td style="font-size:8.5px;text-align:left;max-width:30mm;">{{ $visit['notes'] ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;color:#94A3B8;font-style:italic;padding:4mm;">
                    {{ $language === 'fr' ? 'Aucune visite enregistrée' : 'No visits recorded' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 5. TETANUS PROPHYLAXIS --}}
@if(!empty($payload['tetanus_vaccinations']))
<div class="anc-section-card">
    <div class="anc-section-header">{{ $language === 'fr' ? 'VACCINATION ANTITETANIQUE' : 'TETANUS PROPHYLAXIS' }}</div>
    <div class="anc-section-body" style="padding:0;">
        <table class="anc-tetanus-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'Dose' : 'Dose' }}</th>
                    <th>{{ $language === 'fr' ? 'Date d\'administration' : 'Date Given' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['tetanus_vaccinations'] as $tt)
                <tr>
                    <td><strong>{{ $tt['dose'] ?? '—' }}</strong></td>
                    <td>{{ $tt['date'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 6. ANTENATAL PROPHYLAXIS LIST --}}
@if(!empty($payload['antenatal_prophylaxis']))
<div class="anc-section-card">
    <div class="anc-section-header">{{ $language === 'fr' ? 'PROPHYLAXIE ANTENATALE' : 'ANTENATAL PROPHYLAXIS GIVEN' }}</div>
    <div class="anc-section-body">
        <ul class="anc-prophylaxis-list">
            @foreach($payload['antenatal_prophylaxis'] as $med)
                <li>{{ $med }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 7. DELIVERY PLAN --}}
@if(!empty($payload['delivery_plan']))
<div class="anc-delivery-box">
    <div class="anc-delivery-title">{{ $language === 'fr' ? 'PLAN D\'ACCOUCHEMENT' : 'DELIVERY PLAN' }}</div>
    <div style="font-size:10.5px;color:#0F172A;line-height:1.7;">{{ $payload['delivery_plan'] }}</div>
</div>
@endif

{{-- 8. NEXT APPOINTMENT --}}
@if(!empty($payload['next_appointment']))
<div class="anc-next-appt">
    <div>
        <div class="appt-label">{{ $language === 'fr' ? 'Prochain Rendez-vous' : 'Next Appointment' }}</div>
        <div class="appt-value">{{ $payload['next_appointment'] }}</div>
    </div>
    <div style="font-size:9px;color:rgba(255,255,255,0.75);">
        {{ $language === 'fr' ? 'Merci de respecter vos rendez-vous prenatals' : 'Please attend all scheduled antenatal visits' }}
    </div>
</div>
@endif

{{-- 9. MIDWIFE SIGNATURE --}}
<div class="anc-midwife-block">
    <div style="font-size:10px;color:#475569;">
        <div style="font-size:9px;text-transform:uppercase;color:#94A3B8;letter-spacing:0.4px;margin-bottom:1mm;">
            {{ $language === 'fr' ? 'Sage-femme / Prestataire' : 'Midwife / Provider' }}
        </div>
        <div style="font-weight:700;color:#0F172A;font-size:11px;">
            {{ $payload['midwife_name'] ?? $issuer_name }}
        </div>
    </div>
    <div style="text-align:right;">
        <div class="anc-sig-line">{{ $payload['midwife_name'] ?? $issuer_name }}</div>
        <div style="font-size:8px;color:#94A3B8;margin-top:1mm;">
            {{ $language === 'fr' ? 'Signature autorisee' : 'Authorized Signature' }}
        </div>
    </div>
</div>
@endsection
