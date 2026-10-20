@extends('layouts.finder')

@section('title', $category ? __('blog.category_title', ['category' => $categoryLabel]) : __('blog.page_title'))
@section('description', $category ? __('blog.category_meta', ['category' => $categoryLabel]) : __('blog.meta_description'))

@push('head')
    @include('blog._inline_css', ['file' => 'blog.css'])
    <link rel="alternate" type="application/rss+xml" title="{{ __('blog.feed_title') }}" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.feed'), app()->getLocale()) }}">
    @if($query !== '' || $posts->currentPage() > 1)
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@push('schema')
@php
    $blItems = [];
    foreach (array_values(array_filter(array_merge([$featured], $posts->items()))) as $blI => $blP) {
        $blItems[] = [
            '@type'    => 'ListItem',
            'position' => $blI + 1,
            'url'      => \App\Http\Controllers\BlogController::postUrl($blP),
            'name'     => $blP['title'],
        ];
    }
    $blIndexJsonLd = json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'CollectionPage',
        'name'       => $category ? __('blog.category_title', ['category' => $categoryLabel]) : __('blog.page_title'),
        'description'=> __('blog.meta_description'),
        'url'        => \App\Http\Controllers\BlogController::localised($category ? route('blog.category', $category) : route('blog.index'), app()->getLocale()),
        'inLanguage' => app()->getLocale(),
        'isPartOf'   => ['@id' => \App\Support\FinderSeo::base() . '/#website'],
        'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $blItems],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $blIndexJsonLd !!}
</script>
@endpush

@section('content')
<div class="bl-wrap bl-layout">
    @include('blog._sidebar', ['categories' => $categories, 'recent' => $recent ?? [], 'current' => $category])
<div class="bl-main">
    <section class="bl-hero">
        <p class="f-eyebrow">{{ __('blog.eyebrow') }}</p>
        <h1 class="f-h1">{{ $category ? $categoryLabel : __('blog.hero_title') }}</h1>
        <p class="f-lead">{{ __('blog.lead') }}</p>

        <div class="bl-tools">
            <form class="bl-search" role="search" method="GET" action="{{ $category ? route('blog.category', $category) : route('blog.index') }}">
                <label for="bl-q" class="f-skip">{{ __('blog.search_label') }}</label>
                <input id="bl-q" class="f-input" type="search" name="q" value="{{ $query }}" maxlength="100" placeholder="{{ __('blog.search_placeholder') }}">
                @if(app()->getLocale() !== 'en')<input type="hidden" name="lang" value="{{ app()->getLocale() }}">@endif
                <button class="f-btn f-btn--primary" type="submit">@include('partials.finder.icon', ['name' => 'search', 'size' => 18]){{ __('blog.search_button') }}</button>
            </form>
            <a class="f-btn f-btn--ghost" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.feed'), app()->getLocale()) }}">@include('partials.finder.icon', ['name' => 'rss', 'size' => 18]){{ __('blog.rss') }}</a>
        </div>

        @if($categories !== [])
        <nav aria-label="{{ __('blog.categories_label') }}">
            <ul class="bl-cats">
                <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), app()->getLocale()) }}" @if(! $category) aria-current="page" @endif>{{ __('blog.all_categories') }}</a></li>
                @foreach($categories as $blSlug => $blLabel)
                    <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.category', $blSlug), app()->getLocale()) }}" @if($category === $blSlug) aria-current="page" @endif>{{ $blLabel }}</a></li>
                @endforeach
            </ul>
        </nav>
        @endif

        @if($query !== '')
            <p class="f-muted">{{ __('blog.results_for', ['count' => $total, 'query' => $query]) }}
                · <a href="{{ \App\Http\Controllers\BlogController::localised($category ? route('blog.category', $category) : route('blog.index'), app()->getLocale()) }}">{{ __('blog.clear_search') }}</a></p>
        @endif
    </section>

    @if($featured)
        @php $blFUrl = \App\Http\Controllers\BlogController::postUrl($featured); @endphp
        <article class="bl-featured">
            <a href="{{ $blFUrl }}" tabindex="-1" aria-hidden="true">@include('blog._picture', ['src' => $featured['image'], 'alt' => $featured['hero_alt'] ?? '' ?: $featured['title'], 'eager' => true, 'priority' => true])</a>
            <div class="bl-featured__body">
                <span class="f-badge">{{ __('blog.featured') }}</span>
                @if($featured['category'] !== '')<span class="bl-cat">{{ $featured['category'] }}</span>@endif
                <h2><a href="{{ $blFUrl }}">{{ $featured['title'] }}</a></h2>
                <p class="f-muted">{{ $featured['description'] }}</p>
                <div class="bl-meta">
                    <time datetime="{{ $featured['date'] }}">{{ \Illuminate\Support\Carbon::parse($featured['date'])->translatedFormat('j F Y') }}</time>
                    <span>{{ __('blog.min_read', ['count' => $featured['reading_minutes']]) }}</span>
                    @php $blFAuthor = \App\Http\Controllers\BlogController::authorUrl((string) $featured['author'], $featured['locale']); @endphp
                    @if($blFAuthor)<span>{{ __('blog.by') }} <a href="{{ $blFAuthor }}" rel="author">{{ $featured['author'] }}</a></span>@endif
                </div>
                <p><a class="f-btn f-btn--primary" href="{{ $blFUrl }}">{{ __('blog.read_more') }} @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 18])</a></p>
            </div>
        </article>
    @endif

    @if($posts->isEmpty() && ! $featured)
        <p class="f-muted">{{ $query !== '' ? __('blog.empty_search') : __('blog.empty') }}</p>
    @else
        <div class="bl-grid">
            @foreach($posts as $p)
                @include('blog._card', ['p' => $p, 'heading' => 'h2', 'eager' => false])
            @endforeach
        </div>
    @endif

    @if($posts->hasPages())
        <nav class="bl-pager" aria-label="{{ __('blog.page_n', ['page' => $posts->currentPage()]) }}">
            <span>@if($posts->previousPageUrl())<a class="f-btn f-btn--ghost" rel="prev" href="{{ $posts->previousPageUrl() }}">@include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18]){{ __('blog.prev') }}</a>@endif</span>
            <span>@if($posts->nextPageUrl())<a class="f-btn f-btn--ghost" rel="next" href="{{ $posts->nextPageUrl() }}">{{ __('blog.next') }}@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</a>@endif</span>
        </nav>
    @endif

    <section class="bl-box" style="margin-bottom:3rem">
        <h2>{{ __('blog.explore_title') }}</h2>
        <p class="f-muted" style="margin:0">{{ __('blog.explore_lead') }}</p>
        <div class="bl-explore">
            <a class="f-btn f-btn--ghost" href="{{ route('public.network.medicine-finder') }}">@include('partials.finder.icon', ['name' => 'pill', 'size' => 18]){{ __('blog.explore_medicine') }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.network.blood-finder') }}">@include('partials.finder.icon', ['name' => 'droplet', 'size' => 18]){{ __('blog.explore_blood') }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.care-map') }}">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18]){{ __('blog.explore_directory') }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.faq') }}">@include('partials.finder.icon', ['name' => 'info', 'size' => 18]){{ __('blog.explore_faq') }}</a>
        </div>
    </section>
</div>
</div>
@endsection
