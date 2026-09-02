@extends('documents.base')

@section('title', 'Patient Admission / Registration Form')
@section('subtitle', 'ADM — Inpatient Admission Document')

@section('content')
@php
    $accentColor    = '#0F4C81';
    $admDate        = $payload['admission_date']      ?? '—';
    $admTime        = $payload['admission_time']      ?? '—';
    $admType        = $payload['admission_type']      ?? '—';
    $ward           = $payload['admitting_ward']      ?? '—';
    $doctor         = $payload['admitting_doctor']    ?? '—';
    $admDx          = $payload['admitting_diagnosis'] ?? '—';
    $pd             = $payload['patient_details']     ?? [];
    $nok            = $payload['next_of_kin']         ?? [];
    $ins            = $payload['insurance']           ?? [];
    $allergies      = $payload['allergies']           ?? [];
    $complaint      = $payload['presenting_complaint']?? '—';
    $consentTreat   = $payload['consent_to_treat']    ?? false;
    $consentPhoto   = $payload['consent_to_photograph']?? false;
    $consentTeach   = $payload['consent_to_teaching'] ?? false;
    $rightsExp      = $payload['patient_rights_explained'] ?? false;
    $advDir         = $payload['advance_directive']   ?? 'None';
    $valuables      = $payload['valuables_deposited'] ?? false;
    $valDesc        = $payload['valuables_description']?? null;
    $clerk          = $payload['admitting_clerk']     ?? '—';

    $admTypeColors  = [
        'Emergency' => '#dc2626',
        'Elective'  => '#0369a1',
        'Day Case'  => '#7c3aed',
        'Transfer'  => '#d97706',
    ];
    $admTypeBg = $admTypeColors[$admType] ?? '#374151';

    $hasInsurance = !empty($ins['has_insurance']);
    $nkda = count($allergies) === 1 && str_contains($allergies[0], 'NKDA');
@endphp

