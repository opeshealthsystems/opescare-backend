@extends('layouts.portal')
@section('title', $document->title)
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $document->title }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--normal">{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</span>
            {{ strtoupper($document->language) }}
        </p>
    </div>
</div>

@if(session('success'))<div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Versions table --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="layers"></i> {{ __('public.adm_legal_show_versions_title') }}</h2>
        <span class="od-card__count">{{ count($versions) }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_show_col_version') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_show_col_reaccept') }}</th><th>{{ __('public.adm_legal_show_col_published') }}</th><th>{{ __('public.adm_legal_show_col_effective') }}</th><th>{{ __('public.adm_legal_show_col_change_summary') }}</th></tr>
            </thead>
            <tbody>
                @forelse($versions as $ver)
                    <tr class="{{ $ver->is_current ? 'is-selected' : '' }}">
                        <td data-label="{{ __('public.adm_legal_show_col_version') }}">
                            <span class="oda-mono od-table__name">v{{ $ver->version }}</span>
                            @if($ver->is_current)
                                <span class="od-pill od-pill--ready">{{ __('public.adm_legal_show_status_current') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="od-pill od-pill--{{ $ver->isEffective() ? 'ready' : 'pending' }}">
                                {{ $ver->isEffective() ? __('public.adm_legal_show_status_live') : __('public.adm_legal_show_status_scheduled') }}
                            </span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_reaccept') }}">
                            @if($ver->requires_reacceptance)
                                <span class="od-pill od-pill--urgent">{{ __('admin_extra.yes', [], app()->getLocale()) }}</span>
                            @else
                                <span class="od-table__sub">{{ __('admin_extra.no', [], app()->getLocale()) }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_published') }}">
                            <span class="od-table__sub">{{ $ver->published_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_effective') }}">
                            <span class="od-table__sub">{{ $ver->effective_at?->translatedFormat('d M Y') ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_change_summary') }}">{{ $ver->change_summary ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="od-empty">
                            <div class="od-empty__icon"><i data-lucide="layers"></i></div>
                            <p class="od-empty__text">{{ __('public.adm_legal_show_empty_versions') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</section>

{{-- Current version preview --}}
@php $current = $versions->where('is_current', true)->first(); @endphp
@if($current)
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="file-text"></i> {{ __('public.adm_legal_show_content_title') }} (v{{ $current->version }})</h2>
        <a href="{{ route('public.legal.show', $document->slug) }}" target="_blank" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="external-link"></i> {{ __('public.adm_legal_show_btn_public_view') }}
        </a>
    </div>
    <div class="od-card__body">
        <div class="oda-gb-legal">
            {!! app(\App\Modules\Legal\Services\LegalHtmlSanitizer::class)->clean((string) $current->content_html) !!}
        </div>
    </div>
</section>
@endif
</div>
@endsection
