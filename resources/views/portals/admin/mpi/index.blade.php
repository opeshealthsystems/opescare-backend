@extends('layouts.portal')
@section('title', __('mpi.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('mpi.title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('mpi.title') }}</h1>
        <p class="od-page__sub">{{ __('mpi.subtitle', ['count' => number_format($registerSize)]) }}</p>
    </div>
    <div class="od-row">
        <form method="POST" action="{{ route('portals.admin.mpi.detect') }}" class="oda-inline-form">
            @csrf
            <button type="submit" class="od-btn od-btn--secondary">
                <i data-lucide="scan-search"></i> {{ __('mpi.btn_detect') }}
            </button>
        </form>
    </div>
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="alert-circle"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="alert-circle"></i><div class="oda-ga-alert__body">{{ $errors->first() }}</div></div>@endif

<nav class="od-tabs" aria-label="{{ __('mpi.title') }}">
    @foreach(['pending' => ['git-merge', 'pending'], 'merged' => ['check-check', 'ready'], 'rejected' => ['x-circle', 'urgent']] as $key => [$icon, $dot])
        <a href="{{ route('portals.admin.mpi.index', ['tab' => $key]) }}" class="od-tab {{ $tab === $key ? 'is-active' : '' }}" @if($tab === $key) aria-current="page" @endif>
            <span class="od-tab__dot od-tab__dot--{{ $dot }}"></span>
            <span>{{ __('mpi.tab_' . $key) }}</span>
            <span class="od-tab__count">({{ $counts[$key] }})</span>
        </a>
    @endforeach
</nav>

@if($tab === 'pending')
    <div class="oda-ga-alert oda-ga-alert--warn">
        <i data-lucide="shield-alert"></i>
        <div class="oda-ga-alert__body">{{ __('mpi.merge_warning') }}</div>
    </div>

    {{--
        The patient at the counter holding two cards is evidence the scan
        cannot see. An operator names both Health IDs and the pair lands on
        the desk whatever it scores; the chips then show where the machine
        agrees and where it does not.
    --}}
    <details class="od-card" {{ old('health_id_a') ? 'open' : '' }}>
        <summary class="od-card__head oda-ga-summary">
            <span class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="git-compare"></i> {{ __('mpi.raise_title') }}</span>
        </summary>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.mpi.candidates.raise') }}" class="oda-form oda-form--3">
                @csrf
                <label class="oda-label" for="raise-a">
                    <span class="oda-label__text">{{ __('mpi.raise_a') }}</span>
                    <input id="raise-a" name="health_id_a" value="{{ old('health_id_a') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required class="od-input oda-mono">
                </label>
                <label class="oda-label" for="raise-b">
                    <span class="oda-label__text">{{ __('mpi.raise_b') }}</span>
                    <input id="raise-b" name="health_id_b" class="od-input oda-mono" value="{{ old('health_id_b') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required>
                </label>
                <label class="oda-label" for="raise-note">
                    <span class="oda-label__text">{{ __('mpi.raise_note') }}</span>
                    <input id="raise-note" name="note" class="od-input" value="{{ old('note') }}" maxlength="1000" placeholder="{{ __('mpi.raise_note_placeholder') }}">
                </label>
                <div class="oda-form__actions">
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="git-compare"></i> {{ __('mpi.btn_raise') }}</button>
                </div>
            </form>
        </div>
    </details>

    <div class="od-tabs">
        <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending']) }}" class="od-tab {{ $tier ? '' : 'is-active' }}">{{ __('mpi.tier_all') }}</a>
        @foreach(['deterministic' => 'urgent', 'probable' => 'pending', 'possible' => 'normal'] as $t => $dot)
            <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending', 'tier' => $t]) }}" class="od-tab {{ $tier === $t ? 'is-active' : '' }}">
                <span class="od-tab__dot od-tab__dot--{{ $dot }}"></span>
                {{ __('mpi.tier_' . $t) }} <span class="od-tab__count">({{ $tierCounts[$t] }})</span>
            </a>
        @endforeach
    </div>
@endif

@if($candidates->isEmpty())
    <section class="od-card">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="{{ $tab === 'pending' ? 'check-circle' : 'inbox' }}"></i></span>
            <p class="od-empty__text">{{ __('mpi.empty_' . $tab) }}</p>
        </div>
    </section>
@else
    <div>
    @foreach($candidates as $c)
        @include('portals.admin.mpi._candidate', ['c' => $c, 'footprint' => $footprint])
    @endforeach
    </div>

    @if($candidates->hasPages())<div>{{ $candidates->links() }}</div>@endif
@endif
</div>
@endsection
