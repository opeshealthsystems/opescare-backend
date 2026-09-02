@extends('layouts.portal')

@section('title', __('staff_inv.title_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Inventory')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_overview', [], app()->getLocale()) ?: 'Overview' }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_clinical', [], app()->getLocale()) ?: 'Clinical' }}</div>
    <a href="{{ route('portals.staff.appointments') }}" class="sidebar-link">
        <i data-lucide="calendar-check-2"></i>
        <span>{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</span>
    </a>
        </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_hr', [], app()->getLocale()) ?: 'HR & Staff' }}</div>
                </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_inventory', [], app()->getLocale()) ?: 'Inventory' }}</div>
        <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_operations', [], app()->getLocale()) ?: 'Operations' }}</div>
                        </div>
@endsection

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_inv.breadcrumb_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Inventory')

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_inv_pharm_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="page-subtitle mb-6">{{ __('public.stf_inv_pharm_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Summary Cards --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['total'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_total') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $summary['in_stock'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_in_stock') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['low_stock'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_low_stock') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['out_of_stock'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_out') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['expired'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_expired') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['recalled'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_pharm_stat_recalled') }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <select name="stock_status" class="filter-select">
        <option value="">{{ __('public.stf_inv_pharm_all_statuses') }}</option>
        @foreach(['in_stock','low_stock','out_of_stock'] as $s)
            <option value="{{ $s }}" {{ request('stock_status') === $s ? 'selected' : '' }}>@enum($s, 'stock_status')</option>
        @endforeach
    </select>
    @if($forms->isNotEmpty())
    <select name="form" class="filter-select">
        <option value="">{{ __('public.stf_inv_pharm_all_forms') }}</option>
        @foreach($forms as $f)
            <option value="{{ $f }}" {{ request('form') === $f ? 'selected' : '' }}>{{ $f }}</option>
        @endforeach
    </select>
    @endif
    <select name="is_expired" class="filter-select">
        <option value="">{{ __('staff_inv.filter_all', [], app()->getLocale()) ?: 'All' }}</option>
        <option value="1" {{ request('is_expired') === '1' ? 'selected' : '' }}>{{ __('public.stf_inv_pharm_expired_only') }}</option>
        <option value="0" {{ request('is_expired') === '0' ? 'selected' : '' }}>{{ __('public.stf_inv_pharm_not_expired') }}</option>
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.stf_inv_pharm_ph_search') }}" value="{{ request('search') }}">
    </label>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.stf_inv_pharm_filter_btn') }}
    </button>
    </form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($items->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="pill"></i></div>
                <h3>{{ __('public.stf_inv_pharm_empty_title') }}</h3>
                <p>{{ __('public.stf_inv_pharm_empty_desc') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_inv_pharm_col_medicine') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_form') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_qty') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_status') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_flags') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_updated') }}</th>
                            <th>{{ __('public.stf_inv_pharm_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $sBadge = match($item->stock_status) {
                                'in_stock'     => 'badge-success',
                                'low_stock'    => 'badge-warning',
                                'out_of_stock' => 'badge-danger',
                                default        => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_inv_pharm_col_medicine') }}">
                                <span class="td-strong">{{ $item->medicine_name }}</span>
                                <div class="td-muted">{{ $item->generic_name }}</div>
                            </td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_form') }}">{{ $item->form }} · {{ $item->strength }}</td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_qty') }}"><span class="td-strong">{{ number_format($item->available_quantity) }}</span></td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_status') }}">
                                <span class="badge {{ $sBadge }}">@enum($item->stock_status, 'stock_status')</span>
                            </td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_flags') }}">
                                @if($item->is_expired)   <span class="badge badge-danger badge-sm">{{ __('public.stf_inv_pharm_flag_expired') }}</span> @endif
                                @if($item->is_recalled)  <span class="badge badge-warning badge-sm">{{ __('public.stf_inv_pharm_flag_recalled') }}</span> @endif
                                @if($item->is_quarantined) <span class="badge badge-warning badge-sm">{{ __('public.stf_inv_pharm_flag_quarantine') }}</span> @endif
                                @if(!$item->is_expired && !$item->is_recalled && !$item->is_quarantined)
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_updated') }}" class="td-muted">
                                {{ \Carbon\Carbon::parse($item->last_stock_update)->format('M d, H:i') }}
                            </td>
                            <td data-label="{{ __('public.stf_inv_pharm_col_actions') }}">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-primary btn-xs"
                                        onclick="openRestockModal('{{ $item->id }}', '{{ addslashes($item->medicine_name) }}')">
                                        <i data-lucide="plus"></i>
                                        {{ __('public.stf_inv_pharm_btn_restock') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openDispenseModal('{{ $item->id }}', '{{ addslashes($item->medicine_name) }}')">
                                        <i data-lucide="minus"></i>
                                        {{ __('public.stf_inv_pharm_btn_dispense') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openFlagModal('{{ $item->id }}', '{{ addslashes($item->medicine_name) }}', {{ $item->is_expired ? 1 : 0 }}, {{ $item->is_recalled ? 1 : 0 }}, {{ $item->is_quarantined ? 1 : 0 }})">
                                        <i data-lucide="flag"></i>
                                        {{ __('public.stf_inv_pharm_btn_flags') }}
                                    </button>
                                    
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>


{{-- Restock Modal --}}
<div id="restock-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="plus"></i> {{ __('public.stf_inv_pharm_restock_title') }}</h3>
        <form id="restock-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p id="restock-name"></p>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_inv_pharm_lbl_qty_add') }}</label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeRestockModal()">{{ __('public.stf_inv_pharm_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="plus"></i>
                    {{ __('public.stf_inv_pharm_btn_restock') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Dispense Modal --}}
<div id="dispense-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="minus"></i> {{ __('public.stf_inv_pharm_dispense_title') }}</h3>
        <form id="dispense-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p id="dispense-name"></p>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_inv_pharm_lbl_qty_dispense') }}</label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDispenseModal()">{{ __('public.stf_inv_pharm_cancel') }}</button>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i data-lucide="minus"></i>
                    {{ __('public.stf_inv_pharm_btn_dispense_confirm') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Flag Modal --}}
<div id="flag-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="flag"></i> {{ __('public.stf_inv_pharm_flags_title') }}</h3>
        <form id="flag-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p id="flag-name"></p>
                <label class="form-check">
                    <input type="checkbox" id="flag-expired" name="is_expired" value="1"> {{ __('public.stf_inv_pharm_lbl_expired') }}
                </label>
                <label class="form-check">
                    <input type="checkbox" id="flag-recalled" name="is_recalled" value="1"> {{ __('public.stf_inv_pharm_lbl_recalled') }}
                </label>
                <label class="form-check">
                    <input type="checkbox" id="flag-quarantined" name="is_quarantined" value="1"> {{ __('public.stf_inv_pharm_lbl_quarantined') }}
                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeFlagModal()">{{ __('public.stf_inv_pharm_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.stf_inv_pharm_btn_save_flags') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var baseUrl = '{{ url('/portals/staff/inventory/pharmacy') }}';


    function openRestockModal(id, name) {
        document.getElementById('restock-name').textContent = name;
        document.getElementById('restock-form').action = baseUrl + '/' + id + '/restock';
        document.getElementById('restock-modal').removeAttribute('hidden');
    }
    function closeRestockModal() { document.getElementById('restock-modal').setAttribute('hidden',''); }
    document.getElementById('restock-modal').addEventListener('click', function(e) { if(e.target===this) closeRestockModal(); });

    function openDispenseModal(id, name) {
        document.getElementById('dispense-name').textContent = name;
        document.getElementById('dispense-form').action = baseUrl + '/' + id + '/dispense';
        document.getElementById('dispense-modal').removeAttribute('hidden');
    }
    function closeDispenseModal() { document.getElementById('dispense-modal').setAttribute('hidden',''); }
    document.getElementById('dispense-modal').addEventListener('click', function(e) { if(e.target===this) closeDispenseModal(); });

    function openFlagModal(id, name, expired, recalled, quarantined) {
        document.getElementById('flag-name').textContent = name;
        document.getElementById('flag-form').action = baseUrl + '/' + id + '/flag';
        document.getElementById('flag-expired').checked = !!expired;
        document.getElementById('flag-recalled').checked = !!recalled;
        document.getElementById('flag-quarantined').checked = !!quarantined;
        document.getElementById('flag-modal').removeAttribute('hidden');
    }
    function closeFlagModal() { document.getElementById('flag-modal').setAttribute('hidden',''); }
    document.getElementById('flag-modal').addEventListener('click', function(e) { if(e.target===this) closeFlagModal(); });
</script>
@endsection
