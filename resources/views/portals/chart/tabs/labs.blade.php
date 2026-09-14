{{-- The flag is ResultFlagResolver's — the platform's one laboratory-flag
     authority — and every flag names its source. `unflagged` means nobody
     compared the value; it is rendered as "Not recorded", never as normal. --}}
@php
    $flagBadge = fn (string $severity) => 'badge badge-' . match ($severity) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'success'  => 'success',
        default    => 'neutral',
    };
    $labBadge = \App\Enums\VitalProvenance::LaboratoryResult;
@endphp
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('chart.tabs.labs') }}</h2>
        <span class="badge badge-success">{{ __($labBadge->labelKey()) }}</span>
    </div>
    <div class="panel-body">
        <p class="form-hint mb-4">{{ __('chart.labs_note') }}</p>

        @if($labs->isEmpty())
            <p class="td-muted">{{ __('chart.labs_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-muted">{{ $r['resulted_at']?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="td-strong">{{ $r['parameter'] ?: '—' }}</td>
                            <td class="td-muted">
                                {{ $r['loinc_code'] ?: '—' }}
                                @if($r['loinc_display'])<br><small>{{ $r['loinc_display'] }}</small>@endif
                            </td>
                            <td>{{ $r['value'] ?? '—' }} <span class="td-muted">{{ $r['unit'] }}</span></td>
                            <td class="td-muted">{{ $r['reference_range'] ?: '—' }}</td>
                            <td>
                                <span class="{{ $flagBadge($r['severity']) }}">{{ __('labs.flag.' . $r['flag']) }}</span>
                                @if($r['flag_source'])
                                    <br><small class="td-muted">{{ __('chart.flag_source.' . $r['flag_source']) }}</small>
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
