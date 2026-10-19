{{-- Group D import: web page over the platform's SuspiciousAccessDetectionService. No patient content:
     a flag shows the user, the type and counts. Reviewing annotates the flag; log rows are never touched. --}}
@extends('layouts.portal')
@section('title', __('security_ops.sa_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@php $tones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.sa_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.sa_subtitle', ['threshold' => $threshold, 'minutes' => $window]) }}</p>
    </div>
    <div class="od-row">
        <form method="POST" action="{{ route('portals.admin.security.suspicious_access.scan') }}" class="oda-inline-form">
            @csrf
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="radar"></i> {{ __('security_ops.sa_btn_scan') }}</button>
        </form>
    </div>
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.sa_title') }} <span class="od-card__count">({{ $flags->total() }})</span></h2>
    </div>
    <div class="od-card__body">
    <form method="GET" action="{{ route('portals.admin.security.suspicious_access') }}" class="oda-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('security_ops.sa_col_status') }}">
            <option value="">{{ __('security_ops.sa_filter_all') }}</option>
            @foreach(['open', 'reviewed', 'dismissed', 'escalated'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __('security_ops.sa_status_'.$s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
    </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($flags->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.sa_empty') }}</h3>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.sa_col_user') }}</th>
                    <th>{{ __('security_ops.sa_col_type') }}</th>
                    <th>{{ __('security_ops.sa_col_severity') }}</th>
                    <th>{{ __('security_ops.sa_col_evidence') }}</th>
                    <th>{{ __('security_ops.sa_col_status') }}</th>
                    <th>{{ __('security_ops.sa_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($flags as $flag)
                    @php
                        $who = $users[$flag->user_id]->email ?? substr($flag->user_id, 0, 12).'…';
                        $stPill = match($flag->status) { 'open' => 'od-pill--urgent', 'escalated' => 'od-pill--pending', 'reviewed' => 'od-pill--ready', default => 'od-pill--neutral' };
                    @endphp
                    <tr data-flag="{{ $flag->id }}">
                        <td data-label="{{ __('security_ops.sa_col_user') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $tones[crc32((string) $flag->user_id) % 6] }}">{{ strtoupper(mb_substr($who, 0, 2)) }}</span>
                                <span class="od-table__name">{{ $who }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('security_ops.sa_col_type') }}">{{ __('security_ops.sa_type_'.$flag->flag_type) }}</td>
                        <td data-label="{{ __('security_ops.sa_col_severity') }}"><span class="od-pill {{ in_array($flag->severity, ['critical', 'high']) ? 'od-pill--urgent' : 'od-pill--pending' }}">@enum($flag->severity, 'severity')</span></td>
                        <td data-label="{{ __('security_ops.sa_col_evidence') }}">
                            @if(isset($flag->evidence['record_count']))
                                {{ __('security_ops.sa_evidence_volume', ['count' => $flag->evidence['record_count'], 'minutes' => $flag->evidence['window_minutes'] ?? $window]) }}
                            @else
                                {{ __('security_ops.sa_evidence_emergency') }}
                            @endif
                            <span class="od-table__sub">{{ $flag->created_at?->format('d/m/Y H:i') }}</span>
                        </td>
                        <td data-label="{{ __('security_ops.sa_col_status') }}">
                            <span class="od-pill {{ $stPill }}">{{ __('security_ops.sa_status_'.$flag->status) }}</span>
                            @if($flag->reviewed_at)
                                <span class="od-table__sub">{{ __('security_ops.sa_reviewed_by', ['who' => $users[$flag->reviewed_by]->email ?? '—', 'when' => $flag->reviewed_at->format('d/m/Y H:i')]) }}</span>
                                @if($flag->review_notes)<span class="od-table__sub">{{ \Illuminate\Support\Str::limit($flag->review_notes, 80) }}</span>@endif
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.sa_col_actions') }}">
                            @if($flag->isOpen())
                            <form method="POST" action="{{ route('portals.admin.security.suspicious_access.review', $flag->id) }}" class="oda-ga-rowform">
                                @csrf
                                <select name="decision" class="od-select" aria-label="{{ __('security_ops.sa_col_status') }}">
                                    @foreach($decisions as $d)<option value="{{ $d }}">{{ __('security_ops.sa_status_'.$d) }}</option>@endforeach
                                </select>
                                <input type="text" name="notes" required class="od-input" placeholder="{{ __('security_ops.sa_ph_notes') }}" aria-label="{{ __('security_ops.sa_ph_notes') }}">
                                <button type="submit" class="od-btn od-btn--row od-btn--sm">{{ __('security_ops.sa_btn_review') }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($flags->hasPages())<div class="od-card__foot">{{ $flags->links() }}</div>@endif
</section>
</div>
@endsection
