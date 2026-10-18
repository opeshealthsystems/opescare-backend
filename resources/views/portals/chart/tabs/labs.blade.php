{{-- The flag is ResultFlagResolver's — the platform's one laboratory-flag
     authority — and every flag names its source. `unflagged` means nobody
     compared the value; it is rendered as "Not recorded", never as normal. --}}
@php
    $flagBadge = fn (string $severity) => 'od-pill od-pill--' . match ($severity) {
        'critical' => 'urgent',
        'warning'  => 'pending',
        'success'  => 'ready',
        default    => 'neutral',
    };
    $labBadge = \App\Enums\VitalProvenance::LaboratoryResult;
@endphp
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.tabs.labs') }}</h2>
        <span class="od-pill od-pill--ready">{{ __($labBadge->labelKey()) }}</span>
    </div>
    <div class="od-card__body">
        <p class="odc-hint">{{ __('chart.labs_note') }}</p>

        @if($labs->isEmpty())
            <p class="od-muted">{{ __('chart.labs_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_resulted') }}</th>
                        <th>{{ __('chart.col_test') }}</th>
                        <th>{{ __('chart.col_loinc') }}</th>
                        <th>{{ __('chart.col_value') }}</th>
                        <th>{{ __('chart.col_range') }}</th>
                        <th>{{ __('chart.col_flag') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($labs as $r)
                        <tr>
                            <td class="od-muted">{{ $r['resulted_at']?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                            <td class="odc-strong">{{ $r['parameter'] ?: '—' }}</td>
                            <td class="od-muted">
                                {{ $r['loinc_code'] ?: '—' }}
                                @if($r['loinc_display'])<br><small>{{ $r['loinc_display'] }}</small>@endif
                            </td>
                            <td>{{ $r['value'] ?? '—' }} <span class="od-muted">{{ $r['unit'] }}</span></td>
                            <td class="od-muted">{{ $r['reference_range'] ?: '—' }}</td>
                            <td>
                                <span class="{{ $flagBadge($r['severity']) }}">{{ __('labs.flag.' . $r['flag']) }}</span>
                                @if($r['flag_source'])
                                    <br><small class="od-muted">{{ __('chart.flag_source.' . $r['flag_source']) }}</small>
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
