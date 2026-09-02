@extends('layouts.portal')

@section('title', __('public.portal.consent_title', [], app()->getLocale()) ?: 'Consent Requests')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.consent_breadcrumb', [], app()->getLocale()) ?: 'Consent Requests')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.consent_title', [], $l) ?: 'Consent Requests' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.consent_subtitle', [], $l) ?: 'Review and manage access requests from healthcare providers.' }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($consentRequests->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
        <h3>{{ __('public.portal.no_consent_title', [], $l) ?: 'No Consent Requests' }}</h3>
        <p>{{ __('public.portal.no_consent_desc', [], $l) ?: 'You have no pending or past access requests.' }}</p>
    </div>
</div>
@else
@foreach($consentRequests as $req)
<div class="panel mb-4">
    <div class="panel-body">
        <div class="flex-between" style="align-items:flex-start;gap:var(--p-space-4);">
            <div style="flex:1;">
                <div class="td-strong">{{ $req->requestingFacility?->name ?? __('public.portal.unknown_facility', [], $l) ?: 'Unknown Facility' }}</div>
                <div class="text-muted mb-3">{{ $req->purpose ?? __('public.portal.lbl_access_request', [], $l) ?: 'Access request' }}</div>
                <div class="gap-2" style="display:flex;flex-wrap:wrap;">
                    @foreach(($req->requested_scope ?? []) as $scope)
                    <span class="badge badge-neutral">{{ $scope }}</span>
                    @endforeach
                </div>
                <div class="text-sm text-muted mt-1">
                    {{ __('public.portal.lbl_requested', [], $l) ?: 'Requested' }} {{ $req->created_at->diffForHumans() }}
                    @if($req->duration_minutes)
                     · {{ __('public.portal.lbl_valid_for', [], $l) ?: 'Valid for' }} {{ round($req->duration_minutes / 60, 1) }} {{ __('public.portal.lbl_hours', [], $l) ?: 'hours' }}
                    @endif
                </div>
            </div>
            <div class="row-actions" style="flex-direction:column;align-items:flex-end;">
                @if($req->status === 'pending')
                <form method="POST" action="{{ route('portals.patient.consent.approve', $req->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('public.portal.btn_approve', [], $l) ?: 'Approve' }}</button>
                </form>
                <form method="POST" action="{{ route('portals.patient.consent.deny', $req->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="x"></i> {{ __('public.portal.btn_deny', [], $l) ?: 'Deny' }}</button>
                </form>
                @else
                <span class="badge {{ $req->status === 'approved' ? 'badge-success' : 'badge-neutral' }}">@enum($req->status)</span>
                @if($req->status === 'approved')
                <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('revoke-consent-{{ $req->id }}')">
                    <i data-lucide="shield-off"></i> {{ __('public.portal.btn_revoke_access', [], $l) ?: 'Revoke access' }}
                </button>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach

@if(method_exists($consentRequests, 'links') && $consentRequests->hasPages())
<div class="mt-3">
    {{ $consentRequests->links() }}
</div>
@endif

@foreach($consentRequests as $req)
    @if($req->status === 'approved')
    <div id="revoke-consent-{{ $req->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="revoke-consent-{{ $req->id }}-title">
            <h3 class="modal__title" id="revoke-consent-{{ $req->id }}-title"><i data-lucide="shield-off"></i> {{ __('public.portal.modal_revoke_title', [], $l) ?: 'Revoke access' }}</h3>
            <form method="POST" action="{{ route('portals.patient.consent.revoke', $req->id) }}">
                @csrf
                <div class="modal__body">
                    <p>{{ __('public.portal.modal_revoke_body', [], $l) ?: 'Revoke this consent? The facility will immediately lose access.' }}</p>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('revoke-consent-{{ $req->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.portal.btn_revoke_access', [], $l) ?: 'Revoke access' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

@endif

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
