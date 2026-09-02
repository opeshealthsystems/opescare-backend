@extends('layouts.portal')

@section('title', __('public.portal.family_title', [], app()->getLocale()) ?: 'My Family')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.family_breadcrumb', [], app()->getLocale()) ?: 'My Family')

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="page-head">
    <h2>{{ __('public.portal.family_title', [], $l) ?: 'My Family' }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.patient.family.add') }}" class="btn btn-primary">
        <i data-lucide="user-plus"></i> {{ __('public.portal.btn_add_dependent', [], $l) ?: 'Add Dependent' }}
    </a>
    <a href="{{ route('portals.patient.family.invite') }}" class="btn btn-secondary">
        <i data-lucide="mail"></i> {{ __('public.portal.btn_invite_member', [], $l) ?: 'Invite Member' }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-success mb-6">
    <i data-lucide="check-circle"></i> {{ session('success') }}
</div>
@endif

@if($links->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="users"></i></div>
        <h3>{{ __('public.portal.no_family_title', [], $l) ?: 'No family members linked yet' }}</h3>
        <p>{{ __('public.portal.no_family_desc', [], $l) ?: 'Add a dependent or invite an existing patient to link their records to yours.' }}</p>
    </div>
</div>
@else
<div class="card-grid">
    @foreach($links as $link)
    <div class="panel">
        <div class="panel-body">
            <div class="entity-head mb-4">
                <div class="entity-head__icon">{{ strtoupper(substr($link->dependentPatient->first_name ?? 'D', 0, 1)) }}</div>
                <div>
                    <div class="td-strong">{{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}</div>
                    <div class="td-muted">{{ $link->dependentPatient->health_id }}</div>
                </div>
                <div class="entity-head__spacer"></div>
                @if($link->status === 'pending_invite')
                <span class="badge badge-warning">{{ __('public.portal.lbl_pending', [], $l) ?: 'Pending' }}</span>
                @else
                <span class="badge badge-success">{{ __('public.portal.lbl_active', [], $l) ?: 'Active' }}</span>
                @endif
            </div>
            <div class="text-sm text-muted mb-4">
                @enum($link->relationship) &middot;
                {{ $link->access_level === 'full' ? (__('public.portal.lbl_full_access', [], $l) ?: 'Full access') : (__('public.portal.lbl_read_only', [], $l) ?: 'Read only') }}
            </div>
            @if($link->isExpiredByAge())
            <div class="alert alert-warning mb-3">
                <i data-lucide="alert-triangle"></i>
                {{ __('public.portal.lbl_grace_period', [], $l) ?: 'Access in grace period — expires' }} {{ $link->age_transition_expires_at->format('M d, Y') }}
            </div>
            @endif
            <div class="row-actions">
                @if($link->status === 'active')
                <form method="POST" action="{{ route('portals.patient.family.switch', $link->dependent_patient_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.portal.btn_view_records', [], $l) ?: 'View Records' }}</button>
                </form>
                @endif
                <a href="{{ route('portals.patient.family.edit', $link->id) }}" class="btn btn-secondary btn-sm">{{ __('public.portal.btn_edit', [], $l) ?: 'Edit' }}</a>
                <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('remove-link-{{ $link->id }}')">{{ __('public.portal.btn_remove', [], $l) ?: 'Remove' }}</button>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@if($incomingConsent->isNotEmpty())
<div data-section="guardian-consent" class="mt-8">
    <div class="page-head">
        <h2><i data-lucide="shield-alert"></i> {{ __('public.portal.guardian_section_title', [], $l) ?: 'Guardian Access — Your Approval Needed' }}</h2>
    </div>
    @foreach($incomingConsent as $cl)
    <div class="panel mb-4">
        <div class="panel-body">
            <p class="mb-3">
                <strong>{{ $cl->guardianUser?->name ?? $cl->guardianUser?->email ?? __('public.portal.unknown_guardian', [], $l) ?: 'Unknown guardian' }}</strong>
                {{ __('public.portal.guardian_access_desc', [], $l) ?: 'has guardian access to your records. This access will expire on' }}
                <strong>{{ $cl->age_transition_expires_at->format('M d, Y') }}</strong>
                {{ __('public.portal.guardian_approve_suffix', [], $l) ?: 'unless you approve continued access.' }}
            </p>
            <div class="row-actions">
                <form method="POST" action="{{ route('portals.patient.family.guardian_consent.approve', $cl->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.portal.btn_keep_access', [], $l) ?: 'Keep Access' }}</button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('deny-guardian-{{ $cl->id }}')">{{ __('public.portal.btn_remove_access', [], $l) ?: 'Remove Access' }}</button>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@foreach($links as $link)
<div id="remove-link-{{ $link->id }}" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="remove-link-{{ $link->id }}-title">
        <h3 class="modal__title" id="remove-link-{{ $link->id }}-title"><i data-lucide="user-x"></i> {{ __('public.portal.modal_remove_link_title', [], $l) ?: 'Remove family link' }}</h3>
        <form method="POST" action="{{ route('portals.patient.family.revoke', $link->id) }}">
            @csrf
            <div class="modal__body"><p>{{ __('public.portal.modal_remove_link_body', [], $l) ?: 'Remove this family link?' }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('remove-link-{{ $link->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.portal.btn_remove', [], $l) ?: 'Remove' }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@foreach($incomingConsent as $cl)
<div id="deny-guardian-{{ $cl->id }}" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deny-guardian-{{ $cl->id }}-title">
        <h3 class="modal__title" id="deny-guardian-{{ $cl->id }}-title"><i data-lucide="shield-off"></i> {{ __('public.portal.modal_remove_guardian_title', [], $l) ?: 'Remove guardian access' }}</h3>
        <form method="POST" action="{{ route('portals.patient.family.guardian_consent.deny', $cl->id) }}">
            @csrf
            <div class="modal__body"><p>{{ __('public.portal.modal_remove_guardian_body', [], $l) ?: "Remove this guardian's access?" }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('deny-guardian-{{ $cl->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.portal.btn_remove_access', [], $l) ?: 'Remove Access' }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach
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
