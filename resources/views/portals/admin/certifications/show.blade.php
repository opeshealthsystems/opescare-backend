@extends('layouts.portal')
@section('title', $certification->integration_name . ' — Certification')
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cert_show_breadcrumb_parent'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $certification->integration_name) % count($gcTones)];
    $gcPill = fn ($cls) => match (true) {
        str_contains((string) $cls, 'success') => 'ready',
        str_contains((string) $cls, 'danger')  => 'urgent',
        str_contains((string) $cls, 'warning') => 'pending',
        str_contains((string) $cls, 'teal')    => 'teal',
        str_contains((string) $cls, 'info'), str_contains((string) $cls, 'primary') => 'normal',
        default => 'neutral',
    };
    // Badge level colour becomes a tint class (no inline style).
    $gcLevelTint = fn ($lvl) => match ((string) $lvl) {
        'gold', 'bronze' => 'amber', 'silver' => 'sky', 'platinum' => 'teal', default => 'blue',
    };
@endphp

@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="breadcrumb">
    <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.adm_cert_show_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $certification->integration_name }}</span>
</nav>

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true"><i data-lucide="shield-check"></i></span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $certification->integration_name }}</h1>
            <div class="od-row">
                <span class="od-pill od-pill--dot od-pill--{{ $gcPill($certification->statusBadgeClass()) }}">@enum($certification->status)</span>
                <span class="od-pill od-pill--normal">{{ strtoupper($certification->integration_type) }}</span>
                @if($certification->certification_level)
                <span class="od-pill od-pill--{{ $gcPill($certification->levelBadgeClass()) }}">{{ ucfirst($certification->certification_level) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-grid od-grid--2">

    {{-- Left column: details + badge --}}
    <div class="od-stack">
        <section class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="info"></i> {{ __('public.adm_cert_show_panel_details') }}</h2></div>
            <div class="od-card__body">
                <dl class="oda-dl">
                    <dt>{{ __('public.adm_cert_show_kv_vendor') }}</dt><dd>{{ $certification->vendor_name ?? '—' }}</dd>
                    <dt>{{ __('public.adm_cert_show_kv_contact') }}</dt><dd>{{ $certification->vendor_contact ?? '—' }}</dd>
                    <dt>{{ __('public.adm_cert_show_kv_version') }}</dt><dd>{{ $certification->version ?? '—' }}</dd>
                    <dt>{{ __('public.adm_cert_show_kv_submitted') }}</dt><dd>{{ $certification->submitted_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                    <dt>{{ __('public.adm_cert_show_kv_certified') }}</dt><dd>{{ $certification->certified_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                    <dt>{{ __('public.adm_cert_show_kv_expires') }}</dt><dd>
                        {{ $certification->expires_at?->translatedFormat('d M Y') ?? __('public.adm_cert_show_kv_no_expiry') }}
                        @if($certification->isExpiringSoon())
                            <span class="od-pill od-pill--pending"><i data-lucide="alert-triangle"></i> {{ __('public.adm_cert_show_kv_expiring_soon') }}</span>
                        @endif
                    </dd>
                    @if($certification->scope_description)
                    <dt>{{ __('public.adm_cert_show_kv_scope') }}</dt><dd>{{ $certification->scope_description }}</dd>
                    @endif
                </dl>
            </div>
        </section>

        @if($certification->badge)
        <section class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="award"></i> {{ __('public.adm_cert_show_panel_active_badge') }}</h2></div>
            <div class="od-card__body od-stack">
                <div class="oda-gc-badge">
                    <span class="od-avatar od-avatar--lg od-tint--{{ $gcLevelTint($certification->badge->certification_level) }}" aria-hidden="true"><i data-lucide="{{ $certification->badge->levelIcon() }}"></i></span>
                    <div>
                        <div class="od-detail__name">{{ ucfirst($certification->badge->certification_level) }}</div>
                        <div class="oda-mono">{{ $certification->badge->badge_code }}</div>
                        <div class="oda-help">
                            {{ __('public.adm_cert_show_badge_issued') }} {{ $certification->badge->issued_at->translatedFormat('d M Y') }}
                            @if($certification->badge->expires_at) {{ __('public.adm_cert_show_badge_expires') }} {{ $certification->badge->expires_at->translatedFormat('d M Y') }} @endif
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('portals.admin.certifications.badge.revoke', $certification->badge) }}"
                      class="oda-form" data-confirm="{{ Illuminate\Support\Js::from(__('public.adm_cert_show_js_confirm_revoke')) }}">
                    @csrf
                    <label class="oda-label oda-form__full">
                        <span class="oda-label__text">{{ __('public.adm_cert_show_ph_revoke_reason') }}</span>
                        <input type="text" name="revoke_reason" class="od-input" placeholder="{{ __('public.adm_cert_show_ph_revoke_reason') }}" required>
                    </label>
                    <div class="oda-form__actions">
                        <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_cert_show_btn_revoke') }}</button>
                    </div>
                </form>
            </div>
        </section>
        @elseif($certification->isPassed())
        <section class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="award"></i> {{ __('public.adm_cert_show_panel_issue_badge') }}</h2></div>
            <div class="od-card__body">
                <form method="POST" action="{{ route('portals.admin.certifications.badge.issue', $certification) }}" class="oda-form">
                    @csrf
                    <label class="oda-label">
                        <span class="oda-label__text">{{ __('public.adm_cert_show_lbl_level') }}</span>
                        <select name="certification_level" class="od-select" required>
                            <option value="bronze">@enum('bronze', 'level')</option>
                            <option value="silver">@enum('silver', 'level')</option>
                            <option value="gold">@enum('gold', 'level')</option>
                            <option value="platinum">@enum('platinum', 'level')</option>
                        </select>
                    </label>
                    <label class="oda-label">
                        <span class="oda-label__text">{{ __('public.adm_cert_show_lbl_valid_months') }}</span>
                        <input type="number" name="expires_months" value="12" min="1" max="36" class="od-input">
                    </label>
                    <div class="oda-form__actions">
                        <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_cert_show_btn_issue') }}</button>
                    </div>
                </form>
            </div>
        </section>
        @endif
    </div>

    {{-- Right column: test runs + record new run --}}
    <div class="od-stack">
        <section class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="clipboard-check"></i> {{ __('public.adm_cert_show_panel_record_run') }}</h2></div>
            <div class="od-card__body">
                @if($requirements->isEmpty())
                <div class="od-empty">
                    <span class="od-empty__icon"><i data-lucide="list-checks"></i></span>
                    <p class="od-empty__text">
                        {{ __('public.adm_cert_show_no_requirements') }}
                        <a href="{{ route('portals.admin.certifications.seed') }}" data-submit-closest>{{ __('public.adm_cert_show_seed_link') }}</a> {{ __('public.adm_cert_show_seed_first') }}
                    </p>
                </div>
                @else
                <form method="POST" action="{{ route('portals.admin.certifications.test_run', $certification) }}" class="od-stack">
                    @csrf
                    <label class="oda-label">
                        <span class="oda-label__text">{{ __('public.adm_cert_show_lbl_run_label') }}</span>
                        <input type="text" name="run_label" class="od-input" placeholder="{{ __('public.adm_cert_show_ph_run_label') }}">
                    </label>
                    <div class="od-table-wrap oda-gc-scroll">
                        <table class="od-table">
                            <thead><tr>
                                <th>{{ __('public.adm_cert_show_col_requirement') }}</th>
                                <th>{{ __('public.adm_cert_show_col_result') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($requirements as $i => $req)
                            <tr>
                                <td data-label="{{ __('public.adm_cert_show_col_requirement') }}">
                                    <input type="hidden" name="results[{{ $i }}][requirement_id]" value="{{ $req->id }}">
                                    <span class="od-table__name">{{ $req->name }}</span>
                                    <span class="od-pill od-pill--{{ $gcPill($req->severityBadgeClass()) }}">{{ $req->severity }}</span>
                                </td>
                                <td data-label="{{ __('public.adm_cert_show_col_result') }}">
                                    <select name="results[{{ $i }}][result]" class="od-select">
                                        <option value="passed">{{ __('public.adm_cert_show_opt_pass') }}</option>
                                        <option value="failed">{{ __('public.adm_cert_show_opt_fail') }}</option>
                                        <option value="skipped">{{ __('public.adm_cert_show_opt_skip') }}</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <label class="oda-label">
                        <span class="oda-label__text">{{ __('public.adm_cert_show_lbl_run_notes') }}</span>
                        <textarea name="run_notes" rows="2" class="oda-textarea"></textarea>
                    </label>
                    <div class="oda-form__actions">
                        <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_cert_show_btn_record') }}</button>
                    </div>
                </form>
                @endif
            </div>
        </section>

        @if($certification->testRuns->isNotEmpty())
        <section class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="history"></i> {{ __('public.adm_cert_show_panel_history') }} <span class="od-card__count">({{ $certification->testRuns->count() }})</span></h2></div>
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead><tr>
                        <th>{{ __('public.adm_cert_show_col_run') }}</th><th>{{ __('public.adm_cert_show_col_pass_rate') }}</th><th>{{ __('public.adm_cert_show_col_status') }}</th><th>{{ __('public.adm_cert_show_col_date') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($certification->testRuns->sortByDesc('started_at') as $run)
                    <tr>
                        <td data-label="{{ __('public.adm_cert_show_col_run') }}">{{ $run->run_label ?: __('public.adm_cert_show_run_default') }}</td>
                        <td data-label="{{ __('public.adm_cert_show_col_pass_rate') }}">
                            <span class="od-pill {{ $run->isPassed() ? 'od-pill--ready' : 'od-pill--urgent' }}">{{ $run->passRate() }}%</span>
                            <span class="od-table__sub">({{ $run->passed_count }}/{{ $run->total_requirements }})</span>
                        </td>
                        <td data-label="{{ __('public.adm_cert_show_col_status') }}"><span class="od-pill od-pill--{{ $gcPill($run->statusBadgeClass()) }}">@enum($run->status)</span></td>
                        <td data-label="{{ __('public.adm_cert_show_col_date') }}" class="oda-gc-muted">{{ $run->started_at?->translatedFormat('d M Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </div>

</div>

</div>
@endsection
