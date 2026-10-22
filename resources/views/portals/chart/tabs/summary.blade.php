{{-- Summary tab. Every section is built by App\Services\Chart\ChartSummaryBuilder
     only when the access verdict covers its data category, and `$open[...]`
     says which were built. A section that is not open renders nothing at all
     (the preview note above the tabs explains why), so a closed section can
     never be mistaken for an empty one.

     Allergies FIRST and loud. An allergy list that fails inside an aggregate
     renders as "no known allergies", the most dangerous thing a chart can say,
     so its empty state says "none recorded", never "none". --}}
@php
    $open = $open ?? [];
    $fmtDate = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : '—';
    $fmtTime = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M H:i') : '—';
    $flagPill = fn (?string $flag) => match ($flag) {
        'high', 'low', 'abnormal', 'critical' => 'od-pill--urgent',
        'normal' => 'od-pill--ready',
        default => 'od-pill--neutral',
    };
@endphp

{{-- ── KPI tiles: the newest RECORDED value of each measure ─────────────── --}}
@if(!empty($open['vitals']))
@php $k = $kpis; @endphp
<div class="cs-kpis" role="group" aria-label="{{ __('chart_summary.kpis_label') }}">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="heart-pulse"></i></span><span class="od-kpi__body">
        <span class="od-kpi__label">{{ __('chart_summary.kpi_bp') }}</span>
        @if($k['bp'])
            <span class="od-kpi__value">{{ $k['bp']['value'] }}/{{ $k['bp']['secondary'] ?? '—' }}</span>
            <span class="od-kpi__sub">mmHg · {{ $fmtTime($k['bp']['at']) }}</span>
        @else
            <span class="od-kpi__value od-kpi__value--empty">{{ __('chart_summary.kpi_none') }}</span>
        @endif
    </span></div>
    <div class="od-kpi od-tint--teal"><span class="od-kpi__icon"><i data-lucide="activity"></i></span><span class="od-kpi__body">
        <span class="od-kpi__label">{{ __('chart_summary.kpi_pulse') }}</span>
        @if($k['pulse'])
            <span class="od-kpi__value">{{ $k['pulse']['value'] }}</span>
            <span class="od-kpi__sub">{{ __('chart_summary.unit_bpm') }} · {{ $fmtTime($k['pulse']['at']) }}</span>
        @else
            <span class="od-kpi__value od-kpi__value--empty">{{ __('chart_summary.kpi_none') }}</span>
        @endif
    </span></div>
    <div class="od-kpi od-tint--sky"><span class="od-kpi__icon"><i data-lucide="wind"></i></span><span class="od-kpi__body">
        <span class="od-kpi__label">{{ __('chart_summary.kpi_spo2') }}</span>
        @if($k['spo2'])
            <span class="od-kpi__value">{{ $k['spo2']['value'] }}%</span>
            <span class="od-kpi__sub">{{ $fmtTime($k['spo2']['at']) }}</span>
        @else
            <span class="od-kpi__value od-kpi__value--empty">{{ __('chart_summary.kpi_none') }}</span>
        @endif
    </span></div>
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="thermometer"></i></span><span class="od-kpi__body">
        <span class="od-kpi__label">{{ __('chart_summary.kpi_temp') }}</span>
        @if($k['temperature'])
            <span class="od-kpi__value">{{ $k['temperature']['value'] }} °C</span>
        @else
            <span class="od-kpi__value od-kpi__value--empty">{{ __('chart_summary.kpi_none') }}</span>
        @endif
        <span class="od-kpi__sub">
            {{ $k['weight'] ? $k['weight']['value'] . ' kg' : __('chart_summary.weight_none') }}
            · {{ $k['height'] ? $k['height']['value'] . ' cm' : __('chart_summary.height_none') }}
        </span>
    </span></div>
</div>
@endif

