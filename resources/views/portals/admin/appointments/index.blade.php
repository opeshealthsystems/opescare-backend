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

@if(session('success'))<div class="auth-alert auth-alert-success" style="margin-bottom:1rem;"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="auth-alert auth-alert-danger" style="margin-bottom:1rem;"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card"><div class="kpi-icon blue"><i data-lucide="calendar"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_total') }}</div><div class="kpi-value">{{ $stats['total'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon teal"><i data-lucide="check-circle"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_confirmed') }}</div><div class="kpi-value text-teal">{{ $stats['confirmed'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon danger"><i data-lucide="ban"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_cancelled') }}</div><div class="kpi-value text-danger">{{ $stats['cancelled'] ?? 0 }}</div></div></div>
    <div class="kpi-card"><div class="kpi-icon warning"><i data-lucide="user-x"></i></div><div class="kpi-body"><div class="kpi-label">{{ __('public.adm_appt_kpi_noshow') }}</div><div class="kpi-value text-warning">{{ $stats['no_show'] ?? 0 }}</div></div></div>
</div>

<form method="GET" action="{{ route('portals.admin.appointments.index') }}" class="panel" style="padding:1rem;margin-bottom:1rem;">
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;"><label class="filter-label">{{ __('public.adm_appt_filter_search_lbl') }}</label>
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
                    <th style="text-align:right;"><span class="sr-only">{{ __('public.adm_appt_col_actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments??[] as $appointment)
                @php
                $sBadge=match($appointment->status??''){'scheduled'=>'badge-neutral','confirmed'=>'badge-success','cancelled'=>'badge-danger','no_show'=>'badge-warning','completed'=>'badge-primary',default=>'badge-neutral'};
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $appointment->patient?->full_name ?? '—' }}</div>
                        <div style="font-size:.78rem;font-family:monospace;color:var(--p-text-muted);">{{ $appointment->patient?->health_id ?? '' }}</div>
                    </td>
                    <td style="font-size:.85rem;">{{ $appointment->facility?->name ?? '—' }}</td>
                    <td style="font-size:.85rem;">{{ ucfirst(str_replace('_',' ',$appointment->type??'—')) }}</td>
                    <td>
                        <div style="font-size:.85rem;">{{ $appointment->scheduled_at?->format('d M Y') ?? '—' }}</div>
                        <div style="font-size:.75rem;color:var(--p-text-muted);">{{ $appointment->scheduled_at?->format('H:i') ?? '' }}</div>
                    </td>
                    <td><span class="badge {{ $sBadge }}">@enum($appointment->status ?? '—')</span></td>
                    <td style="font-size:.85rem;">{{ $appointment->provider?->full_name ?? '—' }}</td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:.35rem;justify-content:flex-end;">
                            <a href="{{ route('portals.admin.appointments.show', $appointment) }}" class="btn btn-ghost btn-xs" aria-label="{{ __('public.aria_view_appointment') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) ?: 'View' }}"><i data-lucide="eye"></i></a>
                            @if(!in_array($appointment->status??'',['cancelled','completed']))
                            <button onclick="openCancelModal('{{ $appointment->id }}','{{ route('portals.admin.appointments.cancel',$appointment) }}')" class="btn btn-warning btn-xs"><i data-lucide="ban"></i></button>
                            @endif
                            <form method="POST" action="{{ route('portals.admin.appointments.destroy',$appointment) }}" onsubmit="return confirm('{{ addslashes(__('public.adm_appt_js_confirm_delete')) }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i data-lucide="trash-2"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--p-text-muted);">{{ __('public.adm_appt_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($appointments) && $appointments->hasPages())
    <div style="padding:.75rem 1.25rem;">{{ $appointments->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Cancel Modal --}}
<div id="cancel-modal" style="display:none;position:fixed;inset:0;z-index:60;align-items:center;justify-content:center;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);padding:1rem;" role="dialog">
    <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius-xl);width:100%;max-width:480px;overflow:hidden;box-shadow:var(--p-shadow-lg);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--p-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;">{{ __('public.adm_appt_modal_cancel_title') }}</h3>
            <button onclick="document.getElementById('cancel-modal').style.display='none'" class="topbar-icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" id="cancel-form" action="">
            @csrf @method('PATCH')
            <div style="padding:1.5rem;">
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_appt_modal_cancel_reason_lbl') }} <span style="color:var(--p-danger);">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('public.adm_appt_modal_cancel_reason_ph') }}" required></textarea>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;border-top:1px solid var(--p-border);display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('cancel-modal').style.display='none'" class="btn btn-ghost">{{ __('public.adm_appt_modal_close') }}</button>
                <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> {{ __('public.adm_appt_modal_confirm_cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
function openCancelModal(id, url) {
    document.getElementById('cancel-form').action = url;
    document.getElementById('cancel-form').querySelector('textarea').value = '';
    document.getElementById('cancel-modal').style.display = 'flex';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('[id$="-modal"]').forEach(m=>m.style.display='none');}});
</script>
@endsection
