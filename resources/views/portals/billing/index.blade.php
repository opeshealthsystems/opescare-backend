@extends('layouts.portal')

@section('title', __('billing_desk.title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.list_title'))

@php
    use App\Services\Billing\Xaf;
    $statusBadge = fn (string $s) => 'badge badge-' . match ($s) {
        'paid'      => 'success',
        'part_paid' => 'info',
        'issued'    => 'warning',
        'void'      => 'danger',
        default     => 'neutral',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing_desk.list_title') }}</h1>
        <p class="page-subtitle">{{ __('billing_desk.list_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.billing.create') }}" class="btn btn-primary">
        <i data-lucide="file-plus-2"></i> {{ __('billing_desk.new_invoice') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($session === null)
<div class="alert alert-warning mb-6">
    <i data-lucide="wallet"></i>
    <div>
        {{ __('billing_desk.no_session_banner') }}
        <a href="{{ route('portals.billing.sessions') }}">{{ __('billing_desk.open_session_link') }}</a>
    </div>
</div>
@endif

<div class="panel mb-6">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.billing.index') }}" class="row-actions-inline">
            <div class="form-group" style="flex:1;min-width:220px;margin:0">
                <label class="form-label" for="billing-health-id">{{ __('billing_desk.health_id') }}</label>
                <input type="text" id="billing-health-id" name="health_id" class="form-control"
                       value="{{ $healthId }}" autocomplete="off" placeholder="OC-XXX-000">
                <div class="form-hint">{{ __('billing_desk.health_id_hint') }}</div>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" for="billing-status">{{ __('billing_desk.col_status') }}</label>
                <select id="billing-status" name="status" class="form-control">
                    <option value="">{{ __('billing_desk.all_statuses') }}</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ __('billing_desk.status.'.$s) }} ({{ $counts[$s] ?? 0 }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> {{ __('billing_desk.lookup') }}</button>
            <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary">{{ __('billing_desk.clear') }}</a>
        </form>
        @if($notFound)
            <div class="alert alert-danger mt-4" role="alert"><i data-lucide="user-x"></i><div>{{ __('billing_desk.not_found') }}</div></div>
        @endif
    </div>
</div>

<div class="panel">
    @if($invoices->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="receipt"></i></div>
                <h3>{{ __('billing_desk.empty_list') }}</h3>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('billing_desk.col_number') }}</th>
                        <th>{{ __('billing_desk.col_patient') }}</th>
                        <th>{{ __('billing_desk.col_status') }}</th>
                        <th>{{ __('billing_desk.col_total') }}</th>
                        <th>{{ __('billing_desk.col_paid') }}</th>
                        <th>{{ __('billing_desk.col_balance') }}</th>
                        <th>{{ __('billing_desk.col_date') }}</th>
                        <th class="row-actions">{{ __('billing_desk.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $p     = $patients[$invoice->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('billing_desk.col_number') }}"><span class="td-strong">{{ $invoice->invoice_number }}</span></td>
                        <td data-label="{{ __('billing_desk.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('billing_desk.col_status') }}"><span class="{{ $statusBadge($invoice->status) }}">{{ __('billing_desk.status.'.$invoice->status) }}</span></td>
                        <td data-label="{{ __('billing_desk.col_total') }}">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.col_paid') }}">{{ Xaf::format(Xaf::int($invoice->paid_amount) - Xaf::int($invoice->refunded_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.col_balance') }}"><span class="td-strong">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</span></td>
                        <td data-label="{{ __('billing_desk.col_date') }}" class="td-muted">{{ $invoice->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td class="row-actions" data-label="{{ __('billing_desk.col_open') }}">
                            <a href="{{ route('portals.billing.show', $invoice->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('billing_desk.col_open') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="panel-body">{{ $invoices->links() }}</div>
        @endif
    @endif
</div>

@endsection
