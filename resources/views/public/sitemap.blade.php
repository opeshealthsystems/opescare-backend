@extends('layouts.finder')

{{-- Human site map (/sitemap). Same sources as /sitemap.xml (SeoController::publicPaths). --}}

@section('title', __('sitemap.page_title'))
@section('description', __('sitemap.meta_description'))

@section('content')
<section class="f-section">
    <div class="f-container">
        <div class="fs-narrow">
            <h1>{{ __('sitemap.heading') }}</h1>
            <p>{{ __('sitemap.intro') }}</p>

            @foreach($sections as $key => $links)
                <h2>{{ __('sitemap.sections.' . $key) }}</h2>
                <ul>
                    @foreach($links as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>

                @if($key === 'services')
                    <h2>{{ __('sitemap.sections.facilities') }}</h2>
                    <p>{{ __('sitemap.facilities_count', ['count' => number_format($facilityCount, 0, ',', ' ')]) }}</p>
                    <p><a href="{{ route('public.care-map') }}">{{ __('sitemap.facilities_link') }}</a></p>
                @endif
            @endforeach

            <p class="f-small f-muted">{!! __('sitemap.xml_note', ['link' => '<a href="' . e(route('seo.sitemap')) . '">sitemap.xml</a>']) !!}</p>
        </div>
    </div>
</section>
@endsection
