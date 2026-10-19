@extends('layouts.portal')

@section('title', __('staff_inv.title_blood', [], app()->getLocale()))

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()))


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_inv_blood_title') }}</h1>
        <p class="od-page__sub">{{ __('public.stf_inv_blood_subtitle') }}</p>
    </div>
    <div class="odc-head__actions">
        <button type="button" class="od-btn od-btn--primary" data-call="openUpsertModal">
            <i data-lucide="plus-circle"></i>
            {{ __('public.stf_inv_blood_update_stock_btn') }}
        </button>
    </div>
</header>

@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- What this screen is FOR. Stock typed here is published to the public Blood
     Finder, and that only happens when the facility has a public directory
     listing to publish onto — BloodAvailabilityProjector writes onto
     care_facilities rows linked back by facility_id and silently writes
     nothing when there are none. Saying so beats a clerk keeping a fridge
     up to date that no patient can ever see. --}}
@if($publishesToFinder)
    <div class="odc-note odc-note--info"><i data-lucide="radio-tower"></i><div>{{ __('public.stf_inv_blood_publishes_notice') }}</div></div>
@else
    <div class="odc-note odc-note--warn"><i data-lucide="triangle-alert"></i><div>{{ __('public.stf_inv_blood_no_listing_notice') }}</div></div>
@endif

{{-- Summary Cards --}}
<div class="od-kpis odc-kpis5">
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><x-od-icon name="droplet" /></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.stf_inv_blood_stat_total') }}</span><span class="od-kpi__value">{{ $summary['total_units'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="layers"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.stf_inv_blood_stat_groups') }}</span><span class="od-kpi__value">{{ $summary['groups_covered'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--red">
        <span class="od-kpi__icon"><i data-lucide="calendar-x"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.stf_inv_blood_stat_expired') }}</span><span class="od-kpi__value">{{ $summary['expired'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--rose">
        <span class="od-kpi__icon"><i data-lucide="shield-alert"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.stf_inv_blood_stat_unsafe') }}</span><span class="od-kpi__value">{{ $summary['unsafe'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><x-od-icon name="fridge-thermo" /></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.stf_inv_blood_stat_quarantined') }}</span><span class="od-kpi__value">{{ $summary['quarantined'] }}</span></span>
    </div>
