@extends('layouts.portal')
@section('title', __('public.stf_import_map_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_map_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    // The job stores { file_column => system_field }; this screen is one row per
    // SYSTEM FIELD, so flip it once here rather than inside the loop.
    $fieldToColumn = array_flip($suggested ?? []);
    $headers       = $job->detected_headers ?? [];
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_import_map_title') }}</h1>
        <p class="od-page__sub">
            {{ __('public.stf_import_file_detail') }}: <strong>{{ $job->original_filename }}</strong>
            · {{ __('public.stf_import_type_detail') }}: <strong>{{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</strong>
        </p>
    </div>
</div>

@include('portals.admin.data_import._wizard_steps', ['step' => 2])

@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@if(count($headers) === 0)
    <div class="oda-gb-alert oda-gb-alert--warn">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('public.stf_import_header_hint') }}</div>
    </div>
@endif

@if(count($saved) > 0)
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="bookmark"></i> {{ __('public.stf_import_load_saved') }}</h2>
    </div>
    <div class="od-card__body">
        <div class="oda-toolbar">
            <label class="oda-gb-sr" for="saved-mapping-picker">{{ __('public.stf_import_load_saved') }}</label>
            <select id="saved-mapping-picker" class="od-select">
                <option value="">{{ __('staff_data.select_placeholder') }}</option>
                @foreach($saved as $sm)
                    <option value="{{ json_encode($sm['mapping']) }}">{{ $sm['name'] }}</option>
                @endforeach
            </select>
            <button type="button" class="od-btn od-btn--secondary od-btn--sm" id="apply-saved-mapping">{{ __('public.stf_import_apply') }}</button>
        </div>
    </div>
</section>
@endif

<form method="POST" action="{{ route('portals.admin.data_import.mapping.store', $job->id) }}" class="od-stack">
    @csrf

    <section class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('public.stf_import_map_title') }}</h2>
            <span class="od-card__count">{{ count($systemFields) }}</span>
        </div>
        <div class="od-card__body">
            <p class="oda-help">{{ __('public.stf_import_match_desc') }}</p>
        </div>
        <div class="od-card__body od-card__body--flush">
            <div class="od-table-wrap">
                <table class="od-table">
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
                                <code class="oda-mono {{ $sf['required'] ? 'od-table__name' : '' }}">{{ $sf['key'] }}</code>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_required_hdr') }}">
                                @if($sf['required'])
                                    <span class="od-pill od-pill--urgent">{{ __('public.stf_import_required_badge') }}</span>
                                @else
                                    <span class="od-pill od-pill--neutral">{{ __('public.stf_import_optional_badge') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_import_col_map_to') }}">
                                <select name="mapping[{{ $sf['key'] }}]" class="od-select mapping-select" data-system-field="{{ $sf['key'] }}">
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
        </div>
    </section>

    <section class="od-card">
        <div class="od-card__body">
            <div class="oda-form">
                <label class="oda-label" for="save_as">
                    <span class="oda-label__text"><i data-lucide="bookmark"></i> {{ __('public.stf_import_save_reuse') }}</span>
                    <input type="text" name="save_as" id="save_as" class="od-input" maxlength="80" placeholder="{{ __('public.stf_import_save_name_ph') }}">
                </label>
                <div class="oda-form__actions">
                    <a href="{{ route('portals.admin.data_import.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_cancel_link') }}</a>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                        <i data-lucide="check-circle"></i>
                        {{ __('public.stf_import_save_validate') }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</form>
</div>
@endsection

@section('scripts')
<script @cspNonce>
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
