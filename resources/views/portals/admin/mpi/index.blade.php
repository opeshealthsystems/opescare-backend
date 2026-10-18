@extends('layouts.portal')
@section('title', __('mpi.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('mpi.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('mpi.title') }}</h1>
        <p class="page-subtitle">{{ __('mpi.subtitle', ['count' => number_format($registerSize)]) }}</p>
    </div>
    <form method="POST" action="{{ route('portals.admin.mpi.detect') }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="scan-search"></i> {{ __('mpi.btn_detect') }}
        </button>
    </form>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="dr-tabs">
    @foreach(['pending' => 'git-merge', 'merged' => 'check-check', 'rejected' => 'x-circle'] as $key => $icon)
        <a href="{{ route('portals.admin.mpi.index', ['tab' => $key]) }}" class="dr-tab {{ $tab === $key ? 'active' : '' }}">
            <i data-lucide="{{ $icon }}"></i>
            <span>{{ __('mpi.tab_' . $key) }}</span>
            <span class="dr-tab-count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@if($tab === 'pending')
    <div class="alert alert-warning mb-6">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('mpi.merge_warning') }}</div>
    </div>

    {{--
        The patient at the counter holding two cards is evidence the scan
        cannot see. An operator names both Health IDs and the pair lands on
        the desk whatever it scores; the chips then show where the machine
        agrees and where it does not.
    --}}
    <details class="panel mb-6" {{ old('health_id_a') ? 'open' : '' }}>
        <summary class="btn btn-secondary btn-sm"><i data-lucide="git-compare"></i> {{ __('mpi.raise_title') }}</summary>
        <form method="POST" action="{{ route('portals.admin.mpi.candidates.raise') }}" class="dr-actions-row dr-raise-row">
            @csrf
            <div>
                <label class="form-label" for="raise-a">{{ __('mpi.raise_a') }}</label>
                <input id="raise-a" name="health_id_a" value="{{ old('health_id_a') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required class="form-control adm-mono">
            </div>
            <div>
                <label class="form-label" for="raise-b">{{ __('mpi.raise_b') }}</label>
                <input id="raise-b" name="health_id_b" class="form-control adm-mono" value="{{ old('health_id_b') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required>
            </div>
            <div class="adm-grow">
                <label class="form-label" for="raise-note">{{ __('mpi.raise_note') }}</label>
                <input id="raise-note" name="note" class="form-control" value="{{ old('note') }}" maxlength="1000" placeholder="{{ __('mpi.raise_note_placeholder') }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="git-compare"></i> {{ __('mpi.btn_raise') }}</button>
        </form>
    </details>

    <div class="dr-reasonbar">
        <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending']) }}" class="dr-reason-chip {{ $tier ? '' : 'active' }}">{{ __('mpi.tier_all') }}</a>
        @foreach(['deterministic', 'probable', 'possible'] as $t)
            <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending', 'tier' => $t]) }}" class="dr-reason-chip {{ $tier === $t ? 'active' : '' }}">
                {{ __('mpi.tier_' . $t) }} ({{ $tierCounts[$t] }})
            </a>
        @endforeach
    </div>
@endif

@if($candidates->isEmpty())
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="{{ $tab === 'pending' ? 'check-circle' : 'inbox' }}"></i>
            <p>{{ __('mpi.empty_' . $tab) }}</p>
        </div>
    </div>
@else
    @foreach($candidates as $c)
        @include('portals.admin.mpi._candidate', ['c' => $c, 'footprint' => $footprint])
    @endforeach

    <div class="mt-6">{{ $candidates->links() }}</div>
@endif

@endsection
