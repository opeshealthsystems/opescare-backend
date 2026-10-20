@extends('layouts.finder')

@section('title', __('blog.author_title', ['name' => $name]))
@section('description', __('blog.author_meta', ['name' => $name]))

@push('head')
    @include('blog._inline_css', ['file' => 'blog.css'])
@endpush

@push('schema')
@php
    $baBase = \App\Support\FinderSeo::base();
    $baUrl = \App\Support\FinderSeo::absolute(route('blog.author', $author));
    $baPageUrl = \App\Http\Controllers\BlogController::localised($baUrl, app()->getLocale());
    // The subject: a Person (url = this page, sameAs only from config) or, for
    // the house byline, the shared #organization node by @id.
    $baEntity = $type === 'Organization'
        ? ['@id' => $baBase . '/#organization']
        : \App\Http\Controllers\BlogController::authorSchema($name, __('blog.authors.' . $author . '.bio'));
    $baJsonLd = json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'ProfilePage',
        '@id'        => $baPageUrl . '#webpage',
        'url'        => $baPageUrl,
        'name'       => __('blog.author_title', ['name' => $name]),
        'inLanguage' => app()->getLocale(),
        'isPartOf'   => ['@id' => $baBase . '/#website'],
        'mainEntity' => $baEntity,
        'hasPart'    => array_map(fn ($p) => [
            '@type' => 'BlogPosting',
            'headline' => $p['title'],
            'url' => \App\Support\FinderSeo::absolute(\App\Http\Controllers\BlogController::postUrl($p)),
            'datePublished' => $p['date'],
        ], $posts),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $baJsonLd !!}
</script>
@endpush

@section('content')
<div class="bl-wrap bl-layout">
    @include('blog._sidebar', ['categories' => $categories, 'recent' => $recent ?? [], 'current' => null])
<div class="bl-main">
    <nav class="bl-crumbs" aria-label="{{ __('blog.breadcrumb') }}">
        <ol>
            <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('public.landing'), app()->getLocale()) }}">{{ __('blog.breadcrumb_home') }}</a></li>
            <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), app()->getLocale()) }}">{{ __('blog.title') }}</a></li>
        </ol>
    </nav>

    <section class="bl-hero">
        <p class="f-eyebrow">{{ __('blog.author_eyebrow') }}</p>
        <h1 class="f-h1">{{ $name }}</h1>
        <p class="f-muted" style="margin:0 0 .5rem;font-weight:600">{{ __('blog.authors.' . $author . '.role') }}</p>
        <p class="f-lead">{{ __('blog.authors.' . $author . '.bio') }}</p>
        <p class="f-muted">{{ __('blog.author_count', ['count' => count($posts)]) }}</p>
    </section>

    <h2 class="bl-section-title">{{ __('blog.author_posts', ['name' => $name]) }}</h2>
    @if($posts === [])
        <p class="f-muted">{{ __('blog.author_empty') }}</p>
    @else
        <div class="bl-grid">
            @foreach($posts as $p)
                @include('blog._card', ['p' => $p, 'heading' => 'h3', 'eager' => false])
            @endforeach
        </div>
    @endif

    <p style="margin:2rem 0 3rem"><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), app()->getLocale()) }}">@include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 16]){{ __('blog.back_to_blog') }}</a></p>
</div>
</div>
@endsection
