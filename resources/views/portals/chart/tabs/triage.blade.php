@php
    $badge = fn (?string $level) => 'badge badge-' . match (\App\Support\TriageAcuity::severity($level)) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'info'     => 'info',
        default    => 'neutral',
    };
@endphp
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="siren"></i> {{ __('chart.tabs.triage') }}</h2>
    </div>
    <div class="panel-body">
        @if($triage->isEmpty())
            <p class="td-muted">{{ __('chart.triage_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_assessed') }}</th>
                        <th>{{ __('chart.col_complaint') }}</th>
                        <th>{{ __('chart.col_priority') }}</th>
                        <th>{{ __('chart.col_nurse') }}</th>
                        <th>{{ __('chart.col_facility') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($triage as $t)
                        @php $key = \App\Support\TriageAcuity::labelKey($t['priority']); @endphp
                        <tr>
                            <td class="td-muted">{{ $t['assessed_at']?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                            <td>{{ $t['presenting_complaint'] ?: '—' }}</td>
                            <td>
                                @if($key)
                                    <span class="{{ $badge($t['priority']) }}">{{ __($key) }}</span>
                                @elseif($t['priority'])
                                    <span class="badge badge-neutral">{{ $t['priority'] }}</span>
                                @else
                                    <span class="td-muted">{{ __('chart.priority_none') }}</span>
                                @endif
                            </td>
                            <td>{{ $t['nurse_name'] ?: '—' }}</td>
                            <td>{{ $t['facility_name'] ?: '—' }}</td>
                            <td>
                                {{-- The triage desk is facility-scoped: a record at another
                                     facility 404s there, so no link is offered for it. --}}
                                @if($t['at_active_facility'])
                                    @capability('triage')
                                    <a href="{{ route('portals.triage.show', $t['id']) }}" class="btn btn-secondary btn-sm">
                                        <i data-lucide="external-link"></i> {{ __('chart.open_triage') }}
                                    </a>
                                    @endcapability
                                @else
                                    <span class="td-muted">{{ __('chart.other_facility') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
