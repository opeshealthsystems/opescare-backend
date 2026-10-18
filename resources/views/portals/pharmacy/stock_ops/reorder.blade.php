@extends('layouts.portal')
@section('title', __('pharmacy_ops.reorder_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_reorder'))

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.reorder_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.reorder_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="#rule-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.rule_form_title') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<div class="ph-kpis">
    <div class="stat-card {{ $below->isEmpty() ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__icon"><i data-lucide="trending-down"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.below_title') }}</div>
        <div class="stat-card__value">{{ $below->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_ops.no_auto_order') }}</div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="list"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.rules_title') }}</div>
        <div class="stat-card__value">{{ $items->filter(fn ($i) => $i->reorderRule->first() !== null)->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_stock_desk.all_items') }}: {{ $items->count() }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="trending-down"></i> {{ __('pharmacy_ops.below_title') }} <span class="ph-count-inline">({{ $below->count() }})</span></h3>
        <span class="badge {{ $below->isEmpty() ? 'badge-success' : 'badge-danger' }}">{{ $below->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($below as $row)
                @php $rName = $row->medicine?->name ?? $row->name; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $rName, 'tone' => (int) $row->usable_quantity <= 0 ? 'red' : 'amber'])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.items.show', $row->id) }}">{{ $rName }}</a></div>
                                <div class="ph-cell__sub">{{ $row->medicine?->strength }} {{ $row->medicine?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="fs-mono fs-delta--out">{{ (int) $row->usable_quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="td-num">{{ (int) $row->rule_level }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="td-num">{{ (int) $row->rule_quantity ?: '—' }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $row->medicine_id]) }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="check-circle"></i>
                        <p>{{ __('pharmacy_ops.below_empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-foot">{{ __('pharmacy_ops.no_auto_order') }}</div>
</div>

<details class="panel" id="rule-form" @if($errors->any() || old('medicine_id')) open @endif>
    <summary class="panel-header">
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
        <h3 class="panel-title"><i data-lucide="list"></i> {{ __('pharmacy_ops.rules_title') }} <span class="ph-count-inline">({{ $items->count() }})</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php $rule = $item->reorderRule->first(); $iName = $item->medicine?->name ?? $item->name; $iUse = $usable[$item->id] ?? 0; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $iName, 'tone' => $rule && $iUse < $rule->reorder_level ? 'amber' : ($rule ? 'green' : 'grey')])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}">{{ $iName }}</a></div>
                                <div class="ph-cell__sub">{{ $item->medicine?->strength }} {{ $item->medicine?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="td-num">{{ $iUse }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="fs-mono">
                        @if($rule) <span class="td-num">{{ $rule->reorder_level }}</span> @else <span class="badge badge-neutral">{{ __('pharmacy_ops.no_rule') }}</span> @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="td-num">{{ $rule && $rule->reorder_quantity ? $rule->reorder_quantity : '—' }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}#reorder" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="list"></i>
                        <p>{{ __('pharmacy_ops.rules_empty') }}</p>
                        <a href="#rule-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.rule_form_title') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
