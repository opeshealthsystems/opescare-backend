@extends('layouts.portal')
@section('title', __('public.developer_portal.page_apps', [], app()->getLocale()) ?: 'My Apps')
@section('sidebar_nav') @include('portals.developer._sidebar') @endsection
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) ?: 'Developer Portal' }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('public.developer_portal.nav_apps', [], $l) ?: 'My Apps' }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('public.developer_portal.nav_apps', [], $l) ?: 'My Apps' }}</h2>
        <div class="page-head__spacer"></div>
        <a href="{{ route('portals.developer.apps.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_new_app', [], $l) ?: 'New App' }}</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

    @if(session('new_client_secret'))
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> {{ __('public.developer_portal.lbl_credentials_created', [], $l) ?: 'App created — save your credentials now' }}</h3></div>
        <div class="panel-body">
            <p class="mb-6">{{ __('public.developer_portal.lbl_secret_once', [], $l) ?: 'Your client secret is shown only once. Store it securely — it cannot be retrieved again.' }}</p>
            <div class="code-block mb-6"><strong>{{ __('public.developer_portal.lbl_client_id', [], $l) ?: 'Client ID' }}:</strong> <span class="code-token" data-copy="{{ session('new_client_id') }}">{{ session('new_client_id') }}</span></div>
            <div class="code-block"><strong>{{ __('public.developer_portal.lbl_client_secret', [], $l) ?: 'Client Secret' }}:</strong> <span class="code-token" data-copy="{{ session('new_client_secret') }}">{{ session('new_client_secret') }}</span></div>
        </div>
    </div>
    @endif

    @if($clients->isEmpty())
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="plug" class="empty-state-icon"></i>
            <p>{{ __('public.developer_portal.no_apps', [], $l) ?: 'No apps yet.' }}</p>
            <a href="{{ route('portals.developer.apps.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_create_app', [], $l) ?: 'Create app' }}</a>
        </div>
    </div>
    @else
    <div class="panel">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.developer_portal.col_app_name', [], $l) ?: 'App name' }}</th>
                    <th>{{ __('public.developer_portal.col_client_id', [], $l) ?: 'Client ID' }}</th>
                    <th>{{ __('public.developer_portal.col_environment', [], $l) ?: 'Environment' }}</th>
                    <th>{{ __('public.developer_portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.developer_portal.col_created', [], $l) ?: 'Created' }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                @foreach($clients as $client)
                <tr>
                    <td data-label="{{ __('public.developer_portal.col_app_name', [], $l) ?: 'App name' }}">
                        <span class="td-strong">{{ $client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) ?: 'Unnamed App' }}</span>
                        @if($client->description)
                        <div class="td-muted">{{ Str::limit($client->description, 60) }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_client_id', [], $l) ?: 'Client ID' }}"><span class="code-token" data-copy="{{ $client->client_id }}">{{ Str::limit($client->client_id, 28) }}</span></td>
                    <td data-label="{{ __('public.developer_portal.col_environment', [], $l) ?: 'Environment' }}">
                        <span class="badge {{ ($client->environment ?? 'sandbox') === 'production' ? 'badge-success' : 'badge-info' }}">@enum($client->environment ?? 'sandbox', 'environment')</span>
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge {{ ($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral' }}">@enum($client->status ?? 'active')</span>
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_created', [], $l) ?: 'Created' }}" class="td-muted">{{ $client->created_at->format('d M Y') }}</td>
                    <td class="row-actions" data-label=""><a href="{{ route('portals.developer.apps.show', $client->id) }}" class="btn btn-secondary btn-sm">{{ __('public.developer_portal.lbl_view', [], $l) ?: 'Details' }}</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

@endsection
@section('scripts')
<script>
document.addEventListener('click', function(e){
    var t = e.target.closest('.code-token[data-copy]');
    if(!t) return;
    navigator.clipboard && navigator.clipboard.writeText(t.getAttribute('data-copy'));
    var prev = t.textContent; t.textContent = @json(__('dev_extra.copied', [], $l) ?: 'Copied'); setTimeout(function(){ t.textContent = prev; }, 1200);
});
</script>
@endsection
