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

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="check-square"></i> {{ __('tasks.title') }}</h3>
        @if($tasks->total())<span class="badge badge-primary">{{ $tasks->total() }}</span>@endif
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
                    <td data-label="{{ __('tasks.col_type') }}"><span class="badge badge-neutral">@enum($task->task_type)</span></td>
                    <td data-label="{{ __('tasks.col_due') }}"><span class="td-muted">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_status') }}">
                        @php $sc = ['escalated'=>'danger','acknowledged'=>'info','completed'=>'success'][$task->status] ?? 'warning'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($task->status)</span>
                        @if(($task->priority ?? 'normal') !== 'normal')<span class="badge badge-warning">@enum($task->priority, 'priority')</span>@endif
                    </td>
                    <td class="row-actions" style="display:flex;gap:.4rem;flex-wrap:wrap;">
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
