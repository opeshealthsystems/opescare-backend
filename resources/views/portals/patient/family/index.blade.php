@extends('layouts.portal')

@section('title', __('public.portal.family_title', [], app()->getLocale()))
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.family_breadcrumb', [], app()->getLocale()))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l           = app()->getLocale();
    $majorityAge = $majorityAge ?? 18;
    $graceDays   = $graceDays ?? 30;
    $warningDays = $warningDays ?? 60;
    $relLabel    = function (string $value) {
        $label = __('family.relationship.' . $value);
        return $label === 'family.relationship.' . $value ? ucfirst(str_replace('_', ' ', $value)) : $label;
    };
    $viewingId   = session('guardian_viewing_patient_id');
@endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.family_title', [], $l) }}</h1>
            <p class="pf-sub">{{ __('family.age_child_note', ['age' => $majorityAge]) }}</p>
        </div>
        <div class="pf-actions">
            <a href="{{ route('portals.patient.family.add') }}" class="f-btn f-btn--primary">
                @include('partials.finder.icon', ['name' => 'plus', 'size' => 16])
                {{ __('family.dash_add_child') }}
            </a>
            <a href="{{ route('portals.patient.family.invite') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
                {{ __('family.dash_invite_adult') }}
            </a>
            <a href="{{ route('portals.guardian.dashboard') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'users', 'size' => 16])
                {{ __('family.nav_dependents') }}
            </a>
        </div>
    </div>

    @if(isset($incomingInvites) && $incomingInvites->isNotEmpty())
    <div data-section="incoming-invitations" class="pf-section">
        <h2 class="pf-card-head ods-a7409f7">
            @include('partials.finder.icon', ['name' => 'bell', 'size' => 18])
            {{ __('family.inv_section_title') }}
            <a href="{{ route('portals.patient.family.invitations') }}" class="f-btn f-btn--ghost ods-023e71f">{{ __('family.inv_view_all') }}</a>
        </h2>
        <div class="pf-stack">
            @foreach($incomingInvites as $inv)
            @php $who = $inv->guardianUser?->name ?? $inv->guardianUser?->email ?? (__('public.portal.unknown_guardian', [], $l)); @endphp
            <div class="f-card" data-invitation="{{ $inv->id }}">
                <p class="ods-0998cfa">{{ __('family.inv_from', ['name' => $who, 'relationship' => mb_strtolower($relLabel($inv->relationship)), 'access' => mb_strtolower($inv->access_level === 'full' ? __('family.access.full') : __('family.access.read_only'))]) }}</p>
                <div class="pf-actions">
                    <form class="ods-1386d50" method="POST" action="{{ route('portals.patient.family.invitations.accept', $inv->id) }}">
                        @csrf
                        <button type="submit" class="f-btn f-btn--primary">{{ __('family.inv_accept') }}</button>
                    </form>
                    <form class="ods-1386d50" method="POST" action="{{ route('portals.patient.family.invitations.decline', $inv->id) }}">
                        @csrf
                        <button type="submit" class="f-btn pf-btn--danger-ghost">{{ __('family.inv_decline') }}</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($links->isEmpty())
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'users', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.portal.no_family_title', [], $l) }}</h2>
        <p class="f-muted ods-1386d50">{{ __('public.portal.no_family_desc', [], $l) }}</p>
    </div>
    @else
    <div class="pf-stack">
        @foreach($links as $link)
        @php
            $dep      = $link->dependentPatient;
            $dob      = $dep?->date_of_birth;
            $turns    = $dob ? $dob->copy()->addYears($majorityAge) : null;
            $soon     = $turns && $turns->isFuture() && $turns->diffInDays(now()) <= $warningDays;
            $readOnly = \App\Http\Middleware\GuardianAccessMiddleware::isReadOnly($link);
        @endphp
        <div class="f-card" data-link="{{ $link->id }}">
            <div class="ods-9f0e4db">
                <div class="pf-avatar">{{ strtoupper(substr($dep?->first_name ?? 'D', 0, 1)) }}</div>
                <div class="ods-89bd09b">
                    <div class="ods-8b99e49">
                        {{ $dep ? ($link->status === 'active' ? trim(($dep->first_name ?? '') . ' ' . ($dep->last_name ?? '')) : trim(($dep->first_name ?? '') . ' ' . mb_substr((string) ($dep->last_name ?? ''), 0, 1) . '.')) : (__('public.portal.unknown_patient', [], $l)) }}
                        @if($dep && $viewingId === $dep->id)<span class="f-badge f-badge--good ods-da47284">{{ __('family.dash_currently_viewing') }}</span>@endif
                    </div>
                    @if($dep && $link->status === 'active')
                    <div class="f-small f-muted ods-9709411">{{ $dep->health_id }}</div>
                    @endif
                </div>
                @if($link->status === 'pending_invite')
                <span class="f-badge f-badge--warn">{{ __('public.portal.lbl_pending', [], $l) }}</span>
                @else
                <span class="f-badge f-badge--good">{{ __('public.portal.lbl_active', [], $l) }}</span>
                @endif
            </div>
            <p class="f-small f-muted ods-446a577">
                {{ $relLabel($link->relationship) }} &middot;
                {{ $readOnly ? __('family.access.read_only') : __('family.access.full') }}
                @if($link->status === 'pending_invite') &middot; {{ __('family.dash_pending_invite') }} @endif
            </p>
            @if(!$dep)
            <div class="pf-alert pf-alert--warn ods-446a577">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p class="f-small">{{ __('family.dash_hidden_dependent') }}</p>
            </div>
            @elseif($link->age_transition_expires_at)
            <div class="pf-alert pf-alert--warn ods-446a577">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p class="f-small">{{ __('family.age_grace', ['name' => $dep->first_name, 'date' => $link->age_transition_expires_at->translatedFormat('d M Y')]) }}</p>
            </div>
            @elseif($soon && $link->status === 'active')
            <div class="pf-alert ods-446a577" data-age-transition>
                @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                <p class="f-small">{{ __('family.age_turns_majority', ['name' => $dep->first_name, 'age' => $majorityAge, 'date' => $turns->translatedFormat('d M Y'), 'grace' => $graceDays]) }}</p>
            </div>
            @endif
            <div class="pf-appt__actions">
                @if($link->status === 'active' && $dep)
                <form method="POST" action="{{ route('portals.patient.family.switch', $link->dependent_patient_id) }}">
                    @csrf
                    <input type="hidden" name="next" value="dashboard">
                    <button type="submit" class="f-btn f-btn--primary">{{ __('family.dash_view_as') }}</button>
                </form>
                @endif
                <a href="{{ route('portals.patient.family.edit', $link->id) }}" class="f-btn">{{ __('public.portal.btn_edit', [], $l) }}</a>
                <button type="button" class="f-btn pf-btn--danger-ghost" data-call="pfOpenModal" data-args="[&quot;remove-link-{{ $link->id }}&quot;]">{{ __('public.portal.btn_remove', [], $l) }}</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($incomingConsent->isNotEmpty())
    <div data-section="guardian-consent" class="pf-section ods-062d4a8">
        <h2 class="pf-card-head ods-a7409f7">
            @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18])
            {{ __('public.portal.guardian_section_title', [], $l) }}
        </h2>
        <div class="pf-stack">
            @foreach($incomingConsent as $cl)
            <div class="f-card">
                <p class="ods-c9d4377">
                    <strong>{{ $cl->guardianUser?->name ?? $cl->guardianUser?->email ?? (__('public.portal.unknown_guardian', [], $l)) }}</strong>
                    {{ __('public.portal.guardian_access_desc', [], $l) }}
                    <strong>{{ $cl->age_transition_expires_at->translatedFormat('d M Y') }}</strong>
                    {{ __('public.portal.guardian_approve_suffix', [], $l) }}
                </p>
                <div class="pf-actions">
                    <form method="POST" action="{{ route('portals.patient.family.guardian_consent.approve', $cl->id) }}">
                        @csrf
                        <button type="submit" class="f-btn f-btn--primary">{{ __('public.portal.btn_keep_access', [], $l) }}</button>
                    </form>
                    <button type="button" class="f-btn pf-btn--danger-ghost" data-call="pfOpenModal" data-args="[&quot;deny-guardian-{{ $cl->id }}&quot;]">{{ __('public.portal.btn_remove_access', [], $l) }}</button>
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
                {{ __('public.portal.modal_remove_link_title', [], $l) }}
            </h3>
            <form method="POST" action="{{ route('portals.patient.family.revoke', $link->id) }}">
                @csrf
                <p>{{ __('public.portal.modal_remove_link_body', [], $l) }}</p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" data-call="pfCloseModal" data-args="[&quot;remove-link-{{ $link->id }}&quot;]">{{ __('public.portal.btn_cancel', [], $l) }}</button>
                    <button type="submit" class="f-btn pf-btn--danger">{{ __('public.portal.btn_remove', [], $l) }}</button>
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
                {{ __('public.portal.modal_remove_guardian_title', [], $l) }}
            </h3>
            <form method="POST" action="{{ route('portals.patient.family.guardian_consent.deny', $cl->id) }}">
                @csrf
                <p>{{ __('public.portal.modal_remove_guardian_body', [], $l) }}</p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" data-call="pfCloseModal" data-args="[&quot;deny-guardian-{{ $cl->id }}&quot;]">{{ __('public.portal.btn_cancel', [], $l) }}</button>
                    <button type="submit" class="f-btn pf-btn--danger">{{ __('public.portal.btn_remove_access', [], $l) }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

@endsection

@section('scripts')
<script @cspNonce>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
