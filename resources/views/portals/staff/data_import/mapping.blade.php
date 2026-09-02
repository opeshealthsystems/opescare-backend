@extends('layouts.portal')

@section('title', __('staff_data.title_map', [], app()->getLocale()) ?: 'Import — Map Columns')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.cdss_sidebar_role') }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.cdss_sidebar_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_overview') }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link"><i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard') }}</span></a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_operations') }}</div>
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

@include('portals.staff.data_import._wizard_steps', ['step' => 2])

    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('public.stf_import_map_title') }}</h3>
        </div>
        <div class="panel-body">
            <p class="td-muted">
                {{ __('public.stf_import_file_detail') }}: <strong>{{ $job->original_filename }}</strong> · {{ __('public.stf_import_type_detail') }}: <strong>{{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</strong>
            </p>
            <p class="td-muted mb-6">
                {{ __('public.stf_import_match_desc') }}
            </p>

            @if(session('error'))
                <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
            @endif

            {{-- Saved mappings picker --}}
            @if(count($saved) > 0)
            <div class="alert alert-info mb-6">
                <i data-lucide="bookmark"></i>
                <div>
                    <label class="form-label">{{ __('public.stf_import_load_saved') }}</label>
                    <div class="row-actions-inline">
                        <select id="saved-mapping-picker" class="form-control">
                            <option value="">{{ __('staff_data.select_placeholder', [], app()->getLocale()) ?: '— select —' }}</option>
                            @foreach($saved as $sm)
                                <option value="{{ htmlspecialchars(json_encode($sm['mapping']), ENT_QUOTES) }}">{{ $sm['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="applySavedMapping()">{{ __('public.stf_import_apply') }}</button>
                    </div>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('portals.staff.data_import.mapping.store', $job->id) }}">
                @csrf

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('public.stf_import_col_opescare_field') }}</th>
                                <th>{{ __('public.stf_import_col_required_hdr') }}</th>
                                <th>{{ __('public.stf_import_col_map_to') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($systemFields as $sf)
                            @php
                                // Pre-fill from suggested or current mapping (reversed: field → file_col)
                                $reversedSuggested = array_flip($suggested ?? []);
                                $preSelected = $reversedSuggested[$sf['key']] ?? '';
                            @endphp
                            <tr data-field="{{ $sf['key'] }}">
                                <td data-label="{{ __('public.stf_import_col_opescare_field') }}">
                                    <span class="{{ $sf['required'] ? 'td-strong' : '' }}">{{ $sf['key'] }}</span>
                                </td>
                                <td data-label="{{ __('public.stf_import_col_required_hdr') }}">
                                    @if($sf['required'])
                                        <span class="badge badge-danger">{{ __('public.stf_import_required_badge') }}</span>
                                    @else
                                        <span class="badge badge-neutral">{{ __('public.stf_import_optional_badge') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('public.stf_import_col_map_to') }}">
                                    <select name="mapping[{{ $preSelected ?: $sf['key'] }}]"
                                            class="form-control mapping-select"
                                            data-system-field="{{ $sf['key'] }}">
                                        <option value="">{{ __('public.stf_import_skip') }}</option>
                                        @foreach(($job->detected_headers ?? []) as $col)
                                            <option value="{{ $sf['key'] }}" data-col="{{ $col }}"
                                                {{ ($preSelected === $col || (!$preSelected && $col === $sf['key'])) ? 'selected' : '' }}>
                                                {{ $col }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Save mapping for reuse --}}
                <div class="alert alert-info mt-6">
                    <i data-lucide="bookmark"></i>
                    <div class="row-actions-inline">
                        <span>{{ __('public.stf_import_save_reuse') }}</span>
                        <input type="text" name="save_as" class="form-control" placeholder="{{ __('public.stf_import_save_name_ph') }}">
                    </div>
                </div>

                <div class="row-actions-inline mt-6">
                    <a href="{{ route('portals.staff.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_cancel_link') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-lucide="check-circle"></i>
                        {{ __('public.stf_import_save_validate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script>
function applySavedMapping() {
    var picker = document.getElementById('saved-mapping-picker');
    if (!picker || !picker.value) return;

    try {
        var mapping = JSON.parse(picker.value); // { file_col: system_field }
        // Reverse: system_field → file_col
        var reversed = {};
        for (var col in mapping) {
            reversed[mapping[col]] = col;
        }
        // Apply to each row
        document.querySelectorAll('.mapping-select').forEach(function(sel) {
            var systemField = sel.getAttribute('data-system-field');
            var fileCol = reversed[systemField];
            if (fileCol) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].getAttribute('data-col') === fileCol) {
                        sel.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    } catch(e) {
        console.warn('Could not apply saved mapping', e);
    }
}
</script>
@endsection
