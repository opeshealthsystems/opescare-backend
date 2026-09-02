@extends('layouts.portal')

@section('title', __('staff_inv.title_blood', [], app()->getLocale()) ?: 'Blood Bank Inventory')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff' }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.sidebar_lbl_overview', [], app()->getLocale()) ?: 'Overview' }}</div>
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
    <a href="{{ route('portals.staff.queue') }}" class="sidebar-link">
        <i data-lucide="list-ordered"></i>
        <span>{{ __('public.portal.nav_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</span>
    </a>
    <a href="{{ route('portals.staff.visits') }}" class="sidebar-link">
        <i data-lucide="stethoscope"></i>
        <span>{{ __('public.portal.nav_visits', [], app()->getLocale()) ?: 'Visits' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_hr', [], app()->getLocale()) ?: 'HR & Staff' }}</div>
    <a href="{{ route('portals.staff.hr.directory') }}" class="sidebar-link">
        <i data-lucide="users"></i>
        <span>{{ __('public.portal.nav_staff_directory', [], app()->getLocale()) ?: 'Directory' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.roster') }}" class="sidebar-link">
        <i data-lucide="calendar-range"></i>
        <span>{{ __('public.portal.nav_staff_roster', [], app()->getLocale()) ?: 'Duty Roster' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.shifts') }}" class="sidebar-link">
        <i data-lucide="clock"></i>
        <span>{{ __('public.portal.nav_staff_shifts', [], app()->getLocale()) ?: 'Shifts' }}</span>
    </a>
    <a href="{{ route('portals.staff.hr.leave') }}" class="sidebar-link">
        <i data-lucide="plane-takeoff"></i>
        <span>{{ __('public.portal.nav_staff_leave', [], app()->getLocale()) ?: 'Leave' }}</span>
    </a>
</div>
{{-- Blood Bank is carved OUT of the inventory_ops freeze (see the
     'inventory_ops' block in bootstrap/app.php): it is the only reachable
     writer of the public Blood Finder's data. Pharmacy stock and Supply Chain
     are NOT — they 404 while the flag is off, so they are guarded here rather
     than offered as dead links. --}}
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_inventory', [], app()->getLocale()) ?: 'Inventory' }}</div>
    @feature('inventory_ops')
    <a href="{{ route('portals.staff.inventory.pharmacy') }}" class="sidebar-link">
        <i data-lucide="pill"></i>
        <span>{{ __('public.portal.nav_inventory_pharmacy', [], app()->getLocale()) ?: 'Pharmacy' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.inventory.blood') }}" class="sidebar-link active">
        <i data-lucide="droplets"></i>
        <span>{{ __('public.portal.nav_inventory_blood', [], app()->getLocale()) ?: 'Blood Bank' }}</span>
    </a>
</div>
@feature('inventory_ops')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</div>
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i>
        <span>{{ __('staff_inv.nav_supply_chain', [], app()->getLocale()) ?: 'Supply Chain' }}</span>
    </a>
</div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('staff_inv.nav_lbl_operations', [], app()->getLocale()) ?: 'Operations' }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link">
        <i data-lucide="receipt"></i>
        <span>{{ __('public.portal.nav_billing', [], app()->getLocale()) ?: 'Billing' }}</span>
    </a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link">
        <i data-lucide="headset"></i>
        <span>{{ __('public.portal.nav_support', [], app()->getLocale()) ?: 'Support' }}</span>
    </a>
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link">
        <i data-lucide="upload-cloud"></i>
        <span>{{ __('public.portal.nav_data_import', [], app()->getLocale()) ?: 'Data Import' }}</span>
    </a>
    <a href="{{ route('portals.staff.search') }}" class="sidebar-link {{ request()->routeIs('portals.staff.search') ? 'active' : '' }}">
        <i data-lucide="search"></i>
        <span>{{ __('public.portal.nav_search', [], app()->getLocale()) ?: 'Global Search' }}</span>
    </a>
    <a href="{{ route('portals.staff.files.index') }}" class="sidebar-link {{ request()->routeIs('portals.staff.files*') ? 'active' : '' }}">
        <i data-lucide="paperclip"></i>
        <span>{{ __('public.portal.nav_files', [], app()->getLocale()) ?: 'Files & Attachments' }}</span>
    </a>
    <a href="{{ route('portals.staff.wards') }}" class="sidebar-link {{ request()->routeIs('portals.staff.wards*') ? 'active' : '' }}">
        <i data-lucide="bed"></i>
        <span>{{ __('public.portal.nav_wards', [], app()->getLocale()) ?: 'Wards & Beds' }}</span>
    </a>
</div>
@endsection

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_inv.breadcrumb_blood', [], app()->getLocale()) ?: 'Blood Bank')

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_inv_blood_title') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openUpsertModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.stf_inv_blood_update_stock_btn') }}
    </button>
</div>
<p class="page-subtitle mb-6">{{ __('public.stf_inv_blood_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- What this screen is FOR. Stock typed here is published to the public Blood
     Finder, and that only happens when the facility has a public directory
     listing to publish onto — BloodAvailabilityProjector writes onto
     care_facilities rows linked back by facility_id and silently writes
     nothing when there are none. Saying so beats a clerk keeping a fridge
     up to date that no patient can ever see. --}}
@if($publishesToFinder)
    <div class="alert alert-info mb-6">
        <i data-lucide="radio-tower"></i>
        <div>{{ __('public.stf_inv_blood_publishes_notice') }}</div>
    </div>
@else
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('public.stf_inv_blood_no_listing_notice') }}</div>
    </div>
@endif

{{-- Summary Cards --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $summary['total_units'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_blood_stat_total') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $summary['groups_covered'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_blood_stat_groups') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['expired'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_blood_stat_expired') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $summary['unsafe'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_blood_stat_unsafe') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $summary['quarantined'] }}</div>
        <div class="stat-card__label">{{ __('public.stf_inv_blood_stat_quarantined') }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('portals.staff.inventory.blood') }}" class="filter-bar">
    <select name="blood_group" class="filter-select">
        <option value="">{{ __('public.stf_inv_blood_all_groups') }}</option>
        @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g)
            <option value="{{ $g }}" {{ request('blood_group') === $g ? 'selected' : '' }}>{{ $g }}</option>
        @endforeach
    </select>
    <select name="component" class="filter-select">
        <option value="">{{ __('public.stf_inv_blood_all_components') }}</option>
        @foreach(['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets'] as $c)
            <option value="{{ $c }}" {{ request('component') === $c ? 'selected' : '' }}>@enum($c, 'blood_component')</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.stf_inv_blood_filter_btn') }}
    </button>
    <a href="{{ route('portals.staff.inventory.blood') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_inv_blood_clear_btn') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($items->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="droplets"></i></div>
                <h3>{{ __('public.stf_inv_blood_empty_title') }}</h3>
                <p>{{ __('public.stf_inv_blood_empty_desc') }}</p>
                <button type="button" class="btn btn-primary btn-sm mt-6" onclick="openUpsertModal()">{{ __('public.stf_inv_blood_update_stock_btn') }}</button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_inv_blood_col_group') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_component') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_units') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_flags') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_updated') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $qtyBadge = $item->available_units <= 0 ? 'badge-danger' : ($item->available_units <= 5 ? 'badge-warning' : 'badge-success');
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_inv_blood_col_group') }}">
                                <span class="td-strong">{{ $item->blood_group }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_component') }}">
                                <span class="badge badge-neutral">@enum($item->component)</span>
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_units') }}">
                                <span class="badge {{ $qtyBadge }}">{{ $item->available_units }}</span> {{ __('public.stf_inv_blood_units_label') }}
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_flags') }}">
                                @if($item->is_expired)    <span class="badge badge-danger badge-sm">{{ __('public.stf_inv_blood_flag_expired') }}</span> @endif
                                @if($item->is_unsafe)     <span class="badge badge-danger badge-sm">{{ __('public.stf_inv_blood_flag_unsafe') }}</span> @endif
                                @if($item->is_quarantined)<span class="badge badge-warning badge-sm">{{ __('public.stf_inv_blood_flag_quarantine') }}</span> @endif
                                @if(!$item->is_expired && !$item->is_unsafe && !$item->is_quarantined)
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_updated') }}" class="td-muted">
                                {{ \Carbon\Carbon::parse($item->last_stock_update)->format('M d, H:i') }}
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_actions') }}">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-primary btn-xs"
                                        onclick="openAdjustModal('{{ $item->id }}', '{{ $item->blood_group }}', '{{ addslashes($item->component) }}', 'add')">
                                        <i data-lucide="plus"></i>
                                        {{ __('public.stf_inv_blood_btn_add') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openAdjustModal('{{ $item->id }}', '{{ $item->blood_group }}', '{{ addslashes($item->component) }}', 'subtract')">
                                        <i data-lucide="minus"></i>
                                        {{ __('public.stf_inv_blood_btn_use') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openBloodFlagModal('{{ $item->id }}', '{{ $item->blood_group }} {{ addslashes($item->component) }}', {{ $item->is_expired ? 1 : 0 }}, {{ $item->is_unsafe ? 1 : 0 }}, {{ $item->is_quarantined ? 1 : 0 }})">
                                        <i data-lucide="flag"></i>
                                        {{ __('public.stf_inv_blood_btn_flags') }}
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

{{-- Upsert (Add/Update) Modal --}}
<div id="upsert-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="droplets"></i> {{ __('public.stf_inv_blood_modal_set_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.inventory.blood.upsert') }}">
            @csrf
            <div class="modal__body">
                <p>{{ __('public.stf_inv_blood_modal_set_note') }}</p>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_inv_blood_lbl_group') }}</label>
                        <select name="blood_group" class="form-control" required>
                            @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_inv_blood_lbl_component') }}</label>
                        <select name="component" class="form-control" required>
                            @foreach(['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets'] as $c)
                                <option value="{{ $c }}">@enum($c, 'blood_component')</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_inv_blood_lbl_units') }}</label>
                    <input type="number" name="available_units" class="form-control" required min="0" value="0">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeUpsertModal()">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="save"></i>
                    {{ __('public.stf_inv_blood_btn_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Adjust Modal --}}
<div id="adjust-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 id="adjust-title" class="modal__title"><i data-lucide="plus-minus"></i> {{ __('public.stf_inv_blood_adjust_title') }}</h3>
        <form id="adjust-form" method="POST" action="">
            @csrf
            <input type="hidden" id="adjust-direction" name="direction" value="add">
            <div class="modal__body">
                <p id="adjust-label"></p>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_inv_blood_lbl_adj_units') }}</label>
                    <input type="number" name="units" class="form-control" required min="1" value="1">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeAdjustModal()">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" id="adjust-btn" class="btn btn-primary btn-sm">{{ __('public.stf_inv_blood_btn_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Blood Flag Modal --}}
<div id="blood-flag-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="flag"></i> {{ __('public.stf_inv_blood_flags_title') }}</h3>
        <form id="blood-flag-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p id="blood-flag-label"></p>
                <label class="form-check">
                    <input type="checkbox" id="bf-expired" name="is_expired" value="1"> {{ __('public.stf_inv_blood_lbl_expired') }}
                </label>
                <label class="form-check">
                    <input type="checkbox" id="bf-unsafe" name="is_unsafe" value="1"> {{ __('public.stf_inv_blood_lbl_unsafe') }}
                </label>
                <label class="form-check">
                    <input type="checkbox" id="bf-quarantined" name="is_quarantined" value="1"> {{ __('public.stf_inv_blood_lbl_quarantined') }}
                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeBloodFlagModal()">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.stf_inv_blood_btn_save_flags') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var bloodBase = '{{ url('/portals/staff/inventory/blood') }}';

    function openUpsertModal()  { document.getElementById('upsert-modal').removeAttribute('hidden'); }
    function closeUpsertModal() { document.getElementById('upsert-modal').setAttribute('hidden',''); }
    document.getElementById('upsert-modal').addEventListener('click', function(e) { if(e.target===this) closeUpsertModal(); });

    function openAdjustModal(id, group, component, direction) {
        document.getElementById('adjust-label').textContent = group + ' · ' + component.replace(/_/g,' ');
        document.getElementById('adjust-title').textContent = direction === 'add' ? '{{ __('staff_inv.js_add_units', [], app()->getLocale()) ?: 'Add Units' }}' : '{{ __('staff_inv.js_use_units', [], app()->getLocale()) ?: 'Use Units' }}';
        document.getElementById('adjust-direction').value = direction;
        document.getElementById('adjust-form').action = bloodBase + '/' + id + '/adjust';
        var btn = document.getElementById('adjust-btn');
        btn.className = direction === 'add' ? 'btn btn-primary btn-sm' : 'btn btn-warning btn-sm';
        btn.textContent = direction === 'add' ? '{{ __('staff_inv.js_add', [], app()->getLocale()) ?: 'Add' }}' : '{{ __('staff_inv.js_use', [], app()->getLocale()) ?: 'Use' }}';
        document.getElementById('adjust-modal').removeAttribute('hidden');
    }
    function closeAdjustModal() { document.getElementById('adjust-modal').setAttribute('hidden',''); }
    document.getElementById('adjust-modal').addEventListener('click', function(e) { if(e.target===this) closeAdjustModal(); });

    function openBloodFlagModal(id, label, expired, unsafe, quarantined) {
        document.getElementById('blood-flag-label').textContent = label;
        document.getElementById('blood-flag-form').action = bloodBase + '/' + id + '/flag';
        document.getElementById('bf-expired').checked = !!expired;
        document.getElementById('bf-unsafe').checked = !!unsafe;
        document.getElementById('bf-quarantined').checked = !!quarantined;
        document.getElementById('blood-flag-modal').removeAttribute('hidden');
    }
    function closeBloodFlagModal() { document.getElementById('blood-flag-modal').setAttribute('hidden',''); }
    document.getElementById('blood-flag-modal').addEventListener('click', function(e) { if(e.target===this) closeBloodFlagModal(); });
</script>
@endsection
