@extends('layouts.portal')
@section('title', __('public.adm_cc_mod_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_mod_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_mod_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_mod_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_mod_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_mod_heading') }} <span class="od-card__count">({{ $modules->count() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
    @if($modules->count() === 0)
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="puzzle"></i></span>
            <h3 class="od-empty__title">{{ __('public.adm_cc_mod_empty_heading') }}</h3>
            <p class="od-empty__text">{{ __('public.adm_cc_mod_empty_body') }}</p>
        </div>
    @else
        @foreach($modules as $mod)
        <div class="oda-ga-toggle">
            <div class="oda-ga-toggle__text">
                <span class="od-table__name">{{ $mod->module_label }}</span>
                <div class="oda-ga-toggle__meta">
                    <span class="oda-mono">{{ $mod->module_key }}</span>
                    <span class="od-pill od-pill--neutral">{{ $mod->scope }}</span>
                    @if($mod->scope_value)<span>{{ $mod->scope_value }}</span>@endif
                    @if(!$mod->enabled && $mod->disable_reason)<span>· {{ $mod->disable_reason }}</span>@endif
                    @if($mod->updated_by)<span>· {{ __('public.adm_cc_mod_updated_by') }} {{ $mod->updated_by }}</span>@endif
                </div>
            </div>
            <span class="od-pill {{ $mod->enabled ? 'od-pill--ready' : 'od-pill--neutral' }}">
                {{ $mod->enabled ? __('public.adm_cc_mod_badge_enabled') : __('public.adm_cc_mod_badge_disabled') }}
            </span>
            @if($mod->enabled)
                <label class="oda-ga-switch">
                    <input type="checkbox" checked
                           data-prevent data-call="openDisableModal" data-args="{{ json_encode([$mod->module_key, $mod->module_label]) }}" data-show="disable-modal" data-display="flex"
                           aria-label="{{ __('admin_desk.ga_disable', ['label' => $mod->module_label]) }}">
                    <span class="oda-ga-switch__track"></span>
                </label>
            @else
                <form method="POST" action="{{ route('portals.admin.cc.modules.toggle', urlencode($mod->module_key)) }}" class="oda-inline-form">
                    @csrf
                    <input type="hidden" name="enabled" value="1">
                    <input type="hidden" name="disable_reason" value="">
                    <label class="oda-ga-switch">
                        <input type="checkbox" data-autosubmit aria-label="{{ __('admin_desk.ga_enable', ['label' => $mod->module_label]) }}">
                        <span class="oda-ga-switch__track"></span>
                    </label>
                </form>
            @endif
        </div>
        @endforeach
    @endif
    </div>
</section>
</div>

{{-- Disable Modal --}}
<div id="disable-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('public.adm_cc_mod_disable_modal_heading') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('public.aria_close') }}" data-hide="disable-modal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body">
            <p id="disable-module-name" class="oda-help"></p>
            <form id="disable-form" method="POST" action="" class="oda-form">
                @csrf
                <input type="hidden" name="enabled" value="0">
                <label class="oda-label oda-form__full">
                    <span class="oda-label__text">{{ __('public.adm_cc_mod_disable_modal_lbl_reason') }} <span class="oda-ga-muted">{{ __('public.adm_cc_mod_disable_modal_hint_optional') }}</span></span>
                    <textarea name="disable_reason" class="oda-textarea" rows="2" maxlength="255" placeholder="{{ __('public.adm_cc_mod_disable_modal_ph_reason') }}"></textarea>
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="disable-modal">{{ __('public.adm_cc_mod_disable_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--danger od-btn--sm">{{ __('public.adm_cc_mod_disable_modal_btn_disable') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
    var _ag = {
        modulePrefix: @json(__('public.adm_cc_mod_js_module_prefix')),
    };
    // The enable form above already posts to this named route; the disable modal
    // hand-wrote '/portals/admin/cc/modules/{key}/toggle', which is not a route
    // on this app at all, so Disable answered 405 and nothing toggled.
    var moduleToggleUrl = '{{ route('portals.admin.cc.modules.toggle', ['key' => '__KEY__']) }}';

    function openDisableModal(id, label) {
        document.getElementById('disable-module-name').textContent = _ag.modulePrefix + label;
        document.getElementById('disable-form').action = moduleToggleUrl.replace('__KEY__', encodeURIComponent(id));
    }
</script>
@include('portals.admin._modal')
@endsection
