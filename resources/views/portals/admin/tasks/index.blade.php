@extends('layouts.portal')

@section('title', __('tasks.admin_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('tasks.admin_title'))

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('tasks.admin_title') }}</h1>
        <p class="page-subtitle">{{ __('tasks.admin_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" data-toggle-hidden="new-task">
        <i data-lucide="plus"></i> {{ __('tasks.new_task') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-task" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="clipboard-plus"></i> {{ __('tasks.new_task') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.tasks.store') }}">
            @csrf
            <div class="adm-form-grid">
                <div class="adm-span-2"><label class="form-label" for="title">{{ __('tasks.col_task') }}</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required></div>
                <div><label class="form-label" for="task_type">{{ __('tasks.col_type') }}</label>
                    <select id="task_type" name="task_type" class="form-control" required>
                        @foreach($types as $t)<option value="{{ $t }}" @selected(old('task_type')===$t)>{{ __('tasks.type_' . $t) }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="assigned_to">{{ __('tasks.assign_to') }}</label>
                    <select id="assigned_to" name="assigned_to" class="form-control" required>
                        <option value="">—</option>
                        @foreach($staff as $s)<option value="{{ $s->id }}" @selected(old('assigned_to')===$s->id)>{{ $s->name }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="priority">{{ __('tasks.priority') }}</label>
                    <select id="priority" name="priority" class="form-control">
                        @foreach(['normal','low','high','urgent'] as $p)<option value="{{ $p }}" @selected(old('priority')===$p)>@enum($p, 'priority')</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="due_at">{{ __('tasks.col_due') }}</label>
                    <input type="datetime-local" id="due_at" name="due_at" class="form-control" value="{{ old('due_at') }}"></div>
                <div class="adm-span-2"><label class="form-label" for="description">{{ __('tasks.description') }}</label>
                    <textarea id="description" name="description" class="form-control" rows="2" maxlength="1000">{{ old('description') }}</textarea></div>
            </div>
            <div class="adm-mt-4"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('tasks.create_task') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header adm-flex-between">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('tasks.admin_title') }}</h3>
        <form method="GET" class="adm-inline-flex">
            <select name="status" class="form-control adm-w-auto" data-autosubmit aria-label="{{ __('tasks.col_status') }}">
                <option value="">{{ __('tasks.all_statuses') }}</option>
                @foreach(['open','acknowledged','escalated','completed'] as $st)
                    <option value="{{ $st }}" @selected($status===$st)>@enum($st)</option>
                @endforeach
            </select>
        </form>
    </div>
    @if($tasks->isEmpty())
        <div class="empty-state"><i data-lucide="clipboard"></i><h3>{{ __('tasks.empty_title') }}</h3><p>{{ __('tasks.admin_empty_body') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('tasks.col_task') }}</th><th>{{ __('tasks.assignee') }}</th>
                <th>{{ __('tasks.col_due') }}</th><th>{{ __('tasks.col_status') }}</th><th></th>
            </tr></thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td data-label="{{ __('tasks.col_task') }}"><a href="{{ route('portals.admin.tasks.show', $task->uuid) }}" class="td-strong">{{ $task->title }}</a>
                        <div class="td-muted">{{ __('tasks.type_' . $task->task_type) }}</div></td>
                    <td data-label="{{ __('tasks.assignee') }}"><span class="td-muted">{{ $assignees[$task->assigned_to]->name ?? '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_due') }}"><span class="td-muted">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_status') }}">
                        @php $sc = ['escalated'=>'danger','acknowledged'=>'info','completed'=>'success'][$task->status] ?? 'warning'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($task->status)</span>
                    </td>
                    <td class="row-actions adm-row-actions">
                        @if(!in_array($task->status, ['completed','cancelled']))
                        <form method="POST" action="{{ route('portals.admin.tasks.reassign', $task->uuid) }}" class="adm-inline-flex">
                            @csrf
                            <select name="assigned_to" class="form-control adm-w-auto" required aria-label="{{ __('tasks.assign_to') }}">
                                @foreach($staff as $s)<option value="{{ $s->id }}" @selected($s->id === $task->assigned_to)>{{ $s->name }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</button>
                        </form>
                        <form method="POST" action="{{ route('portals.admin.tasks.complete', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button></form>
                        <form method="POST" action="{{ route('portals.admin.tasks.escalate', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button></form>
                        <form method="POST" action="{{ route('portals.admin.tasks.cancel', $task->uuid) }}" data-confirm="{{ __('admin_desk.confirm_cancel_task') }}">@csrf
                            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="x-circle"></i> {{ __('tasks.cancel') }}</button></form>
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
