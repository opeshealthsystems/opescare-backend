@extends('documents.base')

@section('title', 'Adverse Drug Reaction Report')
@section('subtitle', 'ADR — WHO-UMC Causality &amp; NAFDAC Pharmacovigilance')

@section('content')
@php
    $accentColor      = '#DC2626';
    $reactionDate     = $payload['reaction_date']           ?? '—';
    $reportDate       = $payload['report_date']             ?? '—';
    $suspectDrug      = $payload['suspect_drug']            ?? '—';
    $suspectDose      = $payload['suspect_drug_dose']       ?? '—';
    $suspectRoute     = $payload['suspect_drug_route']      ?? '—';
    $suspectIndic     = $payload['suspect_drug_indication'] ?? '—';
    $suspectStart     = $payload['suspect_drug_start_date'] ?? '—';
    $suspectStop      = $payload['suspect_drug_stop_date']  ?? 'Ongoing';
    $reactionDesc     = $payload['reaction_description']    ?? '—';
    $reactionStart    = $payload['reaction_start_date']     ?? '—';
    $onsetAfter       = $payload['reaction_onset_after']    ?? '—';
    $reactionType     = $payload['reaction_type']           ?? '—';
    $severity         = $payload['reaction_severity']       ?? '—';
    $outcome          = $payload['outcome']                 ?? '—';
    $actionTaken      = $payload['action_taken']            ?? '—';
    $rechallenge      = $payload['rechallenge']             ?? '—';
    $dechallenge      = $payload['dechallenge']             ?? '—';
    $causality        = $payload['causality_who_umc']       ?? '—';
    $concomitant      = $payload['concomitant_drugs']       ?? [];
    $nafdacReported   = $payload['nafdac_reported']         ?? false;
    $nafdacRef        = $payload['nafdac_ref']              ?? null;
    $minsanteReported = $payload['minsante_reported']       ?? false;
    $knownAllergy     = $payload['patient_known_allergy']   ?? false;
    $preventable      = $payload['preventable']             ?? false;
    $prevNotes        = $payload['prevention_notes']        ?? null;
    $reporter         = $payload['reporter']                ?? '—';
    $reporterDesig    = $payload['reporter_designation']    ?? '—';

    $severityColors = [
        'Mild'            => '#15803d',
        'Moderate'        => '#d97706',
        'Severe'          => '#dc2626',
        'Life-threatening'=> '#7c3aed',
        'Fatal'           => '#111827',
    ];
    $severityBg = $severityColors[$severity] ?? '#374151';

    $causalityColors = [
        'Certain'         => '#dc2626',
        'Probable/Likely' => '#ea580c',
        'Possible'        => '#d97706',
        'Unlikely'        => '#6b7280',
        'Conditional'     => '#0369a1',
        'Unassessable'    => '#374151',
    ];
    $causalityBg = $causalityColors[$causality] ?? '#374151';

    $outcomeColors = [
        'Recovered fully'          => '#15803d',
        'Recovering'               => '#0369a1',
        'Recovered with sequelae'  => '#d97706',
        'Not recovered'            => '#dc2626',
        'Fatal'                    => '#111827',
        'Unknown'                  => '#6b7280',
    ];
    $outcomeBg = $outcomeColors[$outcome] ?? '#374151';
@endphp

