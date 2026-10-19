@extends('layouts.portal')
@section('title', __('security_ops.inc_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.inc_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.inc_subtitle') }}</p>
    </div>
    <div class="od-row">
        <button type="button" class="od-btn od-btn--primary" data-show="create-modal" data-display="flex">
            <i data-lucide="plus-circle"></i> {{ __('security_ops.inc_btn_log') }}
        </button>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.inc_title') }} <span class="od-card__count">({{ $incidents->total() }})</span></h2>
    </div>
    <div class="od-card__body">
    <form method="GET" action="{{ route('portals.admin.security.incidents') }}" class="oda-toolbar">
        <select name="severity" class="od-select" aria-label="{{ __('security_ops.inc_filter_severity') }}">
            <option value="">{{ __('security_ops.inc_filter_all_severities') }}</option>
            @foreach(['critical', 'high', 'medium', 'low'] as $sev)
                <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>@enum($sev, 'severity')</option>
            @endforeach
        </select>
        <select name="status" class="od-select" aria-label="{{ __('security_ops.inc_filter_status') }}">
            <option value="">{{ __('security_ops.inc_filter_all_statuses') }}</option>
            @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>@enum($st)</option>
            @endforeach
        </select>
        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
        <a href="{{ route('portals.admin.security.incidents') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('security_ops.btn_clear') }}</a>
    </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($incidents->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.inc_empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('security_ops.inc_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.inc_col_type') }}</th>
                    <th>{{ __('security_ops.inc_col_severity') }}</th>
                    <th>{{ __('security_ops.inc_col_status') }}</th>
                    <th>{{ __('security_ops.inc_col_summary') }}</th>
                    <th>{{ __('security_ops.inc_col_detected') }}</th>
                    <th>{{ __('security_ops.inc_col_resolved') }}</th>
                    <th class="od-table__actions">{{ __('security_ops.inc_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($incidents as $inc)
                    @php
                        $sevBadge = match($inc->severity) {
                            'critical' => 'od-pill--urgent',
                            'high' => 'od-pill--pending',
                            'medium' => 'od-pill--normal',
                            default => 'od-pill--neutral',
                        };
                        $stBadge = match($inc->status) {
                            'open' => 'od-pill--urgent',
                            'investigating' => 'od-pill--pending',
                            'contained' => 'od-pill--normal',
                            'resolved', 'closed' => 'od-pill--ready',
                            default => 'od-pill--neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('security_ops.inc_col_type') }}">
                            <a class="od-table__name" href="{{ route('portals.admin.security.incidents.show', $inc->id) }}">{{ $inc->incident_type }}</a>
                            @if($inc->is_demo)
                                <span class="od-pill od-pill--neutral">{{ __('security_ops.badge_demo') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.inc_col_severity') }}"><span class="od-pill {{ $sevBadge }}">@enum($inc->severity, 'severity')</span></td>
                        <td data-label="{{ __('security_ops.inc_col_status') }}"><span class="od-pill {{ $stBadge }}">@enum($inc->status)</span></td>
                        <td data-label="{{ __('security_ops.inc_col_summary') }}">{{ Str::limit($inc->summary, 80) }}</td>
                        <td data-label="{{ __('security_ops.inc_col_detected') }}" class="oda-ga-muted">{{ $inc->detected_at?->translatedFormat('M d, Y') }}</td>
                        <td data-label="{{ __('security_ops.inc_col_resolved') }}" class="oda-ga-muted">{{ $inc->resolved_at?->translatedFormat('M d, Y') ?? '—' }}</td>
                        <td class="od-table__actions">
                            <button type="button" class="od-btn od-btn--row od-btn--sm"
                                data-call="openUpdateModal" data-args="{{ json_encode([$inc->id, $inc->status, $inc->summary, $inc->contained_at?->format('Y-m-d') ?? '', $inc->resolved_at?->format('Y-m-d') ?? '']) }}" data-show="update-modal" data-display="flex">
                                <i data-lucide="pencil"></i> {{ __('security_ops.inc_btn_update') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @unless($incidents->isEmpty())
    <div class="od-card__foot">
        {{ $incidents->links() }}
    </div>
    @endunless
</section>
</div>

{{-- Create modal --}}
<div id="create-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('security_ops.inc_modal_create_title') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('security_ops.btn_close') }}" data-hide="create-modal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.security.incidents.store') }}" class="oda-form">
                @csrf
                <label class="oda-label" for="inc-type">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_type') }}</span>
                    <input id="inc-type" type="text" name="incident_type" class="od-input" required maxlength="100"
                           placeholder="{{ __('security_ops.inc_form_type_placeholder') }}" value="{{ old('incident_type') }}">
                </label>
                <label class="oda-label" for="inc-sev">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_severity') }}</span>
                    <select id="inc-sev" name="severity" class="od-select" required>
                        @foreach(['low', 'medium', 'high', 'critical'] as $sev)
                            <option value="{{ $sev }}" {{ old('severity') === $sev ? 'selected' : '' }}>@enum($sev, 'severity')</option>
                        @endforeach
                    </select>
                </label>
                <label class="oda-label oda-form__full" for="inc-detected">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_detected_at') }}</span>
                    <input id="inc-detected" type="datetime-local" name="detected_at" class="od-input" required
                           value="{{ old('detected_at', now()->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="oda-label oda-form__full" for="inc-summary">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_summary') }}</span>
                    <textarea id="inc-summary" name="summary" class="oda-textarea" rows="4" required maxlength="2000"
                              placeholder="{{ __('security_ops.inc_form_summary_placeholder') }}">{{ old('summary') }}</textarea>
                </label>
                @if($errors->any())<p class="oda-error oda-form__full">{{ $errors->first() }}</p>@endif
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="create-modal">{{ __('security_ops.btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('security_ops.inc_btn_log') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Update modal --}}
<div id="update-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('security_ops.inc_modal_update_title') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('security_ops.btn_close') }}" data-hide="update-modal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body">
            <form id="update-form" method="POST" action="" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full" for="upd-status">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_status') }}</span>
                    <select id="upd-status" name="status" class="od-select" required>
                        @foreach(['open', 'investigating', 'contained', 'resolved', 'closed'] as $st)
                            <option value="{{ $st }}">@enum($st)</option>
                        @endforeach
                    </select>
                </label>
                <label class="oda-label oda-form__full" for="upd-summary">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_summary') }}</span>
                    <textarea id="upd-summary" name="summary" class="oda-textarea" rows="4" maxlength="2000"></textarea>
                </label>
                <label class="oda-label" for="upd-contained">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_contained_at') }}</span>
                    <input id="upd-contained" type="date" name="contained_at" class="od-input">
                </label>
                <label class="oda-label" for="upd-resolved">
                    <span class="oda-label__text">{{ __('security_ops.inc_form_resolved_at') }}</span>
                    <input id="upd-resolved" type="date" name="resolved_at" class="od-input">
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="update-modal">{{ __('security_ops.btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('security_ops.inc_btn_save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
function openUpdateModal(id, status, summary, containedAt, resolvedAt) {
    document.getElementById('update-form').action = '{{ url('/admin/security/incidents') }}/' + id;
    document.getElementById('upd-status').value = status;
    document.getElementById('upd-summary').value = summary || '';
    document.getElementById('upd-contained').value = containedAt;
    document.getElementById('upd-resolved').value = resolvedAt;
}
@if($errors->any())
document.getElementById('create-modal').style.display = 'flex';
@endif
</script>
@include('portals.admin._modal')
@endsection
