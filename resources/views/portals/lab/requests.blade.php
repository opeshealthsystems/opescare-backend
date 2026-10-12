@extends('layouts.portal')

@section('title', __('lab_manager.requests_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_manager.requests_title'))

@php
    $priorityBadge = fn (string $p) => 'badge badge-' . match ($p) {
        'stat'   => 'danger',
        'urgent' => 'warning',
        default  => 'neutral',
    };
    $priorityKey = fn (?string $p) => match (strtolower(trim((string) $p))) {
        'stat', 'immediate', 'emergency' => 'stat',
        'urgent', 'priority'             => 'urgent',
        default                          => 'routine',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('lab_manager.requests_title') }}</h1>
        <p class="page-subtitle">{{ __('lab_manager.requests_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.lab_manager.dashboard') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('lab_manager.nav_dashboard') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

<div class="panel">
    <div class="panel-header">
        <nav class="row-actions-inline" aria-label="{{ __('lab_manager.requests_title') }}">
            @foreach(['submitted', 'approved', 'declined', 'cancelled'] as $s)
                <a href="{{ route('portals.lab_manager.requests', ['status' => $s]) }}"
                   class="btn btn-sm {{ $s === $status ? 'btn-primary' : 'btn-secondary' }}">
                    {{ __('lab_manager.request_status.' . $s) }}
                    <span class="badge badge-neutral">{{ $counts[$s] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if($requests->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="inbox"></i></div>
                <h3>{{ __('lab_manager.requests_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_manager.col_patient') }}</th>
                        <th>{{ __('lab_manager.col_test') }}</th>
                        <th>{{ __('lab_manager.col_priority') }}</th>
                        <th>{{ __('lab_manager.col_requested') }}</th>
                        <th>{{ __('lab_manager.col_notes') }}</th>
                        @if($status === 'submitted')
                            <th class="row-actions">{{ __('lab_manager.col_actions') }}</th>
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
                        <td data-label="{{ __('lab_manager.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_manager.col_test') }}">
                            <span class="td-strong">{{ $req->test_name }}</span>
                            @if($req->test_code)<div class="td-muted">{{ $req->test_code }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_manager.col_priority') }}"><span class="{{ $priorityBadge($pk) }}">{{ __('lab_manager.priority.' . $pk) }}</span></td>
                        <td data-label="{{ __('lab_manager.col_requested') }}" class="td-muted">{{ $req->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_manager.col_notes') }}" class="td-muted">{{ $req->clinical_notes ?: '—' }}</td>
                        @if($status === 'submitted')
                        <td class="row-actions" data-label="{{ __('lab_manager.col_actions') }}">
                            <form method="POST" action="{{ route('portals.lab_manager.requests.approve', $req->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('lab_manager.approve') }}</button>
                            </form>
                            <details>
                                <summary class="btn btn-secondary btn-sm"><i data-lucide="x"></i> {{ __('lab_manager.decline') }}</summary>
                                <form method="POST" action="{{ route('portals.lab_manager.requests.decline', $req->id) }}" class="form-grid">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label form-label-required" for="decline-reason-{{ $req->id }}">{{ __('lab_manager.decline_reason') }}</label>
                                        <input type="text" id="decline-reason-{{ $req->id }}" name="reason" class="form-control" required minlength="3" maxlength="1000">
                                    </div>
                                    <div class="row-actions-inline">
                                        <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x"></i> {{ __('lab_manager.submit_decline') }}</button>
                                    </div>
                                </form>
                            </details>
                        </td>
                        @else
                        <td data-label="{{ __('lab_manager.col_decision') }}" class="td-muted">
                            @if($req->reviewed_at)
                                {{ __('lab_manager.decided_by', ['name' => $req->reviewer?->name ?? __('lab_desk.unknown'), 'at' => $req->reviewed_at->translatedFormat('d M Y H:i')]) }}
                            @endif
                            @if($req->decision_reason)<div>{{ $req->decision_reason }}</div>@endif
                            @if($req->lab_order_id)
                                <div><a href="{{ route('portals.lab.show', $req->lab_order_id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('lab_manager.order_raised') }}</a></div>
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($status === 'submitted')
            <div class="panel-body"><p class="form-hint">{{ __('lab_manager.approve_hint') }}</p></div>
        @endif
    @endif
</div>

@endsection
