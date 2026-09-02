@extends('layouts.portal')

@section('title', __('broadcasts.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()) ?: 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('broadcasts.title'))

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('broadcasts.title') }}</h1>
        <p class="page-subtitle">{{ __('broadcasts.subtitle') }}</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('new-bc').hidden = !document.getElementById('new-bc').hidden">
        <i data-lucide="megaphone"></i> {{ __('broadcasts.new') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel mb-4" id="new-bc" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="megaphone"></i> {{ __('broadcasts.new') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.broadcasts.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;"><label class="form-label" for="title">{{ __('broadcasts.field_title') }}</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" required></div>
                <div><label class="form-label" for="broadcast_type">{{ __('broadcasts.field_type') }}</label>
                    <select id="broadcast_type" name="broadcast_type" class="form-control" required>
                        @foreach($types as $t)<option value="{{ $t }}" @selected(old('broadcast_type')===$t)>{{ __('broadcasts.type_' . $t) }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="target_type">{{ __('broadcasts.field_audience') }}</label>
                    <select id="target_type" name="target_type" class="form-control" required>
                        @foreach($targetTypes as $tt)<option value="{{ $tt }}" @selected(old('target_type')===$tt)>{{ __('broadcasts.audience_' . $tt) }}</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="priority">{{ __('broadcasts.field_priority') }}</label>
                    <select id="priority" name="priority" class="form-control">
                        @foreach($priorities as $p)<option value="{{ $p }}" @selected(old('priority')===$p)>@enum($p, 'priority')</option>@endforeach
                    </select></div>
                <div><label class="form-label" for="language">{{ __('broadcasts.field_language') }}</label>
                    <select id="language" name="language" class="form-control">
                        <option value="en" @selected(old('language')==='en')>English</option>
                        <option value="fr" @selected(old('language')==='fr')>Français</option>
                    </select></div>
                <div><label class="form-label" for="expires_at">{{ __('broadcasts.field_expires') }}</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at') }}"></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="body">{{ __('broadcasts.field_body') }}</label>
                    <textarea id="body" name="body" class="form-control" rows="4" maxlength="5000" required>{{ old('body') }}</textarea></div>
                <div style="grid-column:1/-1;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center;">
                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="requires_acknowledgement" value="1"> {{ __('broadcasts.field_ack') }}</label>
                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="publish_now" value="1"> {{ __('broadcasts.field_publish_now') }}</label>
                </div>
            </div>
            <div style="margin-top:1rem;"><button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('broadcasts.save') }}</button></div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 class="panel-title"><i data-lucide="radio"></i> {{ __('broadcasts.title') }}</h3>
        <form method="GET"><select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">{{ __('broadcasts.all_statuses') }}</option>
            @foreach(['draft','published','cancelled'] as $st)<option value="{{ $st }}" @selected($status===$st)>@enum($st)</option>@endforeach
        </select></form>
    </div>
    @if($items->isEmpty())
        <div class="empty-state"><i data-lucide="megaphone"></i><h3>{{ __('broadcasts.empty_title') }}</h3><p>{{ __('broadcasts.empty_body') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('broadcasts.field_title') }}</th><th>{{ __('broadcasts.field_audience') }}</th>
                <th>{{ __('broadcasts.field_type') }}</th><th>{{ __('broadcasts.col_status') }}</th>
                <th>{{ __('broadcasts.col_acks') }}</th><th></th>
            </tr></thead>
            <tbody>
                @foreach($items as $bc)
                <tr>
                    <td data-label="{{ __('broadcasts.field_title') }}"><span class="td-strong">{{ $bc->title }}</span>
                        @if(($bc->priority ?? 'normal')!=='normal')<span class="badge badge-warning">@enum($bc->priority, 'priority')</span>@endif</td>
                    <td data-label="{{ __('broadcasts.field_audience') }}"><span class="td-muted">{{ __('broadcasts.audience_' . $bc->target_type) }}</span></td>
                    <td data-label="{{ __('broadcasts.field_type') }}"><span class="badge badge-neutral">{{ __('broadcasts.type_' . $bc->broadcast_type) }}</span></td>
                    <td data-label="{{ __('broadcasts.col_status') }}">
                        @php $sc = ['published'=>'success','cancelled'=>'muted','expired'=>'muted'][$bc->status] ?? 'warning'; @endphp
                        <span class="badge badge-{{ $sc }}">@enum($bc->status)</span></td>
                    <td data-label="{{ __('broadcasts.col_acks') }}">{{ $bc->requires_acknowledgement ? $bc->acknowledgementCount() : '—' }}</td>
                    <td class="row-actions" style="display:flex;gap:.4rem;">
                        @if($bc->status === 'draft')
                        <form method="POST" action="{{ route('portals.admin.broadcasts.publish', $bc->uuid) }}">@csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="send"></i> {{ __('broadcasts.publish') }}</button></form>
                        @endif
                        @if(in_array($bc->status, ['draft','published']))
                        <form method="POST" action="{{ route('portals.admin.broadcasts.cancel', $bc->uuid) }}" onsubmit="return confirm('{{ __('broadcasts.cancel_confirm') }}');">@csrf
                            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="x-circle"></i> {{ __('broadcasts.cancel') }}</button></form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $items->links() }}</div>
    @endif
</div>

@endsection
