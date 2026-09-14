@extends('layouts.portal')
@section('title', __('pharmacy_ops.reorder_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_reorder'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.reorder_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.reorder_subtitle') }}</p>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="trending-down"></i> {{ __('pharmacy_ops.below_title') }}</h3>
        <span class="badge {{ $below->isEmpty() ? 'badge-success' : 'badge-danger' }}">{{ $below->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
            </tr></thead>
            <tbody>
            @forelse($below as $row)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-strong">{{ $row->medicine?->name ?? $row->name }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="fs-mono fs-delta--out">{{ (int) $row->usable_quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="fs-mono">{{ (int) $row->rule_level }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="fs-mono">{{ (int) $row->rule_quantity ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="td-muted empty-cell">{{ __('pharmacy_ops.below_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body td-muted">{{ __('pharmacy_ops.no_auto_order') }}</div>
</div>

<details class="panel" @if($errors->any() || old('medicine_id')) open @endif>
    <summary class="panel-header" style="cursor:pointer;">
        <i data-lucide="plus-circle"></i> {{ __('pharmacy_ops.rule_form_title') }}
    </summary>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.reorder.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="rr-medicine">{{ __('pharmacy_ops.lbl_medicine') }}</label>
                    <select name="medicine_id" id="rr-medicine" class="form-control" required>
                        <option value="">{{ __('pharmacy_ops.choose_medicine') }}</option>
                        @foreach($catalog as $m)
                        <option value="{{ $m->id }}" {{ old('medicine_id') === $m->id ? 'selected' : '' }}>{{ $m->name }}@if($m->form) · {{ $m->form }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="rr-level">{{ __('pharmacy_ops.lbl_min_level') }}</label>
                    <input type="number" name="reorder_level" id="rr-level" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_level') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="rr-qty">{{ __('pharmacy_ops.lbl_suggest_qty') }}</label>
                    <input type="number" name="reorder_quantity" id="rr-qty" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_quantity') }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_save_rule') }}</button>
            </div>
        </form>
    </div>
</details>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list"></i> {{ __('pharmacy_ops.rules_title') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php $rule = $item->reorderRule->first(); @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-strong">{{ $item->medicine?->name ?? $item->name }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="fs-mono">{{ $usable[$item->id] ?? 0 }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="fs-mono">{{ $rule ? $rule->reorder_level : __('pharmacy_ops.no_rule') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="fs-mono">{{ $rule && $rule->reorder_quantity ? $rule->reorder_quantity : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="td-muted empty-cell">{{ __('pharmacy_ops.rules_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
