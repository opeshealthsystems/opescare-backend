@extends('layouts.portal')
@section('title', __('public.adm_appt_page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_appt_breadcrumb'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
    $apptCount = isset($appointments) ? (method_exists($appointments, 'total') ? $appointments->total() : count($appointments)) : 0;
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_appt_page_title') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_appt_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-kpis">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="calendar"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_appt_kpi_total') }}</span><span class="od-kpi__value">{{ $stats['total_today'] ?? 0 }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="check-circle"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_appt_kpi_confirmed') }}</span><span class="od-kpi__value">{{ $stats['confirmed_today'] ?? 0 }}</span></span></div>
    <div class="od-kpi od-tint--rose"><span class="od-kpi__icon"><i data-lucide="ban"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_appt_kpi_cancelled') }}</span><span class="od-kpi__value">{{ $stats['cancelled_today'] ?? 0 }}</span></span></div>
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="user-x"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_appt_kpi_noshow') }}</span><span class="od-kpi__value">{{ $stats['no_show_today'] ?? 0 }}</span></span></div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_appt_page_title') }}</h2>
        <span class="od-card__count">{{ $apptCount }}</span>
    </div>
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.appointments.index') }}" class="oda-toolbar">
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_appt_filter_search_lbl') }}</span>
                <input type="text" name="search" class="od-input" placeholder="{{ __('public.adm_appt_filter_search_ph') }}" value="{{ request('search') }}"></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_appt_filter_status_lbl') }}</span>
                <select name="status" class="od-select">
                    <option value="">{{ __('public.adm_appt_opt_all') }}</option>
                    @foreach(['scheduled','confirmed','cancelled','no_show','completed'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>@enum($s)</option>
                    @endforeach
                </select></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_appt_filter_facility_lbl') }}</span>
                <select name="facility_id" class="od-select">
                    <option value="">{{ __('public.adm_appt_opt_all_facilities') }}</option>
                    @foreach($facilities??[] as $facility)
                    <option value="{{ $facility->id }}" @selected(request('facility_id')==$facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_appt_filter_from_lbl') }}</span>
                <input type="date" name="date_from" class="od-input" value="{{ request('date_from') }}"></label>
            <label class="oda-label"><span class="oda-label__text">{{ __('public.adm_appt_filter_to_lbl') }}</span>
                <input type="date" name="date_to" class="od-input" value="{{ request('date_to') }}"></label>
            <div class="od-row oda-gd-end">
                <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('public.adm_appt_btn_filter') }}</button>
                <a href="{{ route('portals.admin.appointments.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.adm_appt_btn_reset') }}</a>
            </div>
        </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('public.adm_appt_col_patient') }}</th>
                        <th>{{ __('public.adm_appt_col_facility') }}</th>
                        <th>{{ __('public.adm_appt_col_type') }}</th>
                        <th>{{ __('public.adm_appt_col_scheduled_at') }}</th>
                        <th>{{ __('public.adm_appt_col_status') }}</th>
                        <th>{{ __('public.adm_appt_col_provider') }}</th>
                        <th><span class="sr-only">{{ __('public.adm_appt_col_actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments??[] as $appointment)
                    @php
                    $sBadge=match($appointment->status??''){'scheduled'=>'od-pill--neutral','confirmed'=>'od-pill--ready','cancelled'=>'od-pill--urgent','no_show'=>'od-pill--pending','completed'=>'od-pill--normal',default=>'od-pill--neutral'};
                    $pName = trim(($appointment->patient?->first_name ?? '') . ' ' . ($appointment->patient?->last_name ?? ''));
                    @endphp
                    <tr>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($appointment->patient?->health_id ?? $pName) }}">{{ $gdInit($pName) }}</span>
                                <div>
                                    <div class="od-table__name">{{ $pName ?: '—' }}</div>
                                    <div class="od-table__sub oda-mono">{{ $appointment->patient?->health_id ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $appointment->facility?->name ?? '—' }}</td>
                        <td>@enum($appointment->appointment_type, 'appointment_type')</td>
                        <td>
                            <div>{{ $appointment->scheduled_at?->translatedFormat('d M Y') ?? '—' }}</div>
                            <div class="od-table__sub">{{ $appointment->scheduled_at?->format('H:i') ?? '' }}</div>
                        </td>
                        <td><span class="od-pill {{ $sBadge }}">@enum($appointment->status ?? '—')</span></td>
                        <td>{{ $appointment->provider?->name ?? '—' }}</td>
                        <td>
                            <div class="od-table__actions">
                                <a href="{{ route('portals.admin.appointments.show', $appointment) }}" class="od-btn od-btn--row" aria-label="{{ __('public.aria_view_appointment') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) }}"><i data-lucide="eye"></i></a>
                                @if(!in_array($appointment->status??'',['cancelled','completed']))
                                <button type="button" data-call="openCancelModal" data-args="{{ json_encode([$appointment->id, route('portals.admin.appointments.cancel', $appointment)]) }}" data-show="cancel-modal" data-display="flex" class="od-btn od-btn--ghost od-btn--icon od-btn--sm" aria-label="{{ __('public.adm_appt_modal_cancel_title') }}" title="{{ __('public.adm_appt_modal_cancel_title') }}"><i data-lucide="ban"></i></button>
                                @endif
                                {{-- The route is POST admin/appointments/{id}/delete, so no method
                                     spoofing: @method('DELETE') rewrote the verb to one that route
                                     does not answer and the button returned 405. --}}
                                <form method="POST" action="{{ route('portals.admin.appointments.destroy',$appointment) }}" data-confirm="{{ addslashes(__('public.adm_appt_js_confirm_delete')) }}" class="oda-inline-form">
                                    @csrf
                                    <button type="submit" class="od-btn od-btn--danger od-btn--icon od-btn--sm" aria-label="{{ __('public.adm_appt_btn_delete_label') }}" title="{{ __('public.adm_appt_btn_delete_label') }}"><i data-lucide="trash-2"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="od-empty"><span class="od-empty__icon"><i data-lucide="calendar"></i></span><p class="od-empty__text">{{ __('public.adm_appt_empty') }}</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(isset($appointments) && $appointments->hasPages())
    <div class="od-card__foot">{{ $appointments->withQueryString()->links() }}</div>
    @endif
</div>

</div>

{{-- Cancel Modal --}}
<div id="cancel-modal" class="modal-fixed" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 id="cancel-modal-title" class="od-card__title">{{ __('public.adm_appt_modal_cancel_title') }}</h3>
            <button type="button" data-hide="cancel-modal" class="od-btn od-btn--ghost od-btn--icon oda-modal__close" aria-label="{{ __('public.adm_appt_modal_close_label') }}"><i data-lucide="x"></i></button>
        </div>
        {{-- Same as Delete above: portals.admin.appointments.cancel is a POST
             route, so @method('PATCH') here turned Confirm Cancel into a 405. --}}
        <form method="POST" id="cancel-form" action="" class="od-card__body oda-form">
            @csrf
            <label class="oda-label oda-form__full" for="cancel-reason"><span class="oda-label__text">{{ __('public.adm_appt_modal_cancel_reason_lbl') }} *</span>
                <textarea id="cancel-reason" name="reason" class="oda-textarea" rows="3" placeholder="{{ __('public.adm_appt_modal_cancel_reason_ph') }}" required></textarea>
            </label>
            <div class="oda-form__actions">
                <button type="button" data-hide="cancel-modal" class="od-btn od-btn--ghost">{{ __('public.adm_appt_modal_close') }}</button>
                <button type="submit" class="od-btn od-btn--danger"><i data-lucide="ban"></i> {{ __('public.adm_appt_modal_confirm_cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script @cspNonce>
function openCancelModal(id, url) {
    document.getElementById('cancel-form').action = url;
    document.getElementById('cancel-form').querySelector('textarea').value = '';
}
</script>
@include('portals.admin._modal')
@endsection
