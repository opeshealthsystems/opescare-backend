{{-- Titles come from the code dictionary in the reader's language (the ICD-10
     French titles are published, not translated here). A code the dictionary
     does not hold shows the title as it was recorded, and says so. --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.tabs.diagnoses') }}</h2>
    </div>
    <div class="od-card__body">
        @if($diagnoses->isEmpty())
            <p class="od-muted">{{ __('chart.diagnoses_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_recorded') }}</th>
                        <th>{{ __('chart.col_code') }}</th>
                        <th>{{ __('chart.col_title') }}</th>
                        <th>{{ __('chart.col_rank') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                        <th>{{ __('chart.col_provider') }}</th>
                        <th>{{ __('chart.col_visit') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($diagnoses as $d)
                        <tr>
                            <td class="od-muted">{{ $d['recorded_at']?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="odc-strong">{{ $d['code'] ?: '—' }} @if($d['code_system'])<small class="od-muted">{{ $d['code_system'] }}</small>@endif</td>
                            <td>
                                {{ $d['title'] ?: '—' }}
                                @if($d['title'] && $d['title_source'] === 'record')
                                    <br><small class="od-muted">{{ __('chart.title_from_record') }}</small>
                                @endif
                            </td>
                            <td><span class="od-pill {{ $d['is_primary'] ? 'od-pill--normal' : 'od-pill--neutral' }}">{{ $d['is_primary'] ? __('chart.primary') : __('chart.secondary') }}</span></td>
                            <td>@if($d['status'])@enum($d['status'])@else —@endif</td>
                            <td>{{ $d['provider_name'] ?: '—' }}</td>
                            <td class="od-muted">{{ $d['visit_at']?->translatedFormat('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
