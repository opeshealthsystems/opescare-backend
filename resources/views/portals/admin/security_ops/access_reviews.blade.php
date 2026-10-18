{{-- Group D import: web page over the platform's AccessReviewService (the platform exposed it as JSON only). --}}
@extends('layouts.portal')
@section('title', __('security_ops.ar_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.ar_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@php $tones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.ar_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.ar_subtitle') }}</p>
    </div>
    @if($overdue > 0)
    <div class="od-row"><span class="od-pill od-pill--pending">{{ __('security_ops.ar_overdue_schedules', ['count' => $overdue]) }}</span></div>
    @endif
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('security_ops.ar_initiate') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.security.access_review.store') }}" class="oda-form">
            @csrf
            <label class="oda-label" for="ar_email">
                <span class="oda-label__text">{{ __('security_ops.ar_lbl_email') }} *</span>
                <input id="ar_email" type="email" name="email" required class="od-input" value="{{ old('email') }}">
            </label>
            <label class="oda-label oda-form__full" for="ar_reason">
                <span class="oda-label__text">{{ __('security_ops.ar_lbl_reason') }} *</span>
                <textarea id="ar_reason" name="reason" rows="2" required class="oda-textarea">{{ old('reason') }}</textarea>
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="play"></i> {{ __('security_ops.ar_btn_initiate') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.ar_title') }} <span class="od-card__count">({{ $reviews->total() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($reviews->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.ar_empty') }}</h3>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.ar_col_subject') }}</th>
                    <th>{{ __('security_ops.ar_col_reason') }}</th>
                    <th>{{ __('security_ops.ar_col_status') }}</th>
                    <th>{{ __('security_ops.ar_col_outcome') }}</th>
                    <th>{{ __('security_ops.ar_col_when') }}</th>
                    <th>{{ __('security_ops.ar_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($reviews as $review)
                    @php
                        $subject = $subjects[$review->reviewed_subject_id]->email ?? substr($review->reviewed_subject_id, 0, 12).'…';
                        $statusPill = match($review->status) { 'completed' => 'od-pill--ready', 'pending' => 'od-pill--pending', 'in_progress' => 'od-pill--normal', 'escalated' => 'od-pill--urgent', default => 'od-pill--neutral' };
                    @endphp
                    <tr>
                        <td data-label="{{ __('security_ops.ar_col_subject') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $tones[crc32((string) $review->reviewed_subject_id) % 6] }}">{{ strtoupper(mb_substr($subject, 0, 2)) }}</span>
                                <span class="od-table__name">{{ $subject }}</span>
                            </div>
                        </td>
                        <td data-label="{{ __('security_ops.ar_col_reason') }}">{{ \Illuminate\Support\Str::limit($review->findings ?? '—', 80) }}</td>
                        <td data-label="{{ __('security_ops.ar_col_status') }}"><span class="od-pill {{ $statusPill }}">{{ __('security_ops.ar_status_'.$review->status) }}</span></td>
                        <td data-label="{{ __('security_ops.ar_col_outcome') }}">{{ $review->outcome ? __('security_ops.ar_outcome_'.$review->outcome) : '—' }}</td>
                        <td data-label="{{ __('security_ops.ar_col_when') }}" class="oda-ga-muted">{{ ($review->completed_at ?? $review->created_at)?->format('d/m/Y H:i') }}</td>
                        <td data-label="{{ __('security_ops.ar_col_actions') }}">
                            @unless($review->isComplete())
                            <form method="POST" action="{{ route('portals.admin.security.access_review.complete', $review->id) }}" class="oda-ga-rowform">
                                @csrf
                                <select name="outcome" class="od-select" aria-label="{{ __('security_ops.ar_col_outcome') }}">
                                    @foreach($outcomes as $o)<option value="{{ $o }}">{{ __('security_ops.ar_outcome_'.$o) }}</option>@endforeach
                                </select>
                                <input type="text" name="notes" class="od-input" placeholder="{{ __('security_ops.ar_ph_notes') }}" aria-label="{{ __('security_ops.ar_ph_notes') }}">
                                <button type="submit" class="od-btn od-btn--row od-btn--sm">{{ __('security_ops.ar_btn_complete') }}</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($reviews->hasPages())<div class="od-card__foot">{{ $reviews->links() }}</div>@endif
</section>
</div>
@endsection
