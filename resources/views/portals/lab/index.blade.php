@extends('layouts.portal')

@section('title', __('lab_desk.title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_desk.worklist_title'))

@php
    $urgencyBadge = fn (string $u) => 'badge badge-' . match ($u) {
        'stat'   => 'danger',
        'urgent' => 'warning',
        default  => 'neutral',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('lab_desk.worklist_title') }}</h1>
        <p class="page-subtitle">{{ __('lab_desk.worklist_subtitle') }}</p>
    </div>
    @capability('lab_results', 'sample_collection')
    <a href="{{ route('portals.lab.collection') }}" class="btn btn-secondary">
        <i data-lucide="test-tube-2"></i> {{ __('lab_desk.nav_collection') }}
    </a>
    @endcapability
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Exact Health ID lookup. No name search on this desk. ─────────────── --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.lab.index') }}" class="form-grid">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="form-group">
                <label class="form-label" for="lab-health-id">{{ __('lab_desk.health_id') }}</label>
                <input type="text" id="lab-health-id" name="health_id" class="form-control"
                       value="{{ $healthId }}" autocomplete="off" maxlength="64">
                <div class="form-hint">{{ __('lab_desk.health_id_hint') }}</div>
            </div>
            <div class="form-group row-actions-inline">
                <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> <span>{{ __('lab_desk.lookup') }}</span></button>
                @if($healthId)
                    <a href="{{ route('portals.lab.index', ['status' => $status]) }}" class="btn btn-secondary">{{ __('lab_desk.clear') }}</a>
                @endif
            </div>
        </form>
        @if($healthId)
            <p class="td-muted">{{ __('lab_desk.filtered_by', ['health_id' => $healthId]) }}</p>
        @endif
    </div>
</div>

{{-- ── The five columns, as tabs ───────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <nav class="row-actions-inline" aria-label="{{ __('lab_desk.worklist_title') }}">
            @foreach($statuses as $s)
                <a href="{{ route('portals.lab.index', array_filter(['status' => $s, 'health_id' => $healthId])) }}"
                   class="btn btn-sm {{ $s === $status ? 'btn-primary' : 'btn-secondary' }}">
                    {{ __('lab_desk.status.' . $s) }}
                    <span class="badge badge-neutral">{{ $counts[$s] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if($orders->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="flask-conical"></i></div>
                <h3>{{ __('lab_desk.empty_worklist') }}</h3>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_desk.col_patient') }}</th>
                        <th>{{ __('lab_desk.col_test') }}</th>
                        <th>{{ __('lab_desk.col_urgency') }}</th>
                        <th>{{ __('lab_desk.col_ordered_by') }}</th>
                        <th>{{ __('lab_desk.col_ordered_at') }}</th>
                        <th>{{ __('lab_desk.col_results') }}</th>
                        <th class="row-actions">{{ __('lab_desk.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $u     = $order->deskUrgency();
                    @endphp
                    <tr>
                        <td data-label="{{ __('lab_desk.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_test') }}">
                            <span class="td-strong">{{ $order->test_name }}</span>
                            @if($order->test_code)<div class="td-muted">{{ $order->test_code }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_urgency') }}">
                            <span class="{{ $urgencyBadge($u) }}">{{ __('lab_desk.urgency.' . $u) }}</span>
                        </td>
                        <td data-label="{{ __('lab_desk.col_ordered_by') }}" class="td-muted">{{ $order->orderedBy?->name ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.col_ordered_at') }}" class="td-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.col_results') }}" class="td-muted">{{ $order->results_count }}</td>
                        <td class="row-actions" data-label="{{ __('lab_desk.col_open') }}">
                            <a href="{{ route('portals.lab.show', $order->id) }}" class="btn btn-secondary btn-sm">
                                <i data-lucide="eye"></i> {{ __('lab_desk.open_order') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="panel-body">{{ $orders->links() }}</div>
        @endif
    @endif
</div>

@endsection
