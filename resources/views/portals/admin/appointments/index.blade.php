@extends('layouts.portal')
@section('title', __('public.adm_appt_page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_appt_breadcrumb'))
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_appt_page_title') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_appt_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="auth-alert auth-alert-success adm-mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="auth-alert auth-alert-danger adm-mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="kpi-grid adm-mb-6">
    <div class="kpi-card"><div class="kpi-icon blue"><i data-lucide="calendar"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_total') }}</div><div class="kpi-value">{{ $stats['total_today'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon teal"><i data-lucide="check-circle"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_confirmed') }}</div><div class="kpi-value text-teal">{{ $stats['confirmed_today'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon danger"><i data-lucide="ban"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_cancelled') }}</div><div class="kpi-value text-danger">{{ $stats['cancelled_today'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon warning"><i data-lucide="user-x"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_noshow') }}</div><div class="kpi-value text-warning">{{ $stats['no_show_today'] ?? 0 }}</div></div></div>
</div>

<form method="GET" action="{{ route('portals.admin.appointments.index') }}" class="panel adm-filter-panel">
    <div class="adm-flex-end">
        <div class="adm-grow-sm"><label class="filter-label">{{ __('public.adm_appt_filter_search_lbl') }}</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('public.adm_appt_filter_search_ph') }}" value="{{ request('search') }}">
        </div>
        <div><label class="filter-label">{{ __('public.adm_appt_filter_status_lbl') }}</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">{{ __('public.adm_appt_opt_all') }}</option>
                @foreach(['scheduled','confirmed','cancelled','no_show','completed'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>@enum($s)</option>
                @endforeach
            </select>
        </div>
        <div><label class="filter-label">{{ __('public.adm_appt_filter_facility_lbl') }}</label>
            <select name="facility_id" class="form-control form-control-sm">
                <option value="">{{ __('public.adm_appt_opt_all_facilities') }}</option>
                @foreach($facilities??[] as $facility)
                <option value="{{ $facility->id }}" @selected(request('facility_id')==$facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="filter-label">{{ __('public.adm_appt_filter_from_lbl') }}</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div><label class="filter-label">{{ __('public.adm_appt_filter_to_lbl') }}</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">{{ __('public.adm_appt_btn_filter') }}</button>
        <a href="{{ route('portals.admin.appointments.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_appt_btn_reset') }}</a>
    </div>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_appt_col_patient') }}</th>
                    <th>{{ __('public.adm_appt_col_facility') }}</th>
                    <th>{{ __('public.adm_appt_col_type') }}</th>
                    <th>{{ __('public.adm_appt_col_scheduled_at') }}</th>
                    <th>{{ __('public.adm_appt_col_status') }}</th>
                    <th>{{ __('public.adm_appt_col_provider') }}</th>
                    <th class="adm-right"><span class="sr-only">{{ __('public.adm_appt_col_actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments??[] as $appointment)
                @php
                $sBadge=match($appointment->status??''){'scheduled'=>'badge-neutral','confirmed'=>'badge-success','cancelled'=>'badge-danger','no_show'=>'badge-warning','completed'=>'badge-primary',default=>'badge-neutral'};
                @endphp
                <tr>
                    <td>
                        <div class="td-strong">{{ trim(($appointment->patient?->first_name ?? '') . ' ' . ($appointment->patient?->last_name ?? '')) ?: '—' }}</div>
                        <div class="adm-td-mono">{{ $appointment->patient?->health_id ?? '' }}</div>
                    </td>
                    <td class="adm-td-sm">{{ $appointment->facility?->name ?? '—' }}</td>
                    <td class="adm-td-sm">@enum($appointment->appointment_type, 'appointment_type')</td>
                    <td>
                        <div class="adm-td-sm">{{ $appointment->scheduled_at?->translatedFormat('d M Y') ?? '—' }}</div>
                        <div class="adm-td-xs-muted">{{ $appointment->scheduled_at?->format('H:i') ?? '' }}</div>
                    </td>
                    <td><span class="badge {{ $sBadge }}">@enum($appointment->status ?? '—')</span></td>
                    <td class="adm-td-sm">{{ $appointment->provider?->name ?? '—' }}</td>
                    <td class="adm-right">
                        <div class="adm-actions-end">
                            <a href="{{ route('portals.admin.appointments.show', $appointment) }}" class="btn btn-ghost btn-xs" aria-label="{{ __('public.aria_view_appointment') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) }}"><i data-lucide="eye"></i></a>
                            @if(!in_array($appointment->status??'',['cancelled','completed']))
                            <button type="button" data-call="openCancelModal" data-args="{{ json_encode([$appointment->id, route('portals.admin.appointments.cancel', $appointment)]) }}" data-show="cancel-modal" data-display="flex" class="btn btn-warning btn-xs" aria-label="{{ __('public.adm_appt_modal_cancel_title') }}" title="{{ __('public.adm_appt_modal_cancel_title') }}"><i data-lucide="ban"></i></button>
                            @endif
                            {{-- The route is POST admin/appointments/{id}/delete, so no method
                                 spoofing: @method('DELETE') rewrote the verb to one that route
                                 does not answer and the button returned 405. --}}
                            <form method="POST" action="{{ route('portals.admin.appointments.destroy',$appointment) }}" data-confirm="{{ addslashes(__('public.adm_appt_js_confirm_delete')) }}">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-xs" aria-label="{{ __('public.adm_appt_btn_delete_label') }}" title="{{ __('public.adm_appt_btn_delete_label') }}"><i data-lucide="trash-2"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="adm-empty-cell">{{ __('public.adm_appt_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($appointments) && $appointments->hasPages())
    <div class="adm-pager">{{ $appointments->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Cancel Modal --}}
<div id="cancel-modal" class="modal-fixed" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 id="cancel-modal-title" class="modal-fixed__title">{{ __('public.adm_appt_modal_cancel_title') }}</h3>
            <button type="button" data-hide="cancel-modal" class="icon-btn" aria-label="{{ __('public.adm_appt_modal_close_label') }}"><i data-lucide="x"></i></button>
        </div>
        {{-- Same as Delete above: portals.admin.appointments.cancel is a POST
             route, so @method('PATCH') here turned Confirm Cancel into a 405. --}}
        <form method="POST" id="cancel-form" action="">
            @csrf
            <div class="modal-fixed__body">
                <div class="form-group">
                    <label class="form-label" for="cancel-reason">{{ __('public.adm_appt_modal_cancel_reason_lbl') }} <span class="adm-required">*</span></label>
                    <textarea id="cancel-reason" name="reason" class="form-control" rows="3" placeholder="{{ __('public.adm_appt_modal_cancel_reason_ph') }}" required></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" data-hide="cancel-modal" class="btn btn-ghost">{{ __('public.adm_appt_modal_close') }}</button>
                <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> {{ __('public.adm_appt_modal_confirm_cancel') }}</button>
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
