@extends('layouts.portal')

@section('title', __('billing_desk.refund.title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.refund.title'))

@php
    use App\Services\Billing\Xaf;
    $refundBadge = fn (string $s) => 'badge badge-' . match ($s) {
        'processed' => 'success',
        'approved'  => 'info',
        'pending'   => 'warning',
        'rejected'  => 'danger',
        default     => 'neutral',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing_desk.refund.title') }}</h1>
        <p class="page-subtitle">{{ __('billing_desk.refund.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

<div class="panel">
    @if($refunds->isEmpty())
        <div class="panel-body"><div class="empty-state"><div class="empty-state-icon"><i data-lucide="undo-2"></i></div><h3>{{ __('billing_desk.refund.empty') }}</h3></div></div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('billing_desk.col_number') }}</th>
                    <th>{{ __('billing_desk.col_patient') }}</th>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refund.col_reason') }}</th>
                    <th>{{ __('billing_desk.refund.col_status') }}</th>
                    <th>{{ __('billing_desk.refund.col_requested') }}</th>
                    <th class="row-actions">{{ __('billing_desk.refund.col_decision') }}</th>
                </tr></thead>
                <tbody>
                @foreach($refunds as $refund)
                    @php
                        $p     = $patients[$refund->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('billing_desk.col_number') }}">
                            @if($refund->invoice)<a href="{{ route('portals.billing.show', $refund->invoice->id) }}">{{ $refund->invoice->invoice_number }}</a>@else — @endif
                            <div class="td-muted">{{ $refund->payment?->payment_reference }}</div>
                        </td>
                        <td data-label="{{ __('billing_desk.col_patient') }}"><span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>@if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif</td>
                        <td data-label="{{ __('billing_desk.col_amount') }}" class="td-strong">{{ Xaf::format(Xaf::int($refund->amount)) }}</td>
                        <td data-label="{{ __('billing_desk.refund.col_reason') }}">{{ $refund->reason }}@if($refund->status === 'rejected' && $refund->notes)<div class="td-muted">{{ $refund->notes }}</div>@endif</td>
                        <td data-label="{{ __('billing_desk.refund.col_status') }}"><span class="{{ $refundBadge($refund->status) }}">{{ __('billing_desk.refund.status.'.$refund->status) }}</span></td>
                        <td data-label="{{ __('billing_desk.refund.col_requested') }}" class="td-muted">{{ $refund->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="row-actions" data-label="{{ __('billing_desk.refund.col_decision') }}">
                            @if($refund->status === 'pending' && $canRefund)
                                <form method="POST" action="{{ route('portals.billing.refund.approve', $refund->id) }}" style="display:inline">@csrf
                                    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('billing_desk.refund.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('portals.billing.refund.reject', $refund->id) }}" class="row-actions-inline mt-2">@csrf
                                    <input type="text" name="reason" class="form-control" style="max-width:220px" placeholder="{{ __('billing_desk.refund.reject_reason') }}" required minlength="3" maxlength="500" aria-label="{{ __('billing_desk.refund.reject_reason') }}">
                                    <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x"></i> {{ __('billing_desk.refund.reject') }}</button>
                                </form>
                            @elseif($refund->status === 'pending')
                                <span class="td-muted">{{ __('billing_desk.refund.no_authority') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($refunds->hasPages())<div class="panel-body">{{ $refunds->links() }}</div>@endif
    @endif
</div>

@endsection
