@extends('layouts.portal')
{{-- Imported from the platform (portals/staff/files/index). Finder: one
     patient, reached from the chart; signed download links; void with a
     reason instead of delete; release to the patient. --}}
@section('title', __('files.title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.show', $patient->id))
@section('breadcrumb_section', __('files.title'))

@section('content')
<div class="page-head">
    <h2>{{ __('files.title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.chart.show', $patient->id) }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('files.back_to_chart') }}
    </a>
    <a href="{{ route('portals.files.create', ['patientId' => $patient->id]) }}" class="btn btn-primary btn-sm">
        <i data-lucide="upload"></i> {{ __('files.upload_btn') }}
    </a>
</div>
<p class="page-subtitle mb-6">
    {{ __('chart.health_id') }}: <span class="td-strong">{{ $patient->health_id ?? '—' }}</span>
    &middot; {{ __('files.subtitle') }}
</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('info'))
    <div class="alert alert-info mb-6"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title">
            <i data-lucide="paperclip"></i>
            {{ __('files.attachments') }}
        </h3>
        <span class="badge badge-neutral">{{ $attachments->count() }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        @if($attachments->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="folder-open"></i></div>
                <p>{{ __('files.none') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('files.col_file') }}</th><th>{{ __('files.col_category') }}</th><th>{{ __('files.col_description') }}</th><th>{{ __('files.col_size') }}</th><th>{{ __('files.col_date') }}</th><th>{{ __('files.col_status') }}</th><th>{{ __('files.col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($attachments as $att)
                    <tr>
                        <td data-label="{{ __('files.col_file') }}">
                            @php
                                $mime = $att->fileAsset->mime_type ?? '';
                                $icon = str_contains($mime,'pdf') ? 'file-text' : (str_contains($mime,'image') ? 'image' : 'file');
                            @endphp
                            <span class="cell-with-icon">
                                <i data-lucide="{{ $icon }}"></i>
                                <span class="td-strong">{{ $att->fileAsset->original_name ?? '—' }}</span>
                            </span>
                        </td>
                        <td data-label="{{ __('files.col_category') }}">
                            @if($att->category)
                                <span class="badge badge-neutral">{{ $categories[$att->category] ?? $att->category }}</span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('files.col_description') }}" class="td-muted">{{ $att->description ?? '—' }}</td>
                        <td data-label="{{ __('files.col_size') }}" class="td-muted">{{ $att->fileAsset?->humanSize() ?? '—' }}</td>
                        <td data-label="{{ __('files.col_date') }}" class="td-muted">{{ $att->created_at?->translatedFormat('d M Y') }}</td>
                        <td data-label="{{ __('files.col_status') }}">
                            @if($att->isVoided())
                                <span class="badge badge-danger">{{ __('files.status_voided') }}</span>
                                <div class="td-muted">{{ $att->void_reason }}</div>
                            @elseif($att->isReleased())
                                <span class="badge badge-success">{{ __('files.status_released') }}</span>
                            @else
                                <span class="badge badge-neutral">{{ __('files.status_not_released') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('files.col_actions') }}">
                            @unless($att->isVoided())
                            <div class="row-actions-inline">
                                <a href="{{ $links[$att->id] }}" class="btn btn-ghost btn-xs">
                                    <i data-lucide="download"></i> {{ __('files.download') }}
                                </a>

                                @unless($att->isReleased())
                                <details>
                                    <summary class="btn btn-secondary btn-xs"><i data-lucide="send"></i> {{ __('files.release') }}</summary>
                                    <form method="POST" action="{{ route('portals.files.release', $att->id) }}" class="mt-2">
                                        @csrf
                                        @if($att->isRadiologyReport())
                                            <div class="form-group">
                                                <label class="form-label form-label-required">{{ __('files.lbl_modality') }}</label>
                                                <select name="modality" class="form-control" required>
                                                    @foreach($modalities as $m)
                                                        <option value="{{ $m }}">{{ __('files.modalities.' . $m) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label form-label-required">{{ __('files.lbl_body_part') }}</label>
                                                <input type="text" name="body_part" class="form-control" maxlength="150" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label form-label-required">{{ __('files.lbl_study_date') }}</label>
                                                <input type="date" name="study_date" class="form-control" max="{{ now()->toDateString() }}" required>
                                            </div>
                                        @endif
                                        <p class="form-hint">{{ __('files.release_hint') }}</p>
                                        <button type="submit" class="btn btn-primary btn-xs"><i data-lucide="send"></i> {{ __('files.release_confirm') }}</button>
                                    </form>
                                </details>
                                @endunless

                                <details>
                                    <summary class="btn btn-danger btn-xs"><i data-lucide="ban"></i> {{ __('files.void') }}</summary>
                                    <form method="POST" action="{{ route('portals.files.void', $att->id) }}" class="mt-2">
                                        @csrf
                                        <div class="form-group">
                                            <label class="form-label form-label-required">{{ __('files.lbl_void_reason') }}</label>
                                            <input type="text" name="reason" class="form-control" minlength="3" maxlength="500" required>
                                        </div>
                                        <p class="form-hint">{{ __('files.void_hint') }}</p>
                                        <button type="submit" class="btn btn-danger btn-xs"><i data-lucide="ban"></i> {{ __('files.void_confirm') }}</button>
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
        @endif
    </div>
</div>
@endsection