{{-- ── Section 1: Header ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="background:{{ $admTypeBg }};border:2px solid rgba(255,255,255,.4);padding:4px 14px;border-radius:4px;font-weight:700;font-size:13px;">
            {{ $admType }}
        </span>
        <span style="font-size:13px;"><strong>Date:</strong> {{ $admDate }} at {{ $admTime }}</span>
        <span style="font-size:13px;"><strong>Ward:</strong> {{ $ward }}</span>
        <span style="font-size:13px;"><strong>Admitting Doctor:</strong> {{ $doctor }}</span>
    </div>
</div>

{{-- ── Section 2: Patient Demographics ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:2px solid #bfdbfe;padding-bottom:4px;margin-bottom:10px;">
        Patient Demographics
    </h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 20px;">
        @php
        $demoFields = [
            'full_name'           => 'Full Name',
            'dob'                 => 'Date of Birth',
            'age'                 => 'Age',
            'sex'                 => 'Sex',
            'marital_status'      => 'Marital Status',
            'nationality'         => 'Nationality',
            'occupation'          => 'Occupation',
            'phone'               => 'Phone',
            'address'             => 'Address',
            'religion'            => 'Religion',
            'language_preference' => 'Language Preference',
            'id_type'             => 'ID Type',
            'id_number'           => 'ID Number',
        ];
        @endphp
        @foreach($demoFields as $key => $label)
        <div style="padding:5px 8px;background:{{ $loop->iteration % 2 === 1 ? '#f0f9ff' : '#fff' }};border-bottom:1px solid #e5e7eb;font-size:12px;">
            <span style="color:#6b7280;font-size:10px;text-transform:uppercase;display:block;margin-bottom:1px;">{{ $label }}</span>
            <span style="font-weight:600;">{{ $pd[$key] ?? '—' }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Section 3: Next of Kin ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;border-bottom:2px solid #bfdbfe;padding-bottom:4px;margin-bottom:10px;">
        Next of Kin
    </h3>
    @if(count($nok) > 0)
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#eff6ff;color:#1e40af;">
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;">Name</th>
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;">Relationship</th>
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;">Phone</th>
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:left;">Address</th>
                <th style="padding:6px 10px;border:1px solid #bfdbfe;text-align:center;">Primary</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nok as $k)
            @php $isPrimary = !empty($k['is_primary']); @endphp
            <tr style="background:{{ $isPrimary ? '#eff6ff' : '#fff' }};{{ $isPrimary ? 'font-weight:600;' : '' }}">
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $k['name'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $k['relationship'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $k['phone'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $k['address'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;text-align:center;">
                    @if($isPrimary)
                        <span style="background:{{ $accentColor }};color:#fff;padding:1px 8px;border-radius:10px;font-size:10px;">Primary</span>
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size:12px;color:#6b7280;font-style:italic;">No next of kin recorded.</p>
    @endif
</div>

{{-- ── Section 4: Insurance ── --}}
<div style="margin-bottom:20px;border:1px solid #bfdbfe;border-radius:6px;padding:12px 16px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;margin-bottom:10px;">
        Insurance Details
    </h3>
    @if($hasInsurance)
        <div style="display:flex;flex-wrap:wrap;gap:8px 20px;font-size:12px;">
            <span style="background:#15803d;color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Insured</span>
            <span><strong>Insurer:</strong> {{ $ins['insurer'] ?? '—' }}</span>
            <span><strong>Policy No.:</strong> {{ $ins['policy_number'] ?? '—' }}</span>
            <span><strong>Employer:</strong> {{ $ins['employer'] ?? '—' }}</span>
            <span><strong>Auth No.:</strong> {{ $ins['authorization_number'] ?? '—' }}</span>
        </div>
    @else
        <span style="background:#f59e0b;color:#fff;padding:3px 12px;border-radius:10px;font-size:12px;font-weight:700;">Self-Pay</span>
    @endif
</div>

{{-- ── Section 5: Allergies ── --}}
<div style="margin-bottom:20px;">
    @php $allergyBg = $nkda ? '#dcfce7' : '#fee2e2'; $allergyBorder = $nkda ? '#86efac' : '#fca5a5'; @endphp
    <div style="background:{{ $allergyBg }};border:1px solid {{ $allergyBorder }};border-radius:6px;padding:10px 14px;">
        <h3 style="font-size:12px;font-weight:700;color:{{ $nkda ? '#166534' : '#991b1b' }};text-transform:uppercase;margin-bottom:8px;">
            Allergies
        </h3>
        @if($nkda)
            <span style="font-size:13px;font-weight:700;color:#166534;">&#10003; {{ $allergies[0] }}</span>
        @else
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:rgba(0,0,0,.05);">
                        <th style="padding:5px 8px;border:1px solid {{ $allergyBorder }};text-align:left;">Allergen</th>
                        <th style="padding:5px 8px;border:1px solid {{ $allergyBorder }};text-align:left;">Reaction</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allergies as $al)
                    @php $isStr = is_string($al); @endphp
                    <tr>
                        <td style="padding:5px 8px;border:1px solid {{ $allergyBorder }};font-weight:600;">{{ $isStr ? $al : ($al['allergen'] ?? '—') }}</td>
                        <td style="padding:5px 8px;border:1px solid {{ $allergyBorder }};">{{ $isStr ? '' : ($al['reaction'] ?? '—') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

{{-- ── Section 6: Complaint + Diagnosis ── --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="flex:1;min-width:200px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;">
        <p style="font-size:10px;color:#6b7280;text-transform:uppercase;font-weight:700;margin:0 0 4px;">Presenting Complaint</p>
        <p style="font-size:13px;margin:0;">{{ $complaint }}</p>
    </div>
    <div style="flex:1;min-width:200px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;">
        <p style="font-size:10px;color:#1e40af;text-transform:uppercase;font-weight:700;margin:0 0 4px;">Admitting Diagnosis</p>
        <p style="font-size:13px;font-weight:600;margin:0;">{{ $admDx }}</p>
    </div>
</div>

{{-- ── Section 7: Advance Directive ── --}}
<div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:12px;font-weight:700;color:#374151;">Advance Directive:</span>
    @php
        $advDirBg = $advDir === 'None' ? '#6b7280' : '#dc2626';
    @endphp
    <span style="background:{{ $advDirBg }};color:#fff;padding:3px 12px;border-radius:10px;font-size:12px;font-weight:600;">
        {{ $advDir }}
    </span>
</div>

{{-- ── Section 8: Consent ── --}}
<div style="margin-bottom:20px;border:1px solid #e5e7eb;border-radius:6px;padding:12px 16px;">
    <h3 style="font-size:12px;font-weight:700;color:{{ $accentColor }};text-transform:uppercase;margin-bottom:10px;">
        Consent
    </h3>
    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        @foreach([
            ['label' => 'Consent to Treat',       'val' => $consentTreat],
            ['label' => 'Consent to Photography',  'val' => $consentPhoto],
            ['label' => 'Consent to Teaching',     'val' => $consentTeach],
        ] as $item)
        <div style="background:{{ $item['val'] ? '#dcfce7' : '#fee2e2' }};border:1px solid {{ $item['val'] ? '#86efac' : '#fca5a5' }};border-radius:6px;padding:8px 14px;display:flex;align-items:center;gap:8px;font-size:12px;">
            <span style="font-size:16px;color:{{ $item['val'] ? '#15803d' : '#dc2626' }};">
                {{ $item['val'] ? '&#9746;' : '&#9744;' }}
            </span>
            <span>{{ $item['label'] }}</span>
            <strong style="color:{{ $item['val'] ? '#15803d' : '#dc2626' }};">
                {{ $item['val'] ? 'Yes' : 'No' }}
            </strong>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Section 9: Patient Rights Explained ── --}}
<div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:12px;font-weight:700;">Patient Rights Explained:</span>
    <span style="background:{{ $rightsExp ? '#15803d' : '#dc2626' }};color:#fff;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;">
        {{ $rightsExp ? 'Yes' : 'No' }}
    </span>
</div>

{{-- ── Section 10: Valuables ── --}}
@if($valuables)
<div style="margin-bottom:20px;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:10px 14px;">
    <p style="font-size:12px;font-weight:700;color:#92400e;margin:0 0 4px;">&#128274; Valuables Deposited</p>
    <p style="font-size:12px;margin:0;">{{ $valDesc ?? 'Items deposited — see valuables receipt.' }}</p>
</div>
@endif

{{-- ── Section 11: Patient Rights Summary ── --}}
<div style="margin-bottom:20px;border:1px solid #bfdbfe;border-radius:6px;padding:12px 16px;background:#f0f9ff;">
    <h3 style="font-size:11px;font-weight:700;color:#1e40af;text-transform:uppercase;margin-bottom:8px;">Patient Rights Summary</h3>
    <ul style="margin:0;padding-left:16px;">
        <li style="font-size:11px;margin-bottom:4px;">Right to privacy and dignity throughout your care.</li>
        <li style="font-size:11px;margin-bottom:4px;">Right to confidentiality of all medical information.</li>
        <li style="font-size:11px;margin-bottom:4px;">Right to seek a second medical opinion.</li>
        <li style="font-size:11px;margin-bottom:4px;">Right to refuse treatment after being informed of the consequences.</li>
        <li style="font-size:11px;">Right to receive information about your diagnosis, treatment, and prognosis in a language you understand.</li>
    </ul>
</div>

{{-- ── Section 12: Signatures ── --}}
<div style="border-top:2px solid #bfdbfe;padding-top:14px;display:flex;gap:20px;flex-wrap:wrap;">
    @foreach([
        'Patient / Guardian',
        'Admitting Clerk: ' . $clerk,
        'Admitting Doctor: ' . $doctor,
    ] as $sig)
    <div style="flex:1;min-width:160px;text-align:center;">
        <div style="border-bottom:1px solid #374151;height:40px;margin-bottom:4px;"></div>
        <p style="font-size:11px;color:#6b7280;margin:0;">{{ $sig }}</p>
        <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">Date: _____________</p>
    </div>
    @endforeach
</div>
@endsection
