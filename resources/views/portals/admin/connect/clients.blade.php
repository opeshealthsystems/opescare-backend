@extends('layouts.portal')

@section('title', __('public.adm_connect_clients_title'))

@section('sidebar')
    @include('portals.admin.connect._sidebar')
@endsection

@section('content')

<div class="page-head">
    <h2><i data-lucide="app-window"></i> {{ __('public.adm_connect_clients_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <button class="btn btn-primary" onclick="opOpenModal('createModal')">
        <i data-lucide="plus"></i> {{ __('public.adm_connect_clients_new_btn') }}
    </button>
</div>
<p class="td-muted mb-6">{{ __('public.adm_connect_clients_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('public.adm_connect_clients_col_status') }}">
        <option value="">{{ __('public.adm_connect_clients_filter_status_all') }}</option>
        @foreach(['pending','active','suspended','revoked'] as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <select name="environment" class="filter-select" aria-label="{{ __('public.adm_connect_clients_col_env') }}">
        <option value="">{{ __('public.adm_connect_clients_filter_status_all') }}</option>
        <option value="sandbox" {{ request('environment') == 'sandbox' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_sandbox') }}</option>
        <option value="production" {{ request('environment') == 'production' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_production') }}</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_connect_clients_btn_filter') }}</button>
    <a href="{{ route('portals.admin.connect.clients') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_connect_clients_btn_reset') }}</a>
</form>

{{-- Clients Table --}}
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_connect_clients_col_name') }}</th>
                    <th>{{ __('public.adm_connect_clients_col_id') }}</th>
                    <th>{{ __('public.adm_connect_clients_col_env') }}</th>
                    <th>{{ __('public.adm_connect_clients_col_scopes') }}</th>
                    <th>{{ __('public.adm_connect_clients_col_status') }}</th>
                    <th>{{ __('public.adm_connect_clients_col_created') }}</th>
                    <th class="row-actions">{{ __('public.adm_connect_clients_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td data-label="{{ __('public.adm_connect_clients_col_name') }}">
                            <div class="td-strong">{{ $client->name }}</div>
                            @if($client->description)<div class="td-muted">{{ Str::limit($client->description, 60) }}</div>@endif
                            @if($client->contact_email)<div class="td-muted">{{ $client->contact_email }}</div>@endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_clients_col_id') }}"><span class="code-token">{{ $client->client_id }}</span></td>
                        <td data-label="{{ __('public.adm_connect_clients_col_env') }}">
                            <span class="badge badge-{{ $client->environment === 'sandbox' ? 'primary' : 'success' }}">{{ $client->environment }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_connect_clients_col_scopes') }}">
                            @foreach(array_slice($client->scopes ?? [], 0, 3) as $scope)
                                <span class="badge badge-purple">{{ $scope }}</span>
                            @endforeach
                            @if(count($client->scopes ?? []) > 3)
                                <span class="td-muted">+{{ count($client->scopes) - 3 }} more</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_clients_col_status') }}">
                            <span class="badge badge-{{
                                $client->status === 'active' ? 'success' :
                                ($client->status === 'pending' ? 'warning' :
                                ($client->status === 'suspended' ? 'primary' : 'danger'))
                            }}">{{ $client->status }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_connect_clients_col_created') }}">{{ $client->created_at->format('d M Y') }}</td>
                        <td class="row-actions" data-label="{{ __('public.adm_connect_clients_col_actions') }}">
                            @if($client->status === 'pending')
                                <form method="POST" action="{{ route('portals.admin.connect.clients.action', $client->id) }}" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-success btn-sm" title="{{ __('public.aria_approve') }}"><i data-lucide="check"></i></button>
                                </form>
                            @endif
                            @if($client->status === 'active')
                                <form method="POST" action="{{ route('portals.admin.connect.clients.action', $client->id) }}" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="action" value="suspend">
                                    <button class="btn btn-warning btn-sm" title="{{ __('public.aria_suspend') }}"><i data-lucide="pause"></i></button>
                                </form>
                            @endif
                            @if(in_array($client->status, ['active','suspended']))
                                <button type="button" class="btn btn-danger btn-sm" title="{{ __('public.adm_connect_clients_btn_revoke') }}" onclick="opOpenModal('revoke-{{ $client->id }}')"><i data-lucide="ban"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_connect_clients_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($clients->hasPages())
        <div class="panel-body">{{ $clients->links() }}</div>
    @endif
</div>

{{-- Revoke confirm modals --}}
@foreach($clients as $client)
    @if(in_array($client->status, ['active','suspended']))
    <div id="revoke-{{ $client->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="revoke-{{ $client->id }}-title">
            <h3 class="modal__title" id="revoke-{{ $client->id }}-title"><i data-lucide="ban"></i> {{ __('public.adm_connect_clients_modal_revoke_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.connect.clients.action', $client->id) }}">
                @csrf
                <input type="hidden" name="action" value="revoke">
                <div class="modal__body"><p>{{ __('public.adm_connect_clients_btn_revoke') }} <strong>{{ $client->name }}</strong>? {{ __('public.adm_connect_clients_modal_revoke_body') }}</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('revoke-{{ $client->id }}')">{{ __('public.adm_connect_clients_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.adm_connect_clients_btn_revoke') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

{{-- Create Client Modal --}}
<div id="createModal" class="modal-backdrop mt-6" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="createModal-title">
        <h3 class="modal__title" id="createModal-title"><i data-lucide="plus-circle"></i> {{ __('public.adm_connect_clients_modal_new_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.connect.clients.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.adm_connect_clients_lbl_name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('public.adm_connect_clients_ph_name') }}" required maxlength="100" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_connect_clients_lbl_email') }}</label>
                        <input type="email" name="contact_email" class="form-control" placeholder="{{ __('public.adm_connect_clients_ph_email') }}" maxlength="100" value="{{ old('contact_email') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_connect_clients_lbl_desc') }}</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="300" placeholder="{{ __('public.adm_connect_clients_ph_desc') }}">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_connect_clients_lbl_env') }}</label>
                    <select name="environment" class="form-control" required>
                        <option value="sandbox" {{ old('environment') == 'sandbox' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_sandbox') }}</option>
                        <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_production') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_connect_clients_lbl_scopes') }}</label>
                    <div class="toggle-grid">
                        @foreach($scopes as $key => $label)
                            <label class="toggle-row">
                                <input type="checkbox" name="scopes[]" value="{{ $key }}" {{ in_array($key, old('scopes', [])) ? 'checked' : '' }}>
                                <span class="toggle-row__body">
                                    <span class="toggle-row__title">{{ $label }}</span>
                                    <span class="toggle-row__desc code-token">{{ $key }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('createModal')">{{ __('public.adm_connect_clients_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="plus-circle"></i> {{ __('public.adm_connect_clients_btn_create') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
