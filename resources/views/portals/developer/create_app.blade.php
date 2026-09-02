@extends('layouts.portal')
@section('title', __('public.developer_portal.page_create_app', [], app()->getLocale()) ?: 'New App')
@section('sidebar_nav') @include('portals.developer._sidebar') @endsection
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.apps') }}">{{ __('public.developer_portal.lnk_my_apps', [], $l) ?: 'My apps' }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('public.developer_portal.page_create_app', [], $l) ?: 'New app' }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('public.developer_portal.page_create_app', [], $l) ?: 'New app' }}</h2>
    </div>

    <div class="panel form-panel">
        <div class="panel-body">

            <div class="alert alert-info mb-6">
                <i data-lucide="info"></i>
                <div>{{ __('public.developer_portal.info_sandbox_only', [], $l) ?: 'New apps receive sandbox credentials only. To access production data, submit a' }}
                <strong>{{ __('public.developer_portal.badge_sandbox', [], $l) ?: 'sandbox credentials' }}</strong> {{ __('public.developer_portal.info_sandbox_only', [], $l) ? '' : 'only.' }}
                <a href="{{ route('portals.developer.production_requests.create') }}">{{ __('public.developer_portal.info_sandbox_link_text', [], $l) ?: 'production access request' }}</a>
                {{ __('public.developer_portal.info_sandbox_only_suffix', [], $l) ?: 'after your app is ready.' }}</div>
            </div>

            <form method="POST" action="{{ route('portals.developer.apps.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.developer_portal.fld_app_name', [], $l) ?: 'App name' }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="{{ __('public.developer_portal.ph_app_name', [], $l) ?: 'e.g. MyHospital Connector' }}" class="form-control">
                    @error('name') <div class="form-hint">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.developer_portal.fld_description', [], $l) ?: 'Description' }}</label>
                    <textarea name="description" rows="3" class="form-control"
                              placeholder="{{ __('public.developer_portal.ph_description', [], $l) ?: 'Brief description of what your integration does…' }}">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.developer_portal.fld_website_url', [], $l) ?: 'Website / redirect URL (optional)' }}</label>
                    <input type="url" name="website_url" value="{{ old('website_url') }}"
                           placeholder="{{ __('public.developer_portal.ph_website_url', [], $l) ?: 'https://yourapp.example.com' }}" class="form-control">
                </div>

                <div class="alert alert-warning mb-6">
                    <i data-lucide="alert-triangle"></i>
                    <div>{{ __('public.developer_portal.warn_secret_once', [], $l) ?: 'Your sandbox client secret will be shown once after creation. Store it securely.' }}</div>
                </div>

                <div class="form-actions-end">
                    <a href="{{ route('portals.developer.apps') }}" class="btn btn-ghost">{{ __('public.developer_portal.btn_cancel', [], $l) ?: 'Cancel' }}</a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_create_app', [], $l) ?: 'Create app' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
