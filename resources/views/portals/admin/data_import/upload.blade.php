@extends('layouts.portal')
@section('title', __('public.stf_import_upload_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_upload_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_import_upload_title') }}</h1>
        <p class="od-page__sub">{{ __('public.stf_import_upload_desc') }}</p>
    </div>
</div>

@include('portals.admin.data_import._wizard_steps', ['step' => 1])

@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@if($errors->any())
    <div class="oda-gb-alert oda-gb-alert--bad">
        <i data-lucide="triangle-alert"></i>
        <div>
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="upload-cloud"></i> {{ __('public.stf_import_upload_title') }}</h2>
    </div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.data_import.store') }}" enctype="multipart/form-data" class="oda-form">
            @csrf

            <label class="oda-label oda-form__full" for="import_type">
                <span class="oda-label__text">{{ __('public.stf_import_import_type') }}</span>
                <select name="import_type" id="import_type" class="od-select" required>
                    <option value="">{{ __('public.stf_import_select_type') }}</option>
                    @foreach($importTypes as $key => $def)
                        <option value="{{ $key }}" {{ old('import_type') === $key ? 'selected' : '' }}>{{ $def['label'] }}</option>
                    @endforeach
                </select>
            </label>

            {{-- Expected-columns hint, filled by the script below --}}
            <div id="field-hint" class="oda-gb-alert oda-gb-alert--info oda-form__full" hidden>
                <i data-lucide="table-2"></i>
                <div>
                    <strong>{{ __('staff_data.expected_columns') }}</strong>
                    <div id="field-hint-required"></div>
                    <div id="field-hint-optional" class="od-table__sub"></div>
                </div>
            </div>

            <label class="oda-label oda-form__full" for="import_file">
                <span class="oda-label__text">{{ __('public.stf_import_file_label') }}</span>
                <input type="file" name="file" id="import_file" class="od-input oda-gb-file" required accept=".csv,.xlsx,.xls">
                <span class="oda-help">{{ __('public.stf_import_header_hint') }}</span>
            </label>

            <div class="oda-form__actions">
                <a href="{{ route('portals.admin.data_import.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_cancel_link') }}</a>
                <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                    <i data-lucide="upload"></i>
                    {{ __('public.stf_import_upload_btn') }}
                </button>
            </div>
        </form>
    </div>
</section>
</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    var fields = @json(collect($importTypes)->map(fn ($d) => ['required' => $d['required'], 'optional' => $d['optional']]));
    var labels = @json(['required' => __('staff_data.js_required'), 'optional' => __('staff_data.js_optional')]);

    var select = document.getElementById('import_type');
    var hint   = document.getElementById('field-hint');
    var req    = document.getElementById('field-hint-required');
    var opt    = document.getElementById('field-hint-optional');
    if (!select || !hint) return;

    function update() {
        var type = select.value;
        if (!type || !fields[type]) { hint.hidden = true; return; }
        req.textContent = labels.required + ' ' + fields[type].required.join(', ');
        opt.textContent = fields[type].optional.length ? labels.optional + ' ' + fields[type].optional.join(', ') : '';
        hint.hidden = false;
    }

    select.addEventListener('change', update);
    update();
})();
</script>
@endsection
