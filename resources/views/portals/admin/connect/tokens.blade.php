@extends('layouts.portal')

@section('title', __('public.adm_connect_tokens_title'))

@section('sidebar')
    @include('portals.admin.connect._sidebar')
@endsection

@section('content')

<div class="page-head">
    <h2><i data-lucide="key-round"></i> {{ __('public.adm_connect_tokens_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <button class="btn btn-primary" onclick="opOpenModal('issueModal')">
        <i data-lucide="plus"></i> {{ __('public.adm_connect_tokens_btn_issue') }}
    </button>
</div>
<p class="td-muted mb-6">{{ __('public.adm_connect_tokens_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- One-time Token Display --}}
@if(session('new_token'))
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="shield-check"></i> {{ __('public.adm_connect_tokens_new_title') }}</h3></div>
    <div class="panel-body">
        <p class="mb-6"><i data-lucide="alert-triangle"></i> {{ __('public.adm_connect_tokens_warn_once') }}</p>
        <div class="filter-bar">
            <code id="newTokenVal" class="code-token code-token--block">{{ session('new_token') }}</code>
            <button class="btn btn-success btn-sm" onclick="copyToken()"><i data-lucide="copy"></i> {{ __('public.adm_connect_tokens_btn_copy') }}</button>
        </div>
        <div id="copiedMsg" class="text-success mt-6" hidden><i data-lucide="check"></i> {{ __('public.adm_connect_tokens_copied') }}</div>
    </div>
</div>
<script>
var _ag = {
    copyFailed: @json(__('public.adm_connect_tokens_js_copy_failed')),
};
function copyToken(){
    navigator.clipboard.writeText(document.getElementById('newTokenVal').textContent.trim())
        .then(()=>{ document.getElementById('copiedMsg').removeAttribute('hidden'); })
        .catch(()=>{ alert(_ag.copyFailed); });
}
</script>
@endif

{{-- Tokens Table --}}
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_connect_tokens_col_token') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_client') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_label') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_env') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_scopes') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_status') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_expires') }}</th>
                    <th>{{ __('public.adm_connect_tokens_col_last_used') }}</th>
                    <th class="row-actions">{{ __('public.adm_connect_tokens_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tokens as $token)
                    <tr>
                        <td data-label="{{ __('public.adm_connect_tokens_col_token') }}"><span class="code-token">{{ $token->displayToken() }}</span></td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_client') }}">{{ $token->client_id }}</td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_label') }}">{{ $token->label ?: '—' }}</td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_env') }}">
                            <span class="badge badge-{{ $token->environment === 'sandbox' ? 'primary' : 'success' }}">{{ $token->environment }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_scopes') }}">
                            @foreach(array_slice($token->scopes ?? [], 0, 2) as $scope)
                                <span class="badge badge-purple">{{ $scope }}</span>
                            @endforeach
                            @if(count($token->scopes ?? []) > 2)<span class="td-muted">+{{ count($token->scopes) - 2 }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_status') }}">
                            @if(!$token->is_active)
                                <span class="badge badge-danger">{{ __('public.adm_connect_tokens_status_revoked') }}</span>
                            @elseif($token->isExpired())
                                <span class="badge badge-warning">{{ __('public.adm_connect_tokens_status_expired') }}</span>
                            @else
                                <span class="badge badge-success">{{ __('public.adm_connect_tokens_status_active') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_expires') }}">{{ $token->expires_at ? $token->expires_at->format('d M Y') : __('public.adm_connect_tokens_expires_never') }}</td>
                        <td data-label="{{ __('public.adm_connect_tokens_col_last_used') }}">{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : '—' }}</td>
                        <td class="row-actions" data-label="{{ __('public.adm_connect_tokens_col_actions') }}">
                            @if($token->is_active && !$token->isExpired())
                                <button type="button" class="btn btn-danger btn-sm" title="{{ __('public.adm_connect_tokens_btn_revoke') }}" onclick="opOpenModal('revoke-{{ $token->id }}')"><i data-lucide="x-circle"></i> {{ __('public.adm_connect_tokens_btn_revoke') }}</button>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="td-muted empty-cell">{{ __('public.adm_connect_tokens_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tokens->hasPages())
        <div class="panel-body">{{ $tokens->links() }}</div>
    @endif
</div>

{{-- Revoke confirm modals --}}
@foreach($tokens as $token)
    @if($token->is_active && !$token->isExpired())
    <div id="revoke-{{ $token->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="revoke-{{ $token->id }}-title">
            <h3 class="modal__title" id="revoke-{{ $token->id }}-title"><i data-lucide="x-circle"></i> {{ __('public.adm_connect_tokens_modal_revoke_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.connect.tokens.revoke', $token->id) }}">
                @csrf
                <div class="modal__body"><p>{{ __('public.adm_connect_tokens_modal_revoke_body') }}</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('revoke-{{ $token->id }}')">{{ __('public.adm_connect_tokens_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.adm_connect_tokens_btn_revoke') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

{{-- Issue Token Modal --}}
<div id="issueModal" class="modal-backdrop mt-6" {{ $errors->any() ? '' : 'hidden' }}>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="issueModal-title">
        <h3 class="modal__title" id="issueModal-title"><i data-lucide="key-round"></i> {{ __('public.adm_connect_tokens_modal_issue_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.connect.tokens.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_connect_tokens_lbl_client') }}</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">{{ __('public.adm_connect_tokens_select_client_ph') }}</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->client_id }}" {{ old('client_id') == $client->client_id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->environment }})</option>
                        @endforeach
                    </select>
                    @if($clients->isEmpty())
                        <div class="form-hint">{{ __('public.adm_connect_tokens_no_clients') }} <a href="{{ route('portals.admin.connect.clients') }}">{{ __('public.adm_connect_tokens_create_first') }}</a></div>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_connect_tokens_lbl_label') }}</label>
                    <input type="text" name="label" class="form-control" placeholder="{{ __('public.adm_connect_tokens_ph_label') }}" maxlength="80" value="{{ old('label') }}">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_connect_tokens_lbl_env') }}</label>
                    <select name="environment" class="form-control" required>
                        <option value="sandbox" {{ old('environment') == 'sandbox' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_sandbox') }}</option>
                        <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>{{ __('public.adm_connect_clients_filter_production') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_connect_tokens_lbl_expires') }}</label>
                    <input type="number" name="expires_days" class="form-control" placeholder="{{ __('public.adm_connect_tokens_ph_expires') }}" min="1" max="365" value="{{ old('expires_days') }}">
                    <div class="form-hint">{{ __('public.adm_connect_tokens_hint_expires') }}</div>
                </div>
                <div class="alert alert-warning"><i data-lucide="triangle-alert"></i><div>{{ __('public.adm_connect_tokens_warn_once2') }}</div></div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('issueModal')">{{ __('public.adm_connect_tokens_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="key-round"></i> {{ __('public.adm_connect_tokens_btn_issue_submit') }}</button>
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
