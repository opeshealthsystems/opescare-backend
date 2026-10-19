@extends('layouts.portal')

@section('title', __('tasks.admin_title') . ' — OpesCare')

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@php $l = app()->getLocale(); @endphp

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('tasks.admin_title') }}</h1>
        <p class="od-page__sub">{{ __('tasks.admin_subtitle') }}</p>
    </div>
    <div class="od-row">
        <button type="button" class="od-btn od-btn--primary" data-toggle-hidden="new-task">
            <i data-lucide="plus"></i> {{ __('tasks.new_task') }}
        </button>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="od-card" id="new-task" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="clipboard-plus"></i> {{ __('tasks.new_task') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.tasks.store') }}" class="oda-form">
            @csrf
            <label class="oda-label oda-form__full" for="title"><span class="oda-label__text">{{ __('tasks.col_task') }}</span>
                <input type="text" id="title" name="title" class="od-input" maxlength="160" value="{{ old('title') }}" required></label>
            <label class="oda-label" for="task_type"><span class="oda-label__text">{{ __('tasks.col_type') }}</span>
                <select id="task_type" name="task_type" class="od-select" required>
                    @foreach($types as $t)<option value="{{ $t }}" @selected(old('task_type')===$t)>{{ __('tasks.type_' . $t) }}</option>@endforeach
                </select></label>
            <label class="oda-label" for="assigned_to"><span class="oda-label__text">{{ __('tasks.assign_to') }}</span>
                <select id="assigned_to" name="assigned_to" class="od-select" required>
                    <option value="">—</option>
                    @foreach($staff as $s)<option value="{{ $s->id }}" @selected(old('assigned_to')===$s->id)>{{ $s->name }}</option>@endforeach
                </select></label>
            <label class="oda-label" for="priority"><span class="oda-label__text">{{ __('tasks.priority') }}</span>
                <select id="priority" name="priority" class="od-select">
                    @foreach(['normal','low','high','urgent'] as $p)<option value="{{ $p }}" @selected(old('priority')===$p)>@enum($p, 'priority')</option>@endforeach
                </select></label>
            <label class="oda-label" for="due_at"><span class="oda-label__text">{{ __('tasks.col_due') }}</span>
                <input type="datetime-local" id="due_at" name="due_at" class="od-input" value="{{ old('due_at') }}"></label>
            <label class="oda-label oda-form__full" for="description"><span class="oda-label__text">{{ __('tasks.description') }}</span>
                <textarea id="description" name="description" class="oda-textarea" rows="2" maxlength="1000">{{ old('description') }}</textarea></label>
            <div class="oda-form__actions"><button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('tasks.create_task') }}</button></div>
        </form>
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="clipboard-list"></i> {{ __('tasks.admin_title') }}</h2>
        <span class="od-card__count">{{ method_exists($tasks, 'total') ? $tasks->total() : $tasks->count() }}</span>
        <form method="GET" class="od-card__actions">
            <select name="status" class="od-select" data-autosubmit aria-label="{{ __('tasks.col_status') }}">
                <option value="">{{ __('tasks.all_statuses') }}</option>
                @foreach(['open','acknowledged','escalated','completed'] as $st)
                    <option value="{{ $st }}" @selected($status===$st)>@enum($st)</option>
                @endforeach
            </select>
        </form>
    </div>
    @if($tasks->isEmpty())
        <div class="od-card__body"><div class="od-empty"><span class="od-empty__icon"><i data-lucide="clipboard"></i></span><h3 class="od-empty__title">{{ __('tasks.empty_title') }}</h3><p class="od-empty__text">{{ __('tasks.admin_empty_body') }}</p></div></div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('tasks.col_task') }}</th><th>{{ __('tasks.assignee') }}</th>
                    <th>{{ __('tasks.col_due') }}</th><th>{{ __('tasks.col_status') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach($tasks as $task)
                    @php $aName = $assignees[$task->assigned_to]->name ?? '—'; @endphp
                    <tr>
                        <td data-label="{{ __('tasks.col_task') }}"><a href="{{ route('portals.admin.tasks.show', $task->uuid) }}" class="od-table__name">{{ $task->title }}</a>
                            <div class="od-table__sub">{{ __('tasks.type_' . $task->task_type) }}</div></td>
                        <td data-label="{{ __('tasks.assignee') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($task->assigned_to) }}">{{ $gdInit($aName) }}</span>
                                <span class="od-table__sub">{{ $aName }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('tasks.col_due') }}"><span class="od-table__sub">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                        <td data-label="{{ __('tasks.col_status') }}">
                            @php $sc = ['escalated'=>'urgent','acknowledged'=>'normal','completed'=>'ready'][$task->status] ?? 'pending'; @endphp
                            <span class="od-pill od-pill--{{ $sc }}">@enum($task->status)</span>
                        </td>
                        <td>
                            <div class="od-table__actions">
                            <a href="{{ route('portals.admin.tasks.show', $task->uuid) }}" class="od-btn od-btn--row"><i data-lucide="eye"></i> {{ __('admin_desk.task_detail') }}</a>
                            @if(!in_array($task->status, ['completed','cancelled']))
                            <form method="POST" action="{{ route('portals.admin.tasks.reassign', $task->uuid) }}" class="oda-inline-form">
                                @csrf
                                <select name="assigned_to" class="od-select" required aria-label="{{ __('tasks.assign_to') }}">
                                    @foreach($staff as $s)<option value="{{ $s->id }}" @selected($s->id === $task->assigned_to)>{{ $s->name }}</option>@endforeach
                                </select>
                                <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="user-cog"></i> {{ __('tasks.reassign') }}</button>
                            </form>
                            <form method="POST" action="{{ route('portals.admin.tasks.complete', $task->uuid) }}" class="oda-inline-form">@csrf
                                <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button></form>
                            <form method="POST" action="{{ route('portals.admin.tasks.escalate', $task->uuid) }}" class="oda-inline-form">@csrf
                                <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button></form>
                            <form method="POST" action="{{ route('portals.admin.tasks.cancel', $task->uuid) }}" data-confirm="{{ __('admin_desk.confirm_cancel_task') }}" class="oda-inline-form">@csrf
                                <button type="submit" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="x-circle"></i> {{ __('tasks.cancel') }}</button></form>
                            @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $tasks->links() }}</div>
    @endif
</div>

</div>
@endsection
