@extends('layouts.portal')
@section('title', __('public.developer_portal.page_create_prod_req', [], app()->getLocale()))
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.production_requests') }}">{{ __('public.developer_portal.nav_prod_access', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('public.developer_portal.page_create_prod_req', [], $l) }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('public.developer_portal.page_create_prod_req', [], $l) }}</h2>
    </div>

    <div class="panel form-panel">
        <div class="panel-body">

            <div class="alert alert-warning mb-6">
                <i data-lucide="alert-triangle"></i>
                <div>{{ __('public.developer_portal.warn_prod_real_data', [], $l) }}</div>
            </div>

            <form method="POST" action="{{ route('portals.developer.production_requests.store') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.fld_app', [], $l) }}</label>
                    <select name="integration_client_id" required class="form-control">
                        <option value="">{{ __('public.developer_portal.ph_select_app', [], $l) }}</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->client_id }}" {{ old('integration_client_id') === $client->client_id ? 'selected' : '' }}>
                            {{ $client->name ?? $client->client_id }}
                        </option>
                        @endforeach
                    </select>
                    @error('integration_client_id') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.col_use_case', [], $l) }}</label>
                    <input type="text" name="use_case" value="{{ old('use_case') }}" required class="form-control"
                           placeholder="{{ __('dev_extra.ph_use_case', [], $l) }}">
                    @error('use_case') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.fld_tech_desc', [], $l) }} <span class="td-muted">{{ __('public.developer_portal.fld_tech_desc_min', [], $l) }}</span></label>
                    <textarea name="technical_description" rows="4" required minlength="50" class="form-control"
                              placeholder="{{ __('public.developer_portal.ph_tech_desc', [], $l) }}">{{ old('technical_description') }}</textarea>
                    @error('technical_description') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.fld_requested_scopes', [], $l) }} <span class="td-muted">{{ __('public.developer_portal.fld_all_apply', [], $l) }}</span></label>
                    <div class="form-row">
                        @foreach($scopeOptions as $scope)
                        <label class="form-check">
                            <input type="checkbox" name="requested_scopes[]" value="{{ $scope }}"
                                   {{ in_array($scope, old('requested_scopes', [])) ? 'checked' : '' }}>
                            <span class="mono">{{ $scope }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('requested_scopes') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.developer_portal.fld_est_daily_req', [], $l) }}</label>
                        <select name="estimated_daily_requests" class="form-control">
                            <option value="">{{ __('public.developer_portal.ph_select_range', [], $l) }}</option>
                            <option value="< 100">{{ __('public.developer_portal.opt_less_100', [], $l) }}</option>
                            <option value="100–1 000">{{ __('public.developer_portal.opt_100_1000', [], $l) }}</option>
                            <option value="1 000–10 000">{{ __('public.developer_portal.opt_1000_10000', [], $l) }}</option>
                            <option value="10 000–100 000">{{ __('public.developer_portal.opt_10000_100000', [], $l) }}</option>
                            <option value="> 100 000">{{ __('public.developer_portal.opt_more_100000', [], $l) }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.developer_portal.fld_data_residency', [], $l) }}</label>
                        <input type="text" name="data_residency_region" value="{{ old('data_residency_region') }}" class="form-control"
                               placeholder="{{ __('public.developer_portal.ph_data_residency', [], $l) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="handles_patient_data" value="1" {{ old('handles_patient_data') ? 'checked' : '' }}>
                        {{ __('public.developer_portal.chk_handles_patient_data', [], $l) }}
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="security_review_done" value="1" {{ old('security_review_done') ? 'checked' : '' }}>
                        {{ __('public.developer_portal.chk_security_review', [], $l) }}
                    </label>
                </div>

                <div class="alert alert-danger mb-6">
                    <i data-lucide="shield-alert"></i>
                    <div>{{ __('public.developer_portal.warn_compliance', [], $l) }}
                    <a href="{{ route('public.legal', 'api-developer-terms') }}" target="_blank">{{ __('public.developer_portal.lnk_api_dev_terms', [], $l) }}</a>.</div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="terms_accepted" value="1" required {{ old('terms_accepted') ? 'checked' : '' }}>
                        {{ __('public.developer_portal.chk_terms', [], $l) }}
                    </label>
                    @error('terms_accepted') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-actions-end">
                    <a href="{{ route('portals.developer.production_requests') }}" class="btn btn-ghost">{{ __('public.developer_portal.btn_cancel', [], $l) }}</a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="rocket"></i> {{ __('public.developer_portal.btn_submit_request', [], $l) }}</button>
                </div>
            </form>
        </div>
    </div>

@endsection
