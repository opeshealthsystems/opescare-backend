<?php

/*
|--------------------------------------------------------------------------
| Care Map — facility claim, self-service listing, directory review
|--------------------------------------------------------------------------
|
| Kept in its own file rather than appended to the 5,775-key public.php so
| the EN/FR pair stays readable and `php scripts/i18n-audit.php` parity is
| obvious at a glance.
|
*/

return [

    // ── Navigation ─────────────────────────────────────────────────────────
    'nav_my_listing'        => 'My facility listing',
    'nav_directory_review'  => 'Directory review',

    // ── Public claim flow ──────────────────────────────────────────────────
    'page_title'            => 'Claim your facility listing',
    'claim_heading'         => 'Claim this listing',
    'claim_subheading'      => 'Ask to manage :facility on the OpesCare Care Map.',
    'claim_intro'           => 'Claiming a listing lets you keep its phone number, email, opening hours and services up to date. Every change you make is recorded against your name.',
    'claim_review_notice'   => 'Claims are reviewed by an OpesCare administrator. Submitting this form does not grant access and does not mark the facility as verified.',

    'field_name'            => 'Your full name',
    'field_role'            => 'Your role at this facility',
    'field_email'           => 'Work email address',
    'field_phone'           => 'Direct phone number',
    'field_reason'          => 'Anything that helps us verify your claim',
    'field_reason_hint'     => 'For example: your registration number, your position, or a page on the facility website that names you.',
    'field_optional'        => 'optional',

    'role_select'           => 'Select…',
    'role_owner'            => 'I am the owner or operator',
    'role_manager'          => 'I manage this facility for the owner',
    'role_authorized_rep'   => 'I am an authorised representative',
    'role_admin_staff'      => 'I am administrative staff here',

    'hint_false_claim'      => 'Submitting a false claim is a violation of the OpesCare Terms of Service.',
    'btn_submit_claim'      => 'Submit claim',
    'btn_cancel'            => 'Cancel',
    'back_to_listing'       => 'Back to the listing',
    'btn_open_care_map'     => 'Open the Care Map',
    'btn_find_listing'      => 'Find your facility',
    'btn_manage_listing'    => 'Manage my listing',

    'already_pending'       => 'You already have a claim awaiting review for this listing.',
    'already_approved'      => 'You already manage this listing.',

    // ── Claim status page ──────────────────────────────────────────────────
    'my_claims_title'       => 'My facility claims',
    'my_claims_subtitle'    => 'Requests you have made to manage a listing in the Care Map.',
    'col_facility'          => 'Facility',
    'col_status'            => 'Status',
    'col_submitted'         => 'Submitted',
    'col_reviewed'          => 'Reviewed',
    'col_notes'             => 'Reviewer notes',
    'empty_claims'          => 'You have not claimed a facility listing yet.',
    'empty_claims_hint'     => 'Find your facility in the Care Map and use “Claim this listing”.',

    'status_submitted'      => 'Awaiting review',
    'status_under_review'   => 'Under review',
    'status_approved'       => 'Approved',
    'status_rejected'       => 'Rejected',
    'status_revoked'        => 'Revoked',

    // ── Self-service listing editor ────────────────────────────────────────
    'edit_title'            => 'My facility listing',
    'edit_subtitle'         => 'What patients see when they find :facility in the Care Map.',
    'badge_claimed'         => 'Claimed listing',
    'badge_not_verified'    => 'Not verified by OpesCare',
    'not_verified_note'     => 'A claimed listing means a representative of this facility maintains it. It is not the same as OpesCare having verified the institution, and it is shown differently to patients.',
    'audit_note'            => 'Every change is recorded with your name, the previous value and the new one.',

    'section_contact'       => 'Contact details',
    'section_hours'         => 'Opening hours',
    'section_services'      => 'Services and specialties',

    'label_phone_primary'   => 'Main phone number',
    'label_phone_secondary' => 'Second phone number',
    'label_email'           => 'Email address',
    'label_website'         => 'Website',
    'label_description'     => 'About this facility',
    'hint_phone_placeholder'=> 'This listing has no phone number on record. Adding one is the single most useful change you can make.',
    'hint_description'      => 'A short description patients will read before they travel.',
    'btn_save'              => 'Save changes',

    'hours_day'             => 'Day',
    'hours_open'            => 'Opens',
    'hours_close'           => 'Closes',
    'hours_closed'          => 'Closed',
    'hours_24'              => 'Open 24 hours',
    'hours_intro'           => 'Leave a day blank if you would rather not state its hours.',
    'btn_save_hours'        => 'Save opening hours',

    'day_sunday'            => 'Sunday',
    'day_monday'            => 'Monday',
    'day_tuesday'           => 'Tuesday',
    'day_wednesday'         => 'Wednesday',
    'day_thursday'          => 'Thursday',
    'day_friday'            => 'Friday',
    'day_saturday'          => 'Saturday',

    'svc_name'              => 'Service',
    'svc_category'          => 'Category',
    'svc_specialty'         => 'Specialty',
    'svc_availability'      => 'Availability',
    'svc_appointment'       => 'Appointment required',
    'svc_walkin'            => 'Walk-ins accepted',
    'svc_telemedicine'      => 'Available by teleconsultation',
    'svc_intro'             => 'Tell patients what you actually offer. Nothing else in the platform can know this.',
    'btn_add_service'       => 'Add service',
    'btn_remove'            => 'Remove',
    'empty_services'        => 'No services listed yet.',

    'cat_consultation'      => 'Consultation',
    'cat_emergency'         => 'Emergency',
    'cat_diagnostic'        => 'Diagnostics',
    'cat_laboratory'        => 'Laboratory',
    'cat_imaging'           => 'Medical imaging',
    'cat_surgery'           => 'Surgery',
    'cat_maternity'         => 'Maternity',
    'cat_pharmacy'          => 'Pharmacy',
    'cat_dental'            => 'Dental care',
    'cat_rehabilitation'    => 'Rehabilitation',
    'cat_preventive'        => 'Preventive care',

    'avail_available'       => 'Available',
    'avail_limited'         => 'Limited',
    'avail_unavailable'     => 'Unavailable',
    'avail_by_referral'     => 'By referral only',

    'none_title'            => 'No listing to manage yet',
    'none_body'             => 'Once an administrator approves your claim, this page becomes the editor for your facility listing.',

    // ── Admin directory review ─────────────────────────────────────────────
    'review_title'          => 'Directory review',
    'review_subtitle'       => 'Decisions about facilities that a machine must not make on its own.',
    'tab_claims'            => 'Ownership claims',
    'tab_imports'           => 'Import candidates',
    'stat_pending_claims'   => 'Claims awaiting review',
    'stat_pending_imports'  => 'Import candidates pending',
    'recent_decisions'      => 'Recent decisions',

    'claim_col_claimant'    => 'Claimant',
    'claim_col_contact'     => 'Contact',
    'claim_col_role'        => 'Stated role',
    'btn_approve'           => 'Approve',
    'btn_reject'            => 'Reject',
    'btn_revoke'            => 'Revoke',
    'notes_placeholder'     => 'Reason (optional)',
    'empty_claims_queue'    => 'No facility claims are waiting for a decision.',
    'approve_warning'       => 'Approving grants this person permission to edit the listing. It does not mark the facility as verified.',

    'import_col_candidate'  => 'Candidate',
    'import_col_reason'     => 'Why it needs a person',
    'import_col_match'      => 'Looked like',
    'btn_accept'            => 'Add to directory',
    'btn_merge'             => 'Same as match',
    'empty_imports_queue'   => 'No import candidates are waiting for a decision.',
    'match_score'           => 'Similarity :score',
    'match_distance'        => ':metres m apart',
    'no_name_warning'       => 'This candidate has no name tag. Read the raw tags before deciding.',
    'raw_tags'              => 'Raw upstream tags',
    'view_listing'          => 'View listing',
    'filter_all_reasons'    => 'All reasons',
    'source_label'          => 'Source',

    'reason_generic_name'                   => 'Name is too generic',
    'reason_unnamed_element'                => 'Upstream record has no name',
    'reason_uncertain_match'                => 'Weak match to an existing listing',
    'reason_multiple_matches'               => 'Matches more than one listing',
    'reason_type_conflict'                  => 'Facility type disagrees',
    'reason_unresolved_city'                => 'City could not be resolved',
    'reason_already_linked_to_other_element'=> 'Match already linked to another record',

    // ── Flash messages ─────────────────────────────────────────────────────
    'flash_submitted'       => 'Claim submitted. An administrator will review it and contact you.',
    'flash_no_changes'      => 'Nothing changed.',
    'flash_profile_updated' => '{1} 1 field updated.|[2,*] :count fields updated.',
    'flash_service_added'   => 'Service added to your listing.',
    'flash_service_removed' => 'Service removed from your listing.',
    'flash_hours_updated'   => '{0} Opening hours cleared.|{1} Opening hours saved for 1 day.|[2,*] Opening hours saved for :count days.',
    'flash_claim_approved'  => 'Claim approved. The claimant can now edit the listing.',
    'flash_claim_rejected'  => 'Claim rejected.',
    'flash_claim_revoked'   => 'Claim revoked. The listing keeps its existing content.',
    'flash_import_accepted' => 'Candidate added to the directory.',
    'flash_import_merged'   => 'Candidate merged into the existing listing.',
    'flash_import_rejected' => 'Candidate rejected.',

    // ── Errors ─────────────────────────────────────────────────────────────
    'error_already_submitted' => 'You already have a claim on this listing.',
    'error_already_claimed'   => 'Another representative already manages this listing. Please contact support.',
    'error_service_not_found' => 'That service is not on your listing.',
    'error_import_decided'    => 'That candidate has already been decided.',
    'error_import_unnamed'    => 'Give the candidate a name before adding it to the directory.',
    'error_import_no_match'   => 'There is no matched listing to merge into.',
    'error_generic'           => 'Something went wrong. Please try again.',

];
