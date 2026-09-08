@extends('layouts.portal')
@section('title', __('public.stf_import_map_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_map_title'))

@section('content')

@include('portals.admin.data_import._wizard_steps', ['step' => 2])

@php
    // The job stores { file_column => system_field }; this screen is one row per
    // SYSTEM FIELD, so flip it once here rather than inside the loop.
    $fieldToColumn = array_flip($suggested ?? []);
    $headers       = $job->detected_headers ?? [];
@endphp

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">{{ __('public.stf_import_map_title') }}</h3>
    </div>
    <div class="panel-body">
        <p class="td-muted">
            {{ __('public.stf_import_file_detail') }}: <strong>{{ $job->original_filename }}</strong>
            · {{ __('public.stf_import_type_detail') }}: <strong>{{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</strong>
        </p>
        <p class="td-muted mb-6">{{ __('public.stf_import_match_desc') }}</p>

        @if(session('error'))
            <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
        @endif

        @if(count($headers) === 0)
            <div class="alert alert-warning mb-6">
                <i data-lucide="triangle-alert"></i>
                <div>{{ __('public.stf_import_header_hint') }}</div>
            </div>
        @endif

        @if(count($saved) > 0)
        <div class="alert alert-info mb-6">
            <i data-lucide="bookmark"></i>
            <div>
                <label class="form-label" for="saved-mapping-picker">{{ __('public.stf_import_load_saved') }}</label>
                <div class="row-actions-inline">
                    <select id="saved-mapping-picker" class="form-control">
                        <option value="">{{ __('staff_data.select_placeholder') }}</option>
                        @foreach($saved as $sm)
                            <option value="{{ json_encode($sm['mapping']) }}">{{ $sm['name'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-ghost btn-sm" id="apply-saved-mapping">{{ __('public.stf_import_apply') }}</button>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('portals.admin.data_import.mapping.store', $job->id) }}">
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
                            // Prefer the saved/suggested column; otherwise a header
                            // that is literally the field name.
                            $preSelected = $fieldToColumn[$sf['key']] ?? (in_array($sf['key'], $headers, true) ? $sf['key'] : '');
                        @endphp
                        <tr>
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
                                <select name="mapping[{{ $sf['key'] }}]" class="form-control mapping-select" data-system-field="{{ $sf['key'] }}">
                                    <option value="">{{ __('public.stf_import_skip') }}</option>
                                    @foreach($headers as $col)
                                        <option value="{{ $col }}" {{ $preSelected === $col ? 'selected' : '' }}>{{ $col }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-6">
                <i data-lucide="bookmark"></i>
                <div class="row-actions-inline">
                    <label class="form-label" for="save_as">{{ __('public.stf_import_save_reuse') }}</label>
                    <input type="text" name="save_as" id="save_as" class="form-control" maxlength="80" placeholder="{{ __('public.stf_import_save_name_ph') }}">
                </div>
            </div>

            <div class="row-actions-inline mt-6">
                <a href="{{ route('portals.admin.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_cancel_link') }}</a>
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
(function () {
    var apply  = document.getElementById('apply-saved-mapping');
    var picker = document.getElementById('saved-mapping-picker');
    if (!apply || !picker) return;

    apply.addEventListener('click', function () {
        if (!picker.value) return;
        var mapping;
        try { mapping = JSON.parse(picker.value); } catch (e) { return; }

        // Saved mappings are { file_column: system_field }; this form is keyed
        // by system field, so invert before applying.
        var byField = {};
        Object.keys(mapping).forEach(function (col) { byField[mapping[col]] = col; });

        document.querySelectorAll('.mapping-select').forEach(function (sel) {
            var col = byField[sel.getAttribute('data-system-field')];
            sel.value = col ? col : '';
        });
    });
})();
</script>
@endsection
