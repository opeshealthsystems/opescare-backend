@extends('layouts.portal')
@section('title', __('security_ops.emerg_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.emerg_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.emerg_title') }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.emerg_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Filters. There is deliberately NO patient field here: this desk shows a
     patient only inside a specific override, never as something to search. --}}
<form method="GET" action="{{ route('portals.admin.security.emergency_access') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="provider_id" value="{{ request('provider_id') }}"
               placeholder="{{ __('security_ops.emerg_filter_provider') }}"
               aria-label="{{ __('security_ops.emerg_filter_provider') }}">
    </label>
    <select name="review" class="filter-select" aria-label="{{ __('security_ops.emerg_filter_review') }}">
        <option value="">{{ __('security_ops.emerg_filter_all_reviews') }}</option>
        <option value="unreviewed" {{ request('review') === 'unreviewed' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_unreviewed') }}</option>
        <option value="reviewed" {{ request('review') === 'reviewed' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_reviewed') }}</option>
    </select>
    <select name="grant" class="filter-select" aria-label="{{ __('security_ops.emerg_filter_grant') }}">
        <option value="">{{ __('security_ops.emerg_filter_all_grants') }}</option>
        <option value="live" {{ request('grant') === 'live' ? 'selected' : '' }}>{{ __('security_ops.emerg_filter_live') }}</option>
    </select>
    <label class="form-check">
        <input type="checkbox" name="repeat_only" value="1" {{ request('repeat_only') ? 'checked' : '' }}>
        {{ __('security_ops.emerg_filter_repeat_only') }}
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('security_ops.btn_filter') }}</button>
    <a href="{{ route('portals.admin.security.emergency_access') }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('security_ops.emerg_empty_heading') }}</h3>
                <p>{{ __('security_ops.emerg_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.emerg_col_review') }}</th>
                    <th>{{ __('security_ops.emerg_col_patient') }}</th>
                    <th>{{ __('security_ops.emerg_col_provider') }}</th>
                    <th>{{ __('security_ops.emerg_col_facility') }}</th>
                    <th>{{ __('security_ops.emerg_col_reason') }}</th>
                    <th>{{ __('security_ops.emerg_col_records') }}</th>
                    <th>{{ __('security_ops.emerg_col_grant') }}</th>
                    <th>{{ __('security_ops.emerg_col_when') }}</th>
                    <th>{{ __('security_ops.emerg_col_actions') }}</th>
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
                                <span class="badge badge-danger badge-sm"><i data-lucide="siren"></i> {{ __('security_ops.emerg_review_unreviewed') }}</span>
                            @elseif($review->status === 'escalated')
                                <span class="badge badge-warning badge-sm">{{ __('security_ops.emerg_review_escalated') }}</span>
                            @else
                                <span class="badge badge-success badge-sm">{{ __('security_ops.emerg_review_approved') }}</span>
                            @endif
                            @if($decided && $review->reviewed_at)
                                <div class="code-muted">{{ __('security_ops.emerg_review_by', ['when' => $review->reviewed_at->diffForHumans()]) }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_patient') }}" class="td-strong">
                            {{ $ev->patient?->health_id ?? ($ev->patient_id ? substr($ev->patient_id, 0, 12).'…' : '—') }}
                            @if($ev->is_demo)
                                <span class="badge badge-neutral badge-sm">{{ __('security_ops.badge_demo') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_provider') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->provider_id ? substr($ev->provider_id, 0, 12).'…' : '—' }}</span>
                            @if($ev->is_repeat)
                                <span class="badge badge-warning badge-sm">{{ __('security_ops.emerg_repeat') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_facility') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->facility_id ? substr($ev->facility_id, 0, 12).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_reason') }}">{{ Str::limit($ev->reason ?? '—', 60) }}</td>
                        <td data-label="{{ __('security_ops.emerg_col_records') }}">
                            @if($ev->records_viewed)
                                <span class="badge badge-warning badge-sm">
                                    {{ is_array($ev->records_viewed) ? count($ev->records_viewed) : '?' }} {{ __('security_ops.emerg_records_suffix') }}
                                </span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_grant') }}">
                            @if($ev->revoked_at)
                                <span class="badge badge-neutral badge-sm">{{ __('security_ops.emerg_grant_revoked') }}</span>
                                <div class="code-muted">{{ __('security_ops.emerg_grant_revoked_at', ['when' => $ev->revoked_at->diffForHumans()]) }}</div>
                            @elseif($live)
                                <span class="badge badge-danger badge-sm">{{ __('security_ops.emerg_grant_live') }}</span>
                                <div class="code-muted">{{ __('security_ops.emerg_grant_expires', ['when' => $ev->expires_at->diffForHumans()]) }}</div>
                            @elseif($ev->expires_at)
                                <span class="badge badge-neutral badge-sm">{{ __('security_ops.emerg_grant_expired') }}</span>
                            @else
                                <span class="td-muted">{{ __('security_ops.emerg_grant_none') }}</span>
                            @endif
                            {{-- A NULL notified_at after a successful override means the
                                 patient alert never went out. That is a governance
                                 defect, not a detail, so it is shown on the row. --}}
                            @if($ev->notified_at)
                                <div class="code-muted">{{ __('security_ops.emerg_notified', ['when' => $ev->notified_at->diffForHumans()]) }}</div>
                            @else
                                <div><span class="badge badge-warning badge-sm">{{ __('security_ops.emerg_not_notified') }}</span></div>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.emerg_col_when') }}" class="td-muted">
                            {{ $ev->created_at?->format('M d, Y H:i') }}
                            <div class="code-muted">{{ $ev->created_at?->diffForHumans() }}</div>
                        </td>
                        <td class="row-actions">
                            <button type="button" class="btn btn-ghost btn-sm"
                                onclick="openReviewModal('{{ $ev->id }}', '{{ $review->status ?? 'pending' }}', {{ json_encode($review->reviewer_notes ?? '') }})">
                                <i data-lucide="clipboard-check"></i> {{ __('security_ops.emerg_btn_review') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $events->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Review modal --}}
<div id="review-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('security_ops.emerg_modal_title') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('security_ops.btn_close') }}" onclick="closeReviewModal()"><i data-lucide="x"></i></button>
        </div>
        <p class="text-muted text-sm mb-3">{{ __('security_ops.emerg_modal_note') }}</p>
        <form id="review-form" method="POST" action="">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label" for="rev-status">{{ __('security_ops.emerg_form_status') }}</label>
                <select id="rev-status" name="status" class="form-control" required>
                    @foreach($reviewStatuses as $st)
                        <option value="{{ $st }}">{{ __('security_ops.emerg_review_'.$st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="rev-notes">{{ __('security_ops.emerg_form_notes') }}</label>
                <textarea id="rev-notes" name="notes" class="form-control" rows="4" maxlength="2000"
                          placeholder="{{ __('security_ops.emerg_form_notes_placeholder') }}"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeReviewModal()">{{ __('security_ops.btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('security_ops.emerg_btn_save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
