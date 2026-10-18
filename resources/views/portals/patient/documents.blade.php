@extends('layouts.portal')

@section('title', __('family.doc_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.doc_title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
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

    {{-- Documents the record can produce on request. Each is rendered at the
         moment it is downloaded and never stored, so what the patient takes
         away is their record as it stands rather than a copy of how it once
         looked. --}}
    @if(!empty($available))
    <div class="f-card ods-8e86974" data-section="documents-available">
        <h2 class="f-card__title ods-aa7841b">{{ __('document.available_title') }}</h2>
        <p class="f-small f-muted ods-2280133">{{ __('document.available_hint') }}</p>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_document') }}</th>
                        <th>{{ __('family.col_number') }}</th>
                        <th>{{ __('family.col_issued') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($available as $item)
                    <tr>
                        <td class="ods-749274d">{{ $item['title'] }}</td>
                        <td class="ods-9709411">{{ $item['reference'] ?: '—' }}</td>
                        <td>{{ $item['date'] ? \Illuminate\Support\Carbon::parse($item['date'])->translatedFormat('d M Y') : '—' }}</td>
                        <td class="ods-f6e3d7f">
                            <a href="{{ $item['record']
                                ? route('portals.patient.documents.render', ['type' => $item['type'], 'record' => $item['record']])
                                : route('portals.patient.documents.render-whole', ['type' => $item['type']]) }}"
                               class="f-btn ods-20d7aa3">
                                <i class="ods-3830b67" data-lucide="download" aria-hidden="true"></i>
                                {{ __('family.doc_download') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Files a facility attached and released to the patient (scans,
         radiology reports). Stored uploads, downloaded through a short-lived
         signed link. --}}
    @if(!empty($attachments))
    <div class="f-card ods-8e86974" data-section="documents-files">
        <h2 class="f-card__title ods-aa7841b">{{ __('files.patient_title') }}</h2>
        <p class="f-small f-muted ods-2280133">{{ __('files.patient_hint') }}</p>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_document') }}</th>
                        <th>{{ __('files.col_category') }}</th>
                        <th>{{ __('files.col_released') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($attachments as $file)
                    <tr>
                        <td class="ods-749274d">{{ $file['title'] }}</td>
                        <td>{{ $file['category'] }}</td>
                        <td>{{ $file['date'] ? \Illuminate\Support\Carbon::parse($file['date'])->translatedFormat('d M Y') : '—' }}</td>
                        <td class="ods-f6e3d7f">
                            <a href="{{ $file['url'] }}" class="f-btn ods-20d7aa3">
                                <i class="ods-3830b67" data-lucide="download" aria-hidden="true"></i>
                                {{ __('family.doc_download') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(!$patient || ($docCount === 0 && empty($available) && empty($attachments)))
    <div class="f-card ods-99e8a36" data-section="documents-empty">
        <div class="pf-qa__icon ods-4ef0061">
            <i class="ods-f70f93d" data-lucide="file-text" aria-hidden="true"></i>
        </div>
        <p class="f-muted ods-1386d50">{{ __('family.doc_empty') }}</p>
    </div>
    @elseif($docCount > 0)
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
                        <td class="ods-749274d">{{ $doc->title ?? ucfirst(str_replace('_', ' ', (string) $doc->document_type)) }}</td>
                        <td class="ods-9709411">{{ $doc->document_number ?? '—' }}</td>
                        <td>{{ $doc->issued_at ? \Illuminate\Support\Carbon::parse($doc->issued_at)->translatedFormat('d M Y') : '—' }}</td>
                        <td>{{ $doc->expires_at ? \Illuminate\Support\Carbon::parse($doc->expires_at)->translatedFormat('d M Y') : '—' }}</td>
                        <td><span class="f-badge {{ $doc->status === 'released' ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($doc->status)</span></td>
                        <td class="ods-f6e3d7f">
                            @if(app(\App\Services\Patient\PatientDocumentService::class)->renderableFor($doc))
                            <a href="{{ route('portals.patient.documents.download', $doc->id) }}" class="f-btn ods-20d7aa3">
                                <i class="ods-3830b67" data-lucide="download" aria-hidden="true"></i>
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
        <div class="ods-002535c">{{ $documents->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
