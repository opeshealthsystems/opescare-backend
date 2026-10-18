{{-- Group D import: the Finder's automatic go-live checks (FacilityReadinessScoringService::evaluate()).
     Expects $automatic = ['checks' => [key => bool], 'score' => int, 'missing' => [...]]. --}}
<div class="od-card" data-readiness-score="{{ $automatic['score'] }}">
    <div class="od-card__head">
        <h3 class="od-card__title"><i data-lucide="gauge"></i> {{ __('readiness.panel_title') }}</h3>
        <span class="od-pill {{ $automatic['score'] >= 100 ? 'od-pill--ready' : ($automatic['score'] >= 50 ? 'od-pill--pending' : 'od-pill--urgent') }}">{{ __('readiness.score', ['score' => $automatic['score']]) }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-list">
            @foreach($automatic['checks'] as $key => $ok)
                <div class="od-list__row" data-check="{{ $key }}" data-ok="{{ $ok ? '1' : '0' }}">
                    <span class="od-list__icon {{ $ok ? 'od-tint--green' : 'od-tint--amber' }}"><i data-lucide="{{ $ok ? 'check-circle-2' : 'circle-dashed' }}"></i></span>
                    <div class="od-list__text">
                        <div class="od-list__title">{{ __('readiness.check_'.$key) }}</div>
                        @unless($ok)<div class="od-list__sub">{{ __('readiness.hint_'.$key) }}</div>@endunless
                    </div>
                    <span class="od-pill {{ $ok ? 'od-pill--ready' : 'od-pill--neutral' }}">{{ $ok ? __('readiness.done') : __('readiness.todo') }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
