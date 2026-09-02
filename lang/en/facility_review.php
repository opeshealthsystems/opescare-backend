<?php

/*
|--------------------------------------------------------------------------
| Facility import review — the reviewer's side-by-side desk
|--------------------------------------------------------------------------
|
| Strings for the part of the directory-review screen that lets a person
| decide an import candidate: the candidate and the listing it may duplicate,
| shown next to each other, plus the OpenStreetMap attribution the ODbL
| licence obliges us to carry wherever OSM-derived data is displayed.
|
| Kept in its own namespace rather than folded into caremap_claim.php so the
| comparison desk can grow without disturbing the claims half of the screen.
| Must stay 1:1 with lang/fr/facility_review.php (php scripts/i18n-audit.php).
|
*/

return [

    // ── Side-by-side comparison ─────────────────────────────────────────────
    'compare_heading'   => 'Compare before deciding',
    'compare_candidate' => 'Import candidate',
    'compare_existing'  => 'Existing listing it may duplicate',

    'field_name'   => 'Name',
    'field_type'   => 'Type',
    'field_city'   => 'City',
    'field_region' => 'Region',
    'field_coords' => 'Coordinates',
    'field_phone'  => 'Phone',
    'field_source' => 'Source',
    'field_status' => 'Listing status',

    'value_missing' => 'Not recorded',
    'no_match'      => 'The importer found no existing listing that resembles this candidate. It can only be added as a new facility, deferred, or rejected.',

    'match_score'    => 'Name similarity :score',
    'match_distance' => ':metres m apart',

    // Several pending candidates pointing at one existing listing: a reviewer
    // deciding one of them needs to know the others exist, or they will accept
    // the same hospital twice under two spellings.
    'cluster_warning' => ':count candidates in this queue point at this same listing. Decide them together.',

    // ── Attribution (ODbL licence obligation, not decoration) ────────────────
    'attribution'      => 'Source data: :attribution',
    'attribution_note' => 'Candidates below are derived from OpenStreetMap and are published under the Open Database Licence (ODbL). Attribution must be preserved on any listing created from them.',

    // ── Deferral ────────────────────────────────────────────────────────────
    'btn_defer'    => 'Defer',
    'defer_hint'   => 'Park this candidate without deciding it. It leaves the queue but stays open — it can still be added, merged or rejected later.',
    'defer_reason' => 'Why defer?',

    'flash_deferred'      => 'Candidate deferred. It is out of the queue but still undecided.',
    'error_defer_decided' => 'That candidate has already been decided and cannot be deferred.',

    // ── Status filter ───────────────────────────────────────────────────────
    'filter_status'   => 'Status',
    'status_pending'  => 'Awaiting decision',
    'status_deferred' => 'Deferred',
    'status_imported' => 'Added',
    'status_merged'   => 'Merged',
    'status_rejected' => 'Rejected',

    'decided_meta'  => 'Decided by :name on :date',
    'deferred_meta' => 'Deferred by :name on :date',
    'never_verified' => 'Accepting a candidate lists it. It does not verify it — no facility in this directory carries a verified status.',

];
