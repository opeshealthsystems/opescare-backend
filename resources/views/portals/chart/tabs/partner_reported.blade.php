{{-- Partner-reported readings (fhir_partner_*). Their own tab, their own
     audit category, never merged with the facility's vitals/labs/trends.
     Every row carries the provenance badge plus the sending system and
     facility. Built by App\Services\Chart\PartnerReadingsService. --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('partner_readings.title') }}</h2>
        <span class="od-pill od-pill--pending"><i data-lucide="share-2"></i> {{ __('partner_readings.provenance_label') }}</span>
    </div>
    <div class="od-card__body">
        <div class="odc-note odc-note--pending" role="note">
            <i data-lucide="info"></i>
            <div><p>{{ __('partner_readings.separate_note') }}</p></div>
        </div>

        @if($partner['items']->isEmpty())
            <p class="od-muted">{{ __('partner_readings.none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('partner_readings.col_taken') }}</th>
                        <th>{{ __('partner_readings.col_reading') }}</th>
                        <th>{{ __('partner_readings.col_value') }}</th>
                        <th>{{ __('partner_readings.col_range') }}</th>
                        <th>{{ __('partner_readings.col_source') }}</th>
                        <th>{{ __('partner_readings.col_received') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($partner['items'] as $r)
                        <tr>
                            <td class="od-muted">{{ $r['effective_at'] ? \Illuminate\Support\Carbon::parse($r['effective_at'])->translatedFormat('d M Y H:i') : '—' }}</td>
                            <td class="odc-strong">
                                {{ $r['display'] ?: '—' }}
                                <br><small class="od-muted">{{ __('partner_readings.types.' . $r['type']) }} · {{ $r['code_system'] }} {{ $r['code'] }}</small>
                            </td>
                            <td>
                                @if($r['value'] !== null){{ rtrim(rtrim(number_format((float) $r['value'], 4, '.', ''), '0'), '.') }} {{ $r['unit'] }}@endif
                                @if($r['value_text'])<br>{{ $r['value_text'] }}@endif
                                @if(!empty($r['components']))
                                    @foreach($r['components'] as $c)
                                        <br><small>{{ $c['display'] ?? '' }}: {{ $c['value'] ?? $c['value_text'] }} {{ $c['unit'] }}</small>
                                    @endforeach
                                @endif
                                @if($r['conclusion'])<br><small>{{ $r['conclusion'] }}</small>@endif
                                @if($r['value'] === null && !$r['value_text'] && empty($r['components']) && !$r['conclusion'])—@endif
                            </td>
                            <td>{{ $r['reference_range'] ?: '—' }}@if($r['interpretation']) · {{ $r['interpretation'] }}@endif</td>
                            <td>
                                <span class="od-pill od-pill--pending">{{ $r['provenance_label'] }}</span><br>
                                <span class="od-pill od-pill--neutral">{{ __('partner_readings.badge_system', ['name' => $r['sending_system']['name']]) }}</span><br>
                                <span class="od-pill od-pill--neutral">{{ __('partner_readings.badge_facility', ['name' => $r['sending_facility']['name'] ?? __('partner_readings.unknown_facility')]) }}</span>
                            </td>
                            <td class="od-muted">{{ $r['received_at'] ? \Illuminate\Support\Carbon::parse($r['received_at'])->translatedFormat('d M Y H:i') : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($partner['page'] > 1 || $partner['has_more'])
                <nav class="od-tabs" aria-label="{{ __('partner_readings.title') }}">
                    @if($partner['page'] > 1)
                        <a class="od-tab" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'partner_reported', 'page' => $partner['page'] - 1]) }}"><i data-lucide="chevron-left"></i> {{ __('partner_readings.previous') }}</a>
                    @endif
                    @if($partner['has_more'])
                        <a class="od-tab" href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'partner_reported', 'page' => $partner['page'] + 1]) }}">{{ __('partner_readings.next') }} <i data-lucide="chevron-right"></i></a>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</div>
