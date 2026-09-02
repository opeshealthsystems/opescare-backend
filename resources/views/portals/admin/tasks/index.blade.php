@extends('layouts.portal')

@section('title', __('tasks.admin_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()) ?: 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('tasks.admin_title'))

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('tasks.admin_title') }}</h1>
        <p class="page-subtitle">{{ __('tasks.admin_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('new-task').hidden = !document.getElementById('new-task').hidden">
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
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;"><label class="form-label" for="title">{{ __('tasks.col_task') }}</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required></div>
                <div><label class="form-label" for="task_type">{{ __('tasks.col_type') }}</label>
                    <select id="task_type" name="task_type" class="form-control" required>
                        @foreach($types as $t)<option value="{{ $t }}" @selected(old('task_type')===$t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
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
                <div style="grid-column:1/-1;"><label class="form-label" for="description">{{ __('tasks.description') }}</label>
                    <textarea id="description" name="description" class="form-control" rows="2" maxlength="1000">{{ old('description') }}</textarea></div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('tasks.create_task') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('tasks.admin_title') }}</h3>
        <form method="GET" style="display:flex;gap:.4rem;align-items:center;">
            <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
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
                    <td data-label="{{ __('tasks.col_task') }}"><span class="td-strong">{{ $task->title }}</span>
                        <div class="td-muted">{{ ucfirst(str_replace('_',' ', $task->task_type)) }}</div></td>
                    <td data-label="{{ __('tasks.assignee') }}"><span class="td-muted">{{ $assignees[$task->assigned_to]->name ?? '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_due') }}"><span class="td-muted">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_status') }}">
                        @php $sc = ['escalated'=>'danger','acknowledged'=>'info','completed'=>'success'][$task->status] ?? 'warning'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($task->status)</span>
                    </td>
                    <td class="row-actions" style="display:flex;gap:.4rem;flex-wrap:wrap;">
                        @if(!in_array($task->status, ['completed','cancelled']))
                        <form method="POST" action="{{ route('portals.admin.tasks.reassign', $task->uuid) }}" style="display:flex;gap:.3rem;align-items:center;">
                            @csrf
                            <select name="assigned_to" class="form-control" style="width:auto;" required>
                                @foreach($staff as $s)<option value="{{ $s->id }}" @selected($s->id === $task->assigned_to)>{{ $s->name }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</button>
                        </form>
                        <form method="POST" action="{{ route('portals.admin.tasks.complete', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button></form>
                        <form method="POST" action="{{ route('portals.admin.tasks.escalate', $task->uuid) }}">@csrf
                            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button></form>
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
