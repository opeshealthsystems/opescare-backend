@extends('layouts.portal')
@section('title', __('healthorg.signals_title') . ' — OpesCare')
@section('sidebar_role_badge')
<div class="sidebar-role-badge"><i data-lucide="heart-handshake"></i> {{ __('public.healthorg_portal.role_badge', [], app()->getLocale()) ?: 'Health Org' }}</div>
@endsection
@section('sidebar_user_role', __('public.healthorg_portal.role_label', [], app()->getLocale()) ?: 'Health Org Admin')
@section('sidebar_nav')@include('portals.healthorg._sidebar')@endsection
@section('breadcrumb_home', __('public.healthorg_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Health Org Portal')
@section('breadcrumb_home_url', route('portals.healthorg.dashboard'))
@section('breadcrumb_section', __('healthorg.signals_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('healthorg.signals_title') }}</h1>
        <p class="page-subtitle">{{ __('healthorg.signals_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

@if($signals->isEmpty())
<div class="panel"><div class="empty-state"><i data-lucide="activity"></i>
    <h3>{{ __('healthorg.no_signals') }}</h3><p>{{ __('healthorg.no_signals_body') }}</p></div></div>
@else
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('healthorg.col_signal') }}</th><th>{{ __('healthorg.col_severity') }}</th>
                <th>{{ __('healthorg.field_status') }}</th><th>{{ __('healthorg.col_detected') }}</th>
                <th>{{ __('healthorg.col_review') }}</th>
            </tr></thead>
            <tbody>
                @foreach($signals as $signal)
                <tr>
                    <td data-label="{{ __('healthorg.col_signal') }}">
                        <span class="td-strong">@enum($signal->signal_type){{ $signal->condition_code ? ' · ' . $signal->condition_code : '' }}</span>
                        @if($signal->increase_percentage)<div class="td-muted">+{{ rtrim(rtrim((string) $signal->increase_percentage, '0'), '.') }}% {{ __('healthorg.vs_baseline') }}</div>@endif
                    </td>
                    <td data-label="{{ __('healthorg.col_severity') }}">
                        <span class="badge badge-{{ match($signal->severity ?? '') { 'critical' => 'danger', 'high' => 'warning', default => 'neutral' } }}">@enum($signal->severity, 'severity')</span>
                    </td>
                    <td data-label="{{ __('healthorg.field_status') }}">
                        <span class="badge badge-{{ match($signal->status ?? '') { 'confirmed','escalated' => 'danger', 'reviewed' => 'warning', 'resolved' => 'success', 'dismissed' => 'neutral', default => 'primary' } }}">@enum($signal->status)</span>
                    </td>
                    <td data-label="{{ __('healthorg.col_detected') }}"><span class="td-muted">{{ $signal->detected_at?->isoFormat('lll') ?? $signal->created_at?->isoFormat('lll') ?? '—' }}</span></td>
                    <td class="row-actions">
                        @if(!in_array($signal->status, ['resolved','dismissed']))
                        <form method="POST" action="{{ route('portals.healthorg.signals.review', $signal->id) }}" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                            @csrf
                            <select name="action" class="form-control" style="width:auto;" required>
                                <option value="confirm">{{ __('healthorg.act_confirm') }}</option>
                                <option value="escalate">{{ __('healthorg.act_escalate') }}</option>
                                <option value="resolve">{{ __('healthorg.act_resolve') }}</option>
                                <option value="dismiss">{{ __('healthorg.act_dismiss') }}</option>
                            </select>
                            <input type="text" name="comment" class="form-control" style="width:160px;" placeholder="{{ __('healthorg.comment_ph') }}" maxlength="1000">
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="gavel"></i> {{ __('healthorg.review') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $signals->links() }}</div>
</div>
@endif

@endsection
