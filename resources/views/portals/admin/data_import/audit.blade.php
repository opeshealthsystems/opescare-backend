@extends('layouts.portal')
@section('title', __('public.stf_import_audit_log'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_audit_log'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.stf_import_audit_log') }}</h1>
        <p class="od-page__sub">{{ $job->original_filename }} · {{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.data_import.index') }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-left"></i> {{ __('public.stf_import_back') }}</a>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.stf_import_audit_log') }}</h2>
        <span class="od-card__count">{{ $job->auditEvents->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($job->auditEvents->count() === 0)
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="scroll-text"></i></div>
                <h3 class="od-empty__title">{{ __('public.stf_import_no_audit_title') }}</h3>
                <p class="od-empty__text">{{ __('public.stf_import_no_audit_desc') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_import_col_status') }}</th>
                            <th>{{ __('public.stf_import_col_created') }}</th>
                            <th>{{ __('public.stf_import_col_message') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->auditEvents->sortBy('occurred_at') as $event)
                        <tr>
                            <td data-label="{{ __('public.stf_import_col_status') }}">
                                <div class="od-table__name">@enum($event->action)</div>
                                @if($event->actor_id)
                                    <div class="od-table__sub">{{ __('staff_data.audit_by', ['id' => $event->actor_id]) }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_import_col_created') }}">
                                <span class="od-table__sub">{{ \Carbon\Carbon::parse($event->occurred_at)->translatedFormat('M d, Y H:i:s') }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_import_col_message') }}">
                                @if($event->details)
                                    <pre class="oda-pre">{{ json_encode($event->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="od-table__sub">—</span>
                                @endif
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
@endsection
