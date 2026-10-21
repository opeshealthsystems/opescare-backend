{{--
    /download — the Android app (public.download). Home-page design (layouts.hm).
    Version, build, date, size and SHA-256 come from config('mobile.apk') via
    PublicPageController::download(); the file itself is served by nginx on prod
    (public/downloads is not in git). Never claim a store listing exists.
--}}
@extends('layouts.hm')

@section('title', __('public_download.meta_title'))
@section('description', __('public_download.meta_desc'))

@php
    $exIcon   = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
    $released = \Illuminate\Support\Carbon::parse($apk['released'])->locale(app()->getLocale())->isoFormat('LL');
    // json_encode inside @php on purpose: a bare "@context" is a Blade directive.
    $dlApp = json_encode([
        '@context'            => 'https://schema.org',
        '@type'               => 'SoftwareApplication',
        'name'                => 'OpesCare',
        'operatingSystem'     => 'Android',
        'applicationCategory' => 'HealthApplication',
        'softwareVersion'     => (string) $apk['version'],
        'fileSize'            => $apkSizeMb . 'MB',
        'downloadUrl'         => $apkUrl,
        'datePublished'       => (string) $apk['released'],
        'url'                 => \App\Support\FinderSeo::absolute(route('public.download')),
        'description'         => __('public_download.meta_desc'),
        'publisher'           => ['@type' => 'Organization', 'name' => 'OPES Health Systems'],
        'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'XAF'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp

@push('schema')
<script type="application/ld+json">
{!! $dlApp !!}
</script>
    @include('public.explainer._schema', ['ns' => 'public_download', 'route' => 'public.download'])
@endpush
@push('scripts')
    <script src="{{ asset('js/download-page.js') }}?v={{ @filemtime(public_path('js/download-page.js')) ?: '1' }}" @cspNonce defer></script>
@endpush

@section('content')
{{-- Hero ──────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="dl-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <nav class="ex-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_download.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('smartphone', 15) !!}{{ __('public_download.hero.kicker') }}</span>
            <h1 id="dl-title">{{ __('public_download.hero.title_1') }}<br><span>{{ __('public_download.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_download.hero.lead') }}</p>
            <ul class="dl-facts" role="list">
                <li>{!! $exIcon('tag', 15) !!}{{ __('public_download.hero.version', ['version' => $apk['version'], 'build' => $apk['build']]) }}</li>
                <li>{!! $exIcon('hard-drive', 15) !!}{{ __('public_download.hero.size', ['size' => $apkSizeMb]) }}</li>
                <li>{!! $exIcon('calendar', 15) !!}{{ __('public_download.hero.released', ['date' => $released]) }}</li>
            </ul>
            <div class="hm-actions dl-actions">
                <a class="hm-btn hm-btn--white" href="{{ $apkUrl }}" download="opescare.apk" data-dl-apk>{!! $exIcon('download', 18) !!}{{ __('public_download.hero.cta_download') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('mobile.app') }}">{!! $exIcon('globe', 18) !!}{{ __('public_download.hero.cta_web') }}</a>
            </div>
            <p class="dl-note">{{ __('public_download.hero.android_only') }}</p>
        </div>
        <div class="ex-hvis">
            <figure class="dl-qrcard">
                <span class="dl-qr" role="img" aria-label="{{ __('public_download.hero.qr_label') }}">{!! \App\Support\QrSvg::brandedSvg(url('/download')) !!}<img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="40" height="40"></span>
                <figcaption><b>{{ __('public_download.hero.qr_title') }}</b><span>{{ __('public_download.hero.qr_note') }}</span></figcaption>
            </figure>
        </div>
    </div>
</section>

{{-- Install steps ─────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dl-install-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_download.install.eyebrow') }}</p>
            <h2 id="dl-install-title">{{ __('public_download.install.title') }}</h2>
            <p class="hm-sub">{{ __('public_download.install.sub') }}</p>
        </div>
        <ol class="hm-grid4 ex-feats dl-steps" role="list">
            @foreach (['download' => 'download', 'allow' => 'shield-check', 'open' => 'smartphone', 'sign_in' => 'id-card'] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3><span class="dl-n">{{ $loop->iteration }}</span> {{ __('public_download.install.steps.' . $k . '.title') }}</h3>
                    <p>{{ __('public_download.install.steps.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ol>
        <p class="dl-callout">{!! $exIcon('info', 18) !!}<span>{{ __('public_download.install.play_note') }}</span></p>
    </div>
</section>

{{-- Features ──────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dl-feat-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_download.features.eyebrow') }}</p>
            <h2 id="dl-feat-title">{{ __('public_download.features.title') }}</h2>
        </div>
        <ul class="ex-feats ex-feats--3" role="list">
            @foreach ([
                'health_id' => 'id-card', 'records' => 'file-check', 'consent' => 'key-round',
                'family' => 'users', 'appointments' => 'calendar', 'finders' => 'pill',
            ] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_download.features.cards.' . $k . '.title') }}</h3>
                    <p>{{ __('public_download.features.cards.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@include('public._download_flows')

{{-- Verify ────────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dl-verify-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_download.verify.eyebrow') }}</p>
            <h2 id="dl-verify-title">{{ __('public_download.verify.title') }}</h2>
            <p class="hm-sub">{{ __('public_download.verify.sub') }}</p>
        </div>
        <div class="ex-card dl-verify">
            <p class="dl-sha__label">{{ __('public_download.verify.label') }}</p>
            <div class="dl-sha">
                <code id="dl-sha">{{ $apk['sha256'] }}</code>
                <button type="button" class="hm-btn hm-btn--sm" data-dl-copy="dl-sha" data-copied="{{ __('public_download.verify.copied') }}" hidden>{!! $exIcon('check', 16) !!}<span>{{ __('public_download.verify.copy') }}</span></button>
            </div>
            <h3 class="ex-h3 dl-how">{{ __('public_download.verify.how_title') }}</h3>
            <ul class="dl-howlist" role="list">
                <li><code>{{ __('public_download.verify.how_windows') }}</code></li>
                <li><code>{{ __('public_download.verify.how_mac') }}</code></li>
                <li>{{ __('public_download.verify.how_android') }}</li>
            </ul>
        </div>
    </div>
</section>

{{-- FAQ ───────────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-faq-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_download.faq.eyebrow') }}</p>
            <h2 id="ex-faq-title">{{ __('public_download.faq.title') }}</h2>
        </div>
        <div class="ex-faq">
            @foreach (__('public_download.faq.categories') as $cat)
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
                <h2>{{ __('public_download.hero.title_1') }}</h2>
                <p>{{ __('public_download.hero.version', ['version' => $apk['version'], 'build' => $apk['build']]) }} · {{ __('public_download.hero.size', ['size' => $apkSizeMb]) }}</p>
            </div>
            <div class="hm-actions dl-actions">
                <a class="hm-btn hm-btn--light" href="{{ route('mobile.app') }}">{{ __('public_download.hero.cta_web') }}</a>
                <a class="hm-btn hm-btn--white" href="{{ $apkUrl }}" download="opescare.apk">{!! $exIcon('download', 18) !!}{{ __('public_download.hero.cta_download') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
