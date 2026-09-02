@extends('layouts.portal')
@section('title', __('healthorg.outreach_title') . ' — OpesCare')
@section('sidebar_role_badge')
<div class="sidebar-role-badge"><i data-lucide="heart-handshake"></i> {{ __('public.healthorg_portal.role_badge', [], app()->getLocale()) ?: 'Health Org' }}</div>
@endsection
@section('sidebar_user_role', __('public.healthorg_portal.role_label', [], app()->getLocale()) ?: 'Health Org Admin')
@section('sidebar_nav')@include('portals.healthorg._sidebar')@endsection
@section('breadcrumb_home', __('public.healthorg_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Health Org Portal')
@section('breadcrumb_home_url', route('portals.healthorg.dashboard'))
@section('breadcrumb_section', __('healthorg.outreach_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('healthorg.outreach_title') }}</h1>
        <p class="page-subtitle">{{ __('healthorg.outreach_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('new-outreach').hidden = !document.getElementById('new-outreach').hidden">
        <i data-lucide="plus"></i> {{ __('healthorg.new_outreach') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-outreach" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="calendar-plus"></i> {{ __('healthorg.new_outreach') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.healthorg.outreach.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div><label class="form-label" for="title">{{ __('healthorg.field_title') }}</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required></div>
                <div><label class="form-label" for="program_id">{{ __('healthorg.field_program') }}</label>
                    <select id="program_id" name="program_id" class="form-control">
                        <option value="">—</option>
                        @foreach($programs as $pr)<option value="{{ $pr->id }}" @selected(old('program_id')===$pr->id)>{{ $pr->name }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="location">{{ __('healthorg.field_location') }}</label>
                    <input type="text" id="location" name="location" class="form-control" maxlength="200" value="{{ old('location') }}"></div>
                <div><label class="form-label" for="scheduled_at">{{ __('healthorg.field_when') }}</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}"></div>
                <div><label class="form-label" for="target_population">{{ __('healthorg.field_target') }}</label>
                    <input type="text" id="target_population" name="target_population" class="form-control" maxlength="160" value="{{ old('target_population') }}"></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="notes">{{ __('healthorg.field_notes') }}</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('healthorg.create_outreach') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('healthorg.field_title') }}</th><th>{{ __('healthorg.field_program') }}</th>
                <th>{{ __('healthorg.field_location') }}</th><th>{{ __('healthorg.field_when') }}</th>
                <th>{{ __('healthorg.field_status') }}</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($events as $e)
                <tr>
                    <td data-label="{{ __('healthorg.field_title') }}"><span class="td-strong">{{ $e->title }}</span></td>
                    <td data-label="{{ __('healthorg.field_program') }}"><span class="td-muted">{{ $e->program?->name ?? '—' }}</span></td>
                    <td data-label="{{ __('healthorg.field_location') }}"><span class="td-muted">{{ $e->location ?? '—' }}</span></td>
                    <td data-label="{{ __('healthorg.field_when') }}"><span class="td-muted">{{ $e->scheduled_at?->isoFormat('lll') ?? '—' }}</span></td>
                    <td data-label="{{ __('healthorg.field_status') }}">
                        @php $sc = ['completed'=>'success','in_progress'=>'primary','cancelled'=>'danger'][$e->status] ?? 'neutral'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($e->status)</span>
                        @if($e->people_reached !== null)<div class="td-muted">{{ $e->people_reached }} {{ __('healthorg.reached') }}</div>@endif
                    </td>
                    <td class="row-actions">
                        @if(!in_array($e->status, ['completed','cancelled']))
                        <form method="POST" action="{{ route('portals.healthorg.outreach.complete', $e->id) }}" style="display:flex;gap:.4rem;align-items:center;">
                            @csrf
                            <input type="number" name="people_reached" min="0" class="form-control" style="width:110px;" placeholder="{{ __('healthorg.reached') }}">
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="check"></i> {{ __('healthorg.mark_done') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('healthorg.no_outreach') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $events->links() }}</div>
</div>

@endsection
