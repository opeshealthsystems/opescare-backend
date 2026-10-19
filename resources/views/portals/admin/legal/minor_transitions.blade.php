@extends('layouts.portal')
@section('title', __('public.adm_legal_minor_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gbPill = fn ($c) => match ($c) { 'warning' => 'pending', 'info' => 'normal', 'success' => 'ready', 'danger' => 'urgent', default => 'neutral' };
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_legal_minor_h2') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_legal_minor_subtitle') }}</p>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_legal_minor_breadcrumb_span') }}</h2>
        <span class="od-card__count">{{ method_exists($transitions, 'total') ? $transitions->total() : $transitions->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_closures_col_patient') }}</th><th>{{ __('public.adm_legal_minor_col_dob') }}</th><th>{{ __('public.adm_legal_minor_col_turns18') }}</th><th>{{ __('public.adm_legal_minor_col_days_until_18') }}</th><th>{{ __('public.adm_legal_col_status') }}</th></tr>
            </thead>
            <tbody>
                @forelse($transitions as $t)
                    @php
                        $days = $t->daysUntil18();
                        $daysBadge = $days <= 30 ? 'urgent' : ($days <= 90 ? 'pending' : 'ready');
                        $pname = trim(($t->patient?->first_name ?? '') . ' ' . ($t->patient?->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_closures_col_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32($pname) % 6] }}">{{ mb_strtoupper(mb_substr($t->patient?->first_name ?? '?', 0, 1) . mb_substr($t->patient?->last_name ?? '', 0, 1)) }}</span>
                                <div>
                                    <div class="od-table__name">{{ $t->patient?->first_name }} {{ $t->patient?->last_name }}</div>
                                    <div class="od-table__sub oda-mono">{{ $t->patient?->health_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_minor_col_dob') }}">{{ $t->date_of_birth->translatedFormat('d M Y') }}</td>
                        <td data-label="{{ __('public.adm_legal_minor_col_turns18') }}"><span class="od-table__name">{{ $t->turns_18_on->translatedFormat('d M Y') }}</span></td>
                        <td data-label="{{ __('public.adm_legal_minor_col_days_until_18') }}">
                            <span class="od-pill od-pill--{{ $daysBadge }}">
                                @if($days < 0) {{ __('public.adm_legal_minor_overdue') }} @elseif($days === 0) {{ __('public.adm_legal_minor_today') }} @else {{ $days }} {{ __('public.adm_legal_minor_days') }} @endif
                            </span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="od-pill od-pill--{{ $gbPill($t->statusColor()) }}">@enum($t->status)</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                        <div class="od-empty">
                            <div class="od-empty__icon"><i data-lucide="cake"></i></div>
                            <p class="od-empty__text">{{ __('public.adm_legal_minor_empty') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</section>
<div class="oda-gb-pager">{{ $transitions->links() }}</div>
</div>
@endsection
