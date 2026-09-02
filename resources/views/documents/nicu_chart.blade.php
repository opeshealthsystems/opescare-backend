@extends('documents.base')

@section('title', 'NICU Chart')

@section('subtitle', 'NIC — NEONATAL INTENSIVE CARE / SOINS INTENSIFS NÉONATAUX')

@section('content')
@php
    $respColors = [
        'Room air'                 => 'bg-green-100 text-green-800',
        'CPAP'                     => 'bg-blue-100 text-blue-800',
        'High-flow nasal cannula'  => 'bg-blue-200 text-blue-900',
        'Intubated — SIMV'         => 'bg-red-100 text-red-800',
        'Intubated — HFO'          => 'bg-red-200 text-red-900',
    ];
    $feedColors = [
        'Breastfeeding'   => 'bg-pink-100 text-pink-800',
        'EBM (expressed)' => 'bg-pink-200 text-pink-900',
        'Formula'         => 'bg-blue-100 text-blue-800',
        'NG feed'         => 'bg-amber-100 text-amber-800',
        'NBM'             => 'bg-red-100 text-red-800',
    ];

    $hourlyObs      = $payload['hourly_observations'] ?? [];
    $feeding        = $payload['feeding']             ?? [];
    $fluidBalance   = $payload['fluid_balance']       ?? [];
    $phototherapy   = $payload['phototherapy']        ?? [];
    $medications    = $payload['medications']          ?? [];
    $dailyBloods    = $payload['daily_bloods']         ?? null;
    $umbLines       = $payload['umbilical_lines']      ?? null;
    $respSupport    = $payload['respiratory_support']  ?? '';
    $weightChange   = $payload['weight_change_g']      ?? null;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — PINK HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#FDF2F8;border:2px solid #DB2777;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#DB2777;">NICU Chart</div>
            <div class="text-xs font-semibold mt-0.5 uppercase tracking-wider text-pink-700">Fiche de soins intensifs néonataux</div>
            <div class="text-xs text-pink-700 mt-1 space-x-3">
                <span><span class="font-semibold">GA at Birth:</span> {{ $payload['gestational_age_at_birth'] ?? '—' }}</span>
                <span><span class="font-semibold">Corrected Age:</span> {{ $payload['corrected_age'] ?? '—' }}</span>
                <span><span class="font-semibold">{{ $payload['nicu_day'] ?? 'NICU Day' }}</span></span>
                <span><span class="font-semibold">Chart Date:</span> {{ $payload['chart_date'] ?? '—' }}</span>
            </div>
            @if (!empty($payload['primary_diagnosis']))
                <div class="text-xs text-pink-800 mt-1 font-semibold">Dx: {{ $payload['primary_diagnosis'] }}</div>
            @endif
        </div>
        <span class="inline-block rounded-full text-white text-xs font-bold px-3 py-1 uppercase tracking-wide" style="background-color:#DB2777;">NIC</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — WEIGHT CARD
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-3 gap-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Birth Weight</div>
        <div class="text-xl font-black text-gray-800 mt-1">{{ $payload['birth_weight_g'] ?? '—' }}<span class="text-xs font-normal text-gray-500 ml-1">g</span></div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Current Weight</div>
        <div class="text-xl font-black text-gray-800 mt-1">{{ $payload['current_weight_g'] ?? '—' }}<span class="text-xs font-normal text-gray-500 ml-1">g</span></div>
    </div>
    <div class="rounded-lg border {{ ($weightChange !== null && $weightChange < -20) ? 'border-red-300 bg-red-50' : 'border-green-200 bg-green-50' }} p-3 text-center">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Change</div>
        <div class="text-xl font-black {{ ($weightChange !== null && $weightChange < -20) ? 'text-red-700' : 'text-green-700' }} mt-1">
            {{ $weightChange !== null ? (($weightChange >= 0 ? '+' : '') . $weightChange) : '—' }}<span class="text-xs font-normal ml-1">g</span>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — RESPIRATORY SUPPORT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Respiratory Support</h3>
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-block rounded-full {{ $respColors[$respSupport] ?? 'bg-gray-100 text-gray-700' }} px-4 py-1.5 text-sm font-bold">
            {{ $respSupport ?: '—' }}
        </span>
        @if (!empty($payload['fio2_pct']))
            <span class="inline-block rounded bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5">FiO2: {{ $payload['fio2_pct'] }}%</span>
        @endif
        @if (!empty($payload['cpap_pressure']))
            <span class="inline-block rounded bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5">CPAP: {{ $payload['cpap_pressure'] }} cmH2O</span>
        @endif
        @if (!empty($payload['incubator_temp']))
            <span class="inline-block rounded bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold px-2 py-0.5">Incubator: {{ $payload['incubator_temp'] }}</span>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — HOURLY OBSERVATIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($hourlyObs))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Hourly Observations</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-pink-50">
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Hour</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">HR</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">RR</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">SpO2</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Temp(ax)</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Temp(inc)</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">BP</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Colour</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Tone</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Activity</th>
                        <th class="border border-gray-200 px-1.5 py-2 text-left font-semibold text-gray-700">Apnoea</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hourlyObs as $obs)
                        @php
                            $hrVal      = $obs['hr'] ?? null;
                            $spo2Val    = $obs['spo2'] ?? null;
                            $apnoeaVal  = $obs['apnoea_events'] ?? 0;
                            $hrCrit     = $hrVal !== null && ($hrVal < 100 || $hrVal > 180);
                            $spo2Crit   = $spo2Val !== null && $spo2Val < 90;
                        @endphp
                        <tr class="{{ ($hrCrit || $spo2Crit) ? 'bg-red-50' : ($apnoeaVal > 0 ? 'bg-amber-50' : 'even:bg-gray-50') }}">
                            <td class="border border-gray-200 px-1.5 py-1 font-mono text-gray-600">{{ $obs['hour'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 {{ $hrCrit ? 'text-red-700 font-bold' : 'text-gray-800' }}">{{ $hrVal ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-800">{{ $obs['rr'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 {{ $spo2Crit ? 'text-red-700 font-bold' : 'text-gray-800' }}">{{ $spo2Val !== null ? $spo2Val . '%' : '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">{{ $obs['temp_axillary'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">{{ $obs['temp_incubator'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">
                                @if (!empty($obs['bp_sys']))
                                    {{ $obs['bp_sys'] }}/{{ $obs['bp_dia'] ?? '?' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">{{ $obs['color'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">{{ $obs['tone'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 text-gray-700">{{ $obs['activity'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-1.5 py-1 {{ $apnoeaVal > 0 ? 'text-amber-700 font-bold' : 'text-gray-400' }}">{{ $apnoeaVal }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-1 text-xs text-gray-500">Red = HR &lt;100 or &gt;180 / SpO2 &lt;90% &bull; Amber = apnoea events</div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — FEEDING
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Feeding</h3>
    @if (!empty($feeding))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="flex items-center gap-3 mb-3 flex-wrap">
                <span class="inline-block rounded-full {{ $feedColors[$feeding['method'] ?? ''] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-bold">
                    {{ $feeding['method'] ?? '—' }}
                </span>
                <span class="text-xs text-gray-600">Frequency: {{ $feeding['frequency'] ?? '—' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-3 text-xs text-gray-700">
                @if (!empty($feeding['volume_per_feed_ml']))
                    <div><span class="font-semibold">Volume/Feed:</span> {{ $feeding['volume_per_feed_ml'] }} ml</div>
                @endif
                @if (!empty($feeding['total_24h_ml']))
                    <div><span class="font-semibold">Total 24h:</span> {{ $feeding['total_24h_ml'] }} ml</div>
                @endif
                @if ($feeding['feeds_tolerated'] !== null)
                    <div><span class="font-semibold">Tolerated:</span> {{ $feeding['feeds_tolerated'] }}</div>
                @endif
                @if ($feeding['feeds_vomited'] !== null)
                    <div><span class="font-semibold text-red-700">Vomited:</span> {{ $feeding['feeds_vomited'] }}</div>
                @endif
                @if ($feeding['residuals_ml'] !== null)
                    <div><span class="font-semibold text-amber-700">Residuals:</span> {{ $feeding['residuals_ml'] }} ml</div>
                @endif
            </div>
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — FLUID BALANCE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Fluid Balance</h3>
    @if (!empty($fluidBalance))
        <div class="grid grid-cols-5 gap-3">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-2 text-center">
                <div class="text-xs text-gray-500 uppercase">IV</div>
                <div class="font-bold text-blue-800">{{ $fluidBalance['iv_ml'] ?? '—' }} ml</div>
            </div>
            <div class="rounded-lg border border-pink-200 bg-pink-50 p-2 text-center">
                <div class="text-xs text-gray-500 uppercase">Oral</div>
                <div class="font-bold text-pink-800">{{ $fluidBalance['oral_ml'] ?? '—' }} ml</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 text-center">
                <div class="text-xs text-gray-500 uppercase">Urine</div>
                <div class="font-bold text-amber-800">{{ $fluidBalance['urine_ml'] ?? '—' }} ml</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-center">
                <div class="text-xs text-gray-500 uppercase">Stool</div>
                <div class="font-bold text-gray-800">{{ $fluidBalance['stool'] ?? '—' }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-center">
                <div class="text-xs text-gray-500 uppercase">Net</div>
                <div class="font-bold text-gray-800">{{ $fluidBalance['net_ml'] ?? '—' }} ml</div>
            </div>
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — PHOTOTHERAPY
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Phototherapy</h3>
    @if (!empty($phototherapy))
        <div class="rounded-lg border {{ !empty($phototherapy['active']) ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 bg-gray-50' }} p-3 flex flex-wrap items-center gap-4">
            <span class="inline-block rounded-full {{ !empty($phototherapy['active']) ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700' }} px-3 py-1 text-xs font-bold uppercase">
                {{ !empty($phototherapy['active']) ? 'Active' : 'Not Active' }}
            </span>
            @if (!empty($phototherapy['hours_today']))
                <span class="text-xs text-gray-700"><span class="font-semibold">Hours today:</span> {{ $phototherapy['hours_today'] }}h</span>
            @endif
            @if (!empty($phototherapy['bilirubin_latest']))
                <span class="text-xs text-gray-700"><span class="font-semibold">Bilirubin:</span> {{ $phototherapy['bilirubin_latest'] }} ({{ $phototherapy['bilirubin_time'] ?? '' }})</span>
            @endif
            @if (!empty($phototherapy['eye_patches']))
                <span class="inline-block rounded-full bg-gray-200 text-gray-700 px-2 py-0.5 text-xs font-semibold">Eye Patches On</span>
            @endif
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — UMBILICAL LINES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($umbLines))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Umbilical Lines</h3>
        <div class="space-y-2">
            @foreach ($umbLines as $line)
                @php
                    $daysIn = null;
                    if (!empty($line['inserted_date'])) {
                        try {
                            $inserted = new \DateTime($line['inserted_date']);
                            $today    = new \DateTime($payload['chart_date'] ?? 'today');
                            $daysIn   = (int) $inserted->diff($today)->days;
                        } catch (\Exception $e) {
                            $daysIn = null;
                        }
                    }
                @endphp
                <div class="rounded-lg border {{ $daysIn !== null && $daysIn > 7 ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50' }} p-3 flex items-center gap-4 text-xs text-gray-700">
                    <span class="inline-block rounded bg-gray-700 text-white font-bold px-2 py-0.5">{{ $line['type'] ?? '—' }}</span>
                    <span><span class="font-semibold">Inserted:</span> {{ $line['inserted_date'] ?? '—' }}</span>
                    <span><span class="font-semibold">Condition:</span> {{ $line['condition'] ?? '—' }}</span>
                    @if ($daysIn !== null)
                        <span class="{{ $daysIn > 7 ? 'text-red-700 font-bold' : 'text-gray-600' }}">Day {{ $daysIn }}{{ $daysIn > 7 ? ' ⚠ Review removal' : '' }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — MEDICATIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($medications))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Medications</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Drug</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Dose</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Route</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($medications as $med)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 font-semibold text-gray-800">{{ $med['drug'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $med['dose'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $med['route'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 font-mono text-gray-600">{{ $med['time'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — DAILY BLOODS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($dailyBloods))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Daily Bloods</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Test</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Result</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dailyBloods as $blood)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 font-semibold text-gray-800">{{ $blood['test'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $blood['result'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 font-mono text-gray-600">{{ $blood['time'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — PARENT/KANGAROO BADGES
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex flex-wrap gap-2">
    @if (!empty($payload['parent_visit']))
        <span class="inline-block rounded-full bg-pink-100 text-pink-800 px-3 py-1 text-xs font-bold">&#10084; Parent Visit Today</span>
    @endif
    @if (!empty($payload['kangaroo_care']))
        <span class="inline-block rounded-full bg-pink-200 text-pink-900 px-3 py-1 text-xs font-bold">Kangaroo Care</span>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 12 — NURSING NOTES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['nursing_notes']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Nursing Notes</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['nursing_notes'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 13 — SIGNATURES
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t border-gray-200 pt-4">
    <div class="grid grid-cols-3 gap-6 text-xs text-gray-700">
        <div>
            <div class="font-semibold text-gray-800">Neonatologist</div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1">{{ $payload['neonatologist'] ?? $issuer_name ?? '—' }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-800">NICU Nurse</div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1">{{ $payload['nicu_nurse'] ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Document No.</div>
            <div class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</div>
            <div class="mt-1 text-xs text-gray-400">{{ $issued_at ?? '—' }}</div>
        </div>
    </div>
</div>
@endsection
