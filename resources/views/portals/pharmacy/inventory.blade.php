@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Drug Inventory')
@section('sidebar_role_badge')
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    {{ __('public.pharmacy_portal.role_badge', [], app()->getLocale()) ?: 'Pharmacy' }}
</div>
@endsection
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist')
@section('sidebar_nav')
@include('portals.pharmacy._sidebar')
@endsection
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Pharmacy Portal')
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_stock', [], app()->getLocale()) ?: 'Public Availability')
@php
    $l = app()->getLocale();
    $badgeFor = fn ($v) => match ($v) {
        'in_stock'     => 'badge-success',
        'low_stock'    => 'badge-warning',
        'out_of_stock' => 'badge-danger',
        default        => 'badge-neutral',
    };
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_stock', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_stock', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    @feature('inventory_ops')
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="settings"></i> {{ __('public.pharmacy_portal.btn_manage_stock', [], $l) ?: 'Manage stock' }}
    </a>
    @endfeature
</div>

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div>
        <ul class="alert-list">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

{{-- Why a pharmacy may be invisible to patients, in the order worth fixing. --}}
@foreach($issues as $issue)
    @if($issue === 'unlinked')
    <div class="banner banner--danger">
        <i data-lucide="unlink"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_unlinked_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_unlinked_body', [], $l) }}
        </div>
    </div>
    @elseif($issue === 'no_coordinates')
    <div class="banner banner--warning">
        <i data-lucide="map-pin-off"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_no_coords_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_no_coords_body', [], $l) }}
        </div>
    </div>
    @elseif($issue === 'not_listed')
    <div class="banner banner--warning">
        <i data-lucide="eye-off"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_not_listed_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_not_listed_body', [], $l) }}
        </div>
    </div>
    @endif
@endforeach

@if($listing)
<div class="banner banner--info">
    <i data-lucide="search"></i>
    <div>
        <strong>{{ $listing->facility_name }}</strong> —
        {{ __('public.pharmacy_portal.stock_cov_summary', [
            'reported' => $coverage['reported'],
            'total'    => $coverage['total'],
        ], $l) }}
    </div>
</div>

{{-- Report a medicine this pharmacy has never published availability for. --}}
<details class="panel" @if(old('medicine_id')) open @endif>
    <summary class="panel-header" style="cursor:pointer;">
        <i data-lucide="plus-circle"></i>
        {{ __('public.pharmacy_portal.stock_add_title', [], $l) }}
    </summary>
    <div class="panel-body">
        @if($catalog->isEmpty())
            <p class="td-muted">{{ __('public.pharmacy_portal.stock_catalog_empty', [], $l) }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-medicine">{{ __('public.pharmacy_portal.stock_add_medicine', [], $l) }}</label>
                    <select name="medicine_id" id="add-medicine" class="form-control" required>
                        <option value="">{{ __('public.pharmacy_portal.stock_add_choose', [], $l) }}</option>
                        @foreach($catalog as $m)
                        <option value="{{ $m->id }}" {{ old('medicine_id') === $m->id ? 'selected' : '' }}>
                            {{ $m->name }}@if($m->form) · {{ $m->form }}@endif
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-status">{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</label>
                    <select name="stock_status" id="add-status" class="form-control" required>
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ $s->value === 'in_stock' ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-packs">{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</label>
                    <input type="number" name="packs_available" id="add-packs" class="form-control" min="0" max="1000000" value="{{ old('packs_available') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-pack-size">{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}</label>
                    <input type="text" name="pack_size" id="add-pack-size" class="form-control" maxlength="60" value="{{ old('pack_size') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-price">{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</label>
                    <input type="number" name="unit_price" id="add-price" class="form-control" min="0" step="0.01" value="{{ old('unit_price') }}">
                </div>
            </div>
            <label class="form-check">
                <input type="checkbox" name="reservation_enabled" value="1" checked>
                {{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}
            </label>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="megaphone"></i> {{ __('public.pharmacy_portal.stock_btn_report', [], $l) }}
                </button>
            </div>
        </form>
        @endif
    </div>
</details>

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.pharmacy_portal.ph_search_drug', [], $l) ?: 'Drug or generic name…' }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_drugs') }}">
    </label>
    <select name="stock_status" class="filter-select" aria-label="{{ __('public.aria_stock_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.pharmacy_portal.filter_all_stock', [], $l) ?: 'All stock' }}</option>
        @foreach($statuses as $s)
        <option value="{{ $s->value }}" {{ request('stock_status') === $s->value ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) ?: 'Filter' }}</button>
    @if(request()->hasAny(['stock_status','search']))
        <a href="{{ route('portals.pharmacy.stock') }}" class="btn btn-ghost btn-sm">{{ __('public.pharmacy_portal.btn_clear', [], $l) ?: 'Clear' }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_source', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_action', [], $l) ?: 'Action' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php $med = $stock->medicine; @endphp
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}">
                        <span class="td-strong">{{ $med?->name ?? '—' }}</span>
                        <div class="td-muted">{{ $med?->generic_name }} {{ $med?->strength }}</div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}">
                        <span class="badge {{ $badgeFor($stock->stock_status->value) }}">@enum($stock->stock_status->value, 'stock_status')</span>
                        <select name="stock_status" form="stock-{{ $stock->id }}" class="form-control mt-6" required aria-label="{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}">
                            @foreach($statuses as $s)
                            <option value="{{ $s->value }}" {{ $stock->stock_status === $s ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="number" name="packs_available" form="stock-{{ $stock->id }}" class="form-control" min="0" max="1000000" value="{{ $stock->packs_available }}" aria-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="text" name="pack_size" form="stock-{{ $stock->id }}" class="form-control mt-6" maxlength="60" value="{{ $stock->pack_size }}" placeholder="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}" aria-label="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}">
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <input type="number" name="unit_price" form="stock-{{ $stock->id }}" class="form-control" min="0" step="0.01" value="{{ $stock->unit_price }}" aria-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <div class="td-muted">{{ $stock->currency }}</div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}">
                        <label class="form-check">
                            <input type="checkbox" name="reservation_enabled" form="stock-{{ $stock->id }}" value="1" {{ $stock->reservation_enabled ? 'checked' : '' }}>
                            <span class="td-muted">{{ __('public.pharmacy_portal.stock_reserve_hint', [], $l) }}</span>
                        </label>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_source', [], $l) }}">
                        @if($stock->source_system === $portalSource)
                            <span class="badge badge-success">{{ __('public.pharmacy_portal.stock_src_portal', [], $l) }}</span>
                        @else
                            <span class="badge badge-neutral">{{ __('public.pharmacy_portal.stock_src_other', ['source' => $stock->source_system ?: '—'], $l) }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}" class="td-muted">
                        {{ $stock->last_reported_at?->diffForHumans() ?? __('public.pharmacy_portal.stock_never', [], $l) }}
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_action', [], $l) ?: 'Action' }}">
                        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}" id="stock-{{ $stock->id }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="medicine_id" value="{{ $stock->medicine_id }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="stock_status_filter" value="{{ request('stock_status') }}">
                            <button type="submit" class="btn btn-primary btn-xs">
                                <i data-lucide="save"></i> {{ __('public.pharmacy_portal.stock_btn_save', [], $l) }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="td-muted empty-cell">{{ __('public.pharmacy_portal.stock_none', [], $l) }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $stocks?->links() }}</div>
</div>
@endif

@endsection
