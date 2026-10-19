@extends('layouts.portal')
@section('title', __('professional.ver_title', [], app()->getLocale()))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    $gcInit = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('professional.ver_title', [], app()->getLocale()) }}</h1>
        <p class="od-page__sub">{{ __('professional.ver_subtitle', [], app()->getLocale()) }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-kpis oda-gc-kpis--2">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="clock"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('professional.ver_pending_count', [], app()->getLocale()) }}</span><span class="od-kpi__value">{{ $pending->total() }}</span></div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--green"><i data-lucide="shield-check"></i></span>
        <div class="od-kpi__body"><span class="od-kpi__label">{{ __('professional.ver_verified_total', [], app()->getLocale()) }}</span><span class="od-kpi__value">{{ $verifiedTotal }}</span></div>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('professional.ver_pending_count', [], app()->getLocale()) }} <span class="od-card__count">({{ $pending->total() }})</span></h2>
    </div>
@if($pending->total() === 0)
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
        <p class="od-empty__text">{{ __('professional.ver_empty', [], app()->getLocale()) }}</p>
    </div>
</section>
@else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('professional.ver_provider', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_credential', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_number', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_issuer', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.ver_submitted', [], app()->getLocale()) }}</th>
                        <th class="od-table__actions">{{ __('professional.ver_verify', [], app()->getLocale()) }}</th>
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
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $gcTone($credential->provider?->email) }}" aria-hidden="true">{{ $gcInit($credential->provider?->name) }}</span>
                                <div>
                                    <a href="{{ route('portals.admin.verifications.show', $credential->id) }}" class="od-table__name">{{ $credential->provider?->name }}</a>
                                    <span class="od-table__sub">{{ $credential->provider?->email }}</span>
                                    @if($roleLabel)
                                    <span class="od-table__sub">{{ $roleLabel }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('professional.ver_credential', [], app()->getLocale()) }}">
                            <span class="od-pill od-pill--neutral">{{ $ctLabel }}</span>
                        </td>
                        <td data-label="{{ __('professional.ver_number', [], app()->getLocale()) }}" class="oda-mono">{{ $credential->credential_number ?: '—' }}</td>
                        <td data-label="{{ __('professional.ver_issuer', [], app()->getLocale()) }}">{{ $credential->issuing_body ?: '—' }}</td>
                        <td data-label="{{ __('professional.ver_submitted', [], app()->getLocale()) }}" class="oda-gc-muted">{{ $credential->created_at?->diffForHumans() }}</td>
                        <td class="od-table__actions" data-label="{{ __('professional.ver_verify', [], app()->getLocale()) }}">
                            <div class="oda-gc-actions">
                            <form method="POST" action="{{ route('portals.admin.verifications.verify', $credential->id) }}" class="oda-inline-form">
                                @csrf
                                <button type="submit" class="od-btn od-btn--row"><i data-lucide="check"></i> {{ __('professional.ver_verify', [], app()->getLocale()) }}</button>
                            </form>
                            <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-call="opOpenModal" data-args="[&quot;reject-{{ $credential->id }}&quot;]"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($pending->hasPages())
        <div class="od-card__foot">{{ $pending->links() }}</div>
        @endif
</section>

    {{-- Reject modals: optional reason, red confirm --}}
    @foreach($pending as $credential)
    <div id="reject-{{ $credential->id }}" class="modal-backdrop" hidden>
        <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="reject-{{ $credential->id }}-title">
            <div class="od-card__head"><h3 class="od-card__title" id="reject-{{ $credential->id }}-title"><i data-lucide="x-circle"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</h3></div>
            <form method="POST" action="{{ route('portals.admin.verifications.reject', $credential->id) }}" data-confirm="{{ __('admin_desk.confirm_reject_credential') }}" class="od-card__body od-stack">
                @csrf
                <p class="od-table__name">{{ $credential->provider?->name }}</p>
                <label class="oda-label" for="notes-{{ $credential->id }}">
                    <span class="oda-label__text">{{ __('professional.ver_reject_reason', [], app()->getLocale()) }}</span>
                    <textarea name="notes" id="notes-{{ $credential->id }}" rows="3" class="oda-textarea"></textarea>
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;reject-{{ $credential->id }}&quot;]">{{ __('public.adm_users_idx_modal_delete_btn_cancel', [], app()->getLocale()) }}</button>
                    <button type="submit" class="od-btn od-btn--danger"><i data-lucide="x"></i> {{ __('professional.ver_reject', [], app()->getLocale()) }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endif

</div>
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
