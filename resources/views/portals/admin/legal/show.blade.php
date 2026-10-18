@extends('layouts.portal')
@section('title', $document->title)
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_legal_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span>{{ $document->title }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="scale"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $document->title }}</h2>
        <div class="entity-head__sub">
            <span class="badge badge-primary badge-sm">{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</span>
            <span class="td-muted text-sm">{{ strtoupper($document->language) }}</span>
        </div>
    </div>
    <div class="entity-head__spacer"></div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Versions table --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="layers"></i> {{ __('public.adm_legal_show_versions_title') }}</h3></div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_show_col_version') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_show_col_reaccept') }}</th><th>{{ __('public.adm_legal_show_col_published') }}</th><th>{{ __('public.adm_legal_show_col_effective') }}</th><th>{{ __('public.adm_legal_show_col_change_summary') }}</th></tr>
            </thead>
            <tbody>
                @forelse($versions as $ver)
                    <tr class="{{ $ver->is_current ? 'row-emergency' : '' }}">
                        <td data-label="{{ __('public.adm_legal_show_col_version') }}">
                            <span class="mono kv-strong">v{{ $ver->version }}</span>
                            @if($ver->is_current)
                                <span class="badge badge-success badge-sm">{{ __('public.adm_legal_show_status_current') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge {{ $ver->isEffective() ? 'badge-success' : 'badge-warning' }} badge-sm">
                                {{ $ver->isEffective() ? __('public.adm_legal_show_status_live') : __('public.adm_legal_show_status_scheduled') }}
                            </span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_reaccept') }}">
                            @if($ver->requires_reacceptance)
                                <span class="badge badge-danger badge-sm">{{ __('admin_extra.yes', [], app()->getLocale()) }}</span>
                            @else
                                <span class="td-muted">{{ __('admin_extra.no', [], app()->getLocale()) }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_published') }}" class="td-muted">
                            {{ $ver->published_at?->translatedFormat('d M Y H:i') ?? '—' }}
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_effective') }}" class="td-muted">
                            {{ $ver->effective_at?->translatedFormat('d M Y') ?? '—' }}
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_change_summary') }}">{{ $ver->change_summary ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_legal_show_empty_versions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Current version preview --}}
@php $current = $versions->where('is_current', true)->first(); @endphp
@if($current)
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.adm_legal_show_content_title') }} (v{{ $current->version }})</h3>
        <a href="{{ route('public.legal.show', $document->slug) }}" target="_blank" class="btn btn-secondary btn-sm">
            <i data-lucide="external-link"></i> {{ __('public.adm_legal_show_btn_public_view') }}
        </a>
    </div>
    <div class="panel-body">
        <div class="legal-preview">
            {!! app(AppModulesegalservicesegalhtmlsanitizer::class)->clean((string) $current->content_html) !!}
        </div>
    </div>
</div>
@endif


@endsection

