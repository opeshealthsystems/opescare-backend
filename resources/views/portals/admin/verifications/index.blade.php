@extends('layouts.portal')
@section('title', __('professional.ver_title', [], app()->getLocale()))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('professional.ver_title', [], app()->getLocale()))
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('professional.ver_title', [], app()->getLocale()) }}</h1>
        <p class="page-subtitle">{{ __('professional.ver_subtitle', [], app()->getLocale()) }}</p>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="clock"></i></div>
        <div class="stat-card__label">{{ __('professional.ver_pending_count', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $pending->total() }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="shield-check"></i></div>
        <div class="stat-card__label">{{ __('professional.ver_verified_total', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $verifiedTotal }}</div>
    </div>
</div>

@if($pending->total() === 0)
    <div class="panel">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
            <p>{{ __('professional.ver_empty', [], app()->getLocale()) }}</p>
        </div>
    </div>
@else
    <div class="panel">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('professional.ver_provider', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_credential', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_number', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_issuer', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_submitted', [], app()->getLocale()) }}</th>
                        <th class="row-actions">{{ __('professional.ver_verify', [], app()->getLocale()) }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($pending as $credential)
                    @php
                        $ctKey   = 'professional.ct_'.$credential->credential_type;
                        $ctLabel = __($ctKey, [], app()->getLocale());
                        if ($ctLabel === $ctKey) {
                            $ctLabel = __('professional.ct_other', [], app()->getLocale());
                            if ($ctLabel === 'professional.ct_other') { $ctLabel = $credential->credential_type; }
                        }
                    @endphp
                    <tr>
                        <td data-label="{{ __('professional.ver_provider', [], app()->getLocale()) }}">
                            <a href="{{ route('portals.admin.verifications.show', $credential->id) }}" class="td-strong">{{ $credential->provider?->name }}</a>
                            <div class="td-muted">{{ $credential->provider?->email }}</div>
                            @php
                                /*
                                 * The role under a professional's name is a LABEL, not data the
                                 * admin edits here, so it follows the same `portal.role_name.*`
                                 * path the sidebar and topbar use. Printing `roles.description`
                                 * raw showed a French compliance officer "Visiting Doctor" while
                                 * every other role badge on the page read "Medecin vacataire".
                                 * The column stays the fallback for a role with no entry yet.
                                 */
                                $roleKey   = $credential->provider?->role?->name;
                                $roleLabel = $roleKey && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $roleKey)
                                    ? __('portal.role_name.' . $roleKey)
                                    : $credential->provider?->role?->description;
                            @endphp
                            @if($roleLabel)
                            <div class="td-muted">{{ $roleLabel }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('professional.ver_credential', [], app()->getLocale()) }}">
                            <span class="badge badge-neutral">{{ $ctLabel }}</span>
                        </td>
                        <td data-label="{{ __('professional.ver_number', [], app()->getLocale()) }}">{{ $credential->credential_number ?: '—' }}</td>
                        <td data-label="{{ __('professional.ver_issuer', [], app()->getLocale()) }}">{{ $credential->issuing_body ?: '—' }}</td>
                        <td data-label="{{ __('professional.ver_submitted', [], app()->getLocale()) }}" class="td-muted">{{ $credential->created_at?->diffForHumans() }}</td>
                        <td class="row-actions" data-label="{{ __('professional.ver_verify', [], app()->getLocale()) }}">
                            <form method="POST" action="{{ route('portals.admin.verifications.verify', $credential->id) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check"></i> {{ __('professional.ver_verify', [], app()->getLocale()) }}</button>
                            </form>
                            <button type="button" class="btn btn-ghost btn-sm" data-call="opOpenModal" data-args="[&quot;reject-{{ $credential->id }}&quot;]"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($pending->hasPages())
        <div class="panel-body">{{ $pending->links() }}</div>
        @endif
    </div>

    {{-- Reject modals: optional reason, red confirm --}}
    @foreach($pending as $credential)
    <div id="reject-{{ $credential->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reject-{{ $credential->id }}-title">
            <h3 class="modal__title" id="reject-{{ $credential->id }}-title"><i data-lucide="x-circle"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</h3>
            <form method="POST" action="{{ route('portals.admin.verifications.reject', $credential->id) }}" data-confirm="{{ __('admin_desk.confirm_reject_credential') }}">
                @csrf
                <div class="modal__body">
                    <p class="td-strong mb-2">{{ $credential->provider?->name }}</p>
                    <label class="form-label" for="notes-{{ $credential->id }}">{{ __('professional.ver_reject_reason', [], app()->getLocale()) }}</label>
                    <textarea name="notes" id="notes-{{ $credential->id }}" rows="3" class="form-control" style="width:100%;"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" data-call="opCloseModal" data-args="[&quot;reject-{{ $credential->id }}&quot;]">{{ __('public.adm_users_idx_modal_delete_btn_cancel', [], app()->getLocale()) }}</button>
                    <button type="submit" class="btn btn-danger"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endif

@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
