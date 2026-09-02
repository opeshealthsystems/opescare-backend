@extends('layouts.portal')
@section('title', __('public.adm_cc_mod_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_mod_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_mod_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_mod_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_mod_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel">
    @if($modules->count() === 0)
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="puzzle"></i></div>
            <h3>{{ __('public.adm_cc_mod_empty_heading') }}</h3>
            <p>{{ __('public.adm_cc_mod_empty_body') }}</p>
        </div>
    @else
    <div class="panel-body">
        @foreach($modules as $mod)
        <div class="toggle-row">
            <div class="toggle-row__body">
                <div class="toggle-row__title">{{ $mod->module_label }}</div>
                <div class="toggle-row__desc">
                    <span class="code-token">{{ $mod->module_key }}</span>
                    <span class="badge badge-neutral badge-sm">{{ $mod->scope }}</span>
                    @if($mod->scope_value)<span class="td-muted">{{ $mod->scope_value }}</span>@endif
                    @if(!$mod->enabled && $mod->disable_reason)<span class="td-muted">· {{ $mod->disable_reason }}</span>@endif
                    @if($mod->updated_by)<span class="td-muted">· {{ __('public.adm_cc_mod_updated_by') }} {{ $mod->updated_by }}</span>@endif
                </div>
            </div>
            <span class="badge {{ $mod->enabled ? 'badge-success' : 'badge-neutral' }} badge-sm">
                {{ $mod->enabled ? __('public.adm_cc_mod_badge_enabled') : __('public.adm_cc_mod_badge_disabled') }}
            </span>
            @if($mod->enabled)
                <label class="switch">
                    <input type="checkbox" checked
                           onclick="event.preventDefault(); openDisableModal('{{ $mod->module_key }}', '{{ addslashes($mod->module_label) }}');"
                           aria-label="Disable {{ $mod->module_label }}">
                    <span class="switch__track"></span>
                </label>
            @else
                <form method="POST" action="{{ route('portals.admin.cc.modules.toggle', urlencode($mod->module_key)) }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="enabled" value="1">
                    <input type="hidden" name="disable_reason" value="">
                    <label class="switch">
                        <input type="checkbox" onchange="this.form.submit()" aria-label="Enable {{ $mod->module_label }}">
                        <span class="switch__track"></span>
                    </label>
                </form>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Disable Modal --}}
<div id="disable-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.adm_cc_mod_disable_modal_heading') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="closeDisableModal()"><i data-lucide="x"></i></button>
        </div>
        <p id="disable-module-name" class="td-muted text-sm mb-4"></p>
        <form id="disable-form" method="POST" action="">
            @csrf
            <input type="hidden" name="enabled" value="0">
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.adm_cc_mod_disable_modal_lbl_reason') }} <span class="td-muted">{{ __('public.adm_cc_mod_disable_modal_hint_optional') }}</span></label>
                <textarea name="disable_reason" class="form-control" rows="2" maxlength="255" placeholder="{{ __('public.adm_cc_mod_disable_modal_ph_reason') }}"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDisableModal()">{{ __('public.adm_cc_mod_disable_modal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('public.adm_cc_mod_disable_modal_btn_disable') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var _ag = {
        modulePrefix: @json(__('public.adm_cc_mod_js_module_prefix')),
    };
    function openDisableModal(id, label) {
        document.getElementById('disable-module-name').textContent = _ag.modulePrefix + label;
        document.getElementById('disable-form').action = '/portals/admin/cc/modules/' + encodeURIComponent(id) + '/toggle';
        document.getElementById('disable-modal').classList.add('open');
    }
    function closeDisableModal() { document.getElementById('disable-modal').classList.remove('open'); }
    document.getElementById('disable-modal').addEventListener('click', function(e) { if (e.target === this) closeDisableModal(); });
</script>
@endsection
