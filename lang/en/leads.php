<?php

return [
    // Organization types (shared public + admin)
    'org_types' => [
        'facility'  => 'Healthcare facility',
        'insurer'   => 'Insurer',
        'lab'       => 'Laboratory',
        'pharmacy'  => 'Pharmacy',
        'developer' => 'Developer / Integrator',
        'other'     => 'Other',
    ],

    // Pipeline statuses
    'statuses' => [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'won'       => 'Won',
        'lost'      => 'Lost',
    ],

    // Public "Request a demo" page
    'demo' => [
        'page_title' => 'Request a demo | OpesCare',
        'meta_description'  => 'Book a personalized OpesCare demo for your facility, insurer, laboratory, pharmacy, or developer team.',
        'nav_label'         => 'Request a demo',
        'badge'             => 'For organizations',
        'heading'           => 'See OpesCare in action',
        'subheading'        => 'Tell us about your organization and our team will set up a tailored walkthrough.',
        'form_title'        => 'Request your demo',
        'form_subtitle'     => 'We typically respond within one business day.',
        'field_org_name'        => 'Organization name',
        'field_org_name_ph'     => 'e.g. Centre Hospitalier de Yaoundé',
        'field_org_type'        => 'Organization type',
        'field_org_type_ph'     => 'Select organization type',
        'field_name'            => 'Your name',
        'field_name_ph'         => 'Full name',
        'field_email'           => 'Work email',
        'field_email_ph'        => 'you@organization.org',
        'field_phone'           => 'Phone',
        'field_phone_ph'        => 'Optional',
        'field_message'         => 'What would you like to see?',
        'field_message_ph'      => 'Tell us about your goals, team size, and any specific needs.',
        'submit'                => 'Request demo',
        'success_title'         => 'Thanks — our team will contact you',
        'success_body'          => 'We have received your request and a member of our team will reach out shortly to schedule your demo.',
        'success_cta'           => 'Back to pricing',
    ],

    // Admin leads inbox
    'admin' => [
        'page_title'        => 'Leads',
        'breadcrumb'        => 'Leads',
        'heading'           => 'Leads & demo pipeline',
        'description'       => 'Demo requests captured from the marketing site. Newest first.',
        'filter_all'        => 'All statuses',
        'filter_label'      => 'Filter by status',
        'filter_apply'      => 'Filter',
        'filter_reset'      => 'Reset',
        'stat_total'        => 'Total leads',
        'col_organization'  => 'Organization',
        'col_type'          => 'Type',
        'col_contact'       => 'Contact',
        'col_source'        => 'Source',
        'col_status'        => 'Status',
        'col_date'          => 'Date',
        'col_actions'       => 'Actions',
        'empty'             => 'No leads yet. Demo requests will appear here.',
        'action_update'     => 'Update',
        'modal_title'       => 'Update lead status',
        'modal_status'      => 'Status',
        'modal_note'        => 'Note (optional)',
        'modal_note_ph'     => 'Add a note about this update…',
        'modal_cancel'      => 'Cancel',
        'modal_save'        => 'Save',
        'flash_updated'     => 'Lead updated.',
    ],
];
