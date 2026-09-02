@extends('layouts.portal')

@section('title', __('billing.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('billing.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing.title') }}</h1>
        <p class="page-subtitle">{{ __('billing.subtitle') }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt"></i> {{ __('billing.invoices') }}</h3>
    </div>
    @if($invoices->isEmpty())
        <div class="empty-state">
            <i data-lucide="receipt"></i>
            <h3>{{ __('billing.empty_title') }}</h3>
            <p>{{ __('billing.empty_body') }}</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('billing.invoice_number') }}</th>
                    <th>{{ __('billing.date') }}</th>
                    <th>{{ __('billing.facility') }}</th>
                    <th>{{ __('billing.total') }}</th>
                    <th>{{ __('billing.balance') }}</th>
                    <th>{{ __('billing.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                    @php
                        $due = (float) $inv->balance_amount > 0
                            ? (float) $inv->balance_amount
                            : max(0, (float) $inv->patient_responsibility_amount - (float) $inv->paid_amount);
                        $stCls = match($inv->status) { 'paid' => 'badge-success', 'void' => 'badge-muted', 'overdue' => 'badge-danger', default => 'badge-warning' };
                    @endphp
                    <tr>
                        <td data-label="{{ __('billing.invoice_number') }}"><span class="td-strong">{{ $inv->invoice_number }}</span></td>
                        <td data-label="{{ __('billing.date') }}"><span class="td-muted">{{ $inv->issued_at?->isoFormat('LL') ?? $inv->created_at?->isoFormat('LL') }}</span></td>
                        <td data-label="{{ __('billing.facility') }}"><span class="td-muted">{{ $inv->facility?->name ?? '—' }}</span></td>
                        <td data-label="{{ __('billing.total') }}">XAF {{ number_format((float) $inv->patient_responsibility_amount, 0) }}</td>
                        <td data-label="{{ __('billing.balance') }}"><strong>XAF {{ number_format($due, 0) }}</strong></td>
                        <td data-label="{{ __('billing.status') }}"><span class="badge {{ $stCls }}">@enum($inv->status)</span></td>
                        <td class="row-actions">
                            @if($due > 0)
                            <a href="{{ route('portals.patient.billing.pay', $inv->id) }}" class="btn btn-primary btn-sm">
                                <i data-lucide="smartphone"></i> {{ __('billing.pay_now') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $invoices->links() }}</div>
    @endif
</div>

@endsection
