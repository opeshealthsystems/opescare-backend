<?php

/**
 * Console strings for `facilities:import-master`.
 *
 * A new namespace of its own so the national master-list import cannot collide
 * with any other translation file. Review reasons (`uncertain_match`,
 * `generic_name`, …) are deliberately NOT translated here: they are the
 * identifiers already stored in `facility_import_reviews.reason` and shared with
 * the OpenStreetMap import, and renaming them per locale would split the queue.
 */
return [
    'title'              => 'Cameroon national facility master list',
    'dry_run_notice'     => 'DRY RUN — nothing will be written. Re-run with --apply to write.',
    'file_missing'       => 'Dataset not found at :path.',
    'file_invalid'       => 'The dataset at :path is not valid JSON, or has no "facilities" array.',
    'loaded'             => ':count facilities loaded from :path (retrieved :retrieved).',
    'attribution'        => 'Attribution written onto every row: :attribution',
    'unverified_notice'  => 'Nothing here is verified — the workbook\'s verification columns are blank on every row, so every facility stays unverified.',
    'unknown_region'     => 'No records for region ":region". Regions in this dataset: :regions',
    'limit_applied'      => '--limit applied: processing the first :count records.',
    'region_filter'      => '--region applied: :count of :total records are in :region.',

    'records_considered' => 'Records considered',
    'inserted'           => 'Inserted as new facilities',
    'updated'            => 'Existing facilities enriched',
    'unchanged'          => 'Matched, nothing to add',
    'protected'          => 'Skipped — facility owns its data',
    'review'             => 'Held for human review',
    'unmapped'           => 'Skipped — unmappable facility type',
    'no_coords'          => 'Skipped — no coordinates',

    'review_heading'     => 'Held for review, by reason',
    'fields_heading'     => 'Fields filled on existing facilities',
    'region_heading'     => 'By region',
    'region_unknown'     => '(no region)',

    'column_region'      => 'Region',
    'column_inserted'    => 'Insert',
    'column_updated'     => 'Update',
    'column_review'      => 'Review',
    'column_unchanged'   => 'Unchanged',
    'column_protected'   => 'Protected',

    'dry_run_complete'   => 'Dry run complete — no rows were written. Re-run with --apply to write.',
    'applied'            => 'Import applied.',
    'review_pending'     => ':count candidate(s) are in facility_import_reviews with status = pending. Nothing was merged or inserted for them.',
];
