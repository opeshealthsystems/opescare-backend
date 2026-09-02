@extends('layouts.portal')

@section('title', __('staff_clinical.rx_new_title'))

@section('breadcrumb_home', __('public.staff_portal.clin_lab_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_clinical.rx_breadcrumb_new'))

@section('content')

<div class="page-head">
    <h2>{{ __('staff_clinical.rx_new_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.prescriptions') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        {{ __('public.stf_back') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('staff_clinical.rx_new_subtitle') }}</p>

@if(session('error'))
    <div class="alert alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <i data-lucide="triangle-alert"></i>
        <div>
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="alert alert-info mb-4">
    <i data-lucide="lock"></i>
    <div>{{ __('staff_clinical.rx_immutable_note') }}</div>
</div>

@php
    $oldItems = old('items');
    $rows     = (is_array($oldItems) && count($oldItems)) ? array_values($oldItems) : [[]];
@endphp

<form method="POST" action="{{ route('portals.staff.prescriptions.store') }}" id="rx-form">
    @csrf

    <div class="panel mb-4">
        <div class="panel-body">
            <div class="form-group mb-4">
                <label class="form-label" for="rx-patient">{{ __('staff_clinical.rx_patient') }}</label>
                @if($patients->isEmpty())
                    <p class="td-muted">{{ __('staff_clinical.rx_no_patients') }}</p>
                @else
                    <select name="patient_id" id="rx-patient" class="form-control" required>
                        <option value="">{{ __('staff_clinical.rx_select_patient') }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') === $p->id ? 'selected' : '' }}>
                                {{ trim(($p->last_name ?? '') . ' ' . ($p->first_name ?? '')) }} — {{ $p->health_id }}
                            </option>
                        @endforeach
                    </select>
                    <p class="form-hint">{{ __('staff_clinical.rx_patient_hint') }}</p>
                @endif
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label" for="rx-validity">{{ __('staff_clinical.rx_validity_days') }}</label>
                    <input type="number" name="validity_days" id="rx-validity" class="form-control"
                           min="1" max="365" value="{{ old('validity_days', $defaultValidity) }}">
                    <p class="form-hint">{{ __('staff_clinical.rx_validity_hint') }}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="rx-notes">{{ __('staff_clinical.rx_notes') }}</label>
                <textarea name="notes" id="rx-notes" class="form-control" rows="2"
                          placeholder="{{ __('staff_clinical.rx_notes_ph') }}">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-header">
            <i data-lucide="pill"></i>
            <span class="panel-title">{{ __('staff_clinical.rx_items') }}</span>
        </div>
        <div class="panel-body">
            <div id="rx-items">
                @foreach($rows as $i => $row)
                <div class="rx-item-row panel mb-4" data-rx-row>
                    <div class="panel-body">
                        <div class="form-group mb-4">
                            <label class="form-label">{{ __('staff_clinical.rx_medicine') }}</label>
                            <select name="items[{{ $i }}][medicine_id]" class="form-control" required data-rx-field="medicine_id">
                                <option value="">{{ __('staff_clinical.rx_select_medicine') }}</option>
                                @foreach($medicines as $m)
                                    <option value="{{ $m->id }}" {{ ($row['medicine_id'] ?? null) === $m->id ? 'selected' : '' }}>
                                        {{ $m->name }}@if($m->strength) — {{ $m->strength }}@endif @if($m->form)({{ $m->form }})@endif
                                        @if($m->is_controlled) [{{ __('staff_clinical.rx_controlled') }}]@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-row mb-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('staff_clinical.rx_dose') }}</label>
                                <input type="text" name="items[{{ $i }}][dose]" class="form-control" maxlength="100"
                                       data-rx-field="dose"
                                       placeholder="{{ __('staff_clinical.rx_dose_ph') }}" value="{{ $row['dose'] ?? '' }}">
                                <p class="form-hint">{{ __('staff_clinical.rx_dose_hint') }}</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('staff_clinical.rx_frequency') }}</label>
                                <input type="text" name="items[{{ $i }}][frequency]" class="form-control" maxlength="100" required
                                       data-rx-field="frequency"
                                       placeholder="{{ __('staff_clinical.rx_frequency_ph') }}" value="{{ $row['frequency'] ?? '' }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">{{ __('staff_clinical.rx_route') }}</label>
                                <select name="items[{{ $i }}][route]" class="form-control" data-rx-field="route">
                                    <option value="">{{ __('staff_clinical.opt_select') }}</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route }}" {{ ($row['route'] ?? null) === $route ? 'selected' : '' }}>
                                            @enum($route, 'medication_route')
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('staff_clinical.rx_duration_days') }}</label>
                                <input type="number" name="items[{{ $i }}][duration_days]" class="form-control" min="1" max="365"
                                       data-rx-field="duration_days" value="{{ $row['duration_days'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('staff_clinical.rx_quantity') }}</label>
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control" min="1" max="1000"
                                       data-rx-field="quantity" value="{{ $row['quantity'] ?? '' }}">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-ghost btn-sm" data-rx-remove hidden>
                                <i data-lucide="trash-2"></i>
                                {{ __('staff_clinical.rx_remove_item') }}
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-secondary btn-sm" id="rx-add-item">
                <i data-lucide="plus"></i>
                {{ __('staff_clinical.rx_add_item') }}
            </button>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary" @disabled($patients->isEmpty())>
            <i data-lucide="clipboard-plus"></i>
            {{ __('staff_clinical.rx_submit') }}
        </button>
        <a href="{{ route('portals.staff.prescriptions') }}" class="btn btn-ghost">{{ __('public.staff_portal.btn_cancel') }}</a>
    </div>
</form>

<script>
(function () {
    var list   = document.getElementById('rx-items');
    var addBtn = document.getElementById('rx-add-item');
    if (!list || !addBtn) { return; }

    function reindex() {
        var rows = list.querySelectorAll('[data-rx-row]');
        rows.forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            var remove = row.querySelector('[data-rx-remove]');
            if (remove) { remove.hidden = rows.length < 2; }
        });
    }

    addBtn.addEventListener('click', function () {
        var rows  = list.querySelectorAll('[data-rx-row]');
        var clone = rows[rows.length - 1].cloneNode(true);
        clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
        clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        list.appendChild(clone);
        reindex();
        if (window.lucide && window.lucide.createIcons) { window.lucide.createIcons(); }
    });

    list.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-rx-remove]');
        if (!trigger) { return; }
        if (list.querySelectorAll('[data-rx-row]').length < 2) { return; }
        trigger.closest('[data-rx-row]').remove();
        reindex();
    });

    reindex();
})();
</script>

@endsection
