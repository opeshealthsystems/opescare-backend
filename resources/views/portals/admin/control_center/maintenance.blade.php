@extends('layouts.portal')
@section('title', __('public.adm_cc_mnt_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_mnt_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_mnt_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_mnt_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_mnt_subtitle') }}</p>
    </div>
    <div class="od-row">
        <button type="button" class="od-btn od-btn--primary" data-show="create-modal" data-display="flex">
            <i data-lucide="plus"></i> {{ __('public.adm_cc_mnt_btn_schedule') }}
        </button>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>
@endif

{{-- Active Window Banner --}}
@php $active = $windows->firstWhere('is_active', true); @endphp
@if($active && $active->isCurrentlyActive())
<div class="oda-ga-alert oda-ga-alert--danger">
    <i data-lucide="wrench"></i>
    <div class="oda-ga-alert__body">
        <strong>{{ __('public.adm_cc_mnt_banner_active') }}</strong> {{ $active->title }}
        <span class="oda-ga-muted">{{ __('admin_desk.ga_until', ['when' => \Carbon\Carbon::parse($active->ends_at)->translatedFormat('M d, Y H:i')]) }}</span>
    </div>
</div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_mnt_heading') }} <span class="od-card__count">({{ $windows->count() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($windows->count() === 0)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="wrench"></i></span>
                <h3 class="od-empty__title">{{ __('public.adm_cc_mnt_empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('public.adm_cc_mnt_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_mnt_col_title') }}</th><th>{{ __('public.adm_cc_mnt_col_starts') }}</th><th>{{ __('public.adm_cc_mnt_col_ends') }}</th><th>{{ __('public.adm_cc_mnt_col_status') }}</th><th>{{ __('public.adm_cc_mnt_col_emergency') }}</th><th>{{ __('public.adm_cc_mnt_col_created_by') }}</th><th class="od-table__actions">{{ __('public.adm_cc_mnt_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($windows as $win)
                    @php
                        $now = now();
                        $starts = \Carbon\Carbon::parse($win->starts_at);
                        $ends   = \Carbon\Carbon::parse($win->ends_at);
                        $live   = $win->is_active && $now->between($starts, $ends);
                        $upcoming = $win->is_active && $now->lt($starts);
                    @endphp
                    <tr>
                        <td data-label="Title">
                            <span class="od-table__name">{{ $win->title }}</span>
                            @if($win->message)
                                <span class="od-table__sub">{{ Str::limit($win->message, 60) }}</span>
                            @endif
                        </td>
                        <td data-label="Starts">{{ $starts->translatedFormat('M d, Y H:i') }}</td>
                        <td data-label="Ends">{{ $ends->translatedFormat('M d, Y H:i') }}</td>
                        <td data-label="Status">
                            @if($live)
                                <span class="od-pill od-pill--urgent">{{ __('public.adm_cc_mnt_badge_live') }}</span>
                            @elseif($upcoming)
                                <span class="od-pill od-pill--pending">{{ __('public.adm_cc_mnt_badge_upcoming') }}</span>
                            @elseif(!$win->is_active)
                                <span class="od-pill od-pill--neutral">{{ __('public.adm_cc_mnt_badge_inactive') }}</span>
                            @else
                                <span class="od-pill od-pill--neutral">{{ __('public.adm_cc_mnt_badge_expired') }}</span>
                            @endif
                        </td>
                        <td data-label="Emergency Access">
                            @if($win->allow_emergency_access)
                                <span class="od-pill od-pill--ready">{{ __('public.adm_cc_mnt_badge_allowed') }}</span>
                            @else
                                <span class="od-pill od-pill--neutral">{{ __('public.adm_cc_mnt_badge_blocked') }}</span>
                            @endif
                        </td>
                        <td data-label="Created By" class="oda-ga-muted">{{ $win->created_by ?? '—' }}</td>
                        <td class="od-table__actions">
                            <form method="POST" action="{{ route('portals.admin.cc.maintenance.toggle', $win->id) }}" class="oda-inline-form">
                                @csrf
                                <input type="hidden" name="active" value="{{ $win->is_active ? '0' : '1' }}">
                                <label class="oda-ga-switch">
                                    <input type="checkbox" {{ $win->is_active ? 'checked' : '' }} data-autosubmit aria-label="{{ __('admin_desk.ga_toggle', ['label' => $win->title]) }}">
                                    <span class="oda-ga-switch__track"></span>
                                </label>
                            </form>
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

{{-- Create Modal --}}
<div id="create-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('public.adm_cc_mnt_modal_heading') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('public.aria_close') }}" data-hide="create-modal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.cc.maintenance.store') }}" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full">
                    <span class="oda-label__text">{{ __('public.adm_cc_mnt_modal_lbl_title') }} *</span>
                    <input type="text" name="title" class="od-input" required maxlength="150" placeholder="{{ __('public.adm_cc_mnt_modal_ph_title') }}">
                </label>
                <label class="oda-label oda-form__full">
                    <span class="oda-label__text">{{ __('public.adm_cc_mnt_modal_lbl_message') }} <span class="oda-ga-muted">{{ __('public.adm_cc_mnt_modal_lbl_message_hint') }}</span></span>
                    <textarea name="message" class="oda-textarea" rows="2" maxlength="500" placeholder="{{ __('public.adm_cc_mnt_modal_ph_message') }}"></textarea>
                </label>
                <label class="oda-label">
                    <span class="oda-label__text">{{ __('public.adm_cc_mnt_modal_lbl_start') }} *</span>
                    <input type="datetime-local" name="starts_at" class="od-input" required>
                </label>
                <label class="oda-label">
                    <span class="oda-label__text">{{ __('public.adm_cc_mnt_modal_lbl_end') }} *</span>
                    <input type="datetime-local" name="ends_at" class="od-input" required>
                </label>
                <label class="oda-check oda-form__full">
                    <input type="checkbox" name="allow_emergency_access" value="1">
                    {{ __('public.adm_cc_mnt_modal_allow_emergency') }}
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="create-modal">{{ __('public.adm_cc_mnt_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('public.adm_cc_mnt_modal_btn_schedule') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('portals.admin._modal')
@endsection
