@extends('layouts.portal')

@section('title', __('lab_manager.requests_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_manager.requests_title'))

@php
    $priorityPill = fn (string $p) => match ($p) { 'stat' => 'od-pill--stat', 'urgent' => 'od-pill--urgent', default => 'od-pill--normal' };
    $statusDot    = fn (string $s) => match ($s) { 'submitted' => 'pending', 'approved' => 'ready', 'declined' => 'urgent', default => 'normal' };
    $priorityKey = fn (?string $p) => match (strtolower(trim((string) $p))) {
        'stat', 'immediate', 'emergency' => 'stat',
        'urgent', 'priority'             => 'urgent',
        default                          => 'routine',
    };
@endphp

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('lab_manager.requests_title') }}</h1>
        <p class="od-page__sub">{{ __('lab_manager.requests_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.lab_manager.dashboard') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('lab_manager.nav_dashboard') }}
    </a>
</header>

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><ul class="odl-errlist">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div>
@endif

<section class="od-card">
    <div class="od-card__body odl-pad-top">
    <nav class="od-tabs" aria-label="{{ __('lab_manager.requests_title') }}">
        @foreach(['submitted', 'approved', 'declined', 'cancelled'] as $s)
            <a href="{{ route('portals.lab_manager.requests', ['status' => $s]) }}"
               class="od-tab {{ $s === $status ? 'is-active' : '' }}" @if($s === $status) aria-current="page" @endif>
                <span class="od-tab__dot od-tab__dot--{{ $statusDot($s) }}"></span>{{ __('lab_manager.request_status.' . $s) }} <span class="od-tab__count">({{ $counts[$s] ?? 0 }})</span>
            </a>
        @endforeach
    </nav>

    @if($requests->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="inbox"></i></span>
            <p class="od-empty__title">{{ __('lab_manager.requests_empty') }}</p>
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('lab_manager.col_patient') }}</th>
                        <th>{{ __('lab_manager.col_test') }}</th>
                        <th>{{ __('lab_manager.col_priority') }}</th>
                        <th>{{ __('lab_manager.col_requested') }}</th>
                        <th>{{ __('lab_manager.col_notes') }}</th>
                        @if($status === 'submitted')
                            <th class="od-table__actions">{{ __('lab_manager.col_actions') }}</th>
                        @else
                            <th>{{ __('lab_manager.col_decision') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach($requests as $req)
                    @php
                        $p     = $req->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $pk    = $priorityKey($req->priority);
                    @endphp
                    <tr>
                        <td class="od-table__num" data-label="#">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('lab_manager.col_patient') }}">
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div>
                            </div>
                        </td>
                        <td data-label="{{ __('lab_manager.col_test') }}">
                            <div class="od-table__name">{{ $req->test_name }}</div>
                            @if($req->test_code)<div class="od-table__sub">{{ $req->test_code }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_manager.col_priority') }}"><span class="od-pill od-pill--dot {{ $priorityPill($pk) }}">{{ __('lab_manager.priority.' . $pk) }}</span></td>
                        <td data-label="{{ __('lab_manager.col_requested') }}" class="od-muted">{{ $req->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_manager.col_notes') }}" class="od-muted">{{ $req->clinical_notes ?: '—' }}</td>
                        @if($status === 'submitted')
                        <td class="od-table__actions odl-rowactions" data-label="{{ __('lab_manager.col_actions') }}">
                            <form method="POST" action="{{ route('portals.lab_manager.requests.approve', $req->id) }}">
                                @csrf
                                <button type="submit" class="od-btn od-btn--row">{{ __('lab_manager.approve') }}</button>
                            </form>
                            <details class="odl-details">
                                <summary class="od-btn od-btn--secondary od-btn--sm">{{ __('lab_manager.decline') }}</summary>
                                <form method="POST" action="{{ route('portals.lab_manager.requests.decline', $req->id) }}" class="odl-form">
                                    @csrf
                                    <label class="odl-label" for="decline-reason-{{ $req->id }}">{{ __('lab_manager.decline_reason') }}</label>
                                    <input type="text" id="decline-reason-{{ $req->id }}" name="reason" class="od-input" required minlength="3" maxlength="1000">
                                    <button type="submit" class="od-btn od-btn--danger"><i data-lucide="x"></i> {{ __('lab_manager.submit_decline') }}</button>
                                </form>
                            </details>
                        </td>
                        @else
                        <td data-label="{{ __('lab_manager.col_decision') }}" class="od-muted">
                            @if($req->reviewed_at)
                                {{ __('lab_manager.decided_by', ['name' => $req->reviewer?->name ?? __('lab_desk.unknown'), 'at' => $req->reviewed_at->translatedFormat('d M Y H:i')]) }}
                            @endif
                            @if($req->decision_reason)<div>{{ $req->decision_reason }}</div>@endif
                            @if($req->lab_order_id)
                                <div><a href="{{ route('portals.lab.show', $req->lab_order_id) }}" class="od-btn od-btn--row">{{ __('lab_manager.order_raised') }}</a></div>
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($status === 'submitted')
            <p class="od-muted odl-small odl-foot">{{ __('lab_manager.approve_hint') }}</p>
        @endif
    @endif
    </div>
</section>

</div>
@endsection
