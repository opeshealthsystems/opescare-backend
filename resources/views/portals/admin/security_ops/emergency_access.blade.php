@extends('layouts.portal')
@section('title', __('security_ops.emerg_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.emerg_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.emerg_subtitle') }}</p>
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
        <h2 class="od-card__title">{{ __('security_ops.emerg_title') }} <span class="od-card__count">({{ $events->total() }})</span></h2>
    </div>
    <div class="od-card__body">
    {{-- Filters. There is deliberately NO patient field here: this desk shows a
         patient only inside a specific override, never as something to search. --}}
    <form method="GET" action="{{ route('portals.admin.security.emergency_access') }}" class="oda-toolbar">
        <label class="od-field">
            <i data-lucide="search"></i>
            <input type="text" name="provider_id" value="{{ request('provider_id') }}" class="od-input"
                   placeholder="{{ __('security_ops.emerg_filter_provider') }}"
                   aria-label="{{ __('security_ops.emerg_filter_provider') }}">
        </label>
        <select name="review" class="od-select" aria-label="{{ __('security_ops.emerg_filter_review') }}">
            <option value="">{{ __('security_ops.emerg_filter_all_reviews') }}</option>
            <option value="unreviewed" {{ request('review') === 'unreviewed' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_unreviewed') }}</option>
            <option value="reviewed" {{ request('review') === 'reviewed' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_reviewed') }}</option>
        </select>
        <select name="grant" class="od-select" aria-label="{{ __('security_ops.emerg_filter_grant') }}">
            <option value="">{{ __('security_ops.emerg_filter_all_grants') }}</option>
            <option value="live" {{ request('grant') === 'live' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_live') }}</option>
        </select>
        <label class="oda-check">
            <input type="checkbox" name="repeat_only" value="1" {{ request('repeat_only') ? 'checked' : '' }}>
            {{ __('security_ops.emerg_filter_repeat_only') }}
        </label>
        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
        <a href="{{ route('portals.admin.security.emergency_access') }}" class="od-btn od-btn--ghost od-btn--sm">{{ __('security_ops.btn_clear') }}</a>
    </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($events->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.emerg_empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('security_ops.emerg_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.emerg_col_review') }}</th>
                    <th>{{ __('security_ops.emerg_col_patient') }}</th>
                    <th>{{ __('security_ops.emerg_col_provider') }}</th>
                    <th>{{ __('security_ops.emerg_col_facility') }}</th>
                    <th>{{ __('security_ops.emerg_col_reason') }}</th>
                    <th>{{ __('security_ops.emerg_col_records') }}</th>
                    <th>{{ __('security_ops.emerg_col_grant') }}</th>
                    <th>{{ __('security_ops.emerg_col_when') }}</th>
                    <th class="od-table__actions">{{ __('security_ops.emerg_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($events as $ev)
                    @php
                        $review = $reviews[$ev->id] ?? null;
                        $decided = $review && $review->status !== 'pending';
                        $live = $ev->expires_at && $ev->expires_at->isFuture() && ! $ev->revoked_at;
                    @endphp
                    <tr>
                        <td data-label="{{ __('security_ops.emerg_col_review') }}">
                            @if(! $decided)
                                <span class="od-pill od-pill--urgent od-pill--dot">{{ __('security_ops.emerg_review_unreviewed') }}</span>
                            @elseif($review->status === 'escalated')
                                <span class="od-pill od-pill--pending">{{ __('security_ops.emerg_review_escalated') }}</span>
                            @else
                                <span class="od-pill od-pill--ready">{{ __('security_ops.emerg_review_approved') }}</span>
                            @endif
                            @if($decided && $review->reviewed_at)
                                <span class="od-table__sub">{{ __('security_ops.emerg_review_by', ['when' => $review->reviewed_at->diffForHumans()]) }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_patient') }}">
                            <span class="od-table__name oda-mono">{{ $ev->patient?->health_id ?? ($ev->patient_id ? substr($ev->patient_id, 0, 12).'…' : '—') }}</span>
                            @if($ev->is_demo)
                                <span class="od-pill od-pill--neutral">{{ __('security_ops.badge_demo') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_provider') }}">
                            <span class="oda-mono">{{ $ev->provider_id ? substr($ev->provider_id, 0, 12).'…' : '—' }}</span>
                            @if($ev->is_repeat)
                                <span class="od-pill od-pill--pending">{{ __('security_ops.emerg_repeat') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_facility') }}" class="oda-ga-muted">
                            <span class="oda-mono">{{ $ev->facility_id ? substr($ev->facility_id, 0, 12).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_reason') }}">{{ Str::limit($ev->reason ?? '—', 60) }}</td>
                        <td data-label="{{ __('security_ops.emerg_col_records') }}">
                            @if($ev->records_viewed)
                                <span class="od-pill od-pill--pending">
                                    {{ is_array($ev->records_viewed) ? count($ev->records_viewed) : '?' }} {{ __('security_ops.emerg_records_suffix') }}
                                </span>
                            @else
                                <span class="oda-ga-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_grant') }}">
                            @if($ev->revoked_at)
                                <span class="od-pill od-pill--neutral">{{ __('security_ops.emerg_grant_revoked') }}</span>
                                <span class="od-table__sub">{{ __('security_ops.emerg_grant_revoked_at', ['when' => $ev->revoked_at->diffForHumans()]) }}</span>
                            @elseif($live)
                                <span class="od-pill od-pill--urgent">{{ __('security_ops.emerg_grant_live') }}</span>
                                <span class="od-table__sub">{{ __('security_ops.emerg_grant_expires', ['when' => $ev->expires_at->diffForHumans()]) }}</span>
                            @elseif($ev->expires_at)
                                <span class="od-pill od-pill--neutral">{{ __('security_ops.emerg_grant_expired') }}</span>
                            @else
                                <span class="oda-ga-muted">{{ __('security_ops.emerg_grant_none') }}</span>
                            @endif
                            {{-- A NULL notified_at after a successful override means the
                                 patient alert never went out. That is a governance
                                 defect, not a detail, so it is shown on the row. --}}
                            @if($ev->notified_at)
                                <span class="od-table__sub">{{ __('security_ops.emerg_notified', ['when' => $ev->notified_at->diffForHumans()]) }}</span>
                            @else
                                <div><span class="od-pill od-pill--pending">{{ __('security_ops.emerg_not_notified') }}</span></div>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_when') }}">
                            <span class="od-table__name">{{ $ev->created_at?->translatedFormat('M d, Y H:i') }}</span>
                            <span class="od-table__sub">{{ $ev->created_at?->diffForHumans() }}</span>
                        </td>
                        <td class="od-table__actions">
                            <button type="button" class="od-btn od-btn--row od-btn--sm"
                                data-call="openReviewModal" data-args="{{ json_encode([$ev->id, $review->status ?? 'pending', $review->reviewer_notes ?? '']) }}">
                                <i data-lucide="clipboard-check"></i> {{ __('security_ops.emerg_btn_review') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @unless($events->isEmpty())
    <div class="od-card__foot">
        {{ $events->links() }}
    </div>
    @endunless
</section>
</div>

{{-- Review modal --}}
<div id="review-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('security_ops.emerg_modal_title') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('security_ops.btn_close') }}" data-call="closeReviewModal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body od-stack">
            <p class="oda-help">{{ __('security_ops.emerg_modal_note') }}</p>
            <form id="review-form" method="POST" action="" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full" for="rev-status">
                    <span class="oda-label__text">{{ __('security_ops.emerg_form_status') }}</span>
                    <select id="rev-status" name="status" class="od-select" required>
                        @foreach($reviewStatuses as $st)
                            <option value="{{ $st }}">{{ __('security_ops.emerg_review_'.$st) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="oda-label oda-form__full" for="rev-notes">
                    <span class="oda-label__text">{{ __('security_ops.emerg_form_notes') }}</span>
                    <textarea id="rev-notes" name="notes" class="oda-textarea" rows="4" maxlength="2000"
                              placeholder="{{ __('security_ops.emerg_form_notes_placeholder') }}"></textarea>
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-call="closeReviewModal">{{ __('security_ops.btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('security_ops.emerg_btn_save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
function openReviewModal(id, status, notes) {
    document.getElementById('review-form').action = '{{ url('/admin/security/emergency-access') }}/' + id + '/review';
    document.getElementById('rev-status').value = status;
    document.getElementById('rev-notes').value = notes || '';
    document.getElementById('review-modal').style.display = 'flex';
}
function closeReviewModal() { document.getElementById('review-modal').style.display = 'none'; }
document.getElementById('review-modal').addEventListener('click', function (e) { if (e.target === this) closeReviewModal(); });
</script>
@endsection
