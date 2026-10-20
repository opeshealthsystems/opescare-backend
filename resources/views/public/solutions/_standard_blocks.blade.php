{{-- The two blocks every OPES system page carries: what every system includes,
     and buy-or-receive-free. Optional $slug pre-selects the system on the form. --}}
<section class="sol-section" aria-labelledby="sol-includes" data-block="includes">
    <h2 class="sol-h2" id="sol-includes">{{ __('solutions.includes.title') }}</h2>
    <ul class="sol-grid">
        <li class="sol-card">
            <div class="sol-card__head">
                <span class="sol-card__icon">@include('partials.finder.icon', ['name' => 'smartphone', 'size' => 22])</span>
                <h3 class="sol-card__title">{{ __('solutions.includes.chart_title') }}</h3>
            </div>
            <p class="sol-card__body">{{ __('solutions.includes.chart_body') }}</p>
        </li>
        <li class="sol-card">
            <div class="sol-card__head">
                <span class="sol-card__icon">@include('partials.finder.icon', ['name' => 'network', 'size' => 22])</span>
                <h3 class="sol-card__title">{{ __('solutions.includes.interop_title') }}</h3>
            </div>
            <p class="sol-card__body">{{ __('solutions.includes.interop_body') }}</p>
        </li>
    </ul>
</section>

<section class="sol-section" aria-labelledby="sol-offer" data-block="offer">
    <div class="sol-band sol-band--brand">
        <h2 class="sol-h2" id="sol-offer">{{ __('solutions.offer.title') }}</h2>
        <ul class="sol-grid">
            <li class="sol-card">
                <div class="sol-card__head">
                    <span class="sol-card__icon">@include('partials.finder.icon', ['name' => 'tag', 'size' => 22])</span>
                    <h3 class="sol-card__title">{{ __('solutions.offer.buy_title') }}</h3>
                </div>
                <p class="sol-card__body">{{ __('solutions.offer.buy_body') }}</p>
            </li>
            <li class="sol-card">
                <div class="sol-card__head">
                    <span class="sol-card__icon">@include('partials.finder.icon', ['name' => 'gift', 'size' => 22])</span>
                    <h3 class="sol-card__title">{{ __('solutions.offer.free_title') }}</h3>
                </div>
                <p class="sol-card__body">{{ __('solutions.offer.free_body') }}</p>
            </li>
        </ul>
        <div class="sol-actions">
            <a class="f-btn f-btn--primary" href="{{ route('public.solutions.request', array_filter(['system' => $slug ?? null])) }}">@include('partials.finder.icon', ['name' => 'send', 'size' => 18]){{ __('solutions.offer.cta') }}</a>
            <a class="f-btn" href="{{ route('public.solutions.request', array_filter(['system' => $slug ?? null, 'kind' => 'donation'])) }}">@include('partials.finder.icon', ['name' => 'gift', 'size' => 18]){{ __('solutions.index.cta_free') }}</a>
        </div>
    </div>
</section>
