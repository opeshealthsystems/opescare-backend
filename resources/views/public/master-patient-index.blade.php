{{--
    /master-patient-index — what an MPI is, why duplicates happen, how OpesCare
    matches and merges. Home-page design (layouts.hm).

    Every claim maps to code; keep it that way:
      - registration check: Patient::findExistingIdentity() (national ID, phone hash,
        name + DOB normalised; never merges), used by MobileAuthController,
        MobileFamilyController, FamilyController, PatientProfileCompletionController,
        and PatientIdentityService (front desk, DeskPatientController)
      - blocking, scoring (agreement / contradiction), tiers, threshold 60:
        App\Modules\MasterPatientIndex\Services\MasterPatientIndexService
        (name block is first+last as given, accent-folded — a swapped order is NOT
        matched on name; the page says so)
      - merge / unmerge (FKs discovered at run time, alias, survivorship, LIFO,
        refused after purge): PatientMergeService
      - review desk, survivor + reason required, raise by hand: AdminIdentityReviewController
        (/admin/mpi, platform.admin)
      - scans: routes/console.php mpi:detect nightly (--since="2 days") + weekly
--}}
@extends('layouts.hm')

@section('title', __('public_mpi.meta_title'))
@section('description', __('public_mpi.meta_desc'))

@push('schema')
    @include('public.explainer._schema', ['ns' => 'public_mpi', 'route' => 'public.master-patient-index'])
@endpush
@push('scripts')
    <script src="{{ asset('js/explainer-flow.js') }}?v={{ @filemtime(public_path('js/explainer-flow.js')) ?: '1' }}" @cspNonce defer></script>
@endpush

@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp

@section('content')
{{-- Hero ──────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="ex-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <nav class="ex-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_mpi.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('git-merge', 15) !!}{{ __('public_mpi.hero.kicker') }}</span>
            <h1 id="ex-title">{{ __('public_mpi.hero.title_1') }}<br><span>{{ __('public_mpi.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_mpi.hero.lead') }}</p>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.organization') }}">{!! $exIcon('building-2', 18) !!}{{ __('public_mpi.hero.cta_register') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('public.solutions.index') }}">{{ __('public_mpi.hero.cta_solutions') }}</a>
            </div>
        </div>
        <div class="ex-hvis">
            {{-- Illustrative sample records, labelled as such. --}}
            <div class="hx-mpi" role="img" aria-label="{{ __('public_mpi.vis.label') }}">
                <div class="hx-recs" aria-hidden="true">
                    <div class="hx-rec"><header>{{ __('public_mpi.vis.rec1_src') }}</header><b>Amara Dongmo</b><span>F · 12/03/1991</span></div>
                    <div class="hx-rec"><header>{{ __('public_mpi.vis.rec2_src') }}</header><b>Amara D. Dongmo</b><span>F · 12/03/1991</span></div>
                    <div class="hx-rec"><header>{{ __('public_mpi.vis.rec3_src') }}</header><b>DONGMO Amara</b><span>F · 1991</span></div>
                </div>
                <div class="hx-arrow" aria-hidden="true">{!! $exIcon('git-merge', 22) !!}</div>
                <div class="hx-gold" aria-hidden="true"><small>{!! $exIcon('user-check', 14) !!}{{ __('public_mpi.vis.one') }}</small><b>Amara Dongmo</b><span class="hx-id">CM-HID 0000 0000 0000</span>
                    <dl>
                        <div><dt>{{ __('public_mpi.vis.linked') }}</dt><dd>3</dd></div>
                        <div><dt>{{ __('public_mpi.vis.facilities') }}</dt><dd>3</dd></div>
                        <div><dt>{{ __('public_mpi.vis.reviewed') }}</dt><dd>{{ __('public_mpi.vis.reviewed_v') }}</dd></div>
                        <div><dt>{{ __('public_mpi.vis.merge') }}</dt><dd>{{ __('public_mpi.vis.merge_v') }}</dd></div>
                    </dl>
                </div>
            </div>
            <p class="ex-sample">{{ __('public_mpi.vis.sample') }}</p>
        </div>
    </div>
</section>

{{-- What it is + why duplicates happen ────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-what-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_mpi.what.eyebrow') }}</p>
            <h2 id="ex-what-title">{{ __('public_mpi.what.title') }}</h2>
            <p class="hm-sub">{{ __('public_mpi.what.sub') }}</p>
        </div>
        <h3 class="ex-h3">{{ __('public_mpi.what.causes_title') }}</h3>
        <ul class="ex-causes" role="list">
            @foreach (['order' => 'users', 'spelling' => 'languages', 'dob' => 'calendar', 'facilities' => 'building-2', 'phones' => 'smartphone'] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_mpi.what.causes.' . $k . '.title') }}</h3>
                    <p>{{ __('public_mpi.what.causes.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- How matching works ────────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="ex-match-title">
    <div class="hm-wrap hm-split ex-split-top">
        <div>
            <p class="hm-eyebrow">{{ __('public_mpi.match.eyebrow') }}</p>
            <h2 id="ex-match-title">{{ __('public_mpi.match.title') }}</h2>
            <p class="hm-sub">{{ __('public_mpi.match.sub') }}</p>
        </div>
        <ul class="hm-list ex-list-tight" role="list">
            @foreach (['check' => 'search', 'blocking' => 'network', 'score' => 'fingerprint', 'limit' => 'eye-off', 'scans' => 'clock'] as $k => $icon)
                <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __('public_mpi.match.items.' . $k . '.title') }}</b><span>{{ __('public_mpi.match.items.' . $k . '.desc') }}</span></div></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- How it works (animated) ───────────────────────────────────────────── --}}
@include('public.explainer._flow', [
    'ns' => 'public_mpi',
    'icons' => ['building-2', 'search', 'users', 'git-merge', 'undo-2'],
])

{{-- How OpesCare makes it possible ────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-how-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_mpi.how.eyebrow') }}</p>
            <h2 id="ex-how-title">{{ __('public_mpi.how.title') }}</h2>
            <p class="hm-sub">{{ __('public_mpi.how.sub') }}</p>
        </div>
        <ul class="ex-feats ex-feats--3" role="list">
            @foreach (['check' => 'search', 'cross' => 'network', 'reasons' => 'fingerprint', 'human' => 'user-check', 'merge' => 'git-merge', 'alias' => 'link'] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_mpi.how.cards.' . $k . '.title') }}</h3>
                    <p>{{ __('public_mpi.how.cards.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@include('public.explainer._means', [
    'ns' => 'public_mpi',
    'pIcons' => ['id-card', 'qr-code', 'user-check'],
    'fIcons' => ['search', 'flask-conical', 'scroll-text'],
])

@include('public.explainer._faq_cta', ['ns' => 'public_mpi'])
@endsection
