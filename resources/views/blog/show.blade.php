@extends('layouts.finder')

{{-- This page owns its whole SEO head: article Open Graph, hreflang to the
     linked translation only, and BlogPosting + BreadcrumbList + FAQPage. --}}
@section('seo_head')
@php
    $blBase = \App\Support\FinderSeo::base();
    $blImg = asset($post['image']);
    $blAlternates = [];
    foreach ($translations as $blL => $blT) {
        $blAlternates[$blL] = \App\Support\FinderSeo::absolute(\App\Http\Controllers\BlogController::postUrl($blT));
    }
    $blXDefault = $blAlternates['en'] ?? $url;
    $blBlogUrl = \App\Http\Controllers\BlogController::localised(route('blog.index'), $post['locale']);

    // A named person writes as a Person with a url (their author page); the
    // house byline is the shared #organization node. See authorSchema().
    $blAuthor = \App\Http\Controllers\BlogController::authorSchema($post['author'], (string) ($post['author_bio'] ?? ''));

    $blGraph = [
        [
            '@type'            => 'BlogPosting',
            '@id'              => $url . '#article',
            'headline'         => $post['title'],
            'description'      => $post['description'],
            'image'            => ['@type' => 'ImageObject', 'url' => $blImg, 'width' => 1200, 'height' => 630],
            'datePublished'    => $post['date'],
            'dateModified'     => $post['updated'],
            'author'           => $blAuthor,
            'publisher'        => [
                '@type' => 'Organization',
                '@id'   => $blBase . '/#organization',
                'name'  => 'OpesCare',
                'logo'  => ['@type' => 'ImageObject', 'url' => asset('brand/opescare-logo-512.png'), 'width' => 512, 'height' => 512],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'inLanguage'       => $post['locale'],
            'articleSection'   => $post['category'],
            'keywords'         => implode(', ', $post['tags']),
            'wordCount'        => $post['words'],
            'isPartOf'         => ['@type' => 'Blog', '@id' => $blBase . '/blog#blog', 'name' => __('blog.feed_title'), 'url' => $blBlogUrl],
        ],
        [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('blog.breadcrumb_home'), 'item' => $blBase . ($post['locale'] === 'en' ? '' : '/?lang=' . $post['locale'])],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('blog.title'), 'item' => $blBlogUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $url],
            ],
        ],
    ];
    if ($post['faq'] !== []) {
        $blGraph[] = [
            '@type'      => 'FAQPage',
            'inLanguage' => $post['locale'],
            'mainEntity' => array_map(fn ($q) => [
                '@type' => 'Question', 'name' => $q['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['a']],
            ], $post['faq']),
        ];
    }
    // Kept inside this php block: a literal schema-context key outside it is
    // compiled by Blade as a directive and silently corrupted.
    $blJsonLd = json_encode(['@context' => 'https://schema.org', '@graph' => $blGraph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<title>{{ \App\Support\FinderSeo::branded($post['title']) }}</title>
<meta name="description" content="{{ $post['description'] }}">
<link rel="canonical" href="{{ $url }}">
@foreach($blAlternates as $blL => $blHref)
<link rel="alternate" hreflang="{{ $blL }}" href="{{ $blHref }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $blXDefault }}">
<link rel="alternate" type="application/rss+xml" title="{{ __('blog.feed_title') }}" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.feed'), $post['locale']) }}">
<meta name="geo.region" content="CM">
<meta property="og:type" content="article">
<meta property="og:site_name" content="OpesCare">
<meta property="og:title" content="{{ $post['title'] }}">
<meta property="og:description" content="{{ $post['description'] }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:locale" content="{{ $post['locale'] === 'fr' ? 'fr_CM' : 'en_CM' }}">
@foreach($blAlternates as $blL => $blHref)
@if($blL !== $post['locale'])<meta property="og:locale:alternate" content="{{ $blL === 'fr' ? 'fr_CM' : 'en_CM' }}">@endif
@endforeach
<meta property="og:image" content="{{ $blImg }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $post['hero_alt'] }}">
<meta property="article:published_time" content="{{ $post['date'] }}">
<meta property="article:modified_time" content="{{ $post['updated'] }}">
@if($post['category'] !== '')<meta property="article:section" content="{{ $post['category'] }}">@endif
@foreach($post['tags'] as $blTag)
<meta property="article:tag" content="{{ $blTag }}">
@endforeach
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $post['title'] }}">
<meta name="twitter:description" content="{{ $post['description'] }}">
<meta name="twitter:image" content="{{ $blImg }}">
<meta name="twitter:image:alt" content="{{ $post['hero_alt'] }}">
<script type="application/ld+json">
{!! $blJsonLd !!}
</script>
@endsection

@push('head')
    @include('blog._inline_css', ['file' => 'blog.css'])
@endpush

@if(!empty($post['has_diagrams']))
@push('head')
    @include('blog._inline_css', ['file' => 'blog-diagrams.css'])
@endpush
@endif

@push('scripts')
    <script @cspNonce src="{{ asset('js/blog.js') }}?v={{ @filemtime(public_path('js/blog.js')) ?: '1' }}" defer></script>
    @if(!empty($post['has_diagrams']))
    <script @cspNonce src="{{ asset('js/blog-diagrams.js') }}?v={{ @filemtime(public_path('js/blog-diagrams.js')) ?: '1' }}" defer></script>
    @endif
@endpush

@section('content')
@php
    $blShareUrl = rawurlencode($url);
    $blShareText = rawurlencode($post['title']);
    $blDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('j F Y');
@endphp
<div class="bl-layout bl-layout--article">
@include('blog._sidebar', ['categories' => $categories ?? [], 'recent' => $recent ?? [], 'current' => $post['category_slug'], 'locale' => $post['locale'], 'toc' => $post['toc']])
<article class="bl-article" lang="{{ $post['locale'] }}">
    <nav class="bl-crumbs" aria-label="{{ __('blog.breadcrumb') }}">
        <ol>
            <li><a href="{{ route('public.landing') }}{{ $post['locale'] === 'en' ? '' : '?lang=' . $post['locale'] }}">{{ __('blog.breadcrumb_home') }}</a></li>
            <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), $post['locale']) }}">{{ __('blog.title') }}</a></li>
            @if($post['category'] !== '')
            <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.category', $post['category_slug']), $post['locale']) }}">{{ $post['category'] }}</a></li>
            @endif
        </ol>
    </nav>

    <header>
        @if($post['category'] !== '')
            <a class="bl-cat" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.category', $post['category_slug']), $post['locale']) }}">{{ $post['category'] }}</a>
        @endif
        <h1>{{ $post['title'] }}</h1>
        @if($post['description'] !== '')<p class="bl-dek">{{ $post['description'] }}</p>@endif
        <div class="bl-meta">
            @php $blByUrl = \App\Http\Controllers\BlogController::authorUrl($post['author'], $post['locale']); @endphp
            <span>{{ __('blog.by') }} @if($blByUrl)<a href="{{ $blByUrl }}" rel="author">{{ $post['author'] }}</a>@else{{ $post['author'] }}@endif</span>
            <span>{{ __('blog.published') }} <time datetime="{{ $post['date'] }}">{{ $blDate($post['date']) }}</time></span>
            @if($post['updated'] !== $post['date'])
                <span>{{ __('blog.updated') }} <time datetime="{{ $post['updated'] }}">{{ $blDate($post['updated']) }}</time></span>
            @endif
            <span>@include('partials.finder.icon', ['name' => 'clock', 'size' => 16]) {{ __('blog.min_read', ['count' => $post['reading_minutes']]) }}</span>
            @foreach($translations as $blL => $blT)
                @if($blL !== $post['locale'])
                    <a class="bl-langswitch" href="{{ \App\Http\Controllers\BlogController::postUrl($blT) }}" hreflang="{{ $blL }}" lang="{{ $blL }}">@include('partials.finder.icon', ['name' => 'languages', 'size' => 16]){{ __('blog.read_in', ['language' => __('blog.lang_' . $blL)]) }}</a>
                @endif
            @endforeach
            @if(!empty($post['downloadable']))
                <a class="bl-download" href="{{ route('blog.download', $post['slug']) }}" download data-blog-download>@include('partials.finder.icon', ['name' => 'download', 'size' => 16]){{ __('blog.download_pdf') }}</a>
            @endif
        </div>
    </header>

    @if($imageExists)
    <figure class="bl-heroimg">
        @include('blog._picture', ['src' => $post['image'], 'alt' => $post['hero_alt'], 'eager' => true, 'priority' => true])
    </figure>
    @endif

    @if($post['key_takeaways'] !== [])
    <aside class="bl-box" aria-labelledby="bl-kt">
        <h2 id="bl-kt">@include('partials.finder.icon', ['name' => 'lightbulb', 'size' => 18]){{ __('blog.key_takeaways') }}</h2>
        <ul>
            @foreach($post['key_takeaways'] as $blK)<li>{{ $blK }}</li>@endforeach
        </ul>
    </aside>
    @endif

    @if(count($post['toc']) >= 2)
    <nav class="bl-box bl-toc-inline" aria-labelledby="bl-toc">
        <h2 id="bl-toc">@include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 18]){{ __('blog.toc_title') }}</h2>
        @php
            // Nest each h3 under the h2 before it, so only h2s are numbered.
            $blTree = [];
            foreach ($post['toc'] as $blH) {
                if ($blH['level'] === 3 && $blTree !== []) {
                    $blTree[array_key_last($blTree)]['children'][] = $blH;
                } else {
                    $blTree[] = $blH + ['children' => []];
                }
            }
        @endphp
        <ol>
            @foreach($blTree as $blH)
                <li><a href="#{{ $blH['id'] }}">{{ $blH['text'] }}</a>
                    @if($blH['children'] !== [])
                        <ul class="bl-toc-sub">
                            @foreach($blH['children'] as $blC)
                                <li><a href="#{{ $blC['id'] }}">{{ $blC['text'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @endif

    {{-- Rendered from Markdown with raw HTML escaped (see MarkdownRenderer). --}}
    {{-- Diagram markers come only from MarkdownRenderer and resolve to whitelisted partials. --}}
    <div class="bl-prose">
        @foreach(\App\Support\Blog\BlogDiagrams::segments($post['html']) as $blSeg)
            @if(isset($blSeg['diagram']))
                {!! \App\Support\Blog\BlogDiagrams::render($blSeg['diagram']) !!}
            @else
                {!! $blSeg['html'] !!}
            @endif
        @endforeach
    </div>

    @if(($post['author_bio'] ?? '') !== '')
    <aside class="bl-box bl-author" aria-labelledby="bl-author">
        <h2 id="bl-author">@include('partials.finder.icon', ['name' => 'user', 'size' => 18]){{ __('blog.about_author') }}</h2>
        <p class="bl-author__name">@if($blByUrl)<a href="{{ $blByUrl }}" rel="author">{{ $post['author'] }}</a>@else{{ $post['author'] }}@endif</p>
        <p class="f-muted" style="margin:0">{{ $post['author_bio'] }}</p>
        @if(!empty($post['downloadable']))
            <p style="margin:.75rem 0 0"><a class="f-btn f-btn--ghost" href="{{ route('blog.download', $post['slug']) }}" download>@include('partials.finder.icon', ['name' => 'download', 'size' => 18]){{ __('blog.download_pdf_long') }}</a></p>
        @endif
    </aside>
    @endif

    @if($post['faq'] !== [])
    <section class="bl-faq" aria-labelledby="bl-faq">
        <h2 class="bl-section-title" id="bl-faq">{{ __('blog.faq_title') }}</h2>
        @foreach($post['faq'] as $blQ)
            <details>
                <summary><span>{{ $blQ['q'] }}</span>@include('partials.finder.icon', ['name' => 'chevron-down', 'size' => 18])</summary>
                <p>{{ $blQ['a'] }}</p>
            </details>
        @endforeach
    </section>
    @endif

    @if($post['sources'] !== [])
    <section aria-labelledby="bl-src">
        <h2 class="bl-section-title" id="bl-src">{{ __('blog.sources_title') }}</h2>
        <ol class="bl-sources">
            @foreach($post['sources'] as $blS)
                <li>
                    @if($blS['url'] !== '')
                        <a href="{{ $blS['url'] }}" rel="noopener noreferrer">{{ $blS['title'] }}</a>
                    @else
                        {{ $blS['title'] }}
                    @endif
                    @if($blS['publisher'] !== '') — {{ $blS['publisher'] }}@endif
                    @if($blS['accessed']) ({{ __('blog.accessed', ['date' => $blDate($blS['accessed'])]) }})@endif
                </li>
            @endforeach
        </ol>
    </section>
    @endif

    <section aria-labelledby="bl-share">
        <h2 class="bl-section-title" id="bl-share">{{ __('blog.share_title') }}</h2>
        <ul class="bl-share">
            <li><a href="https://wa.me/?text={{ $blShareText }}%20{{ $blShareUrl }}" rel="noopener noreferrer" target="_blank" data-share="whatsapp">{{ __('blog.share_whatsapp') }}</a></li>
            <li><a href="https://www.facebook.com/sharer/sharer.php?u={{ $blShareUrl }}" rel="noopener noreferrer" target="_blank" data-share="facebook">{{ __('blog.share_facebook') }}</a></li>
            <li><a href="https://twitter.com/intent/tweet?url={{ $blShareUrl }}&amp;text={{ $blShareText }}" rel="noopener noreferrer" target="_blank" data-share="x">{{ __('blog.share_x') }}</a></li>
            <li><a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $blShareUrl }}" rel="noopener noreferrer" target="_blank" data-share="linkedin">{{ __('blog.share_linkedin') }}</a></li>
            <li><a href="https://t.me/share/url?url={{ $blShareUrl }}&amp;text={{ $blShareText }}" rel="noopener noreferrer" target="_blank" data-share="telegram">{{ __('blog.share_telegram') }}</a></li>
            <li><a href="mailto:?subject={{ $blShareText }}&amp;body={{ $blShareUrl }}" data-share="email">@include('partials.finder.icon', ['name' => 'mail', 'size' => 16]){{ __('blog.share_email') }}</a></li>
            <li><button type="button" hidden data-blog-copy data-url="{{ $url }}" data-done="{{ __('blog.share_copied') }}">@include('partials.finder.icon', ['name' => 'link', 'size' => 16])<span data-label>{{ __('blog.share_copy') }}</span></button></li>
            <li><button type="button" hidden data-blog-share data-url="{{ $url }}" data-title="{{ $post['title'] }}">@include('partials.finder.icon', ['name' => 'share-2', 'size' => 16]){{ __('blog.share_native') }}</button></li>
        </ul>
    </section>

    <section class="bl-box" aria-labelledby="bl-explore">
        <h2 id="bl-explore">{{ __('blog.explore_title') }}</h2>
        <p class="f-muted" style="margin:0">{{ __('blog.explore_lead') }}</p>
        <div class="bl-explore">
            <a class="f-btn f-btn--ghost" href="{{ route('public.network.medicine-finder') }}">@include('partials.finder.icon', ['name' => 'pill', 'size' => 18]){{ __('blog.explore_medicine') }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.network.blood-finder') }}">@include('partials.finder.icon', ['name' => 'droplet', 'size' => 18]){{ __('blog.explore_blood') }}</a>
            <a class="f-btn f-btn--ghost" href="{{ route('public.care-map') }}">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18]){{ __('blog.explore_directory') }}</a>
        </div>
    </section>

    @if($related !== [])
    <section aria-labelledby="bl-related">
        <h2 class="bl-section-title" id="bl-related">{{ __('blog.related_title') }}</h2>
        <div class="bl-grid">
            @foreach($related as $p)
                @include('blog._card', ['p' => $p, 'heading' => 'h3', 'eager' => false])
            @endforeach
        </div>
    </section>
    @endif

    @if($adjacent['prev'] || $adjacent['next'])
    <nav class="bl-nav2" aria-label="{{ __('blog.back_to_blog') }}">
        @if($adjacent['prev'])
            <a rel="prev" href="{{ \App\Http\Controllers\BlogController::postUrl($adjacent['prev']) }}"><small>{{ __('blog.prev') }}</small>{{ $adjacent['prev']['title'] }}</a>
        @else <span></span> @endif
        @if($adjacent['next'])
            <a class="bl-next" rel="next" href="{{ \App\Http\Controllers\BlogController::postUrl($adjacent['next']) }}"><small>{{ __('blog.next') }}</small>{{ $adjacent['next']['title'] }}</a>
        @endif
    </nav>
    @endif

    {{-- Comments: approved only; the form always lands in moderation. --}}
    <section class="bl-comments" id="comments" aria-labelledby="bl-comments">
        <h2 class="bl-section-title" id="bl-comments">@include('partials.finder.icon', ['name' => 'message-circle', 'size' => 20]) {{ trans_choice('blog.comments.count', $commentCount, ['count' => $commentCount]) }}</h2>

        @if(session('blog_comment_status') === 'pending')
            <p class="bl-notice" role="status">{{ __('blog.comments.pending_notice') }}</p>
        @endif

        @if($comments->isEmpty())
            <p class="f-muted">{{ __('blog.comments.none') }}</p>
        @else
            <ol>
                @foreach($comments as $blC)
                    <li class="bl-comment" id="comment-{{ $blC->id }}">
                        <div class="bl-meta"><strong>{{ $blC->author_name }}</strong> <time datetime="{{ $blC->created_at->toIso8601String() }}">{{ $blC->created_at->translatedFormat('j F Y') }}</time></div>
                        <p>{{ $blC->body }}</p>
                        <button type="button" class="f-btn f-btn--ghost" hidden data-reply-to="{{ $blC->id }}" data-reply-title="{{ __('blog.comments.reply_title', ['name' => $blC->author_name]) }}">{{ __('blog.comments.reply') }}</button>
                        @if($blC->replies->isNotEmpty())
                            <ol class="bl-replies">
                                @foreach($blC->replies as $blR)
                                    <li class="bl-comment" id="comment-{{ $blR->id }}">
                                        <div class="bl-meta"><strong>{{ $blR->author_name }}</strong> <time datetime="{{ $blR->created_at->toIso8601String() }}">{{ $blR->created_at->translatedFormat('j F Y') }}</time></div>
                                        <p>{{ $blR->body }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        <h3 id="bl-form-title">{{ __('blog.comments.form_title') }}</h3>
        <p class="f-muted">{{ __('blog.comments.moderated') }}</p>
        <form class="bl-form" method="POST" action="{{ route('blog.comments.store', $post['slug']) }}" novalidate>
            @csrf
            <input type="hidden" name="locale" value="{{ $post['locale'] }}">
            <input type="hidden" name="ts" value="{{ $formToken }}">
            <input type="hidden" name="parent_id" id="bl-parent" value="{{ old('parent_id') }}">
            <div class="bl-hp" aria-hidden="true">
                <label for="bl-website">{{ __('blog.comments.honeypot') }}</label>
                <input type="text" id="bl-website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="f-field">
                <label class="f-label" for="bl-name">{{ __('blog.comments.name') }}</label>
                <input class="f-input" id="bl-name" name="author_name" maxlength="80" required autocomplete="name" value="{{ old('author_name', auth()->user()?->name) }}">
                @error('author_name')<p class="f-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="bl-email">{{ __('blog.comments.email') }}</label>
                <input class="f-input" id="bl-email" type="email" name="author_email" maxlength="190" required autocomplete="email" value="{{ old('author_email', auth()->user()?->email) }}" aria-describedby="bl-email-help">
                <p class="f-hint" id="bl-email-help">{{ __('blog.comments.email_help') }}</p>
                @error('author_email')<p class="f-error">{{ $message }}</p>@enderror
            </div>
            <div class="f-field">
                <label class="f-label" for="bl-body">{{ __('blog.comments.comment') }}</label>
                <textarea class="f-input" id="bl-body" name="body" maxlength="{{ config('blog.comments.max_length', 2000) }}" required aria-describedby="bl-body-help">{{ old('body') }}</textarea>
                <p class="f-hint" id="bl-body-help">{{ __('blog.comments.comment_help', ['max' => config('blog.comments.max_length', 2000)]) }}</p>
                @error('body')<p class="f-error">{{ $message }}</p>@enderror
            </div>
            <label class="bl-check">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                <span>{{ __('blog.comments.consent') }} <a href="{{ route('public.privacy') }}">{{ __('blog.comments.privacy_link') }}</a></span>
            </label>
            @error('consent')<p class="f-error">{{ $message }}</p>@enderror
            <div><button class="f-btn f-btn--primary" type="submit">@include('partials.finder.icon', ['name' => 'send', 'size' => 18]){{ __('blog.comments.submit') }}</button></div>
        </form>
    </section>

    <p class="bl-disclaimer">{{ __('blog.disclaimer') }}</p>
    <p><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), $post['locale']) }}">@include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 16]){{ __('blog.back_to_blog') }}</a></p>
</article>
</div>
@endsection
