@extends('layouts.portal')
@section('title', __('public.lab_portal.enter_result_title', [], app()->getLocale()) ?: 'Enter Result')
@section('sidebar_role_badge')
<div class="sidebar-role-badge"><i data-lucide="microscope"></i> {{ __('public.lab_portal.role_badge', [], app()->getLocale()) ?: 'Laboratory' }}</div>
@endsection
@section('sidebar_user_role', __('public.lab_portal.role_label', [], app()->getLocale()) ?: 'Lab Technician')
@section('sidebar_nav')@include('portals.lab._sidebar')@endsection
@section('breadcrumb_home', __('public.lab_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Lab Portal')
@section('breadcrumb_home_url', route('portals.lab.dashboard'))
@section('breadcrumb_section', __('public.lab_portal.enter_result_title', [], app()->getLocale()) ?: 'Enter Result')
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.lab_portal.enter_result_title', [], $l) ?: 'Enter result' }}</h2>
    <p class="page-subtitle">{{ $order->test_name }} · {{ $order->patient?->full_name ?? $order->patient_id }}</p>
</div>

@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="panel" style="max-width:680px;">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.lab.orders.result.store', $order->id) }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;"><label class="form-label" for="parameter_name">{{ __('public.lab_portal.f_parameter', [], $l) ?: 'Parameter / test' }}</label>
                    <input type="text" id="parameter_name" name="parameter_name" class="form-control" maxlength="160" value="{{ old('parameter_name', $order->test_name) }}" required></div>
                <div><label class="form-label" for="value">{{ __('public.lab_portal.f_value', [], $l) ?: 'Value' }}</label>
                    <input type="text" id="value" name="value" class="form-control" maxlength="160" value="{{ old('value') }}" required></div>
                <div><label class="form-label" for="unit">{{ __('public.lab_portal.f_unit', [], $l) ?: 'Unit' }}</label>
                    <input type="text" id="unit" name="unit" class="form-control" maxlength="40" value="{{ old('unit') }}"></div>
                <div><label class="form-label" for="reference_range">{{ __('public.lab_portal.f_range', [], $l) ?: 'Reference range' }}</label>
                    <input type="text" id="reference_range" name="reference_range" class="form-control" maxlength="80" value="{{ old('reference_range') }}"></div>
                <div><label class="form-label" for="flag">{{ __('public.lab_portal.f_flag', [], $l) ?: 'Flag' }}</label>
                    <select id="flag" name="flag" class="form-control">
                        <option value="N">{{ __('public.lab_portal.flag_normal', [], $l) ?: 'Normal' }}</option>
                        <option value="H">{{ __('public.lab_portal.flag_high', [], $l) ?: 'High' }}</option>
                        <option value="L">{{ __('public.lab_portal.flag_low', [], $l) ?: 'Low' }}</option>
                        <option value="HH">{{ __('public.lab_portal.flag_critical_high', [], $l) ?: 'Critical high' }}</option>
                        <option value="LL">{{ __('public.lab_portal.flag_critical_low', [], $l) ?: 'Critical low' }}</option>
                        <option value="abnormal">{{ __('public.lab_portal.flag_abnormal', [], $l) ?: 'Abnormal' }}</option>
                    </select></div>
                <div style="grid-column:1/-1;"><label class="form-label" for="notes">{{ __('public.lab_portal.f_notes', [], $l) ?: 'Notes' }}</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div>
            </div>
            <div style="margin-top:1rem;display:flex;gap:.6rem;">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('public.lab_portal.save_result', [], $l) ?: 'Save result' }}</button>
                <a href="{{ route('portals.lab.orders') }}" class="btn btn-ghost">{{ __('public.lab_portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
            </div>
        </form>
    </div>
</div>

@endsection
