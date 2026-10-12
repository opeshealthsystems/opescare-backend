@extends('layouts.portal')
@section('title', __('public.developer_portal.page_onboard', [], app()->getLocale()))
@php $l = app()->getLocale(); @endphp

@section('content')
<div class="form-panel">
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="user-plus"></i> {{ __('public.developer_portal.panel_complete_account', [], $l) }}</h3>
        </div>
        <div class="panel-body">
            <p class="td-muted mb-6">
                {{ __('public.developer_portal.onboard_intro', ['email' => $email], $l) . $email . ' was not found. Complete setup to access the OpesCare Developer Portal and obtain sandbox API credentials.' }}
            </p>
            <form method="POST" action="{{ route('portals.developer.onboard.store') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.fld_display_name', [], $l) }}</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" required class="form-control">
                    @error('display_name') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.developer_portal.fld_company', [], $l) }}</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.developer_portal.fld_website', [], $l) }}</label>
                    <input type="url" name="website_url" value="{{ old('website_url') }}" placeholder="https://example.com" class="form-control">
                </div>

                <div class="alert alert-info mb-6">
                    <i data-lucide="info"></i>
                    <div><strong>{{ __('public.developer_portal.info_api_terms_title', [], $l) }}</strong> {{ __('public.developer_portal.info_api_terms_body', [], $l) }}
                    <a href="{{ route('public.legal', 'api-developer-terms') }}" target="_blank">{{ __('public.developer_portal.lnk_api_terms', [], $l) }}</a>.
                    {{ __('public.developer_portal.info_sandbox_note', [], $l) }}</div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="api_terms_accepted" value="1" required>
                        {{ __('public.developer_portal.chk_api_terms', [], $l) }}
                    </label>
                    @error('api_terms_accepted') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="user-plus"></i> {{ __('public.developer_portal.btn_create_account', [], $l) }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
