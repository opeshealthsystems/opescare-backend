@extends('layouts.portal')

@section('title', __('lab_desk.collection_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.collection'))
@section('breadcrumb_section', __('lab_desk.collection_title'))

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
        <h1 class="page-title">{{ __('lab_desk.collection_title') }}</h1>
        <p class="page-subtitle">{{ __('lab_desk.collection_subtitle') }}</p>
    </div>
    @capability('lab_results')
    <a href="{{ route('portals.lab.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('lab_desk.back_to_worklist') }}
    </a>
    @endcapability
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

<div class="panel">
    @if($orders->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="test-tube-2"></i></div>
                <h3>{{ __('lab_desk.empty_collection') }}</h3>
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
                        <th>{{ __('lab_desk.col_ordered_at') }}</th>
                        <th>{{ __('lab_desk.specimen_type') }}</th>
                        <th class="row-actions">{{ __('lab_desk.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    @php
                        $p        = $order->patient;
                        $pName    = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $u        = $order->deskUrgency();
                        $specimen = $order->getAttribute('dictionary_specimen');
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
                        <td data-label="{{ __('lab_desk.col_urgency') }}"><span class="{{ $urgencyBadge($u) }}">{{ __('lab_desk.urgency.' . $u) }}</span></td>
                        <td data-label="{{ __('lab_desk.col_ordered_at') }}" class="td-muted">{{ $order->ordered_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.specimen_type') }}">
                            <input type="text" form="collect-{{ $order->id }}" name="specimen_type" class="form-control"
                                   value="{{ old('specimen_type', $specimen) }}" maxlength="120"
                                   placeholder="{{ $specimen ? '' : __('lab_desk.specimen_unknown') }}">
                        </td>
                        <td class="row-actions" data-label="{{ __('lab_desk.col_actions') }}">
                            <form id="collect-{{ $order->id }}" method="POST" action="{{ route('portals.lab.collect', $order->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i data-lucide="check"></i> {{ __('lab_desk.mark_collected') }}
                                </button>
                            </form>
                            {{-- The collector who sees the unusable tube refuses it here (routes/lab_manager.php). --}}
                            <details>
                                <summary class="btn btn-secondary btn-sm"><i data-lucide="ban"></i> {{ __('lab_manager.reject') }}</summary>
                                <form method="POST" action="{{ route('portals.lab_manager.reject', $order->id) }}" class="form-grid">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label form-label-required" for="reject-reason-{{ $order->id }}">{{ __('lab_manager.reject_reason') }}</label>
                                        <input type="text" id="reject-reason-{{ $order->id }}" name="reason" class="form-control" required minlength="3" maxlength="1000">
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="ban"></i> {{ __('lab_manager.submit_reject') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-body"><p class="form-hint">{{ __('lab_desk.specimen_hint') }}</p></div>
        @if($orders->hasPages())
            <div class="panel-body">{{ $orders->links() }}</div>
        @endif
    @endif
</div>

@endsection
