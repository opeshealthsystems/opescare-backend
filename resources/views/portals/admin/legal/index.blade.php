@extends('layouts.portal')
@section('title', __('public.adm_legal_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_legal_h2') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_legal_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('public.legal') }}" target="_blank" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="external-link"></i> {{ __('public.adm_legal_btn_public_view') }}
        </a>
    </div>
</div>

{{-- KPI Strip --}}
<div class="od-kpis">
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="file-text"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_legal_stat_documents') }}</span><span class="od-kpi__value">{{ $stats['total_documents'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--sky">
        <span class="od-kpi__icon"><i data-lucide="layers"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_legal_stat_versions') }}</span><span class="od-kpi__value">{{ $stats['total_versions'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="check-circle"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_legal_stat_user_acceptances') }}</span><span class="od-kpi__value">{{ number_format($stats['user_acceptances']) }}</span></span>
    </div>
    <div class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="handshake"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_legal_stat_partner_agreements') }}</span><span class="od-kpi__value">{{ number_format($stats['partner_acceptances']) }}</span></span>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="scale"></i> {{ __('public.adm_legal_panel_title') }}</h2>
        <span class="od-card__count">{{ count($documents) }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_col_title') }}</th><th>{{ __('public.adm_legal_col_type') }}</th><th>{{ __('public.adm_legal_col_language') }}</th><th>{{ __('public.adm_legal_col_current_version') }}</th><th>{{ __('public.adm_legal_col_status') }}</th></tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    @php $ver = $doc->versions->first(); @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_col_title') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $doc->title) % 6] }}"><i data-lucide="scale"></i></span>
                                <span class="od-table__name">{{ $doc->title }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_type') }}">
                            <span class="od-pill od-pill--normal">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_language') }}">{{ strtoupper($doc->language) }}</td>
                        <td data-label="{{ __('public.adm_legal_col_current_version') }}">
                            @if($ver)
                                <span class="oda-mono od-table__name">v{{ $ver->version }}</span>
                                @if($ver->requires_reacceptance)
                                    <span class="od-pill od-pill--pending">{{ __('public.adm_legal_reaccept_req') }}</span>
                                @endif
                            @else
                                <span class="od-table__sub">{{ __('public.adm_legal_no_version') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="od-pill od-pill--{{ $doc->is_active ? 'ready' : 'neutral' }}">
                                {{ $doc->is_active ? __('public.adm_legal_status_active') : __('public.adm_legal_status_inactive') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                        <div class="od-empty">
                            <div class="od-empty__icon"><i data-lucide="file-x"></i></div>
                            <p class="od-empty__text">{{ __('public.adm_legal_empty_docs') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</section>
</div>
@endsection