{{-- ── Presenting complaint (body map) + trend + results ──────────────── --}}
@if(!empty($open['pain']) || !empty($open['vitals']) || !empty($open['labs']))
<div class="cs-row">
    @if(!empty($open['pain']))
    @php
        $heart = $figure->heart();
        $pinned = $painSites->filter(fn ($s) => $figure->point($s->region) !== null);
        $mapLabel = $painSites->isEmpty()
            ? __('chart_summary.map_label_none')
            : __('chart_summary.map_label', ['count' => $painSites->count(), 'sites' => $painSites->map(fn ($s) => $s->region->label() . ' ' . $s->severity . '/10')->implode(', ')]);
    @endphp
    <section class="od-card" aria-labelledby="cs-pc-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-pc-title">{{ __('chart_summary.presenting_title') }}</h2></header>
        <div class="od-card__body">
            <div class="cs-fig">
                <div class="cs-stage" role="img" aria-label="{{ $mapLabel }}" data-figure="{{ $figure->figure }}">
                    <div class="cs-stage__box">
                        <img class="cs-stage__base" src="{{ $figure->baseUrl() }}" alt="" width="305" height="720">
                        <span class="cs-current" aria-hidden="true" style="background-image:url('{{ $figure->currentUrl() }}')"></span>
                        <span class="cs-heart" aria-hidden="true" data-heart="{{ $figure->figure }}"
                              style="left:{{ round($heart['x'] * 100, 3) }}%;top:{{ round($heart['y'] * 100, 3) }}%;width:{{ round($heart['w'] * 100, 3) }}%;height:{{ round($heart['h'] * 100, 3) }}%;background-image:url('{{ $figure->heartUrl() }}')"></span>
                        @foreach($pinned as $i => $s)
                            @php [$px, $py] = $figure->point($s->region); @endphp
                            <span class="cs-pin" aria-hidden="true" data-region="{{ $s->region->value }}"
                                  style="left:{{ $px * 100 }}%;top:{{ $py * 100 }}%;animation-delay:{{ ($i % 3) * 0.5 }}s"></span>
                        @endforeach
                    </div>
                </div>
                <div class="cs-views" role="group" aria-label="{{ __('chart_summary.view_label') }}">
                    <button type="button" aria-pressed="true">{{ __('chart_summary.view_front') }}</button>
                    <button type="button" aria-pressed="false" disabled title="{{ __('chart_summary.view_back_soon') }}">{{ __('chart_summary.view_back') }}</button>
                </div>
                <p class="cs-fig__cap">
                    @if($figure->sexAssumed){{ __('pain.figure_sex_unrecorded') }}@elseif($figure->ageAssumed){{ __('pain.figure_age_unrecorded') }}@else{{ __('pain.figure_follows') }}@endif
                </p>
            </div>
            <div class="cs-key"><span><b style="background:var(--od-urgent)"></b>{{ __('chart_summary.pain_key') }}</span></div>
            @forelse($painSites as $s)
                <div class="cs-pain" data-pain-site="{{ $s->region->value }}">
                    <div>
                        <span class="cs-dot" aria-hidden="true"></span>{{ $s->region->label() }}@if($s->note) · {{ $s->note }}@endif
                        <small>
                            {{ $s->onset_at ? __('chart_summary.since', ['date' => $fmtDate($s->onset_at)]) : __('chart_summary.onset_unknown') }}
                            · {{ $s->source->label() }}
                            @if($s->trend) · {{ $s->trend->label() }}@endif
                            @if($s->region->isBack()) · {{ __('pain.back_not_drawn') }}@endif
                        </small>
                    </div>
                    <span class="cs-sev">{{ $s->severity }}/10</span>
                </div>
            @empty
                <p class="od-muted">{{ __('chart_summary.pain_none') }}</p>
            @endforelse
        </div>
    </section>
    @endif

    <div class="cs-stack">
        @if(!empty($open['vitals']))
        @php
            $series = [
                'systolic_bp'       => ['colour' => 'var(--od-primary)', 'dash' => '',    'label' => __('chart_summary.series_systolic')],
                'diastolic_bp'      => ['colour' => 'var(--od-sky)',     'dash' => '4 3', 'label' => __('chart_summary.series_diastolic')],
                'pulse_rate'        => ['colour' => 'var(--od-teal)',    'dash' => '',    'label' => __('chart_summary.series_pulse')],
                'oxygen_saturation' => ['colour' => 'var(--od-pending)', 'dash' => '6 4', 'label' => __('chart_summary.series_spo2')],
            ];
            $alt = collect($trend['series'])->map(fn ($pts, $f) => $series[$f]['label'] . ': ' . collect($pts)->map(fn ($p) => $p['v'])->implode(', '))->implode('; ');
        @endphp
        <section class="od-card" aria-labelledby="cs-trend-title">
            <header class="od-card__head"><h2 class="od-card__title" id="cs-trend-title">{{ __('chart_summary.trend_title', ['days' => \App\Services\Chart\ChartSummaryBuilder::TREND_DAYS]) }}</h2>
                <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'vitals']) }}">{{ __('chart_summary.all_vitals') }}</a></header>
            <div class="od-card__body">
                @if(empty($trend['series']))
                    <p class="od-muted">{{ __('chart_summary.trend_none') }}</p>
                @else
                <svg viewBox="0 0 560 190" width="100%" role="img" aria-labelledby="cs-trend-desc" data-trend-count="{{ $trend['count'] }}">
                    <desc id="cs-trend-desc">{{ __('chart_summary.trend_alt', ['days' => \App\Services\Chart\ChartSummaryBuilder::TREND_DAYS]) }} {{ $alt }}</desc>
                    <g stroke="var(--od-rule)">
                        @foreach($trend['ticks'] as $t)<line x1="{{ $trend['box']['x0'] }}" y1="{{ $t['y'] }}" x2="{{ $trend['box']['x1'] }}" y2="{{ $t['y'] }}"/>@endforeach
                    </g>
                    <g fill="var(--od-muted)" font-size="11">
                        @foreach($trend['ticks'] as $t)<text x="4" y="{{ $t['y'] + 4 }}">{{ $t['v'] }}</text>@endforeach
                        <text x="{{ $trend['box']['x0'] }}" y="182">{{ $trend['start']->translatedFormat('d M') }}</text>
                        <text x="280" y="182" text-anchor="middle">{{ $trend['mid']->translatedFormat('d M') }}</text>
                        <text x="{{ $trend['box']['x1'] }}" y="182" text-anchor="end">{{ $trend['end']->translatedFormat('d M') }}</text>
                    </g>
                    @foreach($trend['series'] as $field => $pts)
                        <polyline fill="none" stroke="{{ $series[$field]['colour'] }}" stroke-width="2.5" @if($series[$field]['dash']) stroke-dasharray="{{ $series[$field]['dash'] }}" @endif
                                  points="{{ collect($pts)->map(fn ($p) => $p['x'] . ',' . $p['y'])->implode(' ') }}"/>
                        @foreach($pts as $p)<circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3" fill="#fff" stroke="{{ $series[$field]['colour'] }}" stroke-width="2"/>@endforeach
                    @endforeach
                </svg>
                <div class="cs-key">
                    @foreach($trend['series'] as $field => $pts)
                        <span><i style="background:{{ $series[$field]['colour'] }}"></i>{{ $series[$field]['label'] }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @endif

        @if(!empty($open['labs']))
        <section class="od-card" aria-labelledby="cs-labs-title">
            <header class="od-card__head"><h2 class="od-card__title" id="cs-labs-title">{{ __('chart_summary.results_title') }}</h2>
                <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'labs']) }}">{{ __('chart_summary.all_results') }}</a></header>
            <div class="od-card__body">
                @if($labs->isEmpty())
                    <p class="od-muted">{{ __('chart.labs_none') }}</p>
                @else
                <div class="od-table-wrap"><table class="od-table cs-table">
                    <thead><tr><th>{{ __('chart_summary.col_test') }}</th><th>{{ __('chart_summary.col_result') }}</th><th>{{ __('chart_summary.col_range') }}</th><th>{{ __('chart_summary.col_status') }}</th><th>{{ __('chart_summary.col_date') }}</th></tr></thead>
                    <tbody>
                    @foreach($labs as $r)
                        <tr>
                            <td class="odc-strong">{{ $r['parameter'] ?: ($r['loinc_display'] ?: '—') }}</td>
                            <td class="cs-num">{{ $r['value'] ?? '—' }} {{ $r['unit'] }}</td>
                            <td class="cs-num od-muted">{{ $r['reference_range'] ?: '—' }}</td>
                            <td><span class="od-pill {{ $flagPill($r['flag']) }}">{{ __('labs.flag.' . ($r['flag'] ?: 'unflagged')) }}</span></td>
                            <td class="cs-num od-muted">{{ $fmtDate($r['resulted_at']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                @endif
            </div>
        </section>
        @endif
    </div>
</div>
@endif

{{-- ── Encounters + care across facilities ─────────────────────────────── --}}
@if(!empty($open['encounters']))
<div class="cs-row2">
    <section class="od-card" aria-labelledby="cs-enc-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-enc-title">{{ __('chart_summary.encounters_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'encounters']) }}">{{ __('chart_summary.all_encounters') }}</a></header>
        <div class="od-card__body">
            @if($encounters->isEmpty())
                <p class="od-muted">{{ __('chart.encounters_none') }}</p>
            @else
            <ol class="cs-tl">
                @foreach($encounters as $e)
                    <li><b>@if($e['type'])@enum($e['type'])@else{{ __('chart_summary.encounter') }}@endif @if($e['reason']) · {{ $e['reason'] }}@endif</b>
                        <small>{{ $fmtDate($e['started_at']) }}@if($e['doctor_name']) · {{ $e['doctor_name'] }}@endif @if($e['facility_name']) · {{ $e['facility_name'] }}@endif @if($e['status']) · @enum($e['status'])@endif</small></li>
                @endforeach
            </ol>
            @endif
        </div>
    </section>
    <section class="od-card" aria-labelledby="cs-mix-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-mix-title">{{ __('chart_summary.care_mix_title') }}</h2></header>
        <div class="od-card__body">
            @if($careMix['total'] === 0)
                <p class="od-muted">{{ __('chart_summary.care_mix_none') }}</p>
            @else
            <div class="cs-donut">
                <svg viewBox="0 0 120 120" width="116" height="116" role="img" aria-labelledby="cs-mix-desc">
                    <desc id="cs-mix-desc">{{ __('chart_summary.care_mix_alt', ['total' => $careMix['total'], 'facilities' => $careMix['facilities']]) }}
                        @foreach($careMix['slices'] as $sl){{ \App\Support\Enums::label($sl['type']) }}: {{ $sl['count'] }}. @endforeach</desc>
                    <g transform="rotate(-90 60 60)" fill="none" stroke-width="16">
                        @foreach($careMix['slices'] as $sl)
                            <circle cx="60" cy="60" r="44" stroke="{{ $sl['colour'] }}" stroke-dasharray="{{ $sl['dash'] }}" stroke-dashoffset="{{ $sl['offset'] }}"/>
                        @endforeach
                    </g>
                    <text x="60" y="58" text-anchor="middle" font-size="20" font-weight="700" fill="var(--od-ink-h)">{{ $careMix['total'] }}</text>
                    <text x="60" y="74" text-anchor="middle" font-size="10" fill="var(--od-muted)">{{ __('chart_summary.encounters_word') }}</text>
                </svg>
                <div class="cs-dl">
                    @foreach($careMix['slices'] as $sl)
                        <div><b class="cs-key-swatch" style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $sl['colour'] }};margin-right:8px"></b>{{ $sl['type'] === 'other' ? __('chart_summary.other') : \App\Support\Enums::label($sl['type']) }} <span>{{ $sl['count'] }}</span></div>
                    @endforeach
                    <div><span>{{ trans_choice('chart_summary.facilities_count', $careMix['facilities'], ['count' => $careMix['facilities']]) }}</span></div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>
@endif

{{-- ── Medicines (always open) + problems and allergies ─────────────────── --}}
<div class="cs-row2">
    <section class="od-card cs-wide" aria-labelledby="cs-meds-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-meds-title">{{ __('chart.medicines_title') }}</h2></header>
        <div class="od-card__body">
            @if($medicines->isEmpty())
                <p class="od-muted">{{ __('chart.medicines_none') }}</p>
            @else
            <div class="od-table-wrap"><table class="od-table cs-table">
                <thead><tr><th>{{ __('chart.col_drug') }}</th><th>{{ __('chart.col_dose') }}</th><th>{{ __('chart.col_frequency') }}</th><th>{{ __('chart.col_duration') }}</th><th>{{ __('chart.col_prescriber') }}</th><th>{{ __('chart.col_status') }}</th></tr></thead>
                <tbody>
                @foreach($medicines as $m)
                    <tr>
                        <td class="odc-strong">{{ $m['drug_name'] ?: '—' }}</td>
                        <td>{{ $m['dose'] ?: '—' }}@if($m['route']) · {{ $m['route'] }}@endif</td>
                        <td>{{ $m['frequency'] ?: '—' }}</td>
                        <td>{{ $m['duration_days'] !== null ? __('chart.duration_days', ['days' => $m['duration_days']]) : '—' }}</td>
                        <td>{{ $m['prescribed_by'] ?: '—' }}</td>
                        <td><span class="od-pill {{ $m['status'] === 'partially_dispensed' ? 'od-pill--pending' : 'od-pill--normal' }}">@enum($m['status'])</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @endif
        </div>
    </section>

    <section class="od-card" aria-labelledby="cs-allergy-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-allergy-title">{{ !empty($open['problems']) ? __('chart_summary.problems_allergies_title') : __('chart.allergies_title') }}</h2>
            @if($allergies->isNotEmpty())<span class="od-pill od-pill--pending">{{ $allergies->count() }}</span>@endif</header>
        <div class="od-card__body">
            <ul class="od-list cs-list-wrap">
                @forelse($allergies as $a)
                    <li class="od-list__row"><span class="od-list__icon od-tint--amber"><i data-lucide="triangle-alert"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $a->substance ?: '—' }}</span>
                        <span class="od-list__sub">@if($a->severity)@enum($a->severity, 'severity')@else{{ __('chart.not_recorded') }}@endif @if($a->status) · @enum($a->status)@endif · {{ $fmtDate($a->created_at) }}</span>
                    </span></li>
                @empty
                    <li class="od-list__row"><span class="od-list__text"><span class="od-list__title od-muted">{{ __('chart.allergies_none') }}</span></span></li>
                @endforelse
                @if(!empty($open['problems']))
                    @forelse($problems as $p)
                        <li class="od-list__row"><span class="od-list__icon od-tint--blue"><i data-lucide="clipboard-list"></i></span><span class="od-list__text">
                            <span class="od-list__title">{{ $p->description ?: '—' }}</span>
                            <span class="od-list__sub">{{ $p->icd_code ?: '—' }} · {{ $fmtDate($p->onset_date) }} @if($p->status) · @enum($p->status)@endif</span>
                        </span></li>
                    @empty
                        <li class="od-list__row"><span class="od-list__text"><span class="od-list__title od-muted">{{ __('chart.problems_none') }}</span></span></li>
                    @endforelse
                @endif
            </ul>
        </div>
    </section>

    @if(!empty($open['diagnoses']))
    <section class="od-card" aria-labelledby="cs-history-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-history-title">{{ __('chart_summary.history_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'diagnoses']) }}">{{ __('chart_summary.all_diagnoses') }}</a></header>
        <div class="od-card__body">
            @if($diagnoses->isEmpty())
                <p class="od-muted">{{ __('chart.diagnoses_none') }}</p>
            @else
            <ul class="od-list cs-list-wrap">
                @foreach($diagnoses as $d)
                    <li class="od-list__row"><span class="od-list__icon od-tint--sky"><i data-lucide="stethoscope"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $d['title'] ?: '—' }}</span>
                        <span class="od-list__sub">{{ $d['code'] ?: '—' }} · {{ $fmtDate($d['visit_at'] ?? $d['recorded_at']) }}@if($d['provider_name']) · {{ $d['provider_name'] }}@endif</span>
                    </span></li>
                @endforeach
            </ul>
            @endif
        </div>
    </section>
    @endif
</div>

{{-- ── Referrals, documents, immunisations, pre-consult ──────────────────── --}}
@if(!empty($open['referrals']) || !empty($open['documents']))
<div class="cs-row2">
    @if(!empty($open['referrals']))
    <section class="od-card" aria-labelledby="cs-ref-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-ref-title">{{ __('chart_summary.referrals_title') }}</h2></header>
        <div class="od-card__body">
            @if($referrals->isEmpty())
                <p class="od-muted">{{ __('chart_summary.referrals_none') }}</p>
            @else
            <ul class="od-list cs-list-wrap">
                @foreach($referrals as $r)
                    <li class="od-list__row"><span class="od-list__icon od-tint--blue"><i data-lucide="send"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $r['specialty'] ?: __('chart_summary.referral') }}@if($r['to']) · {{ $r['to'] }}@endif</span>
                        <span class="od-list__sub">{{ $r['outgoing'] ? __('chart_summary.referral_sent') : __('chart_summary.referral_received') }} {{ $fmtDate($r['at']) }} · @enum($r['status'])</span>
                    </span></li>
                @endforeach
            </ul>
            @endif
        </div>
    </section>
    @endif

    @if(!empty($open['documents']))
    <section class="od-card" aria-labelledby="cs-doc-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-doc-title">{{ __('chart_summary.documents_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'documents']) }}">{{ __('chart_summary.all_documents') }}</a></header>
        <div class="od-card__body">
            @if($documents->isEmpty())
                <p class="od-muted">{{ __('chart.documents_none') }}</p>
            @else
            <ul class="od-list cs-list-wrap">
                @foreach($documents as $d)
                    <li class="od-list__row"><span class="od-list__icon od-tint--teal"><i data-lucide="file-check"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $d->title ?: '—' }}</span>
                        <span class="od-list__sub">{{ $fmtDate($d->issued_at) }}@if($d->facility) · {{ $d->facility->name }}@endif</span>
                    </span></li>
                @endforeach
            </ul>
            @endif
            <p class="cs-cap"><i data-lucide="qr-code"></i>
                {{ __('chart_summary.verifications', ['valid' => $verifications['valid'], 'revoked' => $verifications['revoked']]) }}</p>
        </div>
    </section>
    @endif
</div>
@endif

@if(!empty($open['immunizations']) || !empty($open['preconsult']))
<div class="cs-row2">
    @if(!empty($open['immunizations']))
    <section class="od-card" aria-labelledby="cs-imm-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-imm-title">{{ __('chart_summary.immunizations_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'immunizations']) }}">{{ __('chart_summary.all_immunizations') }}</a></header>
        <div class="od-card__body">
            @if($immunizationCount === 0)
                <p class="od-muted">{{ __('chart.immunizations_none') }}</p>
            @else
            <p class="od-muted">{{ trans_choice('chart_summary.doses_count', $immunizationCount, ['count' => $immunizationCount]) }}</p>
            <ul class="od-list cs-list-wrap">
                @foreach($immunizations as $i)
                    <li class="od-list__row"><span class="od-list__icon od-tint--green"><i data-lucide="syringe"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $i->vaccine_name ?: ($i->vaccine_code ?: '—') }}@if($i->dose_number) · {{ __('chart_summary.dose', ['n' => $i->dose_number]) }}@endif</span>
                        <span class="od-list__sub">{{ $fmtDate($i->administered_at) }}@if($i->facility) · {{ $i->facility->name }}@endif</span>
                    </span></li>
                @endforeach
            </ul>
            @endif
        </div>
    </section>
    @endif

    @if(!empty($open['preconsult']))
    <section class="od-card" aria-labelledby="cs-pre-title">
        <header class="od-card__head"><h2 class="od-card__title" id="cs-pre-title">{{ __('chart_summary.preconsult_title') }}</h2>
            <span class="od-pill od-pill--pending">{{ __('chart.preconsult_badge') }}</span></header>
        <div class="od-card__body">
            @if(!$preconsult)
                <p class="od-muted">{{ __('chart.preconsult_none') }}</p>
            @else
                <p class="od-muted">{{ __('chart.answers_count', ['count' => $preconsult['answered_count']]) }} · {{ $fmtTime($preconsult['submitted_at']) }}</p>
                <ul class="od-list cs-list-wrap">
                    @foreach(array_slice($preconsult['answers'] ?? [], 0, 4) as $answer)
                        <li class="od-list__row"><span class="od-list__text">
                            <span class="od-list__sub">{{ $answer['question_label'] }}</span>
                            <span class="od-list__title">{{ $answer['value_label'] ?? $answer['value'] }}</span>
                        </span></li>
                    @endforeach
                </ul>
                <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'preconsult']) }}">{{ __('chart_summary.all_answers') }}</a>
            @endif
        </div>
    </section>
    @endif
