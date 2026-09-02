@extends('documents.base')

@section('title', 'Chemotherapy Protocol / Administration Record')
@section('subtitle', 'CTX — Oncology Chemotherapy Administration')

@section('content')
@php
    $preBloods       = $payload['pre_cycle_bloods'] ?? [];
    $goNogo          = $payload['go_nogo'] ?? 'GO';
    $preMeds         = $payload['pre_medications'] ?? [];
    $agents          = $payload['chemotherapy_agents'] ?? [];
    $postMeds        = $payload['post_medications'] ?? [];
    $toxicities      = $payload['toxicities_previous_cycle'] ?? [];
    $education       = $payload['patient_education'] ?? [];
    $complications   = $payload['complications_during'] ?? 'None';
    $doseModification = $payload['dose_modification'] ?? null;
    $nogoReason      = $payload['nogo_reason'] ?? null;

    $gonogoColour = function(string $status): string {
        if ($status === 'GO')               return 'background:#d1fae5;color:#065f46;';
        if ($status === 'NO GO')            return 'background:#fee2e2;color:#991b1b;';
        return 'background:#fef3c7;color:#92400e;';
    };

    $ctcaeGradeColour = function(int $grade): string {
        if ($grade === 1) return 'background:#fef9c3;color:#713f12;';
        if ($grade === 2) return 'background:#fef3c7;color:#92400e;';
        if ($grade === 3) return 'background:#fed7aa;color:#9a3412;';
        return 'background:#fee2e2;color:#991b1b;';
    };

    $bloodNormal = [
        'wbc'         => ['label' => 'WBC',          'range' => '4.0 – 11.0 ×10⁹/L'],
        'neutrophils' => ['label' => 'Neutrophils',  'range' => '1.8 – 7.5 ×10⁹/L'],
        'haemoglobin' => ['label' => 'Haemoglobin',  'range' => '12.0 – 17.5 g/dL'],
        'platelets'   => ['label' => 'Platelets',    'range' => '150 – 400 ×10⁹/L'],
        'creatinine'  => ['label' => 'Creatinine',   'range' => '62 – 115 µmol/L'],
        'alt'         => ['label' => 'ALT',          'range' => '7 – 56 U/L'],
        'ast'         => ['label' => 'AST',          'range' => '10 – 40 U/L'],
    ];

    $purposeColour = function(string $purpose): string {
        $map = [
            'Antiemetic'   => 'background:#dbeafe;color:#1e40af;',
            'Steroid'      => 'background:#fce7f3;color:#9d174d;',
            'Antihistamine'=> 'background:#f0fdf4;color:#166534;',
            'Hydration'    => 'background:#ecfeff;color:#155e75;',
        ];
        return $map[$purpose] ?? 'background:#f3f4f6;color:#374151;';
    };
@endphp

