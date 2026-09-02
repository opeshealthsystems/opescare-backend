@extends('documents.base')

@section('title', 'PALLIATIVE CARE PLAN')
@section('subtitle', 'Goals-of-Care / End-of-Life Care Plan | OpesCare Palliative Services')

@section('content')
@php
    $accentColor = '#6D28D9';
    $accentLight = '#F5F3FF';
    $accentMid   = '#DDD6FE';

    $planDate         = $payload['plan_date']          ?? '—';
    $prognosis        = $payload['prognosis']           ?? 'Uncertain';
    $primaryDiagnosis = $payload['primary_diagnosis']   ?? '—';
    $palliativePhase  = $payload['palliative_phase']    ?? 'Stable';
    $goalsOfCare      = $payload['goals_of_care']       ?? '—';

    $patientUnderstanding = $payload['patient_understanding'] ?? '—';
    $patientWishes        = $payload['patient_wishes']        ?? [];
    $symptomsToManage     = $payload['symptoms_to_manage']    ?? [];
    $anticipatoryMeds     = $payload['anticipatory_medications'] ?? [];
    $hydrationNutrition   = $payload['hydration_nutrition']   ?? '—';
    $dnarInPlace          = $payload['dnar_in_place']         ?? false;
    $ceilingOfTreatment   = $payload['ceiling_of_treatment']  ?? [];
    $familyMeetings       = $payload['family_meetings']       ?? [];
    $spiritualCare        = $payload['spiritual_care']        ?? null;
    $socialWork           = $payload['social_work']           ?? null;
    $bereavementPlanned   = $payload['bereavement_support_planned'] ?? false;
    $keyContacts          = $payload['key_contacts']          ?? [];
    $palliativeTeam       = $payload['palliative_team']       ?? [];
    $reviewFrequency      = $payload['review_frequency']      ?? 'Daily';
    $leadPhysician        = $payload['lead_physician']        ?? '—';

    $prognosisBadgeClass = match($prognosis) {
        'Days'    => 'pal-badge-red',
        'Weeks'   => 'pal-badge-amber',
        'Months'  => 'pal-badge-purple',
        default   => 'pal-badge-gray',
    };
    $phaseBadgeClass = match($palliativePhase) {
        'Terminal (days)'  => 'pal-badge-red',
        'Deteriorating'    => 'pal-badge-amber',
        'Unstable'         => 'pal-badge-amber',
        default            => 'pal-badge-gray',
    };

    $severityBadgeClass = static function(string $s): string {
        return match($s) {
            'Severe'   => 'badge-red',
            'Moderate' => 'badge-amber',
            default    => 'badge-green',
        };
    };
@endphp

