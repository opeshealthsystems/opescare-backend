@extends('layouts.portal')

@section('title', __('family.doc_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.doc_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $viewing  = request()->attributes->get('viewing_patient');
    $docCount = method_exists($documents, 'total') ? $documents->total() : $documents->count();
@endphp

@section('content')
<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.doc_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.doc_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $docCount === 0)
    <div class="f-card" style="text-align:center;padding:40px 20px;" data-section="documents-empty">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <i data-lucide="file-text" aria-hidden="true" style="width:24px;height:24px;"></i>
        </div>
        <p class="f-muted" style="margin:0;">{{ __('family.doc_empty') }}</p>
    </div>
    @else
    <div class="f-card" data-section="documents">
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_document') }}</th>
                        <th>{{ __('family.col_number') }}</th>
                        <th>{{ __('family.col_issued') }}</th>
                        <th>{{ __('family.col_expires') }}</th>
                        <th>{{ __('family.col_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($documents as $doc)
                    <tr>
                        <td style="font-weight:650;color:var(--f-ink);">{{ $doc->title ?? ucfirst(str_replace('_', ' ', (string) $doc->document_type)) }}</td>
                        <td style="font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $doc->document_number ?? '—' }}</td>
                        <td>{{ $doc->issued_at ? \Illuminate\Support\Carbon::parse($doc->issued_at)->translatedFormat('d M Y') : '—' }}</td>
                        <td>{{ $doc->expires_at ? \Illuminate\Support\Carbon::parse($doc->expires_at)->translatedFormat('d M Y') : '—' }}</td>
                        <td><span class="f-badge {{ $doc->status === 'released' ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($doc->status)</span></td>
                        <td style="text-align:right;">
                            @if($doc->status === 'released')
                            <a href="{{ route('portals.patient.documents.download', $doc->id) }}" class="f-btn" style="min-height:36px;padding:4px 10px;">
                                <i data-lucide="download" aria-hidden="true" style="width:15px;height:15px;"></i>
                                {{ __('family.doc_download') }}
                            </a>
                            @else
                            <span class="f-small f-muted">{{ __('family.doc_not_released') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if(method_exists($documents, 'links'))
        <div style="margin-top:12px;">{{ $documents->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
