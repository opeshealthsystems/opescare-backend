@extends('layouts.portal')

@section('title', $task->title . ' — OpesCare')

@section('breadcrumb_home', __('tasks.admin_title'))
@section('breadcrumb_home_url', route('portals.admin.tasks'))
@section('breadcrumb_section', __('admin_desk.task_detail'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')

@php
    $closed = in_array($task->status, ['completed', 'cancelled'], true);
    $sc = ['escalated' => 'urgent', 'acknowledged' => 'normal', 'completed' => 'ready', 'cancelled' => 'neutral'][$task->status] ?? 'pending';
    $pc = match ($task->priority ?: 'normal') { 'urgent' => 'urgent', 'high' => 'pending', 'low' => 'neutral', default => 'normal' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $task->title }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--neutral">{{ __('tasks.type_' . $task->task_type) }}</span>
            <span class="od-pill od-pill--{{ $sc }}">@enum($task->status)</span>
            <span class="od-pill od-pill--{{ $pc }}">@enum($task->priority ?: 'normal', 'priority')</span>
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.tasks') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="oda-split">
<div class="od-stack">
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="align-left"></i> {{ __('tasks.description') }}</h2></div>
        <div class="od-card__body"><p class="oda-gd-text oda-gd-prewrap">{{ $task->description ?: __('admin_desk.no_description') }}</p></div>
    </div>

    @unless($closed)
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</h2></div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.tasks.reassign', $task->uuid) }}" class="oda-toolbar">
                @csrf
                <select name="assigned_to" class="od-select" required aria-label="{{ __('tasks.assign_to') }}">
                    @foreach($staff as $s)<option value="{{ $s->id }}" @selected((string) $s->id === (string) $task->assigned_to)>{{ $s->name }}</option>@endforeach
                </select>
                <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</button>
            </form>
        </div>
    </div>
    @endunless
</div>

<div class="od-stack">
    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="info"></i> {{ __('admin_desk.details') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('tasks.assignee') }}</dt><dd>{{ $assignee?->name ?? '—' }}</dd>
                <dt>{{ __('tasks.col_due') }}</dt><dd>{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</dd>
                <dt>{{ __('admin_desk.created_at') }}</dt><dd>{{ $task->created_at?->isoFormat('lll') ?? '—' }}</dd>
                <dt>{{ __('admin_desk.acknowledged_at') }}</dt><dd>{{ $task->acknowledged_at ? \Illuminate\Support\Carbon::parse($task->acknowledged_at)->isoFormat('lll') : '—' }}</dd>
                <dt>{{ __('admin_desk.completed_at') }}</dt><dd>{{ $task->completed_at ? \Illuminate\Support\Carbon::parse($task->completed_at)->isoFormat('lll') : '—' }}</dd>
                <dt>{{ __('admin_desk.reference') }}</dt><dd class="oda-mono">{{ $task->uuid }}</dd>
            </dl>
        </div>
    </div>

    @unless($closed)
    <div class="od-card">
        <div class="od-card__body od-row">
            <form method="POST" action="{{ route('portals.admin.tasks.complete', $task->uuid) }}" class="oda-inline-form">@csrf
                <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button></form>
            <form method="POST" action="{{ route('portals.admin.tasks.escalate', $task->uuid) }}" class="oda-inline-form">@csrf
                <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button></form>
            <form method="POST" action="{{ route('portals.admin.tasks.cancel', $task->uuid) }}" data-confirm="{{ __('admin_desk.confirm_cancel_task') }}" class="oda-inline-form">@csrf
                <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="x-circle"></i> {{ __('tasks.cancel') }}</button></form>
        </div>
    </div>
    @else
    <div class="od-card">
        <div class="od-card__body"><p class="oda-help">{{ __('admin_desk.task_closed_hint') }}</p></div>
    </div>
    @endunless
</div>
</div>

</div>
@endsection
