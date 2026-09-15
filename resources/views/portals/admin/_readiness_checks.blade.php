{{-- Group D import: the Finder's automatic go-live checks (FacilityReadinessScoringService::evaluate()).
     Expects $automatic = ['checks' => [key => bool], 'score' => int, 'missing' => [...]]. --}}
<div class="panel mb-6" data-readiness-score="{{ $automatic['score'] }}">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="gauge"></i> {{ __('readiness.panel_title') }}</h3>
        <span class="badge {{ $automatic['score'] >= 100 ? 'badge-success' : ($automatic['score'] >= 50 ? 'badge-warning' : 'badge-danger') }}">{{ __('readiness.score', ['score' => $automatic['score']]) }}</span>
    </div>
    <div class="panel-body">
        <div class="checklist">
            @foreach($automatic['checks'] as $key => $ok)
                <div class="checklist__item" data-check="{{ $key }}" data-ok="{{ $ok ? '1' : '0' }}">
                    <span class="checklist__icon {{ $ok ? 'ok' : 'fail' }}"><i data-lucide="{{ $ok ? 'check-circle-2' : 'circle-dashed' }}"></i></span>
                    <div class="checklist__body">
                        <div class="checklist__title">{{ __('readiness.check_'.$key) }}</div>
                        @unless($ok)<div class="td-muted text-sm">{{ __('readiness.hint_'.$key) }}</div>@endunless
                    </div>
                    <span class="badge {{ $ok ? 'badge-success' : 'badge-neutral' }} badge-sm">{{ $ok ? __('readiness.done') : __('readiness.todo') }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
