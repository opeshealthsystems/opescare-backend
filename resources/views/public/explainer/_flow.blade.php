{{--
    Animated step-through flow on navy (hero palette, lead-text colour #BFD0E2).
    Expects $ns (lang file with .flow.*) and $icons (one Lucide name per step).
    Optional $key (default 'flow') and $fid (heading id) for a second strip on one page.

    Without JavaScript, or with prefers-reduced-motion, every step is shown in
    full and the controls stay hidden. public/js/explainer-flow.js adds
    .is-anim, steps through on a timer while the section is on screen
    (IntersectionObserver), and offers Previous / Pause / Next.
--}}
@php
    $exIcon  = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
    $exKey   = $ns . '.' . ($key ?? 'flow');
    $exFid   = $fid ?? 'ex-flow-title';
    $exSteps = __($exKey . '.steps');
    $exTotal = count($exSteps);
@endphp
<section class="ex-dark" aria-labelledby="{{ $exFid }}">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="ex-eyebrow">{{ __($exKey . '.eyebrow') }}</p>
            <h2 id="{{ $exFid }}">{{ __($exKey . '.title') }}</h2>
            <p class="ex-sub">{{ __($exKey . '.sub') }}</p>
        </div>
        <div class="ex-flow" data-ex-flow>
            <div class="ex-flow__bar" aria-hidden="true"><i data-ex-bar></i></div>
            <ol class="ex-steps ex-steps--{{ $exTotal }}" role="list" aria-label="{{ __($exKey . '.label') }}">
                @foreach ($exSteps as $i => $step)
                    <li class="ex-step" data-ex-step>
                        <div class="ex-step__top">
                            <span class="ex-step__ic" aria-hidden="true">{!! $exIcon($icons[$i] ?? 'circle-check', 20) !!}</span>
                            <span class="ex-step__n" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="ex-step__who">{{ $step['who'] }}</span>
                        </div>
                        <b>{{ $step['title'] }}</b>
                        <p>{{ $step['desc'] }}</p>
                        <span class="ex-chip">{!! $exIcon('circle-check', 14) !!}{{ $step['chip'] }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="ex-controls" data-ex-controls hidden>
                <p class="ex-count" data-ex-count data-tpl="{{ __($exKey . '.step_of') }}" data-total="{{ $exTotal }}"></p>
                <div class="ex-controls__btns">
                    <button type="button" class="ex-ctl" data-ex-prev aria-label="{{ __($exKey . '.prev') }}">{!! $exIcon('arrow-left', 18) !!}</button>
                    <button type="button" class="ex-ctl ex-ctl--wide" data-ex-toggle aria-pressed="false"
                            data-pause="{{ __($exKey . '.pause') }}" data-play="{{ __($exKey . '.play') }}">{{ __($exKey . '.pause') }}</button>
                    <button type="button" class="ex-ctl" data-ex-next aria-label="{{ __($exKey . '.next') }}">{!! $exIcon('arrow-right', 18) !!}</button>
                </div>
            </div>
        </div>
    </div>
</section>
