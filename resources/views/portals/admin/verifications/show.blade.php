@extends('layouts.portal')
@section('title', __('admin_desk.credential_detail', [], app()->getLocale()))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('professional.ver_title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin.verifications.index'))
@section('breadcrumb_section', __('admin_desk.credential_detail', [], app()->getLocale()))
@section('content')

@php
    $l = app()->getLocale();
    $ctKey   = 'professional.ct_' . $credential->credential_type;
    $ctLabel = \Illuminate\Support\Facades\Lang::has($ctKey) ? __($ctKey, [], $l) : $credential->credential_type;
    $roleKey   = $credential->provider?->role?->name;
    $roleLabel = $roleKey && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $roleKey)
        ? __('portal.role_name.' . $roleKey)
        : $credential->provider?->role?->description;
    $stBadge = match ($credential->status) {
        'active'          => 'badge-success',
        'suspended', 'revoked' => 'badge-danger',
        'expired'         => 'badge-warning',
        default           => 'badge-neutral',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $ctLabel }}</h1>
        <p class="page-subtitle">
            <span class="badge {{ $stBadge }}">@enum($credential->status)</span>
            @if($credential->verified_at)
                <span class="badge badge-success"><i data-lucide="badge-check"></i> {{ __('admin_desk.verified', [], $l) }}</span>
            @elseif($pending)
                <span class="badge badge-warning">{{ __('admin_desk.awaiting_review', [], $l) }}</span>
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.verifications.index') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list', [], $l) }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="adm-detail-grid">
<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user"></i> {{ __('professional.ver_provider', [], $l) }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('admin_desk.name', [], $l) }}</td><td class="kv-strong">{{ $credential->provider?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.email', [], $l) }}</td><td class="kv-strong">{{ $credential->provider?->email ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.role', [], $l) }}</td><td class="kv-strong">{{ $roleLabel ?? '—' }}</td></tr>
                @if($credential->provider)
                <tr><td></td><td><a href="{{ route('admin.users.show', $credential->provider->id) }}" class="link-action"><i data-lucide="external-link"></i> {{ __('admin_desk.open_user', [], $l) }}</a></td></tr>
                @endif
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> {{ __('professional.ver_credential', [], $l) }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('professional.ver_number', [], $l) }}</td><td class="kv-strong">{{ $credential->credential_number ?: '—' }}</td></tr>
                <tr><td>{{ __('professional.ver_issuer', [], $l) }}</td><td class="kv-strong">{{ $credential->issuing_body ?: '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.issued_on', [], $l) }}</td><td class="kv-strong">{{ $credential->issued_date?->translatedFormat('d M Y') ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.expires_on', [], $l) }}</td><td class="kv-strong">{{ $credential->expiry_date?->translatedFormat('d M Y') ?? __('admin_desk.no_expiry', [], $l) }}</td></tr>
                <tr><td>{{ __('admin_desk.document', [], $l) }}</td><td class="kv-strong">{{ $credential->document_path ? __('admin_desk.document_on_file', [], $l) : __('admin_desk.document_none', [], $l) }}</td></tr>
                <tr><td>{{ __('professional.ver_submitted', [], $l) }}</td><td class="kv-strong">{{ $credential->created_at?->isoFormat('lll') ?? '—' }}</td></tr>
            </table>
        </div>
    </div>
</div>

<div>
    <div class="panel mb-4">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="shield-check"></i> {{ __('admin_desk.decision', [], $l) }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('admin_desk.reviewed_by', [], $l) }}</td><td class="kv-strong">{{ $credential->verifier?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.verified_at', [], $l) }}</td><td class="kv-strong">{{ $credential->verified_at?->isoFormat('lll') ?? '—' }}</td></tr>
                <tr><td>{{ __('admin_desk.notes', [], $l) }}</td><td class="kv-strong adm-pre">{{ $credential->notes ?: '—' }}</td></tr>
            </table>
        </div>
    </div>

    @if($pending)
    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="{{ route('portals.admin.verifications.verify', $credential->id) }}" class="mb-4">
                @csrf
                <button type="submit" class="btn btn-success"><i data-lucide="check"></i> {{ __('professional.ver_verify', [], $l) }}</button>
            </form>
            <form method="POST" action="{{ route('portals.admin.verifications.reject', $credential->id) }}" data-confirm="{{ __('admin_desk.confirm_reject_credential', [], $l) }}">
                @csrf
                <label class="form-label" for="notes">{{ __('professional.ver_reject_reason', [], $l) }}</label>
                <textarea name="notes" id="notes" rows="3" class="form-control" maxlength="500"></textarea>
                <div class="adm-mt-2"><button type="submit" class="btn btn-danger"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], $l) }}</button></div>
            </form>
        </div>
    </div>
    @else
    <div class="panel"><div class="panel-body td-muted">{{ __('admin_desk.credential_decided_hint', [], $l) }}</div></div>
    @endif
</div>
</div>

@endsection
