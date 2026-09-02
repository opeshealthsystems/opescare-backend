@extends('layouts.portal')
@section('title', __('public.adm_cc_ff_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_ff_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_ff_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_ff_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_ff_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel">
    @if($flags->count() === 0)
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="toggle-right"></i></div>
            <h3>{{ __('public.adm_cc_ff_empty_heading') }}</h3>
            <p>{{ __('public.adm_cc_ff_empty_body') }}</p>
        </div>
    @else
    <div class="panel-body">
        @foreach($flags as $flag)
        <div class="toggle-row">
            <div class="toggle-row__body">
                <div class="toggle-row__title">{{ $flag->label }}</div>
                <div class="toggle-row__desc">
                    <span class="code-token">{{ $flag->key }}</span>
                    <span class="badge badge-neutral badge-sm">{{ $flag->scope }}</span>
                    @if($flag->updated_by)<span class="td-muted">· {{ __('public.adm_cc_ff_updated_by') }} {{ $flag->updated_by }}</span>@endif
                </div>
            </div>
            <span class="badge {{ $flag->enabled ? 'badge-success' : 'badge-neutral' }} badge-sm">
                {{ $flag->enabled ? __('public.adm_cc_ff_badge_enabled') : __('public.adm_cc_ff_badge_disabled') }}
            </span>
            <form method="POST" action="{{ route('portals.admin.cc.feature_flags.toggle', urlencode($flag->key)) }}" class="inline-form">
                @csrf
                <input type="hidden" name="enabled" value="{{ $flag->enabled ? '0' : '1' }}">
                <label class="switch">
                    <input type="checkbox" {{ $flag->enabled ? 'checked' : '' }} onchange="this.form.submit()" aria-label="Toggle {{ $flag->label }}">
                    <span class="switch__track"></span>
                </label>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
