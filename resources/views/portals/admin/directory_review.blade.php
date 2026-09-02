@extends('layouts.portal')
@section('title', __('caremap_claim.review_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('caremap_claim.review_title'))

@section('head')
<style>
  .dr-tabs{display:flex;gap:.5rem;border-bottom:1px solid var(--p-border);margin-bottom:var(--p-space-5)}
  .dr-tab{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1rem;font-size:.875rem;font-weight:600;color:var(--p-text-muted);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px}
  .dr-tab.active{color:var(--p-primary);border-bottom-color:var(--p-primary)}
  .dr-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;padding:0 .35rem;height:1.35rem;border-radius:999px;background:var(--p-surface-2);font-size:.7rem;font-weight:700}
  .dr-tab.active .dr-tab-count{background:rgba(15,76,129,.12);color:var(--p-primary)}
  .dr-inline-form{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
  .dr-inline-form .form-control{min-width:9rem;padding:.3rem .55rem;font-size:.78rem}
  .dr-tags{font-family:'Courier New',monospace;font-size:.72rem;color:var(--p-text-muted);white-space:pre-wrap;word-break:break-word;max-width:34rem}
  .dr-note{font-size:.8rem;color:var(--p-text-muted);margin-bottom:var(--p-space-4)}
  .dr-reasonbar{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:var(--p-space-4)}
  .dr-reason-chip{padding:.3rem .7rem;border:1px solid var(--p-border);border-radius:999px;font-size:.75rem;font-weight:600;color:var(--p-text-2);text-decoration:none}
  .dr-reason-chip.active{background:var(--p-primary);border-color:var(--p-primary);color:#fff}
  /* Side-by-side: a duplicate cannot be judged from one record. */
  .dr-card{border:1px solid var(--p-border);border-radius:var(--p-radius,8px);margin-bottom:var(--p-space-4);overflow:hidden}
  .dr-card-head{display:flex;align-items:flex-start;gap:.75rem;flex-wrap:wrap;padding:.75rem 1rem;background:var(--p-surface-2);border-bottom:1px solid var(--p-border)}
  .dr-card-title{font-weight:700;font-size:.95rem}
  .dr-card-sub{font-size:.75rem;color:var(--p-text-muted)}
  .dr-compare{display:grid;grid-template-columns:1fr 1fr;gap:0}
  .dr-col{padding:.85rem 1rem}
  .dr-col + .dr-col{border-left:1px solid var(--p-border)}
  .dr-col-head{font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;font-weight:700;color:var(--p-text-muted);margin-bottom:.5rem}
  .dr-col--candidate .dr-col-head{color:var(--p-primary)}
  .dr-field{display:flex;gap:.5rem;font-size:.8rem;padding:.18rem 0}
  .dr-field dt{flex:0 0 6.5rem;color:var(--p-text-muted)}
  .dr-field dd{margin:0;font-weight:600;word-break:break-word}
  .dr-field dd.is-missing{font-weight:400;color:var(--p-text-muted);font-style:italic}
  .dr-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.7rem 1rem;border-top:1px solid var(--p-border)}
  .dr-attrib{font-size:.75rem;color:var(--p-text-muted);margin-bottom:var(--p-space-4)}
  @media (max-width:860px){.dr-compare{grid-template-columns:1fr}.dr-col + .dr-col{border-left:0;border-top:1px solid var(--p-border)}}
</style>
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('caremap_claim.review_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="dr-note">{{ __('caremap_claim.review_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="dr-tabs">
    <a href="{{ route('admin.care-map.review', ['tab' => 'claims']) }}" class="dr-tab {{ $tab === 'claims' ? 'active' : '' }}">
        <i data-lucide="badge-check"></i>
        <span>{{ __('caremap_claim.tab_claims') }}</span>
        <span class="dr-tab-count">{{ $pendingClaimCount }}</span>
    </a>
    <a href="{{ route('admin.care-map.review', ['tab' => 'imports']) }}" class="dr-tab {{ $tab === 'imports' ? 'active' : '' }}">
        <i data-lucide="git-pull-request-arrow"></i>
        <span>{{ __('caremap_claim.tab_imports') }}</span>
        <span class="dr-tab-count">{{ $pendingImportCount }}</span>
    </a>
</div>

@if($tab === 'claims')

    {{-- ══ Ownership claims ══════════════════════════════════════════════ --}}
    <div class="alert alert-warning mb-6">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('caremap_claim.approve_warning') }}</div>
    </div>

    @if($pendingClaims->isEmpty())
        <div class="panel">
            <div class="panel-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                    <p>{{ __('caremap_claim.empty_claims_queue') }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="clock"></i> {{ $pendingClaims->total() }}</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.col_facility') }}</th>
                            <th>{{ __('caremap_claim.claim_col_claimant') }}</th>
                            <th>{{ __('caremap_claim.claim_col_contact') }}</th>
                            <th>{{ __('caremap_claim.col_submitted') }}</th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($pendingClaims as $claim)
                        @php $listing = $claim->careFacility; @endphp
                        <tr>
                            <td data-label="{{ __('caremap_claim.col_facility') }}">
                                <span class="td-strong">{{ $listing?->facility_name ?? $claim->facility?->name ?? '—' }}</span>
                                @if($listing)
                                    <div class="td-muted">{{ $listing->city }}{{ $listing->region ? ', ' . $listing->region : '' }}</div>
                                    <a href="{{ route('public.care-map.profile', $listing->id) }}" class="td-muted" target="_blank" rel="noopener">
                                        {{ __('caremap_claim.view_listing') }}
                                    </a>
                                @endif
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_claimant') }}">
                                <span class="td-strong">{{ $claim->claimant_name ?? $claim->claimant?->name ?? '—' }}</span>
                                <div class="td-muted">{{ $claim->claimant_role ?? '—' }}</div>
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_contact') }}" class="td-muted">
                                {{ $claim->claimant_email ?? $claim->claimant?->email ?? '—' }}
                                @if($claim->claimant_phone)<div>{{ $claim->claimant_phone }}</div>@endif
                                @if($claim->claim_reason)<div>{{ Str::limit($claim->claim_reason, 120) }}</div>@endif
                            </td>
                            <td data-label="{{ __('caremap_claim.col_submitted') }}" class="td-muted">
                                {{ optional($claim->submitted_at ?? $claim->created_at)->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="row-actions">
                                <form method="POST" action="{{ route('admin.care-map.review.claims.reject', $claim->id) }}" class="dr-inline-form">
                                    @csrf
                                    <input type="text" name="review_notes" class="form-control" placeholder="{{ __('caremap_claim.notes_placeholder') }}" maxlength="1000">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i data-lucide="x"></i> {{ __('caremap_claim.btn_reject') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.care-map.review.claims.approve', $claim->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i data-lucide="check"></i> {{ __('caremap_claim.btn_approve') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mb-6">{{ $pendingClaims->links() }}</div>
    @endif

    @if($decidedClaims->isNotEmpty())
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="history"></i> {{ __('caremap_claim.recent_decisions') }}</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('caremap_claim.col_facility') }}</th>
                            <th>{{ __('caremap_claim.claim_col_claimant') }}</th>
                            <th>{{ __('caremap_claim.col_status') }}</th>
                            <th>{{ __('caremap_claim.col_reviewed') }}</th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($decidedClaims as $claim)
                        @php
                            $badge = match($claim->claim_status) {
                                \App\Enums\FacilityClaimStatus::Approved => 'badge-success',
                                \App\Enums\FacilityClaimStatus::Rejected => 'badge-danger',
                                default                                  => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('caremap_claim.col_facility') }}">
                                <span class="td-strong">{{ $claim->careFacility?->facility_name ?? '—' }}</span>
                            </td>
                            <td data-label="{{ __('caremap_claim.claim_col_claimant') }}" class="td-muted">
                                {{ $claim->claimant_name ?? $claim->claimant?->name ?? '—' }}
                            </td>
                            <td data-label="{{ __('caremap_claim.col_status') }}">
                                <span class="badge {{ $badge }}">{{ __('caremap_claim.status_' . $claim->claim_status->value) }}</span>
                            </td>
                            <td data-label="{{ __('caremap_claim.col_reviewed') }}" class="td-muted">
                                {{ optional($claim->reviewed_at)->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="row-actions">
                                @if($claim->claim_status === \App\Enums\FacilityClaimStatus::Approved)
                                    <form method="POST" action="{{ route('admin.care-map.review.claims.revoke', $claim->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm">
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
    @endif

@else

    {{-- ══ Import candidates ═════════════════════════════════════════════ --}}

    {{-- ODbL: attribution travels with the data, wherever the data is shown. --}}
    @if($osmAttribution)
        <p class="dr-attrib">
            <i data-lucide="scale"></i>
            {{ __('facility_review.attribution', ['attribution' => $osmAttribution]) }}
            — {{ __('facility_review.attribution_note') }}
        </p>
    @endif

    {{-- Status: pending by default, but a deferred row must stay reachable. --}}
    <div class="dr-reasonbar">
        @foreach(\App\Enums\FacilityImportReviewStatus::cases() as $case)
            <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $case->value]) }}"
               class="dr-reason-chip {{ $activeStatus === $case ? 'active' : '' }}">
                {{ __($case->translationKey()) }} ({{ $statusCounts[$case->value] ?? 0 }})
            </a>
        @endforeach
    </div>

    <div class="dr-reasonbar">
        <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value]) }}"
           class="dr-reason-chip {{ $activeReason ? '' : 'active' }}">{{ __('caremap_claim.filter_all_reasons') }}</a>
        @foreach($reasonCounts as $reason => $total)
            <a href="{{ route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value, 'reason' => $reason]) }}"
               class="dr-reason-chip {{ $activeReason === $reason ? 'active' : '' }}">
                {{ __('caremap_claim.reason_' . $reason) }} ({{ $total }})
            </a>
        @endforeach
    </div>

    @if($importReviews->isEmpty())
        <div class="panel">
            <div class="panel-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                    <p>{{ __('caremap_claim.empty_imports_queue') }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="import"></i> {{ $importReviews->total() }}</h3>
            </div>
            <div class="panel-body">
                <p class="dr-note">{{ __('facility_review.never_verified') }}</p>

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

                    <div class="dr-card">
                        <div class="dr-card-head">
                            <div>
                                <div class="dr-card-title">{{ $name !== '' ? $name : __('facility_review.value_missing') }}</div>
                                <div class="dr-card-sub">
                                    {{ __('caremap_claim.reason_' . $review->reason) }}
                                    @if($review->match_score !== null)
                                        &bull; {{ __('facility_review.match_score', ['score' => number_format($review->match_score, 2)]) }}
                                    @endif
                                    @if($review->match_distance_m !== null)
                                        &bull; {{ __('facility_review.match_distance', ['metres' => $review->match_distance_m]) }}
                                    @endif
                                </div>
                            </div>
                            <div style="flex:1"></div>
                            <span class="badge badge-neutral">{{ __($review->status->translationKey()) }}</span>
                        </div>

                        @if($cluster > 1)
                            <div class="alert alert-warning" style="margin:.75rem 1rem 0">
                                <i data-lucide="copy"></i>
                                <div>{{ __('facility_review.cluster_warning', ['count' => $cluster]) }}</div>
                            </div>
                        @endif

                        {{-- A duplicate cannot be judged from one record. Both, or neither. --}}
                        <div class="dr-compare">
                            <div class="dr-col dr-col--candidate">
                                <div class="dr-col-head">{{ __('facility_review.compare_candidate') }}</div>
                                <dl>
                                    @foreach($candidateFields as $key => $value)
                                        <div class="dr-field">
                                            <dt>{{ __('facility_review.' . $key) }}</dt>
                                            <dd class="{{ filled($value) ? '' : 'is-missing' }}">{{ filled($value) ? $value : __('facility_review.value_missing') }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                                @if($name === '')
                                    {{-- 136 of the queue are unnamed OSM elements. The Add button is
                                         disabled for them; say why, and point at where the name usually is. --}}
                                    <p class="td-muted"><i data-lucide="alert-triangle"></i> {{ __('caremap_claim.no_name_warning') }}</p>
                                @endif
                                @if(!empty($review->payload))
                                    <details>
                                        <summary class="td-muted">{{ __('caremap_claim.raw_tags') }}</summary>
                                        <div class="dr-tags">{{ json_encode($review->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                                    </details>
                                @endif
                            </div>

                            <div class="dr-col">
                                <div class="dr-col-head">{{ __('facility_review.compare_existing') }}</div>
                                @if($listing)
                                    <dl>
                                        @foreach($existingFields as $key => $value)
                                            <div class="dr-field">
                                                <dt>{{ __('facility_review.' . $key) }}</dt>
                                                <dd class="{{ filled($value) ? '' : 'is-missing' }}">{{ filled($value) ? $value : __('facility_review.value_missing') }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                    <a href="{{ route('public.care-map.profile', $listing->id) }}" class="td-muted" target="_blank" rel="noopener">
                                        <i data-lucide="external-link"></i> {{ __('caremap_claim.view_listing') }}
                                    </a>
                                @else
                                    <p class="td-muted">{{ __('facility_review.no_match') }}</p>
                                @endif
                            </div>
                        </div>

                        @if($review->reviewed_at)
                            <div class="dr-card-sub" style="padding:0 1rem .5rem">
                                {{ __('facility_review.' . ($review->status === \App\Enums\FacilityImportReviewStatus::Deferred ? 'deferred_meta' : 'decided_meta'), [
                                    'name' => $review->reviewer?->name ?? '—',
                                    'date' => $review->reviewed_at->format('Y-m-d'),
                                ]) }}
                                @if($review->review_notes) &bull; {{ $review->review_notes }} @endif
                            </div>
                        @endif

                        @if($review->status->isOpen())
                            <div class="dr-actions">
                                <form method="POST" action="{{ route('admin.care-map.review.imports.accept', $review->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" @disabled($name === '')>
                                        <i data-lucide="plus"></i> {{ __('caremap_claim.btn_accept') }}
                                    </button>
                                </form>
                                @if($review->matched_facility_id)
                                    <form method="POST" action="{{ route('admin.care-map.review.imports.merge', $review->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i data-lucide="merge"></i> {{ __('caremap_claim.btn_merge') }}
                                        </button>
                                    </form>
                                @endif
                                @if($review->status->isPending())
                                    <form method="POST" action="{{ route('admin.care-map.review.imports.defer', $review->id) }}" class="dr-inline-form">
                                        @csrf
                                        <input type="text" name="review_notes" class="form-control" placeholder="{{ __('facility_review.defer_reason') }}" maxlength="1000">
                                        <button type="submit" class="btn btn-ghost btn-sm" title="{{ __('facility_review.defer_hint') }}">
                                            <i data-lucide="clock"></i> {{ __('facility_review.btn_defer') }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.care-map.review.imports.reject', $review->id) }}" class="dr-inline-form">
                                    @csrf
                                    <input type="text" name="review_notes" class="form-control" placeholder="{{ __('caremap_claim.notes_placeholder') }}" maxlength="1000">
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i data-lucide="x"></i> {{ __('caremap_claim.btn_reject') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mb-6">{{ $importReviews->links() }}</div>
    @endif

@endif

@endsection
