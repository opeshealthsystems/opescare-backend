{{-- Allergies FIRST and loud. An allergy list that fails inside an aggregate
     renders as "no known allergies" — the most dangerous thing a chart can say —
     so it is read on its own and its empty state says "none recorded", never
     "none". --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.allergies_title') }}</h2>
        @if($allergies->isNotEmpty())
            <span class="od-pill od-pill--urgent">{{ $allergies->count() }}</span>
        @endif
    </div>
    <div class="od-card__body">
        @if($allergies->isEmpty())
            <p class="od-muted">{{ __('chart.allergies_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_substance') }}</th>
                        <th>{{ __('chart.col_severity') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                        <th>{{ __('chart.col_recorded') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($allergies as $a)
                        <tr>
                            <td class="odc-strong">{{ $a->substance ?: '—' }}</td>
                            <td>@if($a->severity)<span class="od-pill od-pill--urgent">@enum($a->severity, 'severity')</span>@else <span class="od-muted">{{ __('chart.not_recorded') }}</span>@endif</td>
                            <td>@if($a->status)@enum($a->status)@else —@endif</td>
                            <td class="od-muted">{{ $a->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.problems_title') }}</h2>
    </div>
    <div class="od-card__body">
        @if($problems->isEmpty())
            <p class="od-muted">{{ __('chart.problems_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_code') }}</th>
                        <th>{{ __('chart.col_description') }}</th>
                        <th>{{ __('chart.col_onset') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($problems as $p)
                        <tr>
                            <td class="odc-strong">{{ $p->icd_code ?: '—' }}</td>
                            <td>{{ $p->description ?: '—' }}</td>
                            <td class="od-muted">{{ $p->onset_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>@if($p->status)<span class="od-pill {{ $p->status === 'active' ? 'od-pill--pending' : 'od-pill--neutral' }}">@enum($p->status)</span>@else —@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.medicines_title') }}</h2>
    </div>
    <div class="od-card__body">
        @if($medicines->isEmpty())
            <p class="od-muted">{{ __('chart.medicines_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_drug') }}</th>
                        <th>{{ __('chart.col_dose') }}</th>
                        <th>{{ __('chart.col_route') }}</th>
                        <th>{{ __('chart.col_frequency') }}</th>
                        <th>{{ __('chart.col_duration') }}</th>
                        <th>{{ __('chart.col_prescribed') }}</th>
                        <th>{{ __('chart.col_prescriber') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($medicines as $m)
                        <tr>
                            <td class="odc-strong">{{ $m['drug_name'] ?: '—' }}</td>
                            <td>{{ $m['dose'] ?: '—' }}</td>
                            <td>{{ $m['route'] ?: '—' }}</td>
                            <td>{{ $m['frequency'] ?: '—' }}</td>
                            <td>{{ $m['duration_days'] !== null ? __('chart.duration_days', ['days' => $m['duration_days']]) : '—' }}</td>
                            <td class="od-muted">{{ $m['prescribed_at']?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>{{ $m['prescribed_by'] ?: '—' }}</td>
                            <td><span class="od-pill od-pill--normal">@enum($m['status'])</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
