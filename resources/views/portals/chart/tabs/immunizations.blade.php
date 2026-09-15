<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="syringe"></i> {{ __('chart.tabs.immunizations') }}</h2>
        {{-- The web immunisation desk (routes/immunisation_desk.php): same
             capability and the same relationship gate as this chart. --}}
        <a href="{{ route('portals.immunisations.index', $patient->id) }}" class="btn btn-primary">
            <i data-lucide="plus"></i> {{ __('immunisation_desk.open_desk') }}
        </a>
    </div>
    <div class="panel-body">
        @if($immunizations->isEmpty())
            <p class="td-muted">{{ __('chart.immunizations_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_administered') }}</th>
                        <th>{{ __('chart.col_vaccine') }}</th>
                        <th>{{ __('chart.col_dose_number') }}</th>
                        <th>{{ __('chart.col_facility') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                        <th>{{ __('chart.col_verification') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($immunizations as $i)
                        <tr>
                            <td class="td-muted">{{ $i->administered_at?->format('d M Y') ?? '—' }}</td>
                            <td class="td-strong">
                                {{ $i->vaccine_name ?: '—' }}
                                @if($i->vaccine_code)<br><small class="td-muted">{{ $i->vaccine_code }}</small>@endif
                            </td>
                            <td>{{ $i->dose_number ?? '—' }}</td>
                            <td>{{ $i->facility?->name ?: '—' }}</td>
                            <td>@if($i->status)@enum($i->status)@else —@endif</td>
                            <td>
                                @if($i->verification_status)<span class="badge {{ $i->verification_status === 'verified' ? 'badge-success' : 'badge-neutral' }}">@enum($i->verification_status)</span>@endif
                                @if($i->is_historical)<br><small class="td-muted">{{ __('chart.historical') }}</small>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
