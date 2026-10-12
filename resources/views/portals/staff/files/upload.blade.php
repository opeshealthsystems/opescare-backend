@extends('layouts.portal')
{{-- Imported from the platform (portals/staff/files/upload). Finder: the
     patient is fixed by the URL (no free-text resource id); PDF/JPEG/PNG only. --}}
@section('title', __('files.upload_title'))
@section('breadcrumb_home', __('files.title'))
@section('breadcrumb_home_url', route('portals.files.index', ['patientId' => $patient->id]))
@section('breadcrumb_section', __('files.upload_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('files.upload_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('files.back') }}
    </a>
</div>
<p class="page-subtitle mb-6">
    {{ __('chart.health_id') }}: <span class="td-strong">{{ $patient->health_id ?? '—' }}</span>
    &middot; {{ __('files.upload_subtitle') }}
</p>

@if($errors->any())
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.files.store', ['patientId' => $patient->id]) }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label form-label-required" for="file-input">{{ __('files.lbl_file') }}</label>
                <div id="drop-zone" class="empty-state" style="border:2px dashed var(--p-border);border-radius:var(--p-radius);cursor:pointer;"
                     data-click="file-input">
                    <div class="empty-state-icon"><i data-lucide="upload-cloud"></i></div>
                    <div class="td-strong">{{ __('files.click_browse') }}</div>
                    <div id="file-name" class="td-muted">{{ __('files.size_hint', ['max' => $maxSizeMb]) }}</div>
                </div>
                <input type="file" id="file-input" name="file" required class="sr-only"
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    data-call-change="updateFileName" data-call-self>
                @error('file')<div class="form-hint">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('files.lbl_category') }}</label>
                    <select name="category" class="form-control" required>
                        <option value="">{{ __('files.select_category') }}</option>
                        @foreach($categories as $val => $label)
                            <option value="{{ $val }}" {{ old('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('files.lbl_description') }}</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="form-control"
                        maxlength="300" placeholder="{{ __('files.ph_description') }}">
                </div>
            </div>

            <div class="row-actions-inline mt-6">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="upload"></i> {{ __('files.upload_attach') }}
                </button>
                <a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="btn btn-ghost">{{ __('files.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
function updateFileName(input) {
    const label = document.getElementById('file-name');
    if (input.files && input.files[0]) {
        const f = input.files[0];
        const sizeMb = (f.size / (1024*1024)).toFixed(2);
        label.textContent = f.name + ' (' + sizeMb + ' MB)';
        label.style.color = 'var(--p-text)';
        document.getElementById('drop-zone').style.borderColor = 'var(--p-primary)';
    }
}

// Drag-and-drop
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor='var(--p-primary)'; });
dz.addEventListener('dragleave', () => dz.style.borderColor='');
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.style.borderColor='var(--p-primary)';
    const fi = document.getElementById('file-input');
    fi.files = e.dataTransfer.files;
    updateFileName(fi);
});
</script>
@endsection
