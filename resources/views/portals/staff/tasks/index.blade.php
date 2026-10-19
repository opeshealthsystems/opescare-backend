@extends('layouts.portal')

@section('title', __('tasks.title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="od-page odc-page">

<header class="od-page__head">
    <h1 class="od-page__title">{{ __('tasks.title') }}</h1>
    <p class="od-page__sub">{{ __('tasks.subtitle') }}</p>
</header>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Broadcasts addressed to this user at this facility (BroadcastService::forStaff). --}}
@if($broadcasts->isNotEmpty())
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('broadcasts.staff_title') }} <span class="od-card__count">({{ $broadcasts->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        <ul class="od-list">
        @foreach($broadcasts as $bc)
        @php
            $acked = $bc->acknowledgements->isNotEmpty();
            $hot = in_array($bc->priority, ['high', 'urgent'], true);
        @endphp
        <li class="od-list__row">
            <span class="od-list__icon {{ $hot ? 'od-tint--amber' : 'od-tint--blue' }}"><i data-lucide="{{ in_array($bc->broadcast_type, ['alert', 'outage'], true) ? 'alert-triangle' : 'megaphone' }}"></i></span>
            <span class="od-list__text">
                <span class="od-list__title">{{ $bc->title }}</span>
                <span class="od-list__sub">{{ $bc->body }}</span>
            </span>
            <span class="od-pill od-pill--neutral">{{ __('broadcasts.type_' . $bc->broadcast_type) }}</span>
            @if($bc->requires_acknowledgement)
                @if($acked)
                    <span class="od-pill od-pill--ready">{{ __('broadcasts.acknowledged') }}</span>
                @else
                    <form method="POST" action="{{ route('portals.staff.broadcasts.acknowledge', $bc->uuid) }}">@csrf
                        <button type="submit" class="od-btn od-btn--row"><i data-lucide="check-check"></i> {{ __('broadcasts.acknowledge') }}</button>
                    </form>
                @endif
            @endif
        </li>
        @endforeach
        </ul>
    </div>
</section>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('tasks.title') }} <span class="od-card__count">({{ $tasks->total() }})</span></h2>
    </header>
    <nav class="od-tabs" aria-label="{{ __('tasks.title') }}">
        <a href="{{ route('portals.staff.tasks') }}" class="od-tab {{ $scope === 'mine' ? 'is-active' : '' }}" @if($scope === 'mine') aria-current="page" @endif><i data-lucide="user"></i> {{ __('tasks.scope_mine') }}</a>
        <a href="{{ route('portals.staff.tasks', ['scope' => 'facility']) }}" class="od-tab {{ $scope === 'facility' ? 'is-active' : '' }}" @if($scope === 'facility') aria-current="page" @endif><i data-lucide="building-2"></i> {{ __('tasks.scope_facility') }}</a>
    </nav>
    @if($tasks->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="check-circle-2"></i></span>
            <div class="od-empty__title">{{ __('tasks.empty_title') }}</div>
            <p class="od-empty__text">{{ __('tasks.empty_body') }}</p>
            <p class="od-empty__text">{{ __('hospital_desk.empty_tasks_hint') }}</p>
        </div>
    @else
    <div class="od-card__body">
    <div class="od-table-wrap">
        <table class="od-table odc-stack">
            <thead><tr>
                <th class="od-table__num">#</th>
                <th>{{ __('tasks.col_task') }}</th>
                <th>{{ __('tasks.col_type') }}</th>
                @if($scope === 'facility')<th>{{ __('tasks.assignee') }}</th>@endif
                <th>{{ __('tasks.col_due') }}</th>
                <th>{{ __('tasks.col_status') }}</th>
                <th><span class="od-sr">{{ __('tasks.complete') }}</span></th>
            </tr></thead>
            <tbody>
                @foreach($tasks as $i => $task)
                @php
                    $sc = ['escalated' => 'urgent', 'acknowledged' => 'normal', 'completed' => 'ready'][$task->status] ?? 'pending';
                @endphp
                <tr>
                    <td class="od-table__num">{{ ($tasks->firstItem() ?? 1) + $i }}</td>
                    <td data-label="{{ __('tasks.col_task') }}">
                        <span class="od-table__name">{{ $task->title }}</span>
                        @if($task->description)<span class="od-table__sub">{{ \Illuminate\Support\Str::limit($task->description, 100) }}</span>@endif
                    </td>
                    <td data-label="{{ __('tasks.col_type') }}"><span class="od-pill od-pill--neutral">{{ __('tasks.type_' . $task->task_type) }}</span></td>
                    @if($scope === 'facility')<td data-label="{{ __('tasks.assignee') }}"><span class="od-muted">{{ $assignees[$task->assigned_to]->name ?? '—' }}</span></td>@endif
                    <td data-label="{{ __('tasks.col_due') }}"><span class="od-muted">{{ $task->due_at ? \Illuminate\Support\Carbon::parse($task->due_at)->isoFormat('lll') : '—' }}</span></td>
                    <td data-label="{{ __('tasks.col_status') }}">
                        <span class="od-pill od-pill--dot od-pill--{{ $sc }}">@enum($task->status)</span>
                        @if(($task->priority ?? 'normal') !== 'normal')<span class="od-pill od-pill--pending">@enum($task->priority, 'priority')</span>@endif
                    </td>
                    <td class="od-table__actions">
                        @if((string) $task->assigned_to === (string) auth()->id())
                        <div class="odc-rowacts">
                        @if($task->status !== 'acknowledged')
                        <form method="POST" action="{{ route('portals.staff.tasks.acknowledge', $task->uuid) }}">@csrf
                            <button type="submit" class="od-btn od-btn--sm odc-btn-quiet"><i data-lucide="eye"></i> {{ __('tasks.acknowledge') }}</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('portals.staff.tasks.complete', $task->uuid) }}" data-confirm="{{ __('hospital_desk.confirm_task_complete') }}">@csrf
                            <button type="submit" class="od-btn od-btn--row"><i data-lucide="check"></i> {{ __('tasks.complete') }}</button>
                        </form>
                        <form method="POST" action="{{ route('portals.staff.tasks.escalate', $task->uuid) }}" data-confirm="{{ __('hospital_desk.confirm_task_escalate') }}">@csrf
                            <button type="submit" class="od-btn od-btn--sm odc-btn-danger"><i data-lucide="arrow-up-circle"></i> {{ __('tasks.escalate') }}</button>
                        </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @if($tasks->hasPages())<footer class="od-card__foot odc-pager">{{ $tasks->links() }}</footer>@endif
    @endif
</section>

</div>
@endsection
