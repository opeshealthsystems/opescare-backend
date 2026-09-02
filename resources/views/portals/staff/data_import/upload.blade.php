@extends('layouts.portal')

@section('title', __('staff_data.title_upload', [], app()->getLocale()) ?: 'New Import — Upload File')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.cdss_sidebar_role') }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.cdss_sidebar_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_overview') }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link"><i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard') }}</span></a>
    @feature('analytics_dashboards')
    @endfeature
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_operations') }}</div>
    @feature('billing')
    <a href="{{ route('portals.staff.billing') }}" class="sidebar-link"><i data-lucide="receipt"></i><span>{{ __('public.portal.nav_billing') }}</span></a>
    @endfeature
    <a href="{{ route('portals.staff.support') }}" class="sidebar-link"><i data-lucide="headset"></i><span>{{ __('public.portal.nav_support') }}</span></a>
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link active"><i data-lucide="upload-cloud"></i><span>{{ __('public.portal.nav_data_import') }}</span></a>
</div>
    @feature('clinical_decision_support')
    @endfeature
    @feature('inventory_ops')
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i> {{ __('public.portal.nav_supply') }}</a>
    @endfeature
@endsection

@section('breadcrumb_home', __('staff_data.bc_home', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_data.bc_section', [], app()->getLocale()) ?: 'Data Import')

@section('content')

{{-- Wizard Progress --}}
@include('portals.staff.data_import._wizard_steps', ['step' => 1])

<div style="max-width:640px;margin:0 auto;">
    <div class="panel">
        <div class="panel-body" style="padding:2rem;">
            <h2 class="panel-heading">{{ __('public.stf_import_upload_title') }}</h2>
            <p class="text-sm text-muted" style="margin:0 0 1.5rem;">
                {{ __('public.stf_import_upload_desc') }}
            </p>

            @if(session('error'))
                <div class="auth-alert auth-alert-danger" style="margin-bottom:1rem;">
                    <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('portals.staff.data_import.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group" style="margin-bottom:1rem;">
                    <label class="form-label">{{ __('public.stf_import_import_type') }}</label>
                    <select name="import_type" class="form-control" required onchange="updateFieldHint(this.value)">
                        <option value="">{{ __('public.stf_import_select_type') }}</option>
                        @foreach($importTypes as $key => $def)
                            <option value="{{ $key }}" {{ old('import_type') === $key ? 'selected' : '' }}>{{ $def['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Field hint panel --}}
                <div id="field-hint" style="display:none;background:var(--p-surface-2,#f8f9fa);border:1px solid var(--p-border);border-radius:var(--p-radius);padding:.85rem 1rem;margin-bottom:1rem;font-size:.82rem;">
                    <div style="font-weight:600;margin-bottom:.4rem;">{{ __('staff_data.expected_columns', [], app()->getLocale()) ?: 'Expected columns for this type:' }}</div>
                    <div id="field-hint-required" style="margin-bottom:.3rem;"></div>
                    <div id="field-hint-optional" style="color:var(--p-text-muted);"></div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label">{{ __('public.stf_import_file_label') }}</label>
                    <input type="file" name="file" class="form-control" required accept=".csv,.xlsx,.xls">
                    <div style="font-size:.75rem;color:var(--p-text-muted);margin-top:.3rem;">
                        {{ __('public.stf_import_header_hint') }}
                    </div>
                </div>

                <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                    <a href="{{ route('portals.staff.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_cancel_link') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-lucide="upload" style="width:13px;height:13px;"></i>
                        {{ __('public.stf_import_upload_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var importTypeFields = @json(collect($importTypes)->map(fn($d) => ['required' => $d['required'], 'optional' => $d['optional']]));

function updateFieldHint(type) {
    var hint = document.getElementById('field-hint');
    var req  = document.getElementById('field-hint-required');
    var opt  = document.getElementById('field-hint-optional');

    if (!type || !importTypeFields[type]) {
        hint.style.display = 'none';
        return;
    }

    var fields = importTypeFields[type];
    req.innerHTML = '<strong>{{ __('staff_data.js_required', [], app()->getLocale()) ?: 'Required:' }}</strong> ' + fields.required.join(', ');
    opt.innerHTML = fields.optional.length ? '<strong>{{ __('staff_data.js_optional', [], app()->getLocale()) ?: 'Optional:' }}</strong> ' + fields.optional.join(', ') : '';
    hint.style.display = 'block';
}
// Trigger on page load if old value set
var sel = document.querySelector('[name=import_type]');
if (sel && sel.value) updateFieldHint(sel.value);
</script>
@endsection
