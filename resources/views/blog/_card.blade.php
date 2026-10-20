{{-- One post card. Params: $p (post array), $heading (h2|h3), $eager (bool) --}}
@php $blUrl = \App\Http\Controllers\BlogController::postUrl($p); $blTag = $heading ?? 'h2'; @endphp
<article class="bl-card">
    <a href="{{ $blUrl }}" tabindex="-1" aria-hidden="true">
        @include('blog._picture', ['src' => $p['image'], 'alt' => $p['hero_alt'] ?? '' ?: $p['title'], 'eager' => !empty($eager)])
    </a>
    <div class="bl-card__body">
        @if($p['category'] !== '')
            <a class="bl-cat" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.category', $p['category_slug']), $p['locale']) }}">{{ $p['category'] }}</a>
        @endif
        <{{ $blTag }}><a href="{{ $blUrl }}">{{ $p['title'] }}</a></{{ $blTag }}>
        @if($p['description'] !== '')<p>{{ $p['description'] }}</p>@endif
        <div class="bl-meta">
            <time datetime="{{ $p['date'] }}">{{ \Illuminate\Support\Carbon::parse($p['date'])->translatedFormat('j F Y') }}</time>
            <span>{{ __('blog.min_read', ['count' => $p['reading_minutes']]) }}</span>
            @php $blCardAuthor = \App\Http\Controllers\BlogController::authorUrl((string) $p['author'], $p['locale']); @endphp
            @if($blCardAuthor)<span>{{ __('blog.by') }} <a href="{{ $blCardAuthor }}" rel="author">{{ $p['author'] }}</a></span>@endif
        </div>
    </div>
</article>