</div>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('public.stf_inv_blood_title') }} <span class="od-card__count">({{ $items->count() }})</span></h2>
    </header>
    <div class="od-card__body">
    {{-- Filters --}}
    <form method="GET" action="{{ route('portals.staff.inventory.blood') }}" class="odc-toolbar">
        <select name="blood_group" class="od-select" aria-label="{{ __('public.stf_inv_blood_col_group') }}">
            <option value="">{{ __('public.stf_inv_blood_all_groups') }}</option>
            @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g)
                <option value="{{ $g }}" {{ request('blood_group') === $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
        </select>
        <select name="component" class="od-select" aria-label="{{ __('public.stf_inv_blood_col_component') }}">
            <option value="">{{ __('public.stf_inv_blood_all_components') }}</option>
            @foreach(['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets'] as $c)
                <option value="{{ $c }}" {{ request('component') === $c ? 'selected' : '' }}>@enum($c, 'blood_component')</option>
            @endforeach
        </select>
        <button type="submit" class="od-btn od-btn--secondary">
            <i data-lucide="filter"></i> {{ __('public.stf_inv_blood_filter_btn') }}
        </button>
        <a href="{{ route('portals.staff.inventory.blood') }}" class="od-btn od-btn--ghost">{{ __('public.stf_inv_blood_clear_btn') }}</a>
    </form>

        @if($items->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><x-od-icon name="droplet" /></span>
                <div class="od-empty__title">{{ __('public.stf_inv_blood_empty_title') }}</div>
                <p class="od-empty__text">{{ __('public.stf_inv_blood_empty_desc') }}</p>
                <button type="button" class="od-btn od-btn--primary" data-call="openUpsertModal">{{ __('public.stf_inv_blood_update_stock_btn') }}</button>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table odc-stack">
                    <thead>
                        <tr>
                            <th class="od-table__num">#</th>
                            <th>{{ __('public.stf_inv_blood_col_group') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_component') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_units') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_flags') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_updated') }}</th>
                            <th>{{ __('public.stf_inv_blood_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        @php
                            $qtyPill = $item->available_units <= 0 ? 'low' : ($item->available_units <= 5 ? 'reorder' : 'ready');
                        @endphp
                        <tr>
                            <td class="od-table__num">{{ $i + 1 }}</td>
                            <td data-label="{{ __('public.stf_inv_blood_col_group') }}">
                                {{-- Big group tag — same visual language as the public
                                     Blood Finder's group grid. --}}
                                <span class="odc-btag">{{ $item->blood_group }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_component') }}">
                                <span class="od-table__name">@enum($item->component)</span>
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_units') }}">
                                <span class="od-pill od-pill--{{ $qtyPill }}">{{ $item->available_units }}</span> {{ __('public.stf_inv_blood_units_label') }}
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_flags') }}">
                                @if($item->is_expired)    <span class="od-pill od-pill--urgent">{{ __('public.stf_inv_blood_flag_expired') }}</span> @endif
                                @if($item->is_unsafe)     <span class="od-pill od-pill--urgent">{{ __('public.stf_inv_blood_flag_unsafe') }}</span> @endif
                                @if($item->is_quarantined)<span class="od-pill od-pill--pending">{{ __('public.stf_inv_blood_flag_quarantine') }}</span> @endif
                                @if(!$item->is_expired && !$item->is_unsafe && !$item->is_quarantined)
                                    {{-- Screening state is said out loud, not implied by a dash. --}}
                                    <span class="odc-ok"><i data-lucide="check"></i> {{ __('finder_supply.blood_screening_clear') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_inv_blood_col_updated') }}" class="od-muted">
                                {{ \Carbon\Carbon::parse($item->last_stock_update)->translatedFormat('M d, H:i') }}
                            </td>
                            <td class="od-table__actions" data-label="{{ __('public.stf_inv_blood_col_actions') }}">
                                <div class="odc-rowacts">
                                    <button type="button" class="od-btn od-btn--row"
                                        data-call="openAdjustModal" data-args="{{ json_encode([$item->id, $item->blood_group, $item->component, 'add']) }}">
                                        <i data-lucide="plus"></i>
                                        {{ __('public.stf_inv_blood_btn_add') }}
                                    </button>
                                    <button type="button" class="od-btn od-btn--sm odc-btn-quiet"
                                        data-call="openAdjustModal" data-args="{{ json_encode([$item->id, $item->blood_group, $item->component, 'subtract']) }}">
                                        <i data-lucide="minus"></i>
                                        {{ __('public.stf_inv_blood_btn_use') }}
                                    </button>
                                    <button type="button" class="od-kebab" aria-label="{{ __('public.stf_inv_blood_btn_flags') }}" title="{{ __('public.stf_inv_blood_btn_flags') }}"
                                        data-call="openBloodFlagModal" data-args="{{ json_encode([$item->id, $item->blood_group . ' ' . $item->component, $item->is_expired ? 1 : 0, $item->is_unsafe ? 1 : 0, $item->is_quarantined ? 1 : 0]) }}">
                                        <i data-lucide="flag"></i>
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
</section>

{{-- Upsert (Add/Update) Modal --}}
<div id="upsert-modal" class="odc-modal" hidden>
    <div class="odc-modal__box" role="dialog" aria-modal="true" aria-labelledby="upsert-title">
        <h3 id="upsert-title" class="odc-modal__title"><x-od-icon name="droplet" /> {{ __('public.stf_inv_blood_modal_set_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.inventory.blood.upsert') }}" class="odc-form">
            @csrf
            <p class="od-muted">{{ __('public.stf_inv_blood_modal_set_note') }}</p>
            <div class="odc-label">
                {{-- Same `blood_group` field, radio chips instead of a
                     <select>: the grid mirrors the public Blood Finder. --}}
                <span>{{ __('public.stf_inv_blood_lbl_group') }}</span>
                <div class="odc-bgrid" role="radiogroup" aria-label="{{ __('finder_supply.blood_group_pick') }}">
                    @foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g)
                        <label class="odc-bchip">
                            <input type="radio" name="blood_group" value="{{ $g }}" {{ $g === 'O+' ? 'checked' : '' }} required>
                            <span>{{ $g }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <label class="odc-label">{{ __('public.stf_inv_blood_lbl_component') }}
                <select name="component" class="od-select" required>
                    @foreach(['whole_blood','packed_red_cells','fresh_frozen_plasma','platelets'] as $c)
                        <option value="{{ $c }}">@enum($c, 'blood_component')</option>
                    @endforeach
                </select>
            </label>
            <div class="odc-label">
                <label for="upsert-units">{{ __('public.stf_inv_blood_lbl_units') }}</label>
                <div class="odc-stepper">
                    <button type="button" aria-label="{{ __('finder_supply.stepper_minus') }}" data-call="fsStep" data-args="[&quot;upsert-units&quot;,-1]">&minus;</button>
                    <input type="number" id="upsert-units" name="available_units" class="od-input" required min="0" inputmode="numeric" value="0">
                    <button type="button" aria-label="{{ __('finder_supply.stepper_plus') }}" data-call="fsStep" data-args="[&quot;upsert-units&quot;,1]">+</button>
                </div>
            </div>
            @if($publishesToFinder)
            {{-- Honest, and true: BloodAvailabilityProjector republishes on save. --}}
            <div class="odc-note odc-note--info">
                <x-od-icon name="droplet" />
                <span>{{ __('finder_supply.blood_publish_note') }}</span>
            </div>
            @endif
            <div class="odc-actions">
                <button type="button" class="od-btn od-btn--secondary" data-call="closeUpsertModal">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--primary">
                    <i data-lucide="check"></i>
                    {{ __('public.stf_inv_blood_btn_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Adjust Modal --}}
<div id="adjust-modal" class="odc-modal" hidden>
    <div class="odc-modal__box" role="dialog" aria-modal="true" aria-labelledby="adjust-title">
        <h3 id="adjust-title" class="odc-modal__title">{{ __('public.stf_inv_blood_adjust_title') }}</h3>
        <form id="adjust-form" method="POST" action="" class="odc-form">
            @csrf
            <input type="hidden" id="adjust-direction" name="direction" value="add">
            <p id="adjust-label" class="od-muted"></p>
            <div class="odc-label">
                <label for="adjust-units">{{ __('public.stf_inv_blood_lbl_adj_units') }}</label>
                <div class="odc-stepper">
                    <button type="button" aria-label="{{ __('finder_supply.stepper_minus') }}" data-call="fsStep" data-args="[&quot;adjust-units&quot;,-1]">&minus;</button>
                    <input type="number" id="adjust-units" name="units" class="od-input" required min="1" inputmode="numeric" value="1">
                    <button type="button" aria-label="{{ __('finder_supply.stepper_plus') }}" data-call="fsStep" data-args="[&quot;adjust-units&quot;,1]">+</button>
                </div>
            </div>
            @if($publishesToFinder)
            <div class="odc-note odc-note--info">
                <x-od-icon name="droplet" />
                <span>{{ __('finder_supply.blood_publish_note') }}</span>
            </div>
            @endif
            <div class="odc-actions">
                <button type="button" class="od-btn od-btn--secondary" data-call="closeAdjustModal">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" id="adjust-btn" class="od-btn od-btn--primary">{{ __('public.stf_inv_blood_btn_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Blood Flag Modal --}}
<div id="blood-flag-modal" class="odc-modal" hidden>
    <div class="odc-modal__box" role="dialog" aria-modal="true" aria-labelledby="blood-flag-title">
        <h3 id="blood-flag-title" class="odc-modal__title"><i data-lucide="flag"></i> {{ __('public.stf_inv_blood_flags_title') }}</h3>
        <form id="blood-flag-form" method="POST" action="" class="odc-form">
            @csrf
            <p id="blood-flag-label" class="od-muted"></p>
            <label class="odc-check">
                <input type="checkbox" id="bf-expired" name="is_expired" value="1" class="od-check"> {{ __('public.stf_inv_blood_lbl_expired') }}
            </label>
            <label class="odc-check">
                <input type="checkbox" id="bf-unsafe" name="is_unsafe" value="1" class="od-check"> {{ __('public.stf_inv_blood_lbl_unsafe') }}
            </label>
            <label class="odc-check">
                <input type="checkbox" id="bf-quarantined" name="is_quarantined" value="1" class="od-check"> {{ __('public.stf_inv_blood_lbl_quarantined') }}
            </label>
            <div class="odc-actions">
                <button type="button" class="od-btn od-btn--secondary" data-call="closeBloodFlagModal">{{ __('public.stf_inv_blood_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--primary">{{ __('public.stf_inv_blood_btn_save_flags') }}</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection

@section('scripts')
<script @cspNonce>
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
        document.getElementById('adjust-title').textContent = direction === 'add' ? '{{ __('staff_inv.js_add_units', [], app()->getLocale()) }}' : '{{ __('staff_inv.js_use_units', [], app()->getLocale()) }}';
        document.getElementById('adjust-direction').value = direction;
        document.getElementById('adjust-form').action = bloodAdjustUrl.replace('__ID__', encodeURIComponent(id));
        var btn = document.getElementById('adjust-btn');
        btn.className = direction === 'add' ? 'od-btn od-btn--primary' : 'od-btn od-btn--danger';
        btn.textContent = direction === 'add' ? '{{ __('staff_inv.js_add', [], app()->getLocale()) }}' : '{{ __('staff_inv.js_use', [], app()->getLocale()) }}';
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
