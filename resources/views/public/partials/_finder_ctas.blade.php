{{--
    Shared consumer CTA row — the three finders + how it works.
    Include with an optional 'primary' => 'medicine'|'blood'|'directory'
    to promote one button; everything else renders as a plain button.
--}}
@php $fsPrimary = $primary ?? 'directory'; @endphp
<div class="fs-cta-row">
    <a class="f-btn {{ $fsPrimary === 'medicine' ? 'f-btn--primary' : '' }}" href="{{ route('public.network.medicine-finder') }}">
        @include('partials.finder.icon', ['name' => 'pill', 'size' => 18])
        {{ __('finder_story.cta.medicine') }}
    </a>
    <a class="f-btn {{ $fsPrimary === 'blood' ? 'f-btn--primary' : '' }}" href="{{ route('public.network.blood-finder') }}">
        @include('partials.finder.icon', ['name' => 'droplet', 'size' => 18])
        {{ __('finder_story.cta.blood') }}
    </a>
    <a class="f-btn {{ $fsPrimary === 'directory' ? 'f-btn--primary' : '' }}" href="{{ route('public.care-map') }}">
        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
        {{ __('finder_story.cta.directory') }}
    </a>
    <a class="f-btn f-btn--ghost" href="{{ route('public.how-it-works') }}">
        {{ __('finder_story.cta.how') }}
    </a>
</div>
