<?php

/**
 * Clinical terminology review desk (/admin/terminology).
 *
 * Its own namespace so the desk could be added without editing an existing
 * translation file. EN and FR must stay 1:1 — scripts/i18n-audit.php is a CI
 * gate.
 *
 * WHAT IS DELIBERATELY ABSENT: analyte names, test names, LOINC display
 * strings, ICD-10 titles, code-system names and units. Those are clinical
 * vocabulary, not UI copy. "Hemoglobin [Mass/volume] in Blood" is the official
 * LOINC display for 718-7 in every locale; translating it would produce a
 * string that is no longer that code's display and would be wrong in a
 * FHIR payload. The labels AROUND them are translated; they are not.
 */
return [

    // Shared
    'nav' => 'Clinical terminology',
    'nav_log' => 'Application log',
    'breadcrumb_home' => 'Admin',
    'btn_filter' => 'Filter',
    'btn_clear' => 'Clear',
    'btn_open' => 'Review',
    'btn_back' => 'Back',

    // Queue
    'index_title' => 'Clinical terminology',
    'index_subtitle' => 'Proposed LOINC and ICD-10 mappings for this deployment’s vocabulary. A mapping is inert until a person approves it, and approving it still writes nothing to a record — applying is a separate act.',
    'kpi_pending' => 'Awaiting review',
    'kpi_approved' => 'Approved',
    'kpi_rejected' => 'Rejected',
    'kpi_deprecated' => 'Deprecated',
    'filter_status' => 'Status',
    'filter_all_statuses' => 'All statuses',
    'filter_system' => 'Code system',
    'filter_all_systems' => 'All code systems',
    'empty_heading' => 'No mappings match this filter',
    'empty_body' => 'Clear the filter, or run the terminology seeder to propose mappings for the vocabulary in use.',

    // Columns
    'col_local_term' => 'Local term',
    'col_resource_type' => 'Applies to',
    'col_standard' => 'Proposed code',
    'col_confidence' => 'Confidence',
    'col_radius' => 'Records affected',
    'col_status' => 'Status',
    'col_target' => 'Target record',
    'col_row_unit' => 'Unit on the record',
    'col_current_code' => 'Code already there',
    'col_decision' => 'Would be',
    'col_when' => 'When',
    'col_action' => 'Action',
    'col_actor' => 'By',
    'col_detail' => 'Detail',
    'col_mapping' => 'Mapping',
    'col_before' => 'Before',
    'col_after' => 'After',

    // Statuses
    'status_pending' => 'Awaiting review',
    'status_approved' => 'Approved',
    'status_rejected' => 'Rejected',
    'status_deprecated' => 'Deprecated',

    // Resource types
    'resource_lab_result' => 'Result analytes',
    'resource_lab_order' => 'Orderable tests',
    'resource_condition' => 'Diagnoses',

    // Confidence
    'confidence_review' => 'Needs a judgement',
    'confidence_high' => 'Unambiguous',

    // Blast radius
    'radius_write' => ':n would be filled',
    'radius_conflict' => ':n already carry a different code',
    'radius_unit_mismatch' => ':n skipped on unit',
    'radius_summary' => ':total record(s) match this local term. :write would be given the code, :already already carry it, :conflict carry a DIFFERENT code and are skipped, :mismatch match the name but not the unit and are never touched.',
    'radius_none' => 'No record on this platform carries this local term, so applying the mapping would write nothing.',

    // Decisions
    'decision_write' => 'Filled',
    'decision_recode' => 'Re-coded',
    'decision_already' => 'Already coded',
    'decision_conflict' => 'Conflict — skipped',
    'decision_unit_mismatch' => 'Unit mismatch — skipped',

    // Detail panels
    'show_title' => 'Mapping review',
    'panel_mapping' => 'The proposed mapping',
    'panel_radius' => 'What applying this would do',
    'panel_review' => 'Review decision',
    'panel_apply' => 'Apply to records',
    'panel_history' => 'History',
    'field_unit' => 'Unit',
    'field_unit_hint' => 'The unit is part of the mapping — the same analyte in a different unit is a different code.',
    'field_unit_none' => 'No unit — this mapping is not matched on one.',
    'field_display' => 'Standard display',
    'field_reviewer' => 'Reviewed by',
    'field_reviewer_none' => 'Nobody yet',
    'field_reject_reason' => 'Reason for rejecting',
    'field_reject_placeholder' => 'Why this code is not the right one for this term.',
    'review_banner_heading' => 'This one needs a judgement, not a nod.',
    'review_banner_body' => 'A defensible alternative code exists for this term. Confirm the choice against the LOINC browser before approving.',
    'malformed_banner' => 'This code fails validation for its own code system, so it cannot be approved or applied. A wrong code is worse than no code.',
    'review_hint' => 'Approving records that this code is correct. It writes nothing to any patient record.',
    'apply_hint' => 'This writes the code onto the records listed above. It adds a code and nothing else — no value, unit, reference range or flag is touched.',
    'confirm_recode' => 'Yes, replace the different code already on :n record(s). I have checked what is being replaced.',
    'btn_approve' => 'Approve this mapping',
    'btn_reject' => 'Reject this mapping',
    'btn_apply' => 'Apply the code to :n record(s)',
    'history_none' => 'Nothing has been done to this mapping yet.',

    // Application log
    'log_title' => 'Terminology application log',
    'log_subtitle' => 'Every approval, rejection and code written by this desk, with the state each record was in beforehand.',
    'log_empty_heading' => 'Nothing applied yet',
    'log_empty_body' => 'No terminology code has been approved, rejected or written from this desk.',

    // Refusals and flashes
    'refuse_not_approved' => 'This mapping has not been approved, so it cannot be applied. Approve it first.',
    'refuse_no_target' => 'There is no column on this platform for this kind of mapping, so there is nothing to write. Diagnoses already carry their ICD-10 code directly.',
    'refuse_wrong_system' => 'The target columns hold LOINC codes only. A code from another system will not be written into them.',
    'refuse_malformed_code' => 'This code fails validation for its own code system and was refused at the moment of writing, not merely when it was proposed.',
    'flash_not_pending' => 'This mapping has already been decided; it is no longer awaiting review.',
    'flash_malformed' => 'This code fails validation for its own code system and cannot be approved.',
    'flash_approved' => 'Mapping approved. Nothing has been written to any record — use Apply when you are ready.',
    'flash_rejected' => 'Mapping rejected, and the reason is on the record.',
    'flash_applied' => 'Applied: :applied filled, :recoded re-coded. Skipped: :conflict conflicting, :mismatch on unit, :already already carrying this code.',

    // The code dictionary browser — the vocabularies themselves.
    'dict_title' => 'Code dictionary',
    'dict_subtitle' => 'The published vocabularies themselves — WHO ICD-10 and LOINC — as imported from their publishers. These are reference data: they are not edited here, only replaced by importing a newer release.',
    'dict_nav_queue' => 'Mapping review queue',
    'dict_common_count' => ':count flagged as commonly used',
    'dict_never_imported' => 'The dictionary is empty. The importers have never been run — this is not the same as a search that found nothing. Run opescare:import-icd10 and opescare:import-loinc.',
    'dict_provenance' => 'Loaded from:',
    'dict_search_placeholder' => 'Code or description — e.g. E11, cholera, haemoglobin',
    'dict_search' => 'Search',
    'dict_all_systems' => 'All code systems',
    'dict_only_common' => 'Commonly used only',
    'dict_show_headings' => 'Include chapter and block headings',
    'dict_col_code' => 'Code',
    'dict_col_system' => 'System',
    'dict_col_display' => 'Description',
    'dict_col_basis' => 'Basis',
    'dict_heading' => 'Heading — not assignable',
    'dict_chapter' => 'Chapter :chapter',
    'dict_no_results' => 'No codes match that search.',
    // The reconciliation report — the platform's own data read back against
    // the vocabularies. Read-only, and the copy has to keep saying so.
    'gaps_nav' => 'Terminology gaps',
    'gaps_title' => 'Terminology gaps',
    'gaps_subtitle' => 'The codes, units and routes already written onto this deployment’s records, read back against the published vocabularies. It finds what no longer resolves, what is spelled two ways, and what was never backed by a vocabulary at all.',
    'gaps_readonly_notice' => 'This report reads. It changes nothing, and there is no control on this page that could. A clinical record is corrected by an accountable person who amends it — never by a bulk fix launched from a report.',
    'gaps_vocabularies' => 'Checked against:',
    'gaps_no_vocabularies' => 'No vocabulary is loaded — nothing below could be checked against anything.',
    'gaps_expected' => 'Expected vocabulary: :systems',
    'gaps_state_absent' => 'Not in this deployment',
    'gaps_state_empty' => 'Empty',
    'gaps_state_unbacked' => 'Unbacked',
    'gaps_state_clean' => 'Everything resolves',
    'gaps_state_review' => 'Needs a decision',
    'gaps_absent_body' => 'This table or column does not exist here, so there is nothing to check. That is not a finding of zero problems.',
    'gaps_empty_body' => ':rows rows, none of them carrying a value. Empty is not the same as clean: nothing here is coded at all.',
    'gaps_unbacked_body' => ':distinct distinct values across :rows rows, and none of the expected vocabularies is loaded here. These are local strings standing in for codes.',
    'gaps_checked_body' => 'Checked against :system — :resolved of :distinct distinct values resolve, covering :resolved_rows of :rows rows.',
    'gaps_all_resolve' => 'Every value resolves cleanly.',
    'gaps_col_value' => 'Value on the record',
    'gaps_col_rows' => 'Rows',
    'gaps_col_suggestions' => 'Possible matches — suggestions only',
    'gaps_heading_only' => 'Present in the vocabulary, but only as a heading or group — not a code anyone may assign.',
    'gaps_basis_display' => 'matched on description',
    'gaps_basis_code_shape' => 'neighbouring code',
    'gaps_padded' => '“:value” carries leading or trailing whitespace on :rows rows, so it will never match a code exactly.',
    'gaps_duplicates_heading' => 'Probably the same thing, written two ways',
    'gaps_duplicates_caveat' => 'Probably — not certainly. Confirm each one against the source records before deciding anything; two values that look alike are sometimes genuinely different.',
    'gaps_rule_case_punctuation' => 'identical apart from case and punctuation',
    'gaps_rule_near_string' => 'one character apart',
    'gaps_rule_declared_synonym' => 'known abbreviation for the same route',
    'gaps_footer' => 'Nothing on this page has been changed. Every line is for a person to decide.',
];
