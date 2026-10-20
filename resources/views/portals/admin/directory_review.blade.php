@extends('layouts.portal')
@section('title', __('caremap_claim.review_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('caremap_claim.review_title') }}</h1>
        <p class="od-page__sub">{{ __('caremap_claim.review_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<nav class="od-tabs">
    <a href="{{ route('admin.care-map.review', ['tab' => 'claims']) }}" class="od-tab {{ $tab === 'claims' ? 'is-active' : '' }}">
        <i data-lucide="badge-check"></i>
        <span>{{ __('caremap_claim.tab_claims') }}</span>
        <span class="od-tab__count">{{ $pendingClaimCount }}</span>
    </a>
    <a href="{{ route('admin.care-map.review', ['tab' => 'imports']) }}" class="od-tab {{ $tab === 'imports' ? 'is-active' : '' }}">
        <i data-lucide="git-pull-request-arrow"></i>
        <span>{{ __('caremap_claim.tab_imports') }}</span>
        <span class="od-tab__count">{{ $pendingImportCount }}</span>
    </a>
    <a href="{{ route('admin.care-map.review', ['tab' => 'unlinked']) }}" class="od-tab {{ $tab === 'unlinked' ? 'is-active' : '' }}">
        <i data-lucide="unlink"></i>
        <span>{{ __('caremap_claim.tab_unlinked') }}</span>
        <span class="od-tab__count">{{ $unlinkedListings->count() }}</span>
    </a>
</nav>

@if($tab === 'claims')

    {{-- ══ Ownership claims ══════════════════════════════════════════════ --}}
    <div class="oda-gd-flash oda-gd-flash--warn">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('caremap_claim.approve_warning') }}</div>
    </div>

    @if(session('claim_activation_url'))
        <div class="oda-gd-flash">
            <i data-lucide="link"></i>
            <div>
                {{ __('caremap_claim.activation_link_admin') }}
                <input type="text" readonly class="od-input" value="{{ session('claim_activation_url') }}" aria-label="{{ __('caremap_claim.activation_link_admin') }}">
            </div>
        </div>
    @endif

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="clock"></i> {{ __('caremap_claim.tab_claims') }}</h2>
            <span class="od-card__count">{{ $pendingClaims->total() }}</span>
        </div>
    @if($pendingClaims->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="check-circle"></i></span>
                <p class="od-empty__text">{{ __('caremap_claim.empty_claims_queue') }}</p>
            </div>
        </div>
    @else
        <div class="od-card__body od-card__body--flush">
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.col_facility') }}</th>
                            <th>{{ __('caremap_claim.claim_col_claimant') }}</th>
                            <th>{{ __('caremap_claim.claim_col_contact') }}</th>
                            <th>{{ __('caremap_claim.claim_col_proof') }}</th>
                            <th>{{ __('caremap_claim.col_submitted') }}</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($pendingClaims as $claim)
                        @php $listing = $claim->careFacility; $facName = $listing?->facility_name ?? $claim->facility?->name ?? '—'; @endphp
                        <tr>
                            <td data-label="{{ __('caremap_claim.col_facility') }}">
                                <div class="od-table__person">
                                    <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($listing?->id ?? $facName) }}">{{ $gdInit($facName) }}</span>
                                    <div>
                                        <div class="od-table__name">{{ $facName }}</div>
                                        @if($listing)
                                            <div class="od-table__sub">{{ $listing->city }}{{ $listing->region ? ', ' . $listing->region : '' }}</div>
                                            <a href="{{ route('public.care-map.profile', $listing->id) }}" class="od-table__sub" target="_blank" rel="noopener">
                                                {{ __('caremap_claim.view_listing') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_claimant') }}">
                                <span class="od-table__name">{{ $claim->claimant_name ?? $claim->claimant?->name ?? '—' }}</span>
                                <div class="od-table__sub">{{ $claim->claimant_role ?? '—' }}</div>
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_contact') }}">
                                <div class="od-table__sub">{{ $claim->claimant_email ?? $claim->claimant?->email ?? '—' }}</div>
                                @if($claim->claimant_phone)<div class="od-table__sub">{{ $claim->claimant_phone }}</div>@endif
                                @if($claim->claim_reason)<div class="od-table__sub">{{ Str::limit($claim->claim_reason, 120) }}</div>@endif
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_proof') }}">
                                <div class="od-table__sub">{{ __('caremap_claim.status_' . $claim->claim_status->value) }}</div>
                                <div class="od-table__sub">
                                    {{ __('caremap_claim.proof_phone') }}:
                                    @if($claim->isPhoneVerified())
                                        <strong>{{ __('caremap_claim.proof_yes') }}</strong>
                                    @else
                                        <strong>{{ __('caremap_claim.proof_no') }}</strong>
                                        @if($claim->sms_status) ({{ __('caremap_claim.sms_status_' . $claim->sms_status) }}) @endif
                                    @endif
                                </div>
                                @if($listing?->dialablePhone())<div class="od-table__sub">{{ __('caremap_claim.proof_listing_phone') }}: {{ $listing->dialablePhone() }}</div>@endif
                                @if($claim->hasDocument())
                                    <a class="od-table__sub" target="_blank" rel="noopener"
                                       href="{{ URL::temporarySignedRoute('admin.care-map.review.claims.document', now()->addMinutes(\App\Modules\CareMap\Services\FacilityClaimProofService::DOCUMENT_URL_TTL_MINUTES), ['claim' => $claim->id]) }}">
                                        <i data-lucide="file-text"></i> {{ __('caremap_claim.proof_document') }}
                                    </a>
                                @else
                                    <div class="od-table__sub">{{ __('caremap_claim.proof_no_document') }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('caremap_claim.col_submitted') }}">
                                <span class="od-table__sub">{{ optional($claim->submitted_at ?? $claim->created_at)->format('Y-m-d') ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="od-table__actions">
                                    <form method="POST" action="{{ route('admin.care-map.review.claims.reject', $claim->id) }}" class="oda-inline-form">
                                        @csrf
                                        <input type="text" name="review_notes" class="od-input oda-gd-note" placeholder="{{ __('caremap_claim.notes_placeholder') }}" maxlength="1000" aria-label="{{ __('caremap_claim.notes_placeholder') }}">
                                        <button type="submit" class="od-btn od-btn--secondary od-btn--sm">
                                            <i data-lucide="x"></i> {{ __('caremap_claim.btn_reject') }}
                                        </button>
                                    </form>
                                    @if($claim->claim_status->isReviewable() || ! $claim->care_facility_id)
                                    <form method="POST" action="{{ route('admin.care-map.review.claims.approve', $claim->id) }}" class="oda-inline-form">
                                        @csrf
                                        <button type="submit" class="od-btn od-btn--row">
                                            <i data-lucide="check"></i> {{ __('caremap_claim.btn_approve') }}
                                        </button>
                                    </form>
                                    @else
                                        <span class="od-table__sub">{{ __('caremap_claim.approve_unavailable') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="od-card__foot">{{ $pendingClaims->links() }}</div>
    @endif
    </div>

    @if($decidedClaims->isNotEmpty())
        <div class="od-card">
            <div class="od-card__head">
                <h2 class="od-card__title"><i data-lucide="history"></i> {{ __('caremap_claim.recent_decisions') }}</h2>
                <span class="od-card__count">{{ $decidedClaims->count() }}</span>
            </div>
            <div class="od-card__body od-card__body--flush">
                <div class="od-table-wrap">
                    <table class="od-table">
                        <thead>
                            <tr>
                                <th>{{ __('caremap_claim.col_facility') }}</th>
                                <th>{{ __('caremap_claim.claim_col_claimant') }}</th>
                                <th>{{ __('caremap_claim.col_status') }}</th>
                                <th>{{ __('caremap_claim.col_reviewed') }}</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($decidedClaims as $claim)
                            @php
                                $badge = match($claim->claim_status) {
                                    \App\Enums\FacilityClaimStatus::Approved => 'od-pill--ready',
                                    \App\Enums\FacilityClaimStatus::Rejected => 'od-pill--urgent',
                                    default                                  => 'od-pill--neutral',
                                };
                            @endphp
                            <tr>
                                <td data-label="{{ __('caremap_claim.col_facility') }}">
                                    <span class="od-table__name">{{ $claim->careFacility?->facility_name ?? '—' }}</span>
                                </td>
                                <td data-label="{{ __('caremap_claim.claim_col_claimant') }}">
                                    <span class="od-table__sub">{{ $claim->claimant_name ?? $claim->claimant?->name ?? '—' }}</span>
                                </td>
                                <td data-label="{{ __('caremap_claim.col_status') }}">
                                    <span class="od-pill {{ $badge }}">{{ __('caremap_claim.status_' . $claim->claim_status->value) }}</span>
                                </td>
                                <td data-label="{{ __('caremap_claim.col_reviewed') }}">
                                    <span class="od-table__sub">{{ optional($claim->reviewed_at)->format('Y-m-d') ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($claim->claim_status === \App\Enums\FacilityClaimStatus::Approved)
                                        <form method="POST" action="{{ route('admin.care-map.review.claims.revoke', $claim->id) }}" data-confirm="{{ __('admin_desk.confirm_revoke_claim') }}" class="oda-inline-form">
                                            @csrf
                                            <button type="submit" class="od-btn od-btn--ghost od-btn--sm">
                                                <i data-lucide="undo-2"></i> {{ __('caremap_claim.btn_revoke') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

@elseif($tab === 'unlinked')

    {{-- ══ Unlinked listings ═════════════════════════════════════════════ --}}
    {{--
        `care_facilities.facility_id` is the only join between the public
        directory and the operational facilities a clinician holds a posting
        at. A listing without it is invisible to every "has doctors",
        "bookable" and "connected" question the app asks — the facility is in
        the directory and still cannot be found. Approving a claim now fills
        it in, but a listing that predates the claim flow has no claim to
        approve, and this is the only place that join can be made by hand.
    --}}
    <div class="oda-gd-flash oda-gd-flash--warn">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('caremap_claim.link_warning') }}</div>
    </div>

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="unlink"></i> {{ __('caremap_claim.tab_unlinked') }}</h2>
            <span class="od-card__count">{{ $unlinkedListings->count() }}</span>
        </div>
    @if($unlinkedListings->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="check-circle"></i></span>
                <p class="od-empty__text">{{ __('caremap_claim.link_none') }}</p>
            </div>
        </div>
    @else
        <div class="od-card__body od-card__body--flush">
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.link_col_listing') }}</th>
                            <th>{{ __('caremap_claim.link_col_facility') }}</th>
                            <th>{{ __('caremap_claim.link_col_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unlinkedListings as $row)
                            <tr>
                                <td data-label="{{ __('caremap_claim.link_col_listing') }}">
                                    <div class="od-table__person">
                                        <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($row->listing_id) }}">{{ $gdInit($row->listing_name) }}</span>
                                        <div>
                                            <div class="od-table__name">{{ $row->listing_name }}</div>
                                            <div class="od-table__sub">{{ $row->listing_city }} · {{ $row->listing_type }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="{{ __('caremap_claim.link_col_facility') }}">
                                    <div class="od-table__name">{{ $row->facility_name }}</div>
                                    <div class="od-table__sub">{{ \App\Support\FacilityTypeLabel::label($row->facility_type_tenant) }}</div>
                                </td>
                                <td data-label="{{ __('caremap_claim.link_col_action') }}">
                                    <form method="POST" action="{{ route('admin.care-map.review.listings.link', $row->listing_id) }}" class="oda-inline-form">
                                        @csrf
                                        <input type="hidden" name="facility_id" value="{{ $row->facility_id }}">
                                        <button type="submit" class="od-btn od-btn--row">
                                            <i data-lucide="link"></i> {{ __('caremap_claim.link_action') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    </div>

@else

    {{-- ══ Import candidates ═════════════════════════════════════════════ --}}

    {{-- ODbL: attribution travels with the data, wherever the data is shown. --}}
    @if($osmAttribution)
        <p class="oda-help oda-gd-attrib">
            <i data-lucide="scale"></i>
            {{ __('facility_review.attribution', ['attribution' => $osmAttribution]) }}
            — {{ __('facility_review.attribution_note') }}
        </p>
    @endif

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="import"></i> {{ __('caremap_claim.tab_imports') }}</h2>
            <span class="od-card__count">{{ $importReviews->total() }}</span>
        </div>
        <div class="od-card__body od-stack">
            {{-- Status: pending by default, but a deferred row must stay reachable. --}}
            <nav class="od-tabs">
                @foreach(\App\Enums\FacilityImportReviewStatus::cases() as $case)
                    <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $case->value]) }}"
                       class="od-tab {{ $activeStatus === $case ? 'is-active' : '' }}">
                        {{ __($case->translationKey()) }} <span class="od-tab__count">{{ $statusCounts[$case->value] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>

            <nav class="od-tabs">
                <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value]) }}"
                   class="od-tab {{ $activeReason ? '' : 'is-active' }}">{{ __('caremap_claim.filter_all_reasons') }}</a>
                @foreach($reasonCounts as $reason => $total)
                    <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value, 'reason' => $reason]) }}"
                       class="od-tab {{ $activeReason === $reason ? 'is-active' : '' }}">
                        {{ __('caremap_claim.reason_' . $reason) }} <span class="od-tab__count">{{ $total }}</span>
                    </a>
                @endforeach
            </nav>

    @if($importReviews->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="check-circle"></i></span>
                <p class="od-empty__text">{{ __('caremap_claim.empty_imports_queue') }}</p>
            </div>
        </div>
    </div>
    @else
                <p class="oda-help">{{ __('facility_review.never_verified') }}</p>

                @php
                    // Shown only when the value is real. 692 directory rows carry the
                    // literal string 'N/A' in phone_primary; printing that next to a
                    // candidate's phone number would read as a difference that isn't one.
                    $drCoords = static fn ($lat, $lng) => ($lat !== null && $lng !== null)
                        ? number_format((float) $lat, 5) . ', ' . number_format((float) $lng, 5)
                        : null;
                    $drReal = static fn ($v) => (filled($v) && strcasecmp((string) $v, 'N/A') !== 0) ? $v : null;
                @endphp

                @foreach($importReviews as $review)
                    @php
                        $name    = $review->displayName();
                        $listing = $review->matchedFacility;
                        $cluster = $review->matched_facility_id ? ($clusterCounts[$review->matched_facility_id] ?? 1) : 1;

                        $candidateFields = [
                            'field_name'   => $name !== '' ? $name : null,
                            'field_type'   => $review->candidate_type,
                            'field_city'   => $review->candidate_city,
                            'field_region' => $review->candidate_region,
                            'field_coords' => $drCoords($review->latitude, $review->longitude),
                            'field_phone'  => $drReal($review->payload['phone'] ?? $review->payload['contact:phone'] ?? null),
                            'field_source' => $review->source_system . ' / ' . $review->source_ref,
                        ];

                        $existingFields = $listing ? [
                            'field_name'   => $listing->facility_name,
                            'field_type'   => $listing->facility_type,
                            'field_city'   => $listing->city,
                            'field_region' => $listing->region,
                            'field_coords' => $drCoords($listing->latitude, $listing->longitude),
                            'field_phone'  => $drReal($listing->phone_primary),
                            'field_status' => $listing->listing_status,
                        ] : [];
                    @endphp

                    <div class="oda-gd-review">
                        <div class="oda-gd-review__head">
                            <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($review->id) }}">{{ $gdInit($name) }}</span>
                            <div class="oda-gd-review__title">
                                <div class="od-table__name">{{ $name !== '' ? $name : __('facility_review.value_missing') }}</div>
                                <div class="od-table__sub">
                                    {{ __('caremap_claim.reason_' . $review->reason) }}
                                    @if($review->match_score !== null)
                                        &bull; {{ __('facility_review.match_score', ['score' => number_format($review->match_score, 2)]) }}
                                    @endif
                                    @if($review->match_distance_m !== null)
                                        &bull; {{ __('facility_review.match_distance', ['metres' => $review->match_distance_m]) }}
                                    @endif
                                </div>
                            </div>
                            <span class="od-pill od-pill--neutral">{{ __($review->status->translationKey()) }}</span>
                        </div>

                        @if($cluster > 1)
                            <div class="oda-gd-flash oda-gd-flash--warn oda-gd-review__flash">
                                <i data-lucide="copy"></i>
                                <div>{{ __('facility_review.cluster_warning', ['count' => $cluster]) }}</div>
                            </div>
                        @endif

                        {{-- A duplicate cannot be judged from one record. Both, or neither. --}}
                        <div class="oda-gd-compare">
                            <div class="oda-gd-compare__col">
                                <div class="oda-gd-compare__head oda-gd-compare__head--candidate">{{ __('facility_review.compare_candidate') }}</div>
                                <dl class="oda-dl">
                                    @foreach($candidateFields as $key => $value)
                                        <dt>{{ __('facility_review.' . $key) }}</dt>
                                        <dd class="{{ filled($value) ? '' : 'oda-gd-missing' }}">{{ filled($value) ? $value : __('facility_review.value_missing') }}</dd>
                                    @endforeach
                                </dl>
                                @if($name === '')
                                    {{-- 136 of the queue are unnamed OSM elements. The Add button is
                                         disabled for them; say why, and point at where the name usually is. --}}
                                    <p class="oda-help"><i data-lucide="alert-triangle"></i> {{ __('caremap_claim.no_name_warning') }}</p>
                                @endif
                                @if(!empty($review->payload))
                                    <details class="oda-gd-details">
                                        <summary class="oda-help">{{ __('caremap_claim.raw_tags') }}</summary>
                                        <div class="oda-pre">{{ json_encode($review->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                                    </details>
                                @endif
                            </div>

                            <div class="oda-gd-compare__col">
                                <div class="oda-gd-compare__head">{{ __('facility_review.compare_existing') }}</div>
                                @if($listing)
                                    <dl class="oda-dl">
                                        @foreach($existingFields as $key => $value)
                                            <dt>{{ __('facility_review.' . $key) }}</dt>
                                            <dd class="{{ filled($value) ? '' : 'oda-gd-missing' }}">{{ filled($value) ? $value : __('facility_review.value_missing') }}</dd>
                                        @endforeach
                                    </dl>
                                    <a href="{{ route('public.care-map.profile', $listing->id) }}" class="od-card__link" target="_blank" rel="noopener">
                                        <i data-lucide="external-link"></i> {{ __('caremap_claim.view_listing') }}
                                    </a>
                                @else
                                    <p class="oda-help">{{ __('facility_review.no_match') }}</p>
                                @endif
                            </div>
                        </div>

                        @if($review->reviewed_at)
                            <p class="oda-help oda-gd-review__meta">
                                {{ __('facility_review.' . ($review->status === \App\Enums\FacilityImportReviewStatus::Deferred ? 'deferred_meta' : 'decided_meta'), [
                                    'name' => $review->reviewer?->name ?? '—',
                                    'date' => $review->reviewed_at->format('Y-m-d'),
                                ]) }}
                                @if($review->review_notes) &bull; {{ $review->review_notes }} @endif
                            </p>
                        @endif

                        @if($review->status->isOpen())
                            <div class="oda-gd-review__actions">
                                <form method="POST" action="{{ route('admin.care-map.review.imports.accept', $review->id) }}" class="oda-inline-form">
                                    @csrf
                                    <button type="submit" class="od-btn od-btn--primary od-btn--sm" @disabled($name === '')>
                                        <i data-lucide="plus"></i> {{ __('caremap_claim.btn_accept') }}
                                    </button>
                                </form>
                                @if($review->matched_facility_id)
                                    <form method="POST" action="{{ route('admin.care-map.review.imports.merge', $review->id) }}" data-confirm="{{ __('admin_desk.confirm_merge_import') }}" class="oda-inline-form">
                                        @csrf
                                        <button type="submit" class="od-btn od-btn--secondary od-btn--sm">
                                            <i data-lucide="merge"></i> {{ __('caremap_claim.btn_merge') }}
                                        </button>
                                    </form>
                                @endif
                                @if($review->status->isPending())
                                    <form method="POST" action="{{ route('admin.care-map.review.imports.defer', $review->id) }}" class="oda-inline-form">
                                        @csrf
                                        <input type="text" name="review_notes" class="od-input oda-gd-note" placeholder="{{ __('facility_review.defer_reason') }}" maxlength="1000" aria-label="{{ __('facility_review.defer_reason') }}">
                                        <button type="submit" class="od-btn od-btn--ghost od-btn--sm" title="{{ __('facility_review.defer_hint') }}">
                                            <i data-lucide="clock"></i> {{ __('facility_review.btn_defer') }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.care-map.review.imports.reject', $review->id) }}" class="oda-inline-form">
                                    @csrf
                                    <input type="text" name="review_notes" class="od-input oda-gd-note" placeholder="{{ __('caremap_claim.notes_placeholder') }}" maxlength="1000" aria-label="{{ __('caremap_claim.notes_placeholder') }}">
                                    <button type="submit" class="od-btn od-btn--ghost od-btn--sm">
                                        <i data-lucide="x"></i> {{ __('caremap_claim.btn_reject') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
        </div>
        <div class="od-card__foot">{{ $importReviews->links() }}</div>
    </div>
    @endif

@endif

</div>
@endsection
