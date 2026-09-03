@extends('layouts.portal')

@section('title', __('public.portal.family_title', [], app()->getLocale()) ?: 'My Family')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.family_breadcrumb', [], app()->getLocale()) ?: 'My Family')

@section('head')
    @include('portals.patient._finder_head')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.family_title', [], $l) ?: 'My Family' }}</h1>
        </div>
        <div class="pf-actions">
            <a href="{{ route('portals.patient.family.add') }}" class="f-btn f-btn--primary">
                @include('partials.finder.icon', ['name' => 'plus', 'size' => 16])
                {{ __('public.portal.btn_add_dependent', [], $l) ?: 'Add Dependent' }}
            </a>
            <a href="{{ route('portals.patient.family.invite') }}" class="f-btn">
                {{ __('public.portal.btn_invite_member', [], $l) ?: 'Invite Member' }}
            </a>
        </div>
    </div>

    @if($links->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'users', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.no_family_title', [], $l) ?: 'No family members linked yet' }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.portal.no_family_desc', [], $l) ?: 'Add a dependent or invite an existing patient to link their records to yours.' }}</p>
    </div>
    @else
    <div class="pf-stack">
        @foreach($links as $link)
        <div class="f-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="pf-avatar">{{ strtoupper(substr($link->dependentPatient->first_name ?? 'D', 0, 1)) }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:750;color:var(--f-ink);">{{ $link->dependentPatient->first_name }} {{ $link->dependentPatient->last_name }}</div>
                    <div class="f-small f-muted" style="font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $link->dependentPatient->health_id }}</div>
                </div>
                @if($link->status === 'pending_invite')
                <span class="f-badge f-badge--warn">{{ __('public.portal.lbl_pending', [], $l) ?: 'Pending' }}</span>
                @else
                <span class="f-badge f-badge--good">{{ __('public.portal.lbl_active', [], $l) ?: 'Active' }}</span>
                @endif
            </div>
            <p class="f-small f-muted" style="margin:10px 0 0;">
                @enum($link->relationship) &middot;
                {{ $link->access_level === 'full' ? (__('public.portal.lbl_full_access', [], $l) ?: 'Full access') : (__('public.portal.lbl_read_only', [], $l) ?: 'Read only') }}
            </p>
            @if($link->isExpiredByAge())
            <div class="pf-alert pf-alert--warn" style="margin:10px 0 0;">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p class="f-small">{{ __('public.portal.lbl_grace_period', [], $l) ?: 'Access in grace period — expires' }} {{ $link->age_transition_expires_at->format('M d, Y') }}</p>
            </div>
            @endif
            <div class="pf-appt__actions">
                @if($link->status === 'active')
                <form method="POST" action="{{ route('portals.patient.family.switch', $link->dependent_patient_id) }}">
                    @csrf
                    <button type="submit" class="f-btn f-btn--primary">{{ __('public.portal.btn_view_records', [], $l) ?: 'View Records' }}</button>
                </form>
                @endif
                <a href="{{ route('portals.patient.family.edit', $link->id) }}" class="f-btn">{{ __('public.portal.btn_edit', [], $l) ?: 'Edit' }}</a>
                <button type="button" class="f-btn pf-btn--danger-ghost" onclick="pfOpenModal('remove-link-{{ $link->id }}')">{{ __('public.portal.btn_remove', [], $l) ?: 'Remove' }}</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($incomingConsent->isNotEmpty())
    <div data-section="guardian-consent" class="pf-section" style="margin-top:24px;">
        <h2 class="pf-card-head" style="margin-bottom:10px;">
            @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18])
            {{ __('public.portal.guardian_section_title', [], $l) ?: 'Guardian Access — Your Approval Needed' }}
        </h2>
        <div class="pf-stack">
            @foreach($incomingConsent as $cl)
            <div class="f-card">
                <p style="margin:0 0 12px;">
                    <strong>{{ $cl->guardianUser?->name ?? $cl->guardianUser?->email ?? (__('public.portal.unknown_guardian', [], $l) ?: 'Unknown guardian') }}</strong>
                    {{ __('public.portal.guardian_access_desc', [], $l) ?: 'has guardian access to your records. This access will expire on' }}
                    <strong>{{ $cl->age_transition_expires_at->format('M d, Y') }}</strong>
                    {{ __('public.portal.guardian_approve_suffix', [], $l) ?: 'unless you approve continued access.' }}
                </p>
                <div class="pf-actions">
                    <form method="POST" action="{{ route('portals.patient.family.guardian_consent.approve', $cl->id) }}">
                        @csrf
                        <button type="submit" class="f-btn f-btn--primary">{{ __('public.portal.btn_keep_access', [], $l) ?: 'Keep Access' }}</button>
                    </form>
                    <button type="button" class="f-btn pf-btn--danger-ghost" onclick="pfOpenModal('deny-guardian-{{ $cl->id }}')">{{ __('public.portal.btn_remove_access', [], $l) ?: 'Remove Access' }}</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @foreach($links as $link)
    <div id="remove-link-{{ $link->id }}" class="pf-modal-backdrop" hidden>
        <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="remove-link-{{ $link->id }}-title">
            <h3 id="remove-link-{{ $link->id }}-title">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                {{ __('public.portal.modal_remove_link_title', [], $l) ?: 'Remove family link' }}
            </h3>
            <form method="POST" action="{{ route('portals.patient.family.revoke', $link->id) }}">
                @csrf
                <p>{{ __('public.portal.modal_remove_link_body', [], $l) ?: 'Remove this family link?' }}</p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" onclick="pfCloseModal('remove-link-{{ $link->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                    <button type="submit" class="f-btn pf-btn--danger">{{ __('public.portal.btn_remove', [], $l) ?: 'Remove' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    @foreach($incomingConsent as $cl)
    <div id="deny-guardian-{{ $cl->id }}" class="pf-modal-backdrop" hidden>
        <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="deny-guardian-{{ $cl->id }}-title">
            <h3 id="deny-guardian-{{ $cl->id }}-title">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                {{ __('public.portal.modal_remove_guardian_title', [], $l) ?: 'Remove guardian access' }}
            </h3>
            <form method="POST" action="{{ route('portals.patient.family.guardian_consent.deny', $cl->id) }}">
                @csrf
                <p>{{ __('public.portal.modal_remove_guardian_body', [], $l) ?: "Remove this guardian's access?" }}</p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" onclick="pfCloseModal('deny-guardian-{{ $cl->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                    <button type="submit" class="f-btn pf-btn--danger">{{ __('public.portal.btn_remove_access', [], $l) ?: 'Remove Access' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

@endsection

@section('scripts')
<script>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
