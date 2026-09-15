@extends('layouts.portal')

@section('title', __('tasks.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('tasks.title'))

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('tasks.title') }}</h1>
        <p class="page-subtitle">{{ __('tasks.subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Broadcasts addressed to this user at this facility (BroadcastService::forStaff). --}}
@if($broadcasts->isNotEmpty())
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="megaphone"></i> {{ __('broadcasts.staff_title') }}</h3>
    </div>
    <div class="panel-body">
        @foreach($broadcasts as $bc)
        @php $acked = $bc->acknowledgements->isNotEmpty(); @endphp
        <div class="alert {{ in_array($bc->priority, ['high', 'urgent'], true) ? 'alert-warning' : 'alert-info' }} mb-2">
            <i data-lucide="{{ in_array($bc->broadcast_type, ['alert', 'outage'], true) ? 'alert-triangle' : 'megaphone' }}"></i>
            <div style="flex:1;">
                <strong>{{ $bc->title }}</strong>
                <span class="badge badge-neutral">{{ __('broadcasts.type_' . $bc->broadcast_type) }}</span>
                <div>{{ $bc->body }}</div>
            </div>
            @if($bc->requires_acknowledgement)
                @if($acked)
                    <span class="badge badge-success"><i data-lucide="check"></i> {{ __('broadcasts.acknowledged') }}</span>
                @else
                    <form method="POST" action="{{ route('portals.staff.broadcasts.acknowledge', $bc->uuid) }}">@csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check-check"></i> {{ __('broadcasts.acknowledge') }}</button>
                    </form>
                @endif
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
        <h3 class="panel-title"><i data-lucide="check-square"></i> {{ __('tasks.title') }}
            @if($tasks->total())<span class="badge badge-primary">{{ $tasks->total() }}</span>@endif
        </h3>
        <div style="display:flex;gap:.4rem;">
            <a href="{{ route('portals.staff.tasks') }}" class="btn btn-sm {{ $scope === 'mine' ? 'btn-primary' : 'btn-ghost' }}"><i data-lucide="user"></i> {{ __('tasks.scope_mine') }}</a>
            <a href="{{ route('portals.staff.tasks', ['scope' => 'facility']) }}" class="btn btn-sm {{ $scope === 'facility' ? 'btn-primary' : 'btn-ghost' }}"><i data-lucide="building-2"></i> {{ __('tasks.scope_facility') }}</a>
        </div>
    </div>
    @if($tasks->isEmpty())
        <div class="empty-state">
            <i data-lucide="check-circle-2"></i>
            <h3>{{ __('tasks.empty_title') }}</h3>
            <p>{{ __('tasks.empty_body') }}</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('tasks.col_task') }}</th>
                <th>{{ __('tasks.col_type') }}</th>
                @if($scope === 'facility')<th>{{ __('tasks.assignee') }}</th>@endif
                <th>{{ __('tasks.col_due') }}</th>
                <th>{{ __('tasks.col_status') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td data-label="{{ __('tasks.col_task') }}">
                        <span class="td-strong">{{ $task->title }}</span>
                        @if($task->description)<div class="td-muted">{{ \Illuminate\Support\Str::limit($task->description, 100) }}</div>@endif
                    </td>
                    <td data-label="{{ __('tasks.col_type') }}"><span class="badge badge-neutral">{{ __('tasks.type_' . $task->task_type) }}</span></td>
                    @if($scope === 'facility')<td data-label="{{ __('tasks.assignee') }}"><span class="td-muted">{{ $assignees[$task->assigned_to]->name ?? '—' }}</span></td>@endif
                    <td data-label="{{ __('tasks.col_due') }}"><span class="td-muted">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_status') }}">
                        @php $sc = ['escalated'=>'danger','acknowledged'=>'info','completed'=>'success'][$task->status] ?? 'warning'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($task->status)</span>
                        @if(($task->priority ?? 'normal') !== 'normal')<span class="badge badge-warning">@enum($task->priority, 'priority')</span>@endif
                    </td>
                    <td class="row-actions" style="display:flex;gap:.4rem;flex-wrap:wrap;">
                        @if((string) $task->assigned_to === (string) auth()->id())
                        @if($task->status !== 'acknowledged')
                        <form method="POST" action="{{ route('portals.staff.tasks.acknowledge', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('tasks.acknowledge') }}</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('portals.staff.tasks.complete', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button>
                        </form>
                        <form method="POST" action="{{ route('portals.staff.tasks.escalate', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $tasks->links() }}</div>
    @endif
</div>

@endsection
