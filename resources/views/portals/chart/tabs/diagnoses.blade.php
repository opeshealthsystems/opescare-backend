{{-- Titles come from the code dictionary in the reader's language (the ICD-10
     French titles are published, not translated here). A code the dictionary
     does not hold shows the title as it was recorded, and says so. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="file-badge"></i> {{ __('chart.tabs.diagnoses') }}</h2>
    </div>
    <div class="panel-body">
        @if($diagnoses->isEmpty())
            <p class="td-muted">{{ __('chart.diagnoses_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-muted">{{ $d['recorded_at']?->format('d M Y') ?? '—' }}</td>
                            <td class="td-strong">{{ $d['code'] ?: '—' }} @if($d['code_system'])<small class="td-muted">{{ $d['code_system'] }}</small>@endif</td>
                            <td>
                                {{ $d['title'] ?: '—' }}
                                @if($d['title'] && $d['title_source'] === 'record')
                                    <br><small class="td-muted">{{ __('chart.title_from_record') }}</small>
                                @endif
                            </td>
                            <td><span class="badge {{ $d['is_primary'] ? 'badge-info' : 'badge-neutral' }}">{{ $d['is_primary'] ? __('chart.primary') : __('chart.secondary') }}</span></td>
                            <td>@if($d['status'])@enum($d['status'])@else —@endif</td>
                            <td>{{ $d['provider_name'] ?: '—' }}</td>
                            <td class="td-muted">{{ $d['visit_at']?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
