<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.tabs.immunizations') }}</h2>
        {{-- The web immunisation desk (routes/immunisation_desk.php): same
             capability and the same relationship gate as this chart. --}}
        <a href="{{ route('portals.immunisations.index', $patient->id) }}" class="od-btn od-btn--primary">
            <i data-lucide="plus"></i> {{ __('immunisation_desk.open_desk') }}
        </a>
    </div>
    <div class="od-card__body">
        @if($immunizations->isEmpty())
            <p class="od-muted">{{ __('chart.immunizations_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
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
                            <td class="od-muted">{{ $i->administered_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="odc-strong">
                                {{ $i->vaccine_name ?: '—' }}
                                @if($i->vaccine_code)<br><small class="od-muted">{{ $i->vaccine_code }}</small>@endif
                            </td>
                            <td>{{ $i->dose_number ?? '—' }}</td>
                            <td>{{ $i->facility?->name ?: '—' }}</td>
                            <td>@if($i->status)@enum($i->status)@else —@endif</td>
                            <td>
                                @if($i->verification_status)<span class="od-pill {{ $i->verification_status === 'verified' ? 'od-pill--ready' : 'od-pill--neutral' }}">@enum($i->verification_status)</span>@endif
                                @if($i->is_historical)<br><small class="od-muted">{{ __('chart.historical') }}</small>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
