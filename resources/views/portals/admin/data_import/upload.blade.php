@extends('layouts.portal')
@section('title', __('public.stf_import_upload_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_upload_title'))

@section('content')

@include('portals.admin.data_import._wizard_steps', ['step' => 1])

<div class="panel">
    <div class="panel-body">
        <h2 class="panel-heading">{{ __('public.stf_import_upload_title') }}</h2>
        <p class="td-muted mb-6">{{ __('public.stf_import_upload_desc') }}</p>

        @if(session('error'))
            <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-6">
                <i data-lucide="triangle-alert"></i>
                <div>
                    @foreach($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.admin.data_import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="import_type">{{ __('public.stf_import_import_type') }}</label>
                <select name="import_type" id="import_type" class="form-control" required>
                    <option value="">{{ __('public.stf_import_select_type') }}</option>
                    @foreach($importTypes as $key => $def)
                        <option value="{{ $key }}" {{ old('import_type') === $key ? 'selected' : '' }}>{{ $def['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Expected-columns hint, filled by the script below --}}
            <div id="field-hint" class="alert alert-info mb-6" hidden>
                <i data-lucide="table-2"></i>
                <div>
                    <div class="td-strong">{{ __('staff_data.expected_columns') }}</div>
                    <div id="field-hint-required"></div>
                    <div id="field-hint-optional" class="td-muted"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="import_file">{{ __('public.stf_import_file_label') }}</label>
                <input type="file" name="file" id="import_file" class="form-control" required accept=".csv,.xlsx,.xls">
                <div class="td-muted">{{ __('public.stf_import_header_hint') }}</div>
            </div>

            <div class="row-actions-inline mt-6">
                <a href="{{ route('portals.admin.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_cancel_link') }}</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="upload"></i>
                    {{ __('public.stf_import_upload_btn') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
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
