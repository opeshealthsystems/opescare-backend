@extends('layouts.portal')
{{-- Imported from the platform (portals/staff/files/upload). Finder: the
     patient is fixed by the URL (no free-text resource id); PDF/JPEG/PNG only. --}}
@section('title', __('files.upload_title'))
@section('breadcrumb_home', __('files.title'))
@section('breadcrumb_home_url', route('portals.files.index', ['patientId' => $patient->id]))
@section('breadcrumb_section', __('files.upload_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('files.upload_title') }}</h1>
        <p class="od-page__sub">
            {{ __('chart.health_id') }}: <span class="od-strong odc-mono">{{ $patient->health_id ?? '—' }}</span>
            &middot; {{ __('files.upload_subtitle') }}
        </p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('files.back') }}
        </a>
    </div>
</header>

@if($errors->any())
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<form method="POST" action="{{ route('portals.files.store', ['patientId' => $patient->id]) }}" enctype="multipart/form-data" class="odc-form">
    @csrf

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('files.lbl_file') }}</h2></header>
        <div class="od-card__body odc-grid">
            <div class="odc-label odc-span">
                <label class="odc-req" for="file-input">{{ __('files.lbl_file') }}</label>
                <div id="drop-zone" class="odc-drop" data-click="file-input">
                    <i data-lucide="upload-cloud"></i>
                    <span class="od-strong">{{ __('files.click_browse') }}</span>
                    <span id="file-name" class="od-muted">{{ __('files.size_hint', ['max' => $maxSizeMb]) }}</span>
                </div>
                <input type="file" id="file-input" name="file" required class="sr-only od-sr"
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    data-call-change="updateFileName" data-call-self>
                @error('file')<span class="odc-error">{{ $message }}</span>@enderror
            </div>

            <label class="odc-label"><span class="odc-req">{{ __('files.lbl_category') }}</span>
                <select name="category" class="od-select" required>
                    <option value="">{{ __('files.select_category') }}</option>
                    @foreach($categories as $val => $label)
                        <option value="{{ $val }}" {{ old('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="odc-label">{{ __('files.lbl_description') }}
                <input type="text" name="description" value="{{ old('description') }}" class="od-input"
                    maxlength="300" placeholder="{{ __('files.ph_description') }}">
            </label>
        </div>
    </section>

    <div class="odc-actions">
        <a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="od-btn od-btn--secondary">{{ __('files.cancel') }}</a>
        <button type="submit" class="od-btn od-btn--primary">
            <i data-lucide="upload"></i> {{ __('files.upload_attach') }}
        </button>
    </div>
</form>

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
        document.getElementById('drop-zone').classList.add('is-active', 'is-picked');
    }
}

// Drag-and-drop
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('is-active'); });
dz.addEventListener('dragleave', () => dz.classList.remove('is-active'));
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.classList.add('is-active');
    const fi = document.getElementById('file-input');
    fi.files = e.dataTransfer.files;
    updateFileName(fi);
});
</script>
@endsection
