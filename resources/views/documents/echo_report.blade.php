@extends('documents.base')

@section('title', 'Echocardiography Report')

@section('subtitle', 'ECHO — ' . ($payload['study_type'] ?? ''))

@section('content')
@php
    $ef = isset($payload['lv_measurements']['ef_percent']) ? (int) $payload['lv_measurements']['ef_percent'] : null;
    $efColor = 'bg-gray-100 text-gray-800';
    $efBorder = 'border-gray-300';
    if ($ef !== null) {
        if ($ef >= 55) {
            $efColor  = 'bg-green-100 text-green-800';
            $efBorder = 'border-green-400';
        } elseif ($ef >= 40) {
            $efColor  = 'bg-amber-100 text-amber-800';
            $efBorder = 'border-amber-400';
        } else {
            $efColor  = 'bg-red-100 text-red-800';
            $efBorder = 'border-red-400';
        }
    }

    $regColors = [
        'None'     => 'bg-green-100 text-green-800',
        'Trivial'  => 'bg-green-100 text-green-700',
        'Mild'     => 'bg-yellow-100 text-yellow-800',
        'Moderate' => 'bg-amber-100 text-amber-800',
        'Severe'   => 'bg-red-100 text-red-800',
    ];

    $diastGradeColors = [
        'Normal'   => 'bg-green-100 text-green-800',
        'Grade I'  => 'bg-yellow-100 text-yellow-800',
        'Grade II' => 'bg-amber-100 text-amber-800',
        'Grade III'=> 'bg-red-100 text-red-800',
    ];

    $sysColors = [
        'Normal'             => 'bg-green-100 text-green-800',
        'Mildly reduced'     => 'bg-yellow-100 text-yellow-800',
        'Moderately reduced' => 'bg-amber-100 text-amber-800',
        'Severely reduced'   => 'bg-red-100 text-red-800',
    ];

    $lvm    = $payload['lv_measurements'] ?? [];
    $lvf    = $payload['lv_function']     ?? [];
    $diast  = $payload['diastolic_function'] ?? [];
    $valves = $payload['valves'] ?? [];
    $rh     = $payload['right_heart'] ?? [];
    $aorta  = $payload['aorta'] ?? [];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — STUDY HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-blue-200 bg-blue-50 p-5 mb-6">
    <div class="flex flex-wrap items-start gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="inline-flex items-center rounded-full bg-blue-700 px-3 py-1 text-xs font-semibold text-white uppercase tracking-wide">
                    {{ $payload['study_type'] ?? '—' }}
                </span>
                @if (!empty($payload['image_quality']))
                    @php
                        $iqColor = $payload['image_quality'] === 'Good'
                            ? 'bg-green-100 text-green-800'
                            : ($payload['image_quality'] === 'Adequate' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    @endphp
                    <span class="inline-flex items-center rounded-full {{ $iqColor }} px-3 py-1 text-xs font-semibold">
                        Image Quality: {{ $payload['image_quality'] }}
                    </span>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-blue-900">
                <div><span class="font-semibold">Study Date:</span> {{ $payload['study_date'] ?? '—' }}</div>
                <div><span class="font-semibold">Cardiologist:</span> {{ $payload['cardiologist'] ?? '—' }}</div>
                <div><span class="font-semibold">Sonographer:</span> {{ $payload['sonographer'] ?? '—' }}</div>
            </div>
        </div>
    </div>
    @if (!empty($payload['indication']))
        <div class="mt-3 pt-3 border-t border-blue-200 text-sm text-blue-900">
            <span class="font-semibold">Indication:</span> {{ $payload['indication'] }}
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — LV MEASUREMENTS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Left Ventricular Measurements</h3>

    {{-- EF% prominently displayed --}}
    <div class="flex items-center gap-6 mb-4">
        <div class="rounded-xl border-2 {{ $efBorder }} {{ $efColor }} px-8 py-4 text-center min-w-[120px]">
            <div class="text-3xl font-bold">{{ $ef !== null ? $ef . '%' : '—' }}</div>
            <div class="text-xs font-semibold mt-1 uppercase tracking-wide">Ejection Fraction</div>
        </div>
        @if (!empty($lvm['fs_percent']))
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-6 py-4 text-center min-w-[100px]">
                <div class="text-2xl font-bold text-gray-800">{{ $lvm['fs_percent'] }}%</div>
                <div class="text-xs font-semibold mt-1 text-gray-500 uppercase tracking-wide">Fractional Shortening</div>
            </div>
        @endif
        @if (!empty($lvm['lv_mass_g']))
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-6 py-4 text-center min-w-[100px]">
                <div class="text-2xl font-bold text-gray-800">{{ $lvm['lv_mass_g'] }}g</div>
                <div class="text-xs font-semibold mt-1 text-gray-500 uppercase tracking-wide">LV Mass</div>
            </div>
        @endif
    </div>

    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-blue-700 text-white text-xs uppercase tracking-wider">
                <th class="py-2 px-3 text-left font-semibold">Parameter</th>
                <th class="py-2 px-3 text-center font-semibold">Value</th>
                <th class="py-2 px-3 text-center font-semibold">Normal Range</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ([
                ['IVSd',   ($lvm['ivsd_mm']  ?? null), '6–10 mm',  'mm'],
                ['LVEDd',  ($lvm['lvedd_mm'] ?? null), '42–58 mm', 'mm'],
                ['LVESd',  ($lvm['lvesd_mm'] ?? null), '25–40 mm', 'mm'],
                ['LVPWd',  ($lvm['lvpwd_mm'] ?? null), '6–10 mm',  'mm'],
                ['EF%',    ($lvm['ef_percent'] ?? null), '≥ 55%',   '%'],
                ['FS%',    ($lvm['fs_percent'] ?? null), '25–45%',  '%'],
                ['LV Mass',($lvm['lv_mass_g']  ?? null), '67–162 g (♂) / 55–135 g (♀)', 'g'],
            ] as [$label, $val, $normal, $unit])
                @if ($val !== null)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $label }}</td>
                        <td class="py-2 px-3 text-center font-semibold text-gray-900">{{ $val }} {{ $unit }}</td>
                        <td class="py-2 px-3 text-center text-gray-500 text-xs">{{ $normal }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — LV FUNCTION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Left Ventricular Function</h3>
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Systolic Function</div>
            @php $sys = $lvf['systolic'] ?? null; @endphp
            @if ($sys)
                <span class="inline-block rounded-full {{ $sysColors[$sys] ?? 'bg-gray-100 text-gray-800' }} px-3 py-1 text-xs font-semibold">
                    {{ $sys }}
                </span>
            @else
                <span class="text-gray-400 text-sm">—</span>
            @endif
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Wall Motion</div>
            @php $wm = $lvf['wall_motion'] ?? null; @endphp
            @if ($wm)
                <span class="inline-block rounded-full {{ $wm === 'Normal' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }} px-3 py-1 text-xs font-semibold">
                    {{ $wm }}
                </span>
            @else
                <span class="text-gray-400 text-sm">—</span>
            @endif
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 mb-1 uppercase tracking-wide">EF Comment</div>
            <p class="text-xs text-gray-700 text-left">{{ $lvf['ef_comment'] ?? '—' }}</p>
        </div>
    </div>
    @if (!empty($payload['wall_motion_abnormalities']))
        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            <span class="font-semibold">Wall Motion Abnormalities:</span> {{ $payload['wall_motion_abnormalities'] }}
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — DIASTOLIC FUNCTION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Diastolic Function</h3>
    <div class="flex flex-wrap items-start gap-4">
        @php $dg = $diast['grade'] ?? null; @endphp
        @if ($dg)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center min-w-[120px]">
                <div class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Grade</div>
                <span class="inline-block rounded-full {{ $diastGradeColors[$dg] ?? 'bg-gray-100 text-gray-800' }} px-3 py-1 text-xs font-semibold">
                    {{ $dg }}
                </span>
            </div>
        @endif
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm text-gray-700 flex-1">
            @foreach ([
                ['E Wave',        $diast['e_wave'] ?? null,          'cm/s'],
                ['A Wave',        $diast['a_wave'] ?? null,          'cm/s'],
                ["E' Lateral",    $diast['e_prime_lateral'] ?? null, 'cm/s'],
                ["E/E' Ratio",    $diast['e_e_prime_ratio'] ?? null, ''],
            ] as [$label, $val, $unit])
                @if ($val !== null)
                    <div>
                        <span class="font-semibold">{{ $label }}:</span>
                        <span class="ml-1">{{ $val }}{{ $unit ? ' ' . $unit : '' }}</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — VALVE ASSESSMENT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Valve Assessment</h3>
    <div class="grid grid-cols-2 gap-4">
        @foreach ([
            ['Mitral Valve',    'mitral',    $valves['mitral']    ?? []],
            ['Aortic Valve',    'aortic',    $valves['aortic']    ?? []],
            ['Tricuspid Valve', 'tricuspid', $valves['tricuspid'] ?? []],
            ['Pulmonary Valve', 'pulmonary', $valves['pulmonary'] ?? []],
        ] as [$label, $key, $v])
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="font-semibold text-gray-800 text-sm mb-2">{{ $label }}</div>
                @if (!empty($v['morphology']))
                    <div class="text-xs text-gray-600 mb-2">{{ $v['morphology'] }}</div>
                @endif
                <div class="flex flex-wrap gap-2">
                    @if (!empty($v['regurgitation']))
                        @php $reg = $v['regurgitation']; @endphp
                        <span class="inline-block rounded-full {{ $regColors[$reg] ?? 'bg-gray-100 text-gray-800' }} px-2 py-0.5 text-xs font-semibold">
                            Regurgitation: {{ $reg }}
                        </span>
                    @endif
                    @if (!empty($v['stenosis']))
                        <span class="inline-block rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-xs font-semibold">Stenosis</span>
                    @endif
                </div>
                @if (!empty($v['mvarea_cm2']) || !empty($v['avarea_cm2']))
                    @php $area = $v['mvarea_cm2'] ?? $v['avarea_cm2'] ?? null; @endphp
                    @if ($area)
                        <div class="text-xs text-gray-600 mt-1">Valve Area: {{ $area }} cm²</div>
                    @endif
                @endif
                @if (!empty($v['gradient_mmhg']))
                    <div class="text-xs text-gray-600 mt-1">Mean Gradient: {{ $v['gradient_mmhg'] }} mmHg</div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — RIGHT HEART
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Right Heart</h3>
    <div class="grid grid-cols-4 gap-3">
        @foreach ([
            ['RV Size',     $rh['rv_size']     ?? null],
            ['RV Function', $rh['rv_function'] ?? null],
            ['RA Size',     $rh['rai_size']    ?? null],
        ] as [$lbl, $val])
            @if ($val)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">{{ $lbl }}</div>
                    <span class="inline-block rounded-full {{ $val === 'Normal' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }} px-2 py-0.5 text-xs font-semibold">
                        {{ $val }}
                    </span>
                </div>
            @endif
        @endforeach
        @if (!empty($rh['rvsp_mmhg']))
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                <div class="text-xs text-gray-500 mb-1">RVSP (estimated)</div>
                <div class="text-lg font-bold text-gray-800">{{ $rh['rvsp_mmhg'] }} mmHg</div>
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — AORTA
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($aorta['root_mm']) || !empty($aorta['ascending_mm']))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Aorta</h3>
        <div class="flex gap-6 text-sm text-gray-700">
            @if (!empty($aorta['root_mm']))
                <div><span class="font-semibold">Aortic Root:</span> {{ $aorta['root_mm'] }} mm <span class="text-gray-400 text-xs">(Normal ≤ 40 mm)</span></div>
            @endif
            @if (!empty($aorta['ascending_mm']))
                <div><span class="font-semibold">Ascending Aorta:</span> {{ $aorta['ascending_mm'] }} mm <span class="text-gray-400 text-xs">(Normal ≤ 40 mm)</span></div>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — PERICARDIUM & IVC
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Pericardium &amp; IVC</h3>
    <div class="grid grid-cols-2 gap-4 text-sm">
        @if (!empty($payload['pericardium']))
            <div>
                <span class="font-semibold text-gray-700">Pericardium:</span>
                <span class="{{ $payload['pericardium'] === 'Normal' ? 'text-green-700' : 'text-amber-700 font-medium' }} ml-1">
                    {{ $payload['pericardium'] }}
                </span>
            </div>
        @endif
        @if (!empty($payload['ivc']))
            <div>
                <span class="font-semibold text-gray-700">IVC:</span>
                <span class="ml-1 text-gray-800">{{ $payload['ivc'] }}</span>
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — IMPRESSION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['impression']))
    <div class="mb-6 rounded-xl border-2 border-blue-700 bg-blue-50 p-5">
        <h3 class="text-sm font-bold text-blue-900 uppercase tracking-wider mb-2">Impression / Clinical Summary</h3>
        <p class="text-sm text-blue-900 leading-relaxed">{{ $payload['impression'] }}</p>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — RECOMMENDATION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['recommendation']))
    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wider mb-2">Recommendation</h3>
        <p class="text-sm text-blue-900">{{ $payload['recommendation'] }}</p>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — CARDIOLOGIST SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t pt-5">
    <div class="flex justify-end">
        <div class="text-right text-sm">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Reporting Cardiologist</div>
            <div class="font-bold text-gray-900">{{ $payload['cardiologist'] ?? $issuer_name }}</div>
            <div class="text-gray-500">{{ $issued_at }}</div>
        </div>
    </div>
</div>
@endsection