{{-- ── Section 1: Header with severity badge ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="background:{{ $severityBg }};border:2px solid rgba(255,255,255,.5);padding:4px 14px;border-radius:4px;font-weight:700;font-size:13px;text-transform:uppercase;">
            {{ $severity }}
        </span>
        <span style="background:rgba(0,0,0,.25);padding:3px 10px;border-radius:4px;font-size:12px;">
            {{ $reactionType }}
        </span>
        <span style="font-size:13px;margin-left:auto;"><strong>Reaction Date:</strong> {{ $reactionDate }}</span>
        <span style="font-size:13px;"><strong>Report Date:</strong> {{ $reportDate }}</span>
    </div>
</div>

{{-- ── Section 2: Suspect Drug Card ── --}}
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:14px 16px;margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:#991b1b;text-transform:uppercase;margin-bottom:10px;">Suspect Drug</h3>
    <p style="font-size:18px;font-weight:800;color:#7f1d1d;margin:0 0 10px;">{{ $suspectDrug }}</p>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;">
        <span><strong>Dose:</strong> {{ $suspectDose }}</span>
        <span><strong>Route:</strong> {{ $suspectRoute }}</span>
        <span><strong>Indication:</strong> {{ $suspectIndic }}</span>
        <span><strong>Started:</strong> {{ $suspectStart }}</span>
        <span><strong>Stopped:</strong> {{ $suspectStop }}</span>
    </div>
</div>

{{-- ── Section 3: Reaction Details ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:#991b1b;text-transform:uppercase;border-bottom:2px solid #fca5a5;padding-bottom:4px;margin-bottom:10px;">
        Reaction Details
    </h3>
    <p style="font-size:13px;line-height:1.6;margin:0 0 10px;">{{ $reactionDesc }}</p>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;background:#fef2f2;padding:8px 12px;border-radius:4px;">
        <span><strong>Reaction Start:</strong> {{ $reactionStart }}</span>
        <span><strong>Onset After Dose:</strong> {{ $onsetAfter }}</span>
    </div>
</div>

{{-- ── Section 4: Outcome + Action + Rechallenge / Dechallenge ── --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;flex:1;min-width:140px;">
        <p style="font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:700;margin:0 0 6px;">Outcome</p>
        <span style="background:{{ $outcomeBg }};color:#fff;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">
            {{ $outcome }}
        </span>
    </div>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;flex:1;min-width:140px;">
        <p style="font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:700;margin:0 0 6px;">Action Taken</p>
        <span style="background:#374151;color:#fff;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">
            {{ $actionTaken }}
        </span>
    </div>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;flex:1;min-width:140px;">
        <p style="font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:700;margin:0 0 6px;">Dechallenge</p>
        <p style="font-size:12px;margin:0;">{{ $dechallenge }}</p>
    </div>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;flex:1;min-width:140px;">
        <p style="font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:700;margin:0 0 6px;">Rechallenge</p>
        <p style="font-size:12px;margin:0;">{{ $rechallenge }}</p>
    </div>
</div>

{{-- ── Section 5: WHO-UMC Causality (prominent) ── --}}
<div style="margin-bottom:20px;text-align:center;padding:14px;background:{{ $causalityBg }};border-radius:8px;color:#fff;">
    <p style="font-size:10px;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;opacity:.8;">WHO-UMC Causality Assessment</p>
    <p style="font-size:22px;font-weight:800;margin:0;">{{ $causality }}</p>
</div>

{{-- ── Section 6: Concomitant Drugs ── --}}
@if(count($concomitant) > 0)
<div style="margin-bottom:20px;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;">
    <h3 style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;margin-bottom:8px;">Concomitant Drugs</h3>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach($concomitant as $drug)
            <span style="background:#e5e7eb;color:#374151;padding:3px 10px;border-radius:10px;font-size:11px;">{{ $drug }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- ── Section 7: Preventability ── --}}
<div style="margin-bottom:20px;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;background:#fef2f2;">
    <h3 style="font-size:11px;font-weight:700;color:#991b1b;text-transform:uppercase;margin-bottom:10px;">Preventability</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:12px;font-weight:600;">Known Allergy Missed:</span>
            <span style="background:{{ $knownAllergy ? '#dc2626' : '#15803d' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
                {{ $knownAllergy ? 'Yes' : 'No' }}
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:12px;font-weight:600;">Preventable:</span>
            <span style="background:{{ $preventable ? '#dc2626' : '#15803d' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
                {{ $preventable ? 'Yes' : 'No' }}
            </span>
        </div>
    </div>
    @if($prevNotes)
        <p style="font-size:12px;margin:0;color:#7f1d1d;">{{ $prevNotes }}</p>
    @endif
</div>

{{-- ── Section 8: Pharmacovigilance Reporting ── --}}
<div style="margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;">
    <div style="flex:1;min-width:160px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;">
        <p style="font-size:10px;text-transform:uppercase;font-weight:700;color:#374151;margin:0 0 6px;">NAFDAC Reported</p>
        <span style="background:{{ $nafdacReported ? '#15803d' : '#6b7280' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
            {{ $nafdacReported ? 'Yes' : 'No' }}
        </span>
        @if($nafdacReported && $nafdacRef)
            <p style="font-size:11px;color:#374151;margin:4px 0 0;font-family:monospace;">Ref: {{ $nafdacRef }}</p>
        @endif
    </div>
    <div style="flex:1;min-width:160px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;">
        <p style="font-size:10px;text-transform:uppercase;font-weight:700;color:#374151;margin:0 0 6px;">MINSANTE Reported</p>
        <span style="background:{{ $minsanteReported ? '#15803d' : '#6b7280' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
            {{ $minsanteReported ? 'Yes' : 'No' }}
        </span>
    </div>
</div>

{{-- ── Section 9: Reporter ── --}}
<div style="margin-bottom:20px;border-top:1px solid #fca5a5;padding-top:12px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;align-items:flex-end;">
    <div>
        <p style="font-size:12px;font-weight:700;margin:0;">{{ $reporter }}</p>
        <p style="font-size:12px;color:#6b7280;margin:2px 0 0;">{{ $reporterDesig }}</p>
        <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">Signature: _______________________________</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px;margin:0;"><strong>Date:</strong> {{ $reportDate }}</p>
    </div>
</div>

{{-- ── Section 10: NAFDAC Notice ── --}}
<div style="background:#fef2f2;border:2px solid #dc2626;border-radius:6px;padding:10px 14px;">
    <p style="font-size:11px;color:#7f1d1d;margin:0;font-weight:600;">
        &#9888; All serious adverse drug reactions (Grade 3 and above, or unexpected reactions) must be reported to the
        NAFDAC National Pharmacovigilance Centre within <strong>15 calendar days</strong> of the reporter becoming aware of the event.
        Fatal or life-threatening reactions should be reported within <strong>7 days</strong>.
    </p>
</div>
@endsection