</div>
@endif

{{-- Partner-reported readings: their own card, never part of the KPIs or
     trend above (those are facility readings only). --}}
@if(!empty($open['partner']))
<section class="od-card" aria-labelledby="cs-partner-title">
    <header class="od-card__head"><h2 class="od-card__title" id="cs-partner-title">{{ __('partner_readings.title') }}</h2>
        <span class="od-pill od-pill--pending">{{ __('partner_readings.provenance_label') }}</span>
        <a class="od-card__link" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'partner_reported']) }}">{{ __('partner_readings.all') }}</a></header>
    <div class="od-card__body">
        @if($partnerCount === 0)
            <p class="od-muted">{{ __('partner_readings.none') }}</p>
        @else
            <p class="od-muted">{{ trans_choice('partner_readings.summary_count', $partnerCount, ['count' => $partnerCount]) }}</p>
            <ul class="od-list cs-list-wrap">
                @foreach($partnerReadings as $r)
                    <li class="od-list__row"><span class="od-list__icon od-tint--amber"><i data-lucide="share-2"></i></span><span class="od-list__text">
                        <span class="od-list__title">{{ $r['display'] ?: '—' }}@if($r['value'] !== null) · {{ rtrim(rtrim(number_format((float) $r['value'], 4, '.', ''), '0'), '.') }} {{ $r['unit'] }}@endif</span>
                        <span class="od-list__sub">{{ $fmtDate($r['effective_at']) }} · {{ __('partner_readings.badge_system', ['name' => $r['sending_system']['name']]) }} · {{ __('partner_readings.badge_facility', ['name' => $r['sending_facility']['name'] ?? __('partner_readings.unknown_facility')]) }}</span>
                    </span></li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
