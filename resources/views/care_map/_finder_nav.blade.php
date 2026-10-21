{{--
    Section nav shared by the public finder pages. $active: facilities |
    medicines | blood | lab_tests | imaging. Every link is a real route.
--}}
@php
    $fpLang = request('lang') === 'fr' ? ['lang' => 'fr'] : [];
    $fpLinks = [
        'facilities' => route('public.care-map', $fpLang),
        'medicines'  => route('public.network.medicine-finder', $fpLang),
        'blood'      => route('public.network.blood-finder', $fpLang),
        'lab_tests'  => route('public.network.lab-test-finder', $fpLang),
        'imaging'    => route('public.network.imaging-finder', $fpLang),
    ];
@endphp
<div class="fp-appbar">
    <nav class="fp-nav" aria-label="{{ __('finder_pages.nav.label') }}">
        @foreach($fpLinks as $key => $href)
            <a href="{{ $href }}" @if(($active ?? '') === $key) aria-current="page" @endif>{{ __('finder_pages.nav.' . $key) }}</a>
        @endforeach
    </nav>
    <a class="fp-btn fp-btn--danger" href="{{ route('public.care-map.emergency', $fpLang) }}">
        @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
        {{ __('finder_pages.nav.emergency') }}
    </a>
</div>
