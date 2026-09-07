@extends('layouts.portal')

@section('title', __('staff_inv.title_blood', [], app()->getLocale()) ?: 'Blood Bank Inventory')

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_inv.breadcrumb_blood', [], app()->getLocale()) ?: 'Blood Bank')

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_inv_blood_title') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm fs-tap" onclick="openUpsertModal()">
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
                <button type="button" class="btn btn-primary btn-sm mt-6 fs-tap" onclick="openUpsertModal()">{{ __('public.stf_inv_blood_update_stock_btn') }}</button>
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
                                {{-- Big group tag — same visual language as the public
                                     Blood Finder's group grid. --}}
                                <span class="fs-bgroup-tag">{{ $item->blood_group }}</span>
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
                                    {{-- Screening state is said out loud, not implied by a dash. --}}
                                    <span class="fs-screen-ok">@include('partials.finder.icon', ['name' => 'check', 'size' => 12]) {{ __('finder_supply.blood_screening_clear') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_updated') }}" class="td-muted">
                                {{ \Carbon\Carbon::parse($item->last_stock_update)->format('M d, H:i') }}
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_actions') }}">
                                <div class="row-actions-inline">
                                    <button type="button" class="btn btn-primary btn-xs fs-tap"
                                        onclick="openAdjustModal('{{ $item->id }}', '{{ $item->blood_group }}', '{{ addslashes($item->component) }}', 'add')">
                                        <i data-lucide="plus"></i>
                                        {{ __('public.stf_inv_blood_btn_add') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs fs-tap"
                                        onclick="openAdjustModal('{{ $item->id }}', '{{ $item->blood_group }}', '{{ addslashes($item->component) }}', 'subtract')">
                                        <i data-lucide="minus"></i>
                                        {{ __('public.stf_inv_blood_btn_use') }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs fs-tap"
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
                <div class="form-group">
                    {{-- Same `blood_group` field, radio chips instead of a
                         <select>: the grid mirrors the public Blood Finder. --}}
                    <label class="form-label">{{ __('public.stf_inv_blood_lbl_group') }}</label>
                    <div class="fs-bgrid" role="radiogroup" aria-label="{{ __('finder_supply.blood_group_pick') }}">
                        @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g)
                            <label class="fs-bchip">
                                <input type="radio" name="blood_group" value="{{ $g }}" {{ $g === 'O+' ? 'checked' : '' }} required>
                                <span>{{ $g }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_inv_blood_lbl_component') }}</label>
                    <select name="component" class="form-control" required>
                        @foreach(['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets'] as $c)
                            <option value="{{ $c }}">@enum($c, 'blood_component')</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="upsert-units">{{ __('public.stf_inv_blood_lbl_units') }}</label>
                    <div class="fs-stepper">
                        <button type="button" aria-label="{{ __('finder_supply.stepper_minus') }}" onclick="fsStep('upsert-units', -1)">&minus;</button>
                        <input type="number" id="upsert-units" name="available_units" required min="0" inputmode="numeric" value="0">
                        <button type="button" aria-label="{{ __('finder_supply.stepper_plus') }}" onclick="fsStep('upsert-units', 1)">+</button>
                    </div>
                </div>
                @if($publishesToFinder)
                {{-- Honest, and true: BloodAvailabilityProjector republishes on save. --}}
                <div class="fs-publish-note">
                    @include('partials.finder.icon', ['name' => 'droplet', 'size' => 15])
                    <span>{{ __('finder_supply.blood_publish_note') }}</span>
                </div>
                @endif
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeUpsertModal()">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm fs-tap">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
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
                    <label class="form-label" for="adjust-units">{{ __('public.stf_inv_blood_lbl_adj_units') }}</label>
                    <div class="fs-stepper">
                        <button type="button" aria-label="{{ __('finder_supply.stepper_minus') }}" onclick="fsStep('adjust-units', -1)">&minus;</button>
                        <input type="number" id="adjust-units" name="units" required min="1" inputmode="numeric" value="1">
                        <button type="button" aria-label="{{ __('finder_supply.stepper_plus') }}" onclick="fsStep('adjust-units', 1)">+</button>
                    </div>
                </div>
                @if($publishesToFinder)
                <div class="fs-publish-note">
                    @include('partials.finder.icon', ['name' => 'droplet', 'size' => 15])
                    <span>{{ __('finder_supply.blood_publish_note') }}</span>
                </div>
                @endif
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeAdjustModal()">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" id="adjust-btn" class="btn btn-primary btn-sm fs-tap">{{ __('public.stf_inv_blood_btn_confirm') }}</button>
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
    // Built from the named routes, with the id substituted client-side. The old
    // hand-written '/portals/staff/inventory/blood' prefix is a GET-only legacy
    // redirect, so both of these modals posted to a 405 and saved nothing.
    var bloodAdjustUrl = '{{ route('portals.staff.inventory.blood.adjust', ['id' => '__ID__']) }}';
    var bloodFlagUrl   = '{{ route('portals.staff.inventory.blood.flag', ['id' => '__ID__']) }}';

    // Big-thumb stepper: nudge a numeric input without leaving the modal.
    function fsStep(id, delta) {
        var input = document.getElementById(id);
        var min   = parseInt(input.min || '0', 10);
        var next  = (parseInt(input.value, 10) || 0) + delta;
        input.value = Math.max(min, next);
    }

    function openUpsertModal()  { document.getElementById('upsert-modal').removeAttribute('hidden'); }
    function closeUpsertModal() { document.getElementById('upsert-modal').setAttribute('hidden',''); }
    document.getElementById('upsert-modal').addEventListener('click', function(e) { if(e.target===this) closeUpsertModal(); });

    function openAdjustModal(id, group, component, direction) {
        document.getElementById('adjust-label').textContent = group + ' · ' + component.replace(/_/g,' ');
        document.getElementById('adjust-title').textContent = direction === 'add' ? '{{ __('staff_inv.js_add_units', [], app()->getLocale()) ?: 'Add Units' }}' : '{{ __('staff_inv.js_use_units', [], app()->getLocale()) ?: 'Use Units' }}';
        document.getElementById('adjust-direction').value = direction;
        document.getElementById('adjust-form').action = bloodAdjustUrl.replace('__ID__', encodeURIComponent(id));
        var btn = document.getElementById('adjust-btn');
        btn.className = direction === 'add' ? 'btn btn-primary btn-sm fs-tap' : 'btn btn-warning btn-sm fs-tap';
        btn.textContent = direction === 'add' ? '{{ __('staff_inv.js_add', [], app()->getLocale()) ?: 'Add' }}' : '{{ __('staff_inv.js_use', [], app()->getLocale()) ?: 'Use' }}';
        document.getElementById('adjust-modal').removeAttribute('hidden');
    }
    function closeAdjustModal() { document.getElementById('adjust-modal').setAttribute('hidden',''); }
    document.getElementById('adjust-modal').addEventListener('click', function(e) { if(e.target===this) closeAdjustModal(); });

    function openBloodFlagModal(id, label, expired, unsafe, quarantined) {
        document.getElementById('blood-flag-label').textContent = label;
        document.getElementById('blood-flag-form').action = bloodFlagUrl.replace('__ID__', encodeURIComponent(id));
        document.getElementById('bf-expired').checked = !!expired;
        document.getElementById('bf-unsafe').checked = !!unsafe;
        document.getElementById('bf-quarantined').checked = !!quarantined;
        document.getElementById('blood-flag-modal').removeAttribute('hidden');
    }
    function closeBloodFlagModal() { document.getElementById('blood-flag-modal').setAttribute('hidden',''); }
    document.getElementById('blood-flag-modal').addEventListener('click', function(e) { if(e.target===this) closeBloodFlagModal(); });
</script>
@endsection
