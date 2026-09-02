@extends('layouts.portal')

@section('title', __('public.portal.documents_title', [], app()->getLocale()) ?: 'My Documents')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.documents_breadcrumb', [], app()->getLocale()) ?: 'Documents')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.documents_title', [], $l) ?: 'My Documents' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.documents_subtitle', [], $l) ?: 'Official documents and certificates issued to you.' }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('warning'))
<div class="alert alert-warning mb-4"><i data-lucide="alert-circle"></i><div>{{ session('warning') }}</div></div>
@endif

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($documents->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
        <h3>{{ __('public.portal.no_documents_title', [], $l) ?: 'No Documents' }}</h3>
        <p>{{ __('public.portal.no_documents_desc', [], $l) ?: 'You have no official documents issued at this time.' }}</p>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.portal.panel_official_documents', [], $l) ?: 'Official Documents' }}</h2>
        <span class="badge badge-primary">{{ method_exists($documents, 'total') ? $documents->total() : $documents->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.panel_official_documents', [], $l) ?: 'Documents list' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.col_document', [], $l) ?: 'Document' }}</th>
                    <th>{{ __('public.portal.col_type', [], $l) ?: 'Type' }}</th>
                    <th>{{ __('public.portal.col_number', [], $l) ?: 'Number' }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.portal.col_issued', [], $l) ?: 'Issued' }}</th>
                    <th>{{ __('public.portal.col_expires', [], $l) ?: 'Expires' }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td data-label="{{ __('public.portal.col_document', [], $l) ?: 'Document' }}">
                        <span class="td-strong">{{ $doc->title ?? __('public.portal.lbl_untitled_document', [], $l) ?: 'Untitled Document' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_type', [], $l) ?: 'Type' }}">
                        <span class="td-muted">@enum($doc->document_type)</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_number', [], $l) ?: 'Number' }}">
                        <span class="td-mono">{{ $doc->document_number ?? '—' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) ?: 'Status' }}">
                        @php
                            $statusCls = match($doc->status) { 'released' => 'badge-success', 'revoked' => 'badge-danger', default => 'badge-neutral' };
                        @endphp
                        <span class="badge {{ $statusCls }}">@enum($doc->status)</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_issued', [], $l) ?: 'Issued' }}">
                        <span class="td-muted">{{ $doc->issued_at?->format('d M Y') ?? '—' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.col_expires', [], $l) ?: 'Expires' }}">
                        <span class="td-muted">{{ $doc->expires_at?->format('d M Y') ?? (__('public.portal.lbl_no_expiry', [], $l) ?: 'No expiry') }}</span>
                    </td>
                    <td class="row-actions">
                        @if($doc->status === 'released' && $doc->pdf_path)
                        <a href="{{ route('portals.patient.documents.download', $doc->id) }}" class="btn btn-secondary btn-sm">
                            <i data-lucide="download"></i> {{ __('public.portal.btn_download', [], $l) ?: 'Download' }}
                        </a>
                        @elseif($doc->status === 'released')
                        <span class="td-muted">{{ __('public.portal.lbl_processing', [], $l) ?: 'Processing…' }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($documents, 'links') && $documents->hasPages())
    <div class="panel-body">
        {{ $documents->links() }}
    </div>
    @endif
</div>
@endif

@endsection