{{-- ══════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════ --}}
<div style="background:#7c3aed;color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-start;">
        <div style="flex:1;min-width:200px;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.05em;">Protocol</div>
            <div style="font-size:20px;font-weight:700;line-height:1.2;">{{ $payload['protocol_name'] ?? '—' }}</div>
            <div style="margin-top:5px;font-size:12px;opacity:.9;">{{ $payload['cancer_diagnosis'] ?? '—' }}</div>
            <div style="margin-top:2px;font-size:11px;opacity:.75;">ICD-10: {{ $payload['icd10_code'] ?? '—' }}</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
            <span style="background:rgba(255,255,255,.25);border-radius:20px;padding:5px 14px;font-size:14px;font-weight:700;">
                Cycle {{ $payload['cycle_number'] ?? '—' }} / {{ $payload['total_cycles'] ?? '—' }}
            </span>
            <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:3px 12px;font-size:12px;">
                {{ $payload['cycle_date'] ?? '—' }}
            </span>
        </div>
    </div>
    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:16px;font-size:12px;background:rgba(0,0,0,.15);border-radius:6px;padding:8px 12px;">
        <span><strong>BSA:</strong> {{ $payload['bsa_m2'] ?? '—' }} m²</span>
        <span><strong>Weight:</strong> {{ $payload['weight_kg'] ?? '—' }} kg</span>
        <span><strong>Height:</strong> {{ $payload['height_cm'] ?? '—' }} cm</span>
        <span><strong>Oncologist:</strong> {{ $payload['oncologist'] ?? '—' }}</span>
        <span><strong>Chemo Nurse:</strong> {{ $payload['chemo_nurse'] ?? '—' }}</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     PRE-CYCLE BLOODS + GO/NO GO
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Pre-Cycle Blood Results</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#ede9fe;color:#5b21b6;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #ddd6fe;">Parameter</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Result</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Normal Range</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bloodNormal as $key => $meta)
            <tr style="background:{{ $loop->index % 2 === 0 ? '#fff' : '#faf5ff' }};">
                <td style="padding:5px 10px;border:1px solid #ddd6fe;font-weight:600;">{{ $meta['label'] }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $preBloods[$key] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;color:#6b7280;font-size:11px;">{{ $meta['range'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- GO / NO GO badge --}}
<div style="margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
    <span style="font-size:24px;font-weight:800;border-radius:8px;padding:8px 24px;{{ $gonogoColour($goNogo) }}">
        {{ $goNogo }}
    </span>
    @if ($nogoReason)
    <span style="font-size:12px;color:#991b1b;font-style:italic;">{{ $nogoReason }}</span>
    @endif
    @if ($doseModification)
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;padding:6px 14px;font-size:12px;color:#92400e;font-weight:600;">
        Dose Modification: {{ $doseModification }}
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     PRE-MEDICATIONS
══════════════════════════════════════════════ --}}
@if (!empty($preMeds))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Pre-Medications</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#ede9fe;color:#5b21b6;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #ddd6fe;">Drug</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Dose</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Route</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Time</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Purpose</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($preMeds as $i => $m)
            <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#faf5ff' }};">
                <td style="padding:5px 10px;border:1px solid #ddd6fe;font-weight:600;">{{ $m['drug'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['dose'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['route'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['time'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">
                    @if (!empty($m['purpose']))
                    <span style="border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;{{ $purposeColour($m['purpose']) }}">
                        {{ $m['purpose'] }}
                    </span>
                    @else
                    —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════
     CHEMOTHERAPY AGENTS — MAIN TABLE
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Chemotherapy Agents Administered</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#7c3aed;color:#fff;">
                    <th style="padding:7px 10px;text-align:left;border:1px solid #6d28d9;">Drug</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;white-space:nowrap;">Dose /m²</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;white-space:nowrap;">Calc. Dose</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;">Volume</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;">Diluent</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;white-space:nowrap;">Rate (mL/hr)</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;white-space:nowrap;">Start</th>
                    <th style="padding:7px 10px;text-align:center;border:1px solid #6d28d9;white-space:nowrap;">End</th>
                    <th style="padding:7px 10px;text-align:left;border:1px solid #6d28d9;white-space:nowrap;">Given By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($agents as $i => $a)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#faf5ff' }};border-left:3px solid #7c3aed;">
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;font-weight:700;color:#5b21b6;">{{ $a['drug'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $a['dose_per_m2'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;font-weight:600;">{{ $a['calculated_dose'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $a['volume_ml'] ?? '—' }} mL</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $a['diluent'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $a['rate_ml_hr'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;white-space:nowrap;">{{ $a['infusion_start'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;text-align:center;white-space:nowrap;">{{ $a['infusion_end'] ?? '—' }}</td>
                    <td style="padding:6px 10px;border:1px solid #ddd6fe;">{{ $a['administered_by'] ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="padding:12px;text-align:center;color:#9ca3af;border:1px solid #ddd6fe;">No agents recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Post medications --}}
@if (!empty($postMeds))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Post-Medications</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#ede9fe;color:#5b21b6;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #ddd6fe;">Drug</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Dose</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Route</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #ddd6fe;">Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($postMeds as $i => $m)
            <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#faf5ff' }};">
                <td style="padding:5px 10px;border:1px solid #ddd6fe;">{{ $m['drug'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['dose'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['route'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #ddd6fe;text-align:center;">{{ $m['time'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════
     PREVIOUS CYCLE TOXICITIES
══════════════════════════════════════════════ --}}
@if (!empty($toxicities))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Previous Cycle Toxicities (CTCAE)</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach ($toxicities as $t)
        <div style="background:#faf5ff;border:1px solid #ddd6fe;border-radius:8px;padding:8px 12px;font-size:12px;">
            <div style="font-weight:600;color:#374151;margin-bottom:3px;">{{ $t['ctcae_term'] ?? $t['toxicity'] ?? '—' }}</div>
            <span style="border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;{{ $ctcaeGradeColour((int)($t['grade'] ?? 0)) }}">
                Grade {{ $t['grade'] ?? '—' }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Growth factor --}}
@if (!empty($payload['growth_factor']))
<div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:12px 16px;margin-bottom:18px;font-size:12px;">
    <span style="font-weight:700;color:#5b21b6;">Growth Factor Support: </span>
    <span style="color:#374151;">{{ $payload['growth_factor'] }}</span>
</div>
@endif

{{-- Patient education checklist --}}
@if (!empty($education))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Patient Education Provided</div>
    <ul style="margin:0;padding-left:18px;font-size:12px;color:#374151;">
        @foreach ($education as $item)
        <li style="margin-bottom:3px;">{{ $item }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Complications during --}}
<div style="margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#5b21b6;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Complications During This Cycle</div>
    @if (strtolower($complications) === 'none')
    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:3px 12px;font-size:12px;font-weight:600;">None</span>
    @else
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;font-size:12px;color:#991b1b;">
        {{ $complications }}
    </div>
    @endif
</div>

{{-- Next cycle date --}}
@if (!empty($payload['next_cycle_date']))
<div style="background:#f5f3ff;border:2px solid #7c3aed;border-radius:8px;padding:12px 20px;margin-bottom:18px;display:inline-block;">
    <div style="font-size:11px;color:#5b21b6;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Next Cycle Date</div>
    <div style="font-size:18px;font-weight:700;color:#4c1d95;">{{ $payload['next_cycle_date'] }}</div>
</div>
@endif

{{-- Dual Signatures --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:8px;">
    <div style="flex:1;min-width:180px;border-top:2px solid #7c3aed;padding-top:8px;">
        <div style="height:26px;"></div>
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['oncologist'] ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Oncologist</div>
    </div>
    <div style="flex:1;min-width:180px;border-top:2px solid #7c3aed;padding-top:8px;">
        <div style="height:26px;"></div>
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['chemo_nurse'] ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Chemotherapy Nurse</div>
    </div>
</div>
@endsection
