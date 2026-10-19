@extends('layouts.portal')
@section('title', __('security_ops.audit_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('security_ops.audit_title') }}</h1>
        <p class="od-page__sub">{{ __('security_ops.audit_subtitle') }}</p>
    </div>
</div>

{{-- Filters. The actor field takes an exact ID: this is a trail explorer, not
     a directory, and there is no name or patient lookup here by design. --}}
<section class="od-card">
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.security.audit_explorer') }}" class="oda-form oda-form--3">
            <label class="oda-label" for="f-action">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_action_type') }}</span>
                <select id="f-action" name="action_type" class="od-select">
                    <option value="">{{ __('security_ops.audit_filter_all_actions') }}</option>
                    @foreach($actionTypes as $at)
                        <option value="{{ $at }}" {{ request('action_type') === $at ? 'selected' : '' }}>{{ $at }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label" for="f-resource">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_resource_type') }}</span>
                <select id="f-resource" name="resource_type" class="od-select">
                    <option value="">{{ __('security_ops.audit_filter_all_resources') }}</option>
                    @foreach($resourceTypes as $rt)
                        <option value="{{ $rt }}" {{ request('resource_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label" for="f-actor">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_actor_id') }}</span>
                <input id="f-actor" type="text" name="actor_id" value="{{ request('actor_id') }}" class="od-input oda-mono"
                       placeholder="{{ __('security_ops.audit_filter_actor_placeholder') }}">
            </label>
            <label class="oda-label" for="f-from">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_date_from') }}</span>
                <input id="f-from" type="date" name="date_from" value="{{ request('date_from') }}" class="od-input">
            </label>
            <label class="oda-label" for="f-to">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_date_to') }}</span>
                <input id="f-to" type="date" name="date_to" value="{{ request('date_to') }}" class="od-input">
            </label>
            <div class="oda-label">
                <span class="oda-label__text">{{ __('security_ops.audit_filter_emergency_label') }}</span>
                <label class="oda-check">
                    <input type="checkbox" name="emergency_only" value="1" {{ request('emergency_only') ? 'checked' : '' }}>
                    {{ __('security_ops.audit_filter_emergency_only') }}
                </label>
            </div>
            <div class="oda-form__actions">
                <a href="{{ route('portals.admin.security.audit_explorer') }}" class="od-btn od-btn--ghost">{{ __('security_ops.btn_clear') }}</a>
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> {{ __('security_ops.btn_search') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('security_ops.audit_events_title') }} <span class="od-card__count">({{ $events->total() }} {{ __('security_ops.audit_total_suffix') }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($events->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="search-x"></i></span>
                <h3 class="od-empty__title">{{ __('security_ops.audit_empty') }}</h3>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('security_ops.audit_col_action') }}</th>
                    <th>{{ __('security_ops.audit_col_resource') }}</th>
                    <th>{{ __('security_ops.audit_col_actor') }}</th>
                    <th>{{ __('security_ops.audit_col_patient') }}</th>
                    <th>{{ __('security_ops.audit_col_ip') }}</th>
                    <th>{{ __('security_ops.audit_col_authority') }}</th>
                    <th>{{ __('security_ops.audit_col_when') }}</th>
                    <th class="od-table__actions"></th>
                </tr></thead>
                <tbody>
                    @foreach($events as $ev)
                    <tr>
                        <td data-label="{{ __('security_ops.audit_col_action') }}"><span class="od-table__name oda-mono">{{ $ev->action_type }}</span></td>
                        <td data-label="{{ __('security_ops.audit_col_resource') }}">
                            @if($ev->resource_type)
                                <span class="od-pill od-pill--neutral">{{ $ev->resource_type }}</span>
                                @if($ev->resource_id)
                                    <span class="od-table__sub oda-mono">{{ substr($ev->resource_id, 0, 8) }}…</span>
                                @endif
                            @else
                                <span class="oda-ga-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_actor') }}">
                            <span class="oda-mono">{{ $ev->actor_id ? substr($ev->actor_id, 0, 8).'…' : '—' }}</span>
                            @if($ev->actor_role)
                                <span class="od-table__sub">{{ $ev->actor_role }}</span>
                            @endif
                        </td>
                        {{-- Masked: this desk never renders a patient name, and an
                             audit row is not a reason to resolve one. --}}
                        <td data-label="{{ __('security_ops.audit_col_patient') }}" class="oda-ga-muted">
                            <span class="oda-mono">{{ $ev->patient_id ? substr($ev->patient_id, 0, 8).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_ip') }}" class="oda-ga-muted">{{ $ev->ip_address ?? '—' }}</td>
                        <td data-label="{{ __('security_ops.audit_col_authority') }}">
                            @if($ev->emergency_override)
                                <span class="od-pill od-pill--urgent">{{ __('security_ops.audit_badge_emergency') }}</span>
                            @else
                                <span class="od-pill od-pill--neutral">{{ __('security_ops.audit_badge_consented') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_when') }}" class="oda-ga-muted">
                            {{ $ev->created_at?->translatedFormat('M d, Y H:i') }}
                        </td>
                        <td class="od-table__actions">
                            @if($ev->before_state || $ev->after_state || $ev->reason)
                                <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm" aria-label="{{ __('security_ops.audit_view_details') }}"
                                    data-call="showDetail" data-args="{{ json_encode([$ev->before_state, $ev->after_state, $ev->action_type, $ev->reason ?? '']) }}">
                                    <i data-lucide="eye"></i>
                                </button>
                            @endif
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

{{-- Detail modal --}}
<div id="detail-modal" class="modal-fixed">
    <div class="od-card oda-modal oda-ga-modal--wide">
        <div class="od-card__head">
            <h3 id="detail-title" class="od-card__title"></h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('security_ops.btn_close') }}" data-call="closeDetail"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body od-stack">
            <p id="detail-reason" class="oda-help"></p>
            <div class="oda-ga-diff">
                <div>
                    <p class="oda-ga-diff__label">{{ __('security_ops.audit_diff_before') }}</p>
                    <pre id="detail-before" class="oda-pre"></pre>
                </div>
                <div>
                    <p class="oda-ga-diff__label">{{ __('security_ops.audit_diff_after') }}</p>
                    <pre id="detail-after" class="oda-pre"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
var AUDIT_REASON_LABEL = {{ Js::from(__('security_ops.audit_reason_label')) }};
function showDetail(before, after, action, reason) {
    document.getElementById('detail-title').textContent = action;
    document.getElementById('detail-reason').textContent = reason ? AUDIT_REASON_LABEL + ': ' + reason : '';
    document.getElementById('detail-before').textContent = before ? JSON.stringify(before, null, 2) : '—';
    document.getElementById('detail-after').textContent = after ? JSON.stringify(after, null, 2) : '—';
    document.getElementById('detail-modal').style.display = 'flex';
}
function closeDetail() { document.getElementById('detail-modal').style.display = 'none'; }
document.getElementById('detail-modal').addEventListener('click', function (e) { if (e.target === this) closeDetail(); });
</script>
@endsection
