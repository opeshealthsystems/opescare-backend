@extends('layouts.portal')
@section('title', __('healthorg.programs_title') . ' — OpesCare')
@section('sidebar_role_badge')
<div class="sidebar-role-badge"><i data-lucide="heart-handshake"></i> {{ __('public.healthorg_portal.role_badge', [], app()->getLocale()) ?: 'Health Org' }}</div>
@endsection
@section('sidebar_user_role', __('public.healthorg_portal.role_label', [], app()->getLocale()) ?: 'Health Org Admin')
@section('sidebar_nav')@include('portals.healthorg._sidebar')@endsection
@section('breadcrumb_home', __('public.healthorg_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Health Org Portal')
@section('breadcrumb_home_url', route('portals.healthorg.dashboard'))
@section('breadcrumb_section', __('healthorg.programs_title'))
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('healthorg.programs_title') }}</h1>
        <p class="page-subtitle">{{ __('healthorg.programs_subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('new-program').hidden = !document.getElementById('new-program').hidden">
        <i data-lucide="plus"></i> {{ __('healthorg.new_program') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-program" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="folder-plus"></i> {{ __('healthorg.new_program') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.healthorg.programs.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div><label class="form-label" for="name">{{ __('healthorg.field_name') }}</label>
                    <input type="text" id="name" name="name" class="form-control" maxlength="160" value="{{ old('name') }}" required></div>
                <div><label class="form-label" for="program_type">{{ __('healthorg.field_type') }}</label>
                    <select id="program_type" name="program_type" class="form-control">
                        <option value="">—</option>
                        @foreach($programTypes as $t)<option value="{{ $t }}" @selected(old('program_type')===$t)>{{ __('healthorg.ptype_' . $t) }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="start_date">{{ __('healthorg.field_start') }}</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date') }}"></div>
                <div><label class="form-label" for="end_date">{{ __('healthorg.field_end') }}</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date') }}"></div>
                <div><label class="form-label" for="target_population">{{ __('healthorg.field_target') }}</label>
                    <input type="text" id="target_population" name="target_population" class="form-control" maxlength="160" value="{{ old('target_population') }}"></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="description">{{ __('healthorg.field_description') }}</label>
                    <textarea id="description" name="description" class="form-control" rows="2" maxlength="1000">{{ old('description') }}</textarea></div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('healthorg.create_program') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('healthorg.field_name') }}</th><th>{{ __('healthorg.field_type') }}</th>
                <th>{{ __('healthorg.col_period') }}</th><th>{{ __('healthorg.col_outreach') }}</th><th>{{ __('healthorg.field_status') }}</th>
            </tr></thead>
            <tbody>
                @forelse($programs as $p)
                <tr>
                    <td data-label="{{ __('healthorg.field_name') }}"><span class="td-strong">{{ $p->name }}</span>
                        @if($p->target_population)<div class="td-muted">{{ $p->target_population }}</div>@endif</td>
                    <td data-label="{{ __('healthorg.field_type') }}">@if($p->program_type)<span class="badge badge-neutral">{{ __('healthorg.ptype_' . $p->program_type, [], $l) }}</span>@else — @endif</td>
                    <td data-label="{{ __('healthorg.col_period') }}"><span class="td-muted">{{ $p->start_date?->isoFormat('ll') ?? '—' }} → {{ $p->end_date?->isoFormat('ll') ?? '…' }}</span></td>
                    <td data-label="{{ __('healthorg.col_outreach') }}"><span class="badge badge-primary">{{ $p->outreach_events_count }}</span></td>
                    <td data-label="{{ __('healthorg.field_status') }}"><span class="badge badge-{{ $p->status === 'active' ? 'success' : 'neutral' }}">@enum($p->status)</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('healthorg.no_programs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $programs->links() }}</div>
</div>

@endsection
