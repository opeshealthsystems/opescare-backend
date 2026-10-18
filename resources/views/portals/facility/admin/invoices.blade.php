@extends('layouts.portal')

@section('title', __('facility_admin.invoices_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.invoices_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    use App\Services\Billing\Xaf;

    /* Same status colours as the billing desk (portals/billing/index) so a
       status reads the same on both pages. */
    $statusPill = fn (string $s) => 'od-pill od-pill--' . match ($s) {
        'paid'      => 'ready',
        'part_paid' => 'normal',
        'issued'    => 'pending',
        'void'      => 'urgent',
        default     => 'neutral',
    };
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('facility_admin.invoices_title') }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.invoices_subtitle') }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="od-btn od-btn--secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</header>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.invoices_title') }} <span class="od-card__count">({{ method_exists($invoices, 'total') ? $invoices->total() : $invoices->count() }})</span></h2>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($invoices->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="receipt"></i></span>
                <p class="od-empty__text">{{ __('facility_admin.invoices_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="invoices">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('facility_admin.col_invoice_number') }}</th>
                        <th>{{ __('facility_admin.col_invoice_patient') }}</th>
                        <th>{{ __('facility_admin.col_invoice_status') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_invoice_total') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_invoice_paid') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_invoice_balance') }}</th>
                        <th>{{ __('facility_admin.col_invoice_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($invoices as $i => $invoice)
                    @php
                        $p     = $patients[$invoice->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $rowNo = (method_exists($invoices, 'firstItem') ? ($invoices->firstItem() ?? 1) : 1) + $loop->index;
                    @endphp
                    <tr data-invoice="{{ $invoice->invoice_number }}">
                        <td class="od-table__num">{{ $rowNo }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_number') }}"><span class="od-table__name">{{ $invoice->invoice_number }}</span></td>
                        <td data-label="{{ __('facility_admin.col_invoice_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($pName !== '' ? $pName : (string) $invoice->id) }}" aria-hidden="true">{{ $initials($p->first_name ?? '', $p->last_name ?? '') }}</span>
                                <span class="od-table__name">{{ $pName !== '' ? $pName : __('facility_admin.invoice_patient_unknown') }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('facility_admin.col_invoice_status') }}"><span class="{{ $statusPill((string) $invoice->status) }}">{{ __('billing_desk.status.' . $invoice->status) }}</span></td>
                        <td data-label="{{ __('facility_admin.col_invoice_total') }}" class="odc-num">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_paid') }}" class="odc-num">{{ Xaf::format(Xaf::int($invoice->paid_amount)) }}</td>
                        <td data-label="{{ __('facility_admin.col_invoice_balance') }}" class="odc-num"><span class="odc-strong">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</span></td>
                        <td data-label="{{ __('facility_admin.col_invoice_date') }}" class="odc-nowrap">{{ $invoice->created_at?->isoFormat('LL') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div class="od-card__foot">{{ $invoices->links() }}</div>
        @endif
    </div>
</section>

<div class="odc-note odc-note--info"><i data-lucide="info"></i><div class="odc-note__text">{{ __('facility_admin.billing_note') }}</div></div>

</div>
@endsection
