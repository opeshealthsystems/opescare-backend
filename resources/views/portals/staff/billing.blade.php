@extends('layouts.portal')

@section('title', __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.billing_subtitle', [], app()->getLocale()) ?: 'Review patient invoices and record payments.' }}</p>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('portals.staff.billing') }}" class="filter-bar">
    <div class="form-search">
        <span class="search-icon">
            <i data-lucide="search"></i>
        </span>
        <input
            type="text"
            name="patient_id"
            class="form-control"
            placeholder="{{ __('public.staff_portal.filter_patient_id', [], app()->getLocale()) ?: 'Patient ID…' }}"
            value="{{ request('patient_id') }}"
        >
    </div>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i>
        {{ __('public.staff_portal.filter_apply', [], app()->getLocale()) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.staff.billing') }}" class="btn btn-ghost btn-sm">
        {{ __('public.staff_portal.filter_clear', [], app()->getLocale()) ?: 'Clear' }}
    </a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if(count($invoices) === 0)
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="file-x"></i>
                </div>
                <h3>{{ __('public.staff_portal.no_invoices_title', [], app()->getLocale()) ?: 'No Invoices Found' }}</h3>
                <p>{{ __('public.staff_portal.no_invoices_desc', [], app()->getLocale()) ?: 'There are no invoices matching your current filters.' }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.staff_portal.col_invoice_no', [], app()->getLocale()) ?: 'Invoice #' }}</th>
                            <th>{{ __('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID' }}</th>
                            <th>{{ __('public.staff_portal.col_description', [], app()->getLocale()) ?: 'Description' }}</th>
                            <th>{{ __('public.staff_portal.col_amount', [], app()->getLocale()) ?: 'Amount' }}</th>
                            <th>{{ __('public.staff_portal.col_balance', [], app()->getLocale()) ?: 'Balance' }}</th>
                            <th>{{ __('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                            <th>{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        @php
                            $statusBadge = match($invoice->status ?? '') {
                                'paid'      => 'badge-success',
                                'overdue'   => 'badge-danger',
                                'issued'    => 'badge-primary',
                                'cancelled' => 'badge-neutral',
                                'draft'     => 'badge-warning',
                                default     => 'badge-neutral',
                            };
                            $balance      = $invoice->balance_due ?? $invoice->balance ?? 0;
                            $balanceBadge = $balance > 0 ? 'badge-danger' : 'badge-teal';
                            $canPayment   = in_array($invoice->status ?? '', ['issued', 'overdue']);
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.staff_portal.col_invoice_no', [], app()->getLocale()) ?: 'Invoice #' }}">
                                <strong class="mono">
                                    {{ $invoice->invoice_number ?? $invoice->reference ?? '#' . ($invoice->id ?? '?') }}
                                </strong>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID' }}">
                                <span class="mono">{{ $invoice->patient?->health_id ?? ($invoice->patient_id ? '#'.$invoice->patient_id : '—') }}</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_description', [], app()->getLocale()) ?: 'Description' }}">
                                {{ $invoice->description ?? '--' }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_amount', [], app()->getLocale()) ?: 'Amount' }}">
                                {{ number_format($invoice->total_amount ?? $invoice->amount ?? 0, 2) }}
                                {{ $invoice->currency ?? '' }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_balance', [], app()->getLocale()) ?: 'Balance' }}">
                                <span class="badge {{ $balanceBadge }}">
                                    {{ number_format($balance, 2) }}
                                    {{ $invoice->currency ?? '' }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($invoice->status ?? 'unknown')</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}">
                                @if($canPayment)
                                    <a href="{{ route('portals.staff.billing') }}?record_payment={{ $invoice->id ?? $invoice->uuid ?? '' }}" class="btn btn-teal btn-sm">
                                        <i data-lucide="circle-dollar-sign"></i>
                                        {{ __('public.staff_portal.action_record_payment', [], app()->getLocale()) ?: 'Record Payment' }}
                                    </a>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
