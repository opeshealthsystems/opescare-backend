{{-- Summary side panels. Consent and access: whether this facility holds a
     grant (the gate's own question) and THIS facility's rows of the patient's
     access log, the same rows the patient reads in the app. Care team and
     family: only when those categories are open (full scope). --}}
<section class="od-card" aria-labelledby="cs-consent-title">
    <header class="od-card__head"><h2 class="od-card__title" id="cs-consent-title">{{ __('chart_summary.consent_panel_title') }}</h2></header>
    <div class="od-card__body">
        <ul class="od-list cs-list-wrap">
            <li class="od-list__row">
                @if(!empty($grant))
                    <span class="od-list__icon od-tint--green"><i data-lucide="shield-check"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ __('chart_summary.grant_active', ['until' => $grant['expires_at']?->translatedFormat('d M Y H:i') ?? '—']) }}</span>
                        <span class="od-list__sub">{{ __('chart_summary.grant_by', ['by' => __('chart_summary.granted_by.' . (in_array($grant['by'], ['patient', 'guardian'], true) ? $grant['by'] : 'other'))]) }}</span>
                    </span>
                @else
                    <span class="od-list__icon od-tint--blue"><i data-lucide="shield"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ __('chart_summary.grant_none') }}</span>
                        <span class="od-list__sub">{{ $basis ? __('chart.opened_on_basis') . ' ' . __('chart.basis.' . $basis) : '—' }}</span>
                    </span>
                @endif
            </li>
            @if(!empty($open['access']))
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="history"></i></span>
                    <span class="od-list__text">
                        <span class="od-list__title">{{ __('chart_summary.access_log_title') }}</span>
                        @forelse($accessLog as $row)
                            <span class="od-list__sub">
                                {{ $row['is_me'] ? __('chart_summary.you') : ($row['actor'] ?: __('chart_summary.someone')) }}
                                · {{ \Illuminate\Support\Facades\Lang::has('access_log.category.' . $row['category']) ? __('access_log.category.' . $row['category']) : $row['category'] }}
                                · {{ $row['type'] === 'denied' ? __('chart_summary.access_denied') : __('chart_summary.access_read') }}
                                · {{ $row['at']?->translatedFormat('d M H:i') }}
                            </span>
                        @empty
                            <span class="od-list__sub">{{ __('chart_summary.access_log_none') }}</span>
                        @endforelse
                        <span class="od-list__sub">{{ __('chart_summary.access_log_note') }}</span>
                    </span>
                </li>
            @endif
        </ul>

        @if(empty($grant))
            @capability('request_access')
            <form method="POST" action="{{ route('portals.chart.consent_request') }}" class="cs-form">
                @csrf
                <input type="hidden" name="health_id" value="{{ $identity['health_id'] }}">
                <label class="odc-label" for="cs-purpose">{{ __('chart_summary.request_purpose') }}</label>
                <select id="cs-purpose" name="purpose" class="od-select" required>
                    @foreach($purposes as $p)
                        <option value="{{ $p }}">{{ __('consent_requests.purpose.' . $p) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="key-round"></i> {{ __('chart_summary.request_wider') }}</button>
            </form>
            @endcapability
        @endif
    </div>
</section>

@if(!empty($open['care_team']))
<section class="od-card" aria-labelledby="cs-team-title">
    <header class="od-card__head"><h2 class="od-card__title" id="cs-team-title">{{ __('chart_summary.care_team_title') }}</h2></header>
    <div class="od-card__body"><ul class="od-list cs-list-wrap">
        @forelse($careTeam as $m)
            @php
                $parts = preg_split('/\s+/', trim($m['name']), -1, PREG_SPLIT_NO_EMPTY);
                $ini = $parts ? mb_strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) : '—';
            @endphp
            <li class="od-list__row"><span class="od-avatar od-tint--sky" aria-hidden="true">{{ $ini }}</span><span class="od-list__text">
                <span class="od-list__title">{{ $m['name'] }}@if($m['is_me']) ({{ __('chart_summary.you') }})@endif</span>
                <span class="od-list__sub">{{ $m['role'] ? \App\Support\Enums::label($m['role']) : '—' }}@if($m['facility']) · {{ $m['facility'] }}@endif @if($m['primary']) · {{ __('chart_summary.primary') }}@endif</span>
            </span></li>
        @empty
            <li class="od-list__row"><span class="od-list__text"><span class="od-list__title od-muted">{{ __('chart_summary.care_team_none') }}</span></span></li>
        @endforelse
    </ul></div>
</section>
@endif

@if(!empty($open['family']))
<section class="od-card" aria-labelledby="cs-family-title">
    <header class="od-card__head"><h2 class="od-card__title" id="cs-family-title">{{ __('chart_summary.family_title') }}</h2></header>
    <div class="od-card__body"><ul class="od-list cs-list-wrap">
        @foreach($guardians as $g)
            <li class="od-list__row"><span class="od-list__icon od-tint--blue"><i data-lucide="users"></i></span><span class="od-list__text">
                <span class="od-list__title">{{ $g['name'] }} · {{ \Illuminate\Support\Facades\Lang::has('family.relationship.' . $g['relationship']) ? __('family.relationship.' . $g['relationship']) : $g['relationship'] }}</span>
                <span class="od-list__sub">{{ __('chart_summary.guardian_acts') }}</span>
            </span></li>
        @endforeach
        @if($emergencyContact)
            <li class="od-list__row"><span class="od-list__icon od-tint--blue"><i data-lucide="phone"></i></span><span class="od-list__text">
                <span class="od-list__title">{{ __('chart_summary.emergency_contact') }}</span>
                <span class="od-list__sub">{{ collect([$emergencyContact['name'], $emergencyContact['relation'], $emergencyContact['phone']])->filter()->implode(' · ') }}</span>
            </span></li>
        @endif
        @if($guardians->isEmpty() && !$emergencyContact)
            <li class="od-list__row"><span class="od-list__text"><span class="od-list__title od-muted">{{ __('chart_summary.family_none') }}</span></span></li>
        @endif
    </ul></div>
</section>
@endif
