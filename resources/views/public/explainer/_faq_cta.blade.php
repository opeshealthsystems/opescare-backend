{{--
    Visible FAQ (same $ns.faq.categories array the FAQPage JSON-LD is built from)
    followed by the CTA band. Expects $ns; optional $ctaLinks overrides the two buttons.
--}}
@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<section class="hm-s" aria-labelledby="ex-faq-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __($ns . '.faq.eyebrow') }}</p>
            <h2 id="ex-faq-title">{{ __($ns . '.faq.title') }}</h2>
        </div>
        <div class="ex-faq">
            @foreach (__($ns . '.faq.categories') as $cat)
                @foreach ($cat['items'] as $item)
                    <details class="ex-faq__item">
                        <summary><span>{{ $item['q'] }}</span>{!! $exIcon('chevron-down', 18) !!}</summary>
                        <p>{{ $item['a'] }}</p>
                    </details>
                @endforeach
            @endforeach
        </div>

        <div class="hm-cta">
            <div>
                <h2>{{ __($ns . '.cta.title') }}</h2>
                <p>{{ __($ns . '.cta.sub') }}</p>
            </div>
            <div class="hm-actions">
                @if (isset($ctaLinks))
                    {{-- Optional override: [[url, label, modifier], ...] — patient-facing pages. --}}
                    @foreach ($ctaLinks as [$ctaUrl, $ctaLabel, $ctaMod])
                        <a class="hm-btn {{ $ctaMod }}" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
                    @endforeach
                @else
                <a class="hm-btn hm-btn--light" href="{{ route('public.solutions.index') }}">{{ __($ns . '.hero.cta_solutions') }}</a>
                <a class="hm-btn hm-btn--white" href="{{ route('register.organization') }}">{{ __($ns . '.hero.cta_register') }}</a>
                @endif
            </div>
        </div>
    </div>
</section>
