@extends('layouts.portal')
@section('title', __('staff_data.title_import'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_import_data_import_title') }}</h1>
        <p class="od-page__sub">{{ __('public.stf_import_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.data_import.create') }}" class="od-btn od-btn--primary od-btn--sm">
            <i data-lucide="plus-circle"></i>
            {{ __('public.stf_import_new_import') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('staff_data.title_import') }}</h2>
        <span class="od-card__count">{{ method_exists($jobs, 'total') ? $jobs->total() : count($jobs) }}</span>
    </div>
    <div class="od-card__body">
        {{-- Filters --}}
        <form method="GET" action="{{ route('portals.admin.data_import.index') }}" class="oda-toolbar">
            <select name="status" class="od-select">
                <option value="">{{ __('public.stf_import_all_statuses') }}</option>
                @foreach(['uploaded','mapping_required','preview_ready','validated','validation_failed','approved_for_import','importing','completed','completed_with_errors','failed','rolled_back','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@enum($s)</option>
                @endforeach
            </select>
            <select name="import_type" class="od-select">
                <option value="">{{ __('public.stf_import_all_types') }}</option>
                @foreach($importTypes as $key => $def)
                    <option value="{{ $key }}" {{ request('import_type') === $key ? 'selected' : '' }}>{{ $def['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                <i data-lucide="filter"></i> {{ __('public.stf_import_filter') }}
            </button>
            <a href="{{ route('portals.admin.data_import.index') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_clear') }}</a>
        </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if(count($jobs) === 0)
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="upload-cloud"></i></div>
                <h3 class="od-empty__title">{{ __('public.stf_import_no_imports_title') }}</h3>
                <p class="od-empty__text">{{ __('public.stf_import_no_imports_desc') }}</p>
                <a href="{{ route('portals.admin.data_import.create') }}" class="od-btn od-btn--primary od-btn--sm">
                    {{ __('public.stf_import_start') }}
                </a>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_import_col_file') }}</th>
                            <th>{{ __('public.stf_import_col_type') }}</th>
                            <th>{{ __('public.stf_import_col_status') }}</th>
                            <th>{{ __('public.stf_import_col_rows') }}</th>
                            <th>{{ __('public.stf_import_col_created') }}</th>
                            <th>{{ __('public.stf_import_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                        @php
                            $statusBadge = match($job->status) {
                                'completed'                       => 'ready',
                                'completed_with_errors'           => 'pending',
                                'failed', 'validation_failed'     => 'urgent',
                                'importing','approved_for_import' => 'processing',
                                'validated','preview_ready'       => 'normal',
                                default                           => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_import_col_file') }}">
                                <div class="od-table__person">
                                    <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $job->original_filename) % 6] }}"><i data-lucide="file-spreadsheet"></i></span>
                                    <div>
                                        <div class="od-table__name">{{ $job->original_filename }}</div>
                                        <div class="od-table__sub">{{ strtoupper($job->file_extension) }} · {{ number_format($job->file_size_bytes / 1024, 1) }} KB</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_type') }}">
                                <span class="od-pill od-pill--neutral">{{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_status') }}">
                                <span class="od-pill od-pill--{{ $statusBadge }}">@enum($job->status)</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_rows') }}">
                                @if($job->total_rows > 0)
                                    <span class="od-pill od-pill--ready">{{ $job->valid_rows }}</span>
                                    @if($job->invalid_rows > 0)
                                        <span class="od-pill od-pill--urgent">{{ $job->invalid_rows }}</span>
                                    @endif
                                    <span class="od-table__sub">/ {{ $job->total_rows }} {{ __('public.stf_import_total') }}</span>
                                @else
                                    <span class="od-table__sub">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_import_col_created') }}">
                                <span class="od-table__sub">{{ \Carbon\Carbon::parse($job->created_at)->translatedFormat('M d, Y H:i') }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_actions') }}">
                                <div class="od-table__actions">
                                    @if($job->status === 'mapping_required')
                                        <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="od-btn od-btn--row">{{ __('public.stf_import_map_columns_btn') }}</a>
                                    @elseif($job->status === 'preview_ready')
                                        <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('public.stf_import_edit_mapping') }}</a>
                                        <form method="POST" action="{{ route('portals.admin.data_import.validate', $job->id) }}" class="oda-inline-form">@csrf
                                            <button type="submit" class="od-btn od-btn--row">{{ __('public.stf_import_validate') }}</button>
                                        </form>
                                    @elseif(in_array($job->status, ['validated','validation_failed']))
                                        <a href="{{ route('portals.admin.data_import.preview', $job->id) }}" class="od-btn od-btn--row">{{ __('public.stf_import_preview') }}</a>
                                    @elseif($job->canBeRolledBack())
                                        <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-rollback-job="{{ $job->id }}">{{ __('public.stf_import_rollback') }}</button>
                                    @endif

                                    <a href="{{ route('portals.admin.data_import.audit', $job->id) }}" class="od-btn od-btn--ghost od-btn--sm">
                                        <i data-lucide="scroll-text"></i> {{ __('public.stf_import_log') }}
                                    </a>

                                    @if($job->canBeCancelled())
                                        <form method="POST" action="{{ route('portals.admin.data_import.cancel', $job->id) }}" class="oda-inline-form">@csrf
                                            <button type="submit" class="od-btn od-btn--ghost od-btn--sm" data-confirm="{{ __('staff_data.confirm_cancel') }}">{{ __('public.stf_import_cancel') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
</div>

{{-- Rollback modal --}}
<div id="rollback-modal" class="oda-gb-overlay" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="rotate-ccw"></i> {{ __('public.stf_import_rollback_modal_title') }}</h2>
        </div>
        <form id="rollback-form" method="POST" action="">
            @csrf
            <div class="od-card__body od-stack">
                <p class="oda-help">{{ __('public.stf_import_rollback_desc') }}</p>
                <label class="oda-label">
                    <span class="oda-label__text">{{ __('public.stf_import_rollback_reason') }} <span class="od-table__sub">{{ __('public.stf_import_rollback_optional') }}</span></span>
                    <textarea name="reason" class="oda-textarea" rows="3" maxlength="500" placeholder="{{ __('public.stf_import_rollback_ph') }}"></textarea>
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-rollback-close>{{ __('public.stf_import_rollback_back') }}</button>
                    <button type="submit" class="od-btn od-btn--danger od-btn--sm">
                        <i data-lucide="rotate-ccw"></i> {{ __('public.stf_import_rollback_btn') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script @cspNonce>
(function () {
    var modal = document.getElementById('rollback-modal');
    var form  = document.getElementById('rollback-form');
    if (!modal || !form) return;

    // Built from the named route so the URL never has to be hardcoded here.
    var template = @json(route('portals.admin.data_import.rollback', ['id' => '__ID__']));

    function close() { modal.setAttribute('hidden', ''); }

    document.querySelectorAll('[data-rollback-job]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.setAttribute('action', template.replace('__ID__', btn.getAttribute('data-rollback-job')));
            modal.removeAttribute('hidden');
        });
    });

    document.querySelectorAll('[data-rollback-close]').forEach(function (btn) {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', function (e) { if (e.target === this) close(); });
})();
</script>
@endsection
