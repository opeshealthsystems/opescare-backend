@extends('layouts.portal')

@section('title', $task->title . ' — OpesCare')

@section('breadcrumb_home', __('tasks.admin_title'))
@section('breadcrumb_home_url', route('portals.admin.tasks'))
@section('breadcrumb_section', __('admin_desk.task_detail'))

@section('content')

@php
    $closed = in_array($task->status, ['completed', 'cancelled'], true);
    $sc = ['escalated' => 'danger', 'acknowledged' => 'info', 'completed' => 'success', 'cancelled' => 'neutral'][$task->status] ?? 'warning';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $task->title }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-neutral">{{ __('tasks.type_' . $task->task_type) }}</span>
            <span class="badge badge-{{ $sc }}">@enum($task->status)</span>
            <span class="badge badge-warning">@enum($task->priority ?: 'normal', 'priority')</span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.tasks') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="adm-detail-grid">
<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="align-left"></i> {{ __('tasks.description') }}</h3></div>
        <div class="panel-body adm-pre">{{ $task->description ?: __('admin_desk.no_description') }}</div>
    </div>

    @unless($closed)
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</h3></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.tasks.reassign', $task->uuid) }}" class="adm-inline-flex">
                @csrf
                <select name="assigned_to" class="form-control adm-w-auto" required aria-label="{{ __('tasks.assign_to') }}">
                    @foreach($staff as $s)<option value="{{ $s->id }}" @selected((string) $s->id === (string) $task->assigned_to)>{{ $s->name }}</option>@endforeach
                </select>
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</button>
            </form>
        </div>
    </div>
    @endunless
</div>

<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('admin_desk.details') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('tasks.assignee') }}</td><td class="kv-strong">{{ $assignee?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('tasks.col_due') }}</td><td class="kv-strong">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.created_at') }}</td><td class="kv-strong">{{ $task->created_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.acknowledged_at') }}</td><td class="kv-strong">{{ $task->acknowledged_at ? \Illuminate\Support\Carbon::parse($task->acknowledged_at)->isoFormat('lll') : '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.completed_at') }}</td><td class="kv-strong">{{ $task->completed_at ? \Illuminate\Support\Carbon::parse($task->completed_at)->isoFormat('lll') : '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.reference') }}</td><td class="kv-strong adm-mono">{{ $task->uuid }}</td></tr>
            </table>
        </div>
    </div>

    @unless($closed)
    <div class="panel">
        <div class="panel-body adm-row-actions">
            <form method="POST" action="{{ route('portals.admin.tasks.complete', $task->uuid) }}">@csrf
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button></form>
            <form method="POST" action="{{ route('portals.admin.tasks.escalate', $task->uuid) }}">@csrf
                <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button></form>
            <form method="POST" action="{{ route('portals.admin.tasks.cancel', $task->uuid) }}" data-confirm="{{ __('admin_desk.confirm_cancel_task') }}">@csrf
                <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="x-circle"></i> {{ __('tasks.cancel') }}</button></form>
        </div>
    </div>
    @else
    <div class="panel">
        <div class="panel-body td-muted">{{ __('admin_desk.task_closed_hint') }}</div>
    </div>
    @endunless
</div>
</div>

@endsection
