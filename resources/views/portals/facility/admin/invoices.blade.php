@extends('layouts.portal')

@section('title', __('facility_admin.invoices_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.invoices_title'))

@section('content')

@php
    use App\Services\Billing\Xaf;

    /* Same badge map as the billing desk (portals/billing/index) so a status
       reads the same colour on both pages. */
    $statusBadge = fn (string $s) => 'badge badge-' . match ($s) {
        'paid'      => 'success',
        'part_paid' => 'info',
        'issued'    => 'warning',
        'void'      => 'danger',
        default     => 'neutral',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('facility_admin.invoices_title') }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.invoices_subtitle') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="btn btn-secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt-text"></i> {{ __('facility_admin.invoices_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($invoices->isEmpty())
            <div class="empty-state">
                <i data-lucide="receipt"></i>
                <p>{{ __('facility_admin.invoices_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="invoices">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_invoice_number') }}</th>
                        <th>{{ __('facility_admin.col_invoice_patient') }}</th>
                        <th>{{ __('facility_admin.col_invoice_status') }}</th>
                        <th>{{ __('facility_admin.col_invoice_total') }}</th>
                        <th>{{ __('facility_admin.col_invoice_paid') }}</th>
                        <th>{{ __('facility_admin.col_invoice_balance') }}</th>
                        <th>{{ __('facility_admin.col_invoice_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $p     = $patients[$invoice->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr data-invoice="{{ $invoice->invoice_number }}">
                        <td data-label="{{ __('facility_admin.col_invoice_number') }}"><span class="td-strong">{{ $invoice->invoice_number }}</span></td>
                        <td data-label="{{ __('facility_admin.col_invoice_patient') }}">{{ $pName !== '' ? $pName : __('facility_admin.invoice_patient_unknown') }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_status') }}"><span class="{{ $statusBadge((string) $invoice->status) }}">{{ __('billing_desk.status.' . $invoice->status) }}</span></td>
                        <td data-label="{{ __('facility_admin.col_invoice_total') }}">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_paid') }}">{{ Xaf::format(Xaf::int($invoice->paid_amount)) }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_balance') }}">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_date') }}">{{ $invoice->created_at?->isoFormat('LL') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div style="margin-top:1rem;">{{ $invoices->links() }}</div>
        @endif
        <p class="page-subtitle" style="margin:.75rem 0 0;">{{ __('facility_admin.billing_note') }}</p>
    </div>
</div>

@endsection
