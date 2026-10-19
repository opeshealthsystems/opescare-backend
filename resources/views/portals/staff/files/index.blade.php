@extends('layouts.portal')
{{-- Imported from the platform (portals/staff/files/index). Finder: one
     patient, reached from the chart; signed download links; void with a
     reason instead of delete; release to the patient. --}}
@section('title', __('files.title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('files.title') }}</h1>
        <p class="od-page__sub">
            {{ __('chart.health_id') }}: <span class="od-strong odc-mono">{{ $patient->health_id ?? '—' }}</span>
            &middot; {{ __('files.subtitle') }}
        </p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.chart.show', $patient->id) }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('files.back_to_chart') }}
        </a>
        <a href="{{ route('portals.files.create', ['patientId' => $patient->id]) }}" class="od-btn od-btn--primary">
            <i data-lucide="upload"></i> {{ __('files.upload_btn') }}
        </a>
    </div>
</header>

@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('info'))
    <div class="odc-note odc-note--info"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>
@endif
@if($errors->any())
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('files.attachments') }} <span class="od-card__count">({{ $attachments->count() }})</span></h2>
    </header>
        @if($attachments->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="folder-open"></i></span>
                <p class="od-empty__text">{{ __('files.none') }}</p>
                <a href="{{ route('portals.files.create', ['patientId' => $patient->id]) }}" class="od-btn od-btn--primary"><i data-lucide="upload"></i> {{ __('files.upload_btn') }}</a>
            </div>
        @else
        <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table odc-stack">
                <thead><tr>
                    <th class="od-table__num">#</th><th>{{ __('files.col_file') }}</th><th>{{ __('files.col_category') }}</th><th>{{ __('files.col_description') }}</th><th>{{ __('files.col_size') }}</th><th>{{ __('files.col_date') }}</th><th>{{ __('files.col_status') }}</th><th>{{ __('files.col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($attachments as $i => $att)
                    @php
                        $mime = $att->fileAsset->mime_type ?? '';
                        $icon = str_contains($mime,'pdf') ? 'file-text' : (str_contains($mime,'image') ? 'image' : 'file');
                        $tint = str_contains($mime,'pdf') ? 'rose' : (str_contains($mime,'image') ? 'sky' : 'blue');
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td data-label="{{ __('files.col_file') }}">
                            <span class="odc-filecell">
                                <span class="od-list__icon od-tint--{{ $tint }}"><i data-lucide="{{ $icon }}"></i></span>
                                <span class="od-table__name">{{ $att->fileAsset->original_name ?? '—' }}</span>
                            </span>
                        </td>
                        <td data-label="{{ __('files.col_category') }}">
                            @if($att->category)
                                <span class="od-pill od-pill--neutral">{{ $categories[$att->category] ?? $att->category }}</span>
                            @else
                                <span class="od-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('files.col_description') }}" class="od-muted">{{ $att->description ?? '—' }}</td>
                        <td data-label="{{ __('files.col_size') }}" class="od-muted">{{ $att->fileAsset?->humanSize() ?? '—' }}</td>
                        <td data-label="{{ __('files.col_date') }}" class="od-muted">{{ $att->created_at?->translatedFormat('d M Y') }}</td>
                        <td data-label="{{ __('files.col_status') }}">
                            @if($att->isVoided())
                                <span class="od-pill od-pill--urgent">{{ __('files.status_voided') }}</span>
                                <span class="od-table__sub">{{ $att->void_reason }}</span>
                            @elseif($att->isReleased())
                                <span class="od-pill od-pill--ready">{{ __('files.status_released') }}</span>
                            @else
                                <span class="od-pill od-pill--neutral">{{ __('files.status_not_released') }}</span>
                            @endif
                        </td>
                        <td class="od-table__actions" data-label="{{ __('files.col_actions') }}">
                            @unless($att->isVoided())
                            <div class="odc-rowacts">
                                <a href="{{ $links[$att->id] }}" class="od-btn od-btn--row">
                                    <i data-lucide="download"></i> {{ __('files.download') }}
                                </a>

                                @unless($att->isReleased())
                                <details class="odc-details">
                                    <summary class="od-btn od-btn--sm odc-btn-quiet"><i data-lucide="send"></i> {{ __('files.release') }}</summary>
                                    <form method="POST" action="{{ route('portals.files.release', $att->id) }}" class="odc-form">
                                        @csrf
                                        @if($att->isRadiologyReport())
                                            <label class="odc-label"><span class="odc-req">{{ __('files.lbl_modality') }}</span>
                                                <select name="modality" class="od-select" required>
                                                    @foreach($modalities as $m)
                                                        <option value="{{ $m }}">{{ __('files.modalities.' . $m) }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="odc-label"><span class="odc-req">{{ __('files.lbl_body_part') }}</span>
                                                <input type="text" name="body_part" class="od-input" maxlength="150" required>
                                            </label>
                                            <label class="odc-label"><span class="odc-req">{{ __('files.lbl_study_date') }}</span>
                                                <input type="date" name="study_date" class="od-input" max="{{ now()->toDateString() }}" required>
                                            </label>
                                        @endif
                                        <span class="odc-hint">{{ __('files.release_hint') }}</span>
                                        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="send"></i> {{ __('files.release_confirm') }}</button>
                                    </form>
                                </details>
                                @endunless

                                <details class="odc-details">
                                    <summary class="od-btn od-btn--sm odc-btn-danger"><i data-lucide="ban"></i> {{ __('files.void') }}</summary>
                                    <form method="POST" action="{{ route('portals.files.void', $att->id) }}" class="odc-form">
                                        @csrf
                                        <label class="odc-label"><span class="odc-req">{{ __('files.lbl_void_reason') }}</span>
                                            <input type="text" name="reason" class="od-input" minlength="3" maxlength="500" required>
                                        </label>
                                        <span class="odc-hint">{{ __('files.void_hint') }}</span>
                                        <button type="submit" class="od-btn od-btn--danger od-btn--sm"><i data-lucide="ban"></i> {{ __('files.void_confirm') }}</button>
                                    </form>
                                </details>
                            </div>
                            @endunless
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
        @endif
</section>

</div>
@endsection
