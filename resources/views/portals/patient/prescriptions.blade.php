@extends('layouts.portal')

@section('title', __('public.portal.prescriptions_title', [], app()->getLocale()) ?: 'My Prescriptions')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.prescriptions_breadcrumb', [], app()->getLocale()) ?: 'Prescriptions')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.prescriptions_title', [], $l) ?: 'My Prescriptions' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.prescriptions_subtitle', [], $l) ?: 'All medications prescribed to you across your care history.' }}</p>
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
@elseif($prescriptions->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="pill"></i></div>
        <h3>{{ __('public.portal.no_prescriptions_title', [], $l) ?: 'No Prescriptions' }}</h3>
        <p>{{ __('public.portal.no_prescriptions_desc', [], $l) ?: 'You have no recorded prescriptions at this time.' }}</p>
    </div>
</div>
@else
@foreach($prescriptions as $rx)
<div class="panel mb-4">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                <i data-lucide="pill"></i>
                {{ __('public.portal.rx_heading_prefix', [], $l) ?: 'Prescription —' }} {{ $rx->prescribed_at?->format('d M Y') ?? (__('public.portal.rx_unknown_date', [], $l) ?: 'Unknown date') }}
            </h2>
            @if($rx->facility)
            <p class="text-sm text-muted mt-1">{{ $rx->facility->name }}</p>
            @endif
        </div>
        <div class="row-actions">
            @php
                $stCls = match($rx->statusColor()) {
                    'success' => 'badge-success', 'info' => 'badge-info', default => 'badge-neutral'
                };
            @endphp
            <span class="badge {{ $stCls }}">@enum($rx->status)</span>
            @if(in_array($rx->status, ['dispensed', 'active', 'partial']))
            <button type="button" class="btn btn-secondary btn-sm" onclick="opOpenModal('refill-rx-{{ $rx->id }}')">
                <i data-lucide="refresh-cw"></i> {{ __('public.portal.btn_request_refill', [], $l) ?: 'Request Refill' }}
            </button>
            @endif
        </div>
    </div>
    @if($rx->items->isNotEmpty())
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.prescriptions_title', [], $l) ?: 'Prescription items' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_medication', [], $l) ?: 'Medication' }}</th>
                    <th>{{ __('public.portal.col_dose', [], $l) ?: 'Dose' }}</th>
                    <th>{{ __('public.portal.col_frequency', [], $l) ?: 'Frequency' }}</th>
                    <th>{{ __('public.portal.col_duration', [], $l) ?: 'Duration' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rx->items as $item)
                <tr>
                    <td data-label="{{ __('public.portal.col_medication', [], $l) ?: 'Medication' }}"><span class="td-strong">{{ $item->drug_name }}</span></td>
                    <td data-label="{{ __('public.portal.col_dose', [], $l) ?: 'Dose' }}">{{ $item->dose }} ({{ $item->route }})</td>
                    <td data-label="{{ __('public.portal.col_frequency', [], $l) ?: 'Frequency' }}">{{ $item->frequency }}</td>
                    <td data-label="{{ __('public.portal.col_duration', [], $l) ?: 'Duration' }}">{{ $item->duration_days ? $item->duration_days . ' ' . (__('public.portal.lbl_days', [], $l) ?: 'days') : '—' }}</td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        <span class="badge {{ $item->isDispensed() ? 'badge-success' : 'badge-neutral' }}">@enum($item->status)</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endforeach

@if(method_exists($prescriptions, 'links') && $prescriptions->hasPages())
<div class="mt-3">
    {{ $prescriptions->links() }}
</div>
@endif

@foreach($prescriptions as $rx)
    @if(in_array($rx->status, ['dispensed', 'active', 'partial']))
    <div id="refill-rx-{{ $rx->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="refill-rx-{{ $rx->id }}-title">
            <h3 class="modal__title" id="refill-rx-{{ $rx->id }}-title"><i data-lucide="refresh-cw"></i> {{ __('public.portal.modal_refill_title', [], $l) ?: 'Request refill' }}</h3>
            <form method="POST" action="{{ route('portals.patient.prescriptions.refill', $rx->id) }}">
                @csrf
                <div class="modal__body">
                    <p>{{ __('public.portal.modal_refill_body', [], $l) ?: 'Request a refill for this prescription?' }}</p>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('refill-rx-{{ $rx->id }}')">{{ __('public.portal.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('public.portal.btn_request_refill', [], $l) ?: 'Request Refill' }}</button>
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
