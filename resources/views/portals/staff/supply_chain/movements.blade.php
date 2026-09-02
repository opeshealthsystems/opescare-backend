@extends('layouts.portal')
@section('title', __('public.stf_supply_mov_title') . ' — Supply Chain')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_mov_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_mov_subtitle') }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="filter-bar">
        <select name="item" class="filter-select">
            <option value="">{{ __('public.stf_supply_mov_filter_all_items') }}</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" {{ request('item') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
            @endforeach
        </select>
        <select name="type" class="filter-select">
            <option value="">{{ __('public.stf_supply_mov_filter_all_types') }}</option>
            @foreach(['receipt','dispense','transfer','adjustment','return','write_off','opening_stock'] as $t)
                <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" class="filter-select" value="{{ request('from') }}" aria-label="{{ __('public.stf_supply_mov_aria_from') }}">
        <input type="date" name="to" class="filter-select" value="{{ request('to') }}" aria-label="{{ __('public.stf_supply_mov_aria_to') }}">
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_supply_mov_btn_filter') }}</button>
        <a href="{{ route('portals.staff.supply.movements') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_supply_mov_btn_reset') }}</a>
    </form>

    <div class="portal-card">
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_mov_col_datetime') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_item') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_batch') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_type') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_qty') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_unit_cost') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_reference') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_performed_by') }}</th>
                        <th>{{ __('public.stf_supply_mov_col_reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mv)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_mov_col_datetime') }}" class="td-muted">
                                {{ $mv->created_at->format('d M Y') }}<br>
                                <span>{{ $mv->created_at->format('H:i') }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_item') }}">
                                <div class="td-strong">{{ $mv->item->name ?? '—' }}</div>
                                <div class="td-muted">{{ $mv->item->code ?? '' }}</div>
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_batch') }}" class="td-muted">
                                {{ $mv->batch->batch_number ?? '—' }}
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_type') }}">
                                @php
                                    $typeColor = match($mv->movement_type) {
                                        'receipt'       => 'success',
                                        'dispense'      => 'info',
                                        'adjustment'    => 'warning',
                                        'write_off'     => 'danger',
                                        'return'        => 'default',
                                        'transfer'      => 'info',
                                        'opening_stock' => 'success',
                                        default         => 'default',
                                    };
                                @endphp
                                <span class="badge badge--{{ $typeColor }} badge-sm">
                                    @enum($mv->movement_type)
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_qty') }}">
                                <span class="badge {{ $mv->quantity >= 0 ? 'badge-success' : 'badge-danger' }}">
                                    {{ $mv->quantity >= 0 ? '+' : '' }}{{ $mv->quantity }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_unit_cost') }}" class="td-muted">
                                {{ $mv->unit_cost ? number_format($mv->unit_cost, 2) : '—' }}
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_reference') }}" class="td-muted">
                                @if($mv->reference_type)
                                    @enum($mv->reference_type)
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_mov_col_performed_by') }}" class="td-muted">{{ $mv->performed_by ?: '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_mov_col_reason') }}" class="td-muted">
                                {{ $mv->reason ? Str::limit($mv->reason, 55) : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="arrow-left-right"></i></div>
                                <p>{{ __('public.stf_supply_mov_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($movements->hasPages())<div class="panel-footer">{{ $movements->links() }}</div>@endif
    </div>

</div>
@endsection