<style>
    .pal-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
    }
    .pal-header-strip h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 4px 0;
    }
    .pal-header-strip .pal-sub { font-size: 9.5px; opacity: 0.8; }

    .pal-badge-purple { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .pal-badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .pal-badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .pal-badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .pal-badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .badge-purple { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-blue   { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }

    .section-card {
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        margin-bottom: 8px;
        overflow: hidden;
    }
    .section-card-title {
        background: #F3F4F6;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .section-card-body {
        padding: 8px 10px;
        font-size: 10px;
        color: #1F2937;
        line-height: 1.5;
    }

    .two-col   { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    .goals-banner {
        background: {{ $accentLight }};
        border: 2px solid {{ $accentColor }};
        border-radius: 6px;
        padding: 10px 14px;
        text-align: center;
        margin-bottom: 8px;
    }
    .goals-banner .goals-label { font-size: 9px; color: {{ $accentColor }}; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
    .goals-banner .goals-text  { font-size: 12px; font-weight: 700; color: {{ $accentColor }}; }

    .sym-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .sym-table th {
        background: {{ $accentLight }};
        color: {{ $accentColor }};
        font-weight: 700;
        text-align: left;
        padding: 4px 8px;
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .sym-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
    }
    .sym-table tr:last-child td { border-bottom: none; }

    .med-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .med-table th {
        background: #F9FAFB;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        color: #374151;
        padding: 4px 8px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }
    .med-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
    }
    .med-table tr:last-child td { border-bottom: none; }

    .ceiling-list { margin: 0; padding: 0; list-style: none; }
    .ceiling-list li {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
    }
    .ceiling-list li:last-child { border-bottom: none; }
    .ceiling-dot { width: 8px; height: 8px; border-radius: 50%; background: #EF4444; flex-shrink: 0; }

    .family-row {
        padding: 6px 10px;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
    }
    .family-row:last-child { border-bottom: none; }
    .family-date { font-weight: 600; color: {{ $accentColor }}; min-width: 22mm; display: inline-block; }

    .contacts-strip { display: flex; flex-wrap: wrap; gap: 6px; }
    .contact-pill {
        background: {{ $accentLight }};
        border: 1px solid {{ $accentMid }};
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 9.5px;
        color: #374151;
    }
    .contact-pill .cp-name { font-weight: 600; color: {{ $accentColor }}; }

    .compassion-note {
        background: {{ $accentLight }};
        border: 1px solid {{ $accentMid }};
        border-left: 4px solid {{ $accentColor }};
        border-radius: 0 4px 4px 0;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 10px;
        color: #374151;
        font-style: italic;
    }

    .wishes-item {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 4px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
    }
    .wishes-item:last-child { border-bottom: none; }
    .wishes-label { color: #6B7280; width: 42mm; font-weight: 500; }
</style>

{{-- ── HEADER STRIP ─────────────────────────────────────────────── --}}
<div class="pal-header-strip">
    <h2>Palliative Care Plan</h2>
    <div class="pal-sub">
        {{ $facility_name }} &nbsp;|&nbsp; {{ $planDate }} &nbsp;|&nbsp; {{ $primaryDiagnosis }}
    </div>
    <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap;">
        <span class="badge pal-badge-{{ $prognosis === 'Days' ? 'red' : ($prognosis === 'Weeks' ? 'amber' : 'purple') }}" style="font-size:10px; padding:3px 10px;">
            Prognosis: {{ $prognosis }}
        </span>
        <span class="badge {{ $palliativePhase === 'Terminal (days)' ? 'pal-badge-red' : ($palliativePhase === 'Deteriorating' ? 'pal-badge-amber' : 'pal-badge-gray') }}" style="font-size:10px; padding:3px 10px;">
            Phase: {{ $palliativePhase }}
        </span>
        @if($dnarInPlace)
        <span class="badge badge-red" style="font-size:10px; padding:3px 10px;">DNAR IN PLACE</span>
        @endif
    </div>
</div>

{{-- ── GOALS OF CARE ────────────────────────────────────────────── --}}
<div class="goals-banner" style="margin-top:8px;">
    <div class="goals-label">Goals of Care — Objectifs de soins</div>
    <div class="goals-text">{{ $goalsOfCare }}</div>
</div>

{{-- ── PATIENT UNDERSTANDING + WISHES ──────────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Patient's Understanding of Their Condition</div>
        <div class="section-card-body">{{ $patientUnderstanding }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Patient Wishes &amp; Preferences</div>
        <div class="section-card-body" style="padding:6px 10px;">
            @php
                $wishes = is_array($patientWishes) ? $patientWishes : [];
                $wishFields = [
                    'place_of_death'        => 'Preferred place of death',
                    'cultural_preferences'  => 'Cultural preferences',
                    'spiritual_preferences' => 'Spiritual preferences',
                    'funeral_wishes'        => 'Funeral wishes',
                    'organ_donation'        => 'Organ donation',
                ];
            @endphp
            @foreach($wishFields as $wKey => $wLabel)
            @if(!empty($wishes[$wKey]))
            <div class="wishes-item">
                <span class="wishes-label">{{ $wLabel }}:</span>
                <span style="color:#1F2937;">{{ $wishes[$wKey] }}</span>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ── SYMPTOM MANAGEMENT ───────────────────────────────────────── --}}
@if(!empty($symptomsToManage))
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Symptom Management
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="sym-table">
            <thead>
                <tr>
                    <th style="width:22%;">Symptom</th>
                    <th style="width:15%;">Severity</th>
                    <th>Current Management</th>
                    <th style="width:22%;">Target</th>
                </tr>
            </thead>
            <tbody>
                @foreach($symptomsToManage as $sym)
                @php $sevClass = $severityBadgeClass($sym['severity'] ?? 'Mild'); @endphp
                <tr>
                    <td style="font-weight:600;">{{ $sym['symptom'] ?? '—' }}</td>
                    <td><span class="badge {{ $sevClass }}">{{ $sym['severity'] ?? '—' }}</span></td>
                    <td>{{ $sym['current_management'] ?? '—' }}</td>
                    <td style="font-style:italic; color:#374151;">{{ $sym['target'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── ANTICIPATORY MEDICATIONS ─────────────────────────────────── --}}
@if(!empty($anticipatoryMeds))
<div class="section-card">
    <div class="section-card-title">Anticipatory Medications (Prescribed in Advance)</div>
    <div class="section-card-body" style="padding:0;">
        <table class="med-table">
            <thead>
                <tr>
                    <th>Drug</th>
                    <th>Indication</th>
                    <th>Dose</th>
                    <th>Route</th>
                    <th>Frequency</th>
                    <th>PRN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($anticipatoryMeds as $med)
                <tr>
                    <td style="font-weight:600;">{{ $med['drug'] ?? '—' }}</td>
                    <td>{{ $med['indication'] ?? '—' }}</td>
                    <td>{{ $med['dose'] ?? '—' }}</td>
                    <td>{{ $med['route'] ?? '—' }}</td>
                    <td>{{ $med['frequency'] ?? '—' }}</td>
                    <td>
                        @if(!empty($med['available_prn']))
                        <span class="badge badge-amber">PRN</span>
                        @else
                        <span class="badge badge-gray">Scheduled</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── HYDRATION/NUTRITION + CEILING + DNAR ────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Hydration &amp; Nutrition Decisions</div>
        <div class="section-card-body">{{ $hydrationNutrition }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0; border-color:#FCA5A5;">
        <div class="section-card-title" style="background:#FEE2E2; color:#991B1B;">Ceiling of Treatment — Will Not Escalate</div>
        <div class="section-card-body" style="padding:6px 10px;">
            <ul class="ceiling-list">
                @foreach($ceilingOfTreatment as $item)
                <li>
                    <span class="ceiling-dot"></span>
                    <span>{{ $item }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- ── FAMILY MEETINGS LOG ──────────────────────────────────────── --}}
@if(!empty($familyMeetings))
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title">Family Meetings Log</div>
    <div class="section-card-body" style="padding:0;">
        @foreach($familyMeetings as $meeting)
        <div class="family-row">
            <span class="family-date">{{ $meeting['date'] ?? '—' }}</span>
            @if(!empty($meeting['present']))
            <span style="font-size:9px; color:#6B7280; margin-right:8px;">Present: {{ $meeting['present'] }}</span>
            @endif
            <span style="font-size:10px; color:#1F2937;">{{ $meeting['discussion_summary'] ?? '—' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── SPIRITUAL + SOCIAL WORK + BEREAVEMENT ───────────────────── --}}
<div class="three-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Spiritual Care</div>
        <div class="section-card-body">{{ $spiritualCare ?? 'Not requested' }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Social Work</div>
        <div class="section-card-body">{{ $socialWork ?? 'Not involved' }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Bereavement Support</div>
        <div class="section-card-body">
            <span class="badge {{ $bereavementPlanned ? 'badge-green' : 'badge-gray' }}">
                {{ $bereavementPlanned ? 'Planned' : 'Not yet planned' }}
            </span>
        </div>
    </div>
</div>

{{-- ── KEY CONTACTS ─────────────────────────────────────────────── --}}
@if(!empty($keyContacts))
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title">Key Contacts</div>
    <div class="section-card-body">
        <div class="contacts-strip">
            @foreach($keyContacts as $contact)
            <div class="contact-pill">
                <span class="cp-name">{{ $contact['name'] ?? '—' }}</span>
                @if(!empty($contact['role']))
                <span> — {{ $contact['role'] }}</span>
                @endif
                @if(!empty($contact['phone']))
                <span style="color:#6B7280;"> | {{ $contact['phone'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── PALLIATIVE TEAM + REVIEW + LEAD PHYSICIAN ───────────────── --}}
<div class="two-col" style="margin-bottom:0;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Palliative Care Team</div>
        <div class="section-card-body">
            @foreach($palliativeTeam as $member)
            <div style="font-size:10px; color:#1F2937; margin-bottom:2px;">{{ $member }}</div>
            @endforeach
            @if(empty($palliativeTeam))
            <span style="color:#9CA3AF; font-style:italic;">Not listed</span>
            @endif
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Review &amp; Lead Physician</div>
        <div class="section-card-body">
            <div style="margin-bottom:4px;">
                <span style="font-size:9px; color:#6B7280;">Review frequency:</span>
                <span class="badge badge-purple" style="margin-left:4px;">{{ $reviewFrequency }}</span>
            </div>
            <div style="font-weight:600; font-size:11px; margin-top:4px;">{{ $leadPhysician }}</div>
            <div style="font-size:9px; color:#6B7280;">Lead Physician / Palliative Care Consultant</div>
        </div>
    </div>
</div>

{{-- ── COMPASSION NOTE ──────────────────────────────────────────── --}}
<div class="compassion-note">
    Compassionate care note: This plan prioritises the patient's comfort, dignity, and quality of remaining
    life. All care decisions have been made with the patient's best interests, expressed wishes, cultural
    values, and spiritual beliefs at the forefront. The palliative care team remains available to support
    the patient, their family, and the clinical team throughout this journey.
</div>
@endsection
