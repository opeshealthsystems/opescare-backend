{{-- Desktop-only left sidebar for the blog (hidden below 1100px; the same
     content stays inline there). Params: $categories, $recent, $current
     (category slug or null), $locale, $toc (optional, article pages). --}}
@php $blLoc = $locale ?? app()->getLocale(); @endphp
<aside class="bl-side" aria-label="{{ __('blog.sidebar_label') }}">
    <div class="bl-side__inner">
        @if(! empty($toc) && count($toc) >= 2)
            <nav class="bl-side__block" aria-label="{{ __('blog.toc_title') }}">
                <p class="bl-side__title">@include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 16]){{ __('blog.toc_title') }}</p>
                <ol class="bl-side__toc">
                    @foreach($toc as $blH)
                        @if($blH['level'] === 2)
                            <li><a href="#{{ $blH['id'] }}">{{ $blH['text'] }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @else
            <form class="bl-side__block" role="search" method="GET" action="{{ route('blog.index') }}">
                <label class="bl-side__title" for="bl-side-q">@include('partials.finder.icon', ['name' => 'search', 'size' => 16]){{ __('blog.search_label') }}</label>
                <input id="bl-side-q" class="f-input" type="search" name="q" maxlength="100" placeholder="{{ __('blog.search_placeholder') }}">
                @if($blLoc !== 'en')<input type="hidden" name="lang" value="{{ $blLoc }}">@endif
            </form>
        @endif

        @if(! empty($categories))
            <nav class="bl-side__block" aria-label="{{ __('blog.categories_label') }}">
                <p class="bl-side__title">@include('partials.finder.icon', ['name' => 'tag', 'size' => 16]){{ __('blog.categories_label') }}</p>
                <ul class="bl-side__list">
                    <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.index'), $blLoc) }}" @if(empty($current)) aria-current="page" @endif>{{ __('blog.all_categories') }}</a></li>
                    @foreach($categories as $blSlug => $blLabel)
                        <li><a href="{{ \App\Http\Controllers\BlogController::localised(route('blog.category', $blSlug), $blLoc) }}" @if(($current ?? null) === $blSlug) aria-current="page" @endif>{{ $blLabel }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if(! empty($recent))
            <nav class="bl-side__block" aria-label="{{ __('blog.sidebar_recent') }}">
                <p class="bl-side__title">@include('partials.finder.icon', ['name' => 'clock', 'size' => 16]){{ __('blog.sidebar_recent') }}</p>
                <ul class="bl-side__list bl-side__list--posts">
                    @foreach(array_slice($recent, 0, 5) as $blP)
                        <li><a href="{{ \App\Http\Controllers\BlogController::postUrl($blP) }}">{{ $blP['title'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <a class="bl-side__rss" href="{{ \App\Http\Controllers\BlogController::localised(route('blog.feed'), $blLoc) }}">@include('partials.finder.icon', ['name' => 'rss', 'size' => 16]){{ __('blog.rss') }}</a>
    </div>
</aside>
