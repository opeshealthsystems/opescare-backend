{{-- Allergies FIRST and loud. An allergy list that fails inside an aggregate
     renders as "no known allergies" — the most dangerous thing a chart can say —
     so it is read on its own and its empty state says "none recorded", never
     "none". --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="alert-triangle"></i> {{ __('chart.allergies_title') }}</h2>
        @if($allergies->isNotEmpty())
            <span class="badge badge-danger">{{ $allergies->count() }}</span>
        @endif
    </div>
    <div class="panel-body">
        @if($allergies->isEmpty())
            <p class="td-muted">{{ __('chart.allergies_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-strong">{{ $a->substance ?: '—' }}</td>
                            <td>@if($a->severity)<span class="badge badge-danger">@enum($a->severity, 'severity')</span>@else <span class="td-muted">{{ __('chart.not_recorded') }}</span>@endif</td>
                            <td>@if($a->status)@enum($a->status)@else —@endif</td>
                            <td class="td-muted">{{ $a->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('chart.problems_title') }}</h2>
    </div>
    <div class="panel-body">
        @if($problems->isEmpty())
            <p class="td-muted">{{ __('chart.problems_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-strong">{{ $p->icd_code ?: '—' }}</td>
                            <td>{{ $p->description ?: '—' }}</td>
                            <td class="td-muted">{{ $p->onset_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>@if($p->status)<span class="badge {{ $p->status === 'active' ? 'badge-warning' : 'badge-neutral' }}">@enum($p->status)</span>@else —@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="pill"></i> {{ __('chart.medicines_title') }}</h2>
    </div>
    <div class="panel-body">
        @if($medicines->isEmpty())
            <p class="td-muted">{{ __('chart.medicines_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-strong">{{ $m['drug_name'] ?: '—' }}</td>
                            <td>{{ $m['dose'] ?: '—' }}</td>
                            <td>{{ $m['route'] ?: '—' }}</td>
                            <td>{{ $m['frequency'] ?: '—' }}</td>
                            <td>{{ $m['duration_days'] !== null ? __('chart.duration_days', ['days' => $m['duration_days']]) : '—' }}</td>
                            <td class="td-muted">{{ $m['prescribed_at']?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>{{ $m['prescribed_by'] ?: '—' }}</td>
                            <td><span class="badge badge-info">@enum($m['status'])</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
