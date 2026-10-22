<?php

// Partner-reported readings: data an external system pushed in over FHIR R4.
// Always shown apart from readings taken at a facility.
return [
    'tab'               => 'Partner-reported',
    'title'             => 'Partner-reported readings',
    'provenance_label'  => 'Reported by partner system',
    'separate_note'     => 'These readings were sent by another system over the interoperability API. Nobody at this facility measured them; they are not part of this facility\'s vitals, results or trends, and the partner\'s own range and interpretation are shown as sent.',
    'none'              => 'No partner system has reported readings for this patient.',
    'summary_count'     => '{0} No partner-reported readings|{1} :count partner-reported reading|[2,*] :count partner-reported readings',
    'all'               => 'All partner-reported readings',
    'badge_system'      => 'System: :name',
    'badge_facility'    => 'Sent by: :name',
    'unknown_facility'  => 'Unknown facility',
    'col_reading'       => 'Reading',
    'col_value'         => 'Value',
    'col_range'         => 'Partner range / interpretation',
    'col_taken'         => 'Taken',
    'col_received'      => 'Received',
    'col_source'        => 'Source',
    'types' => [
        'observation'       => 'Observation',
        'diagnostic_report' => 'Report',
        'immunization'      => 'Immunization',
    ],
    'previous'          => 'Newer',
    'next'              => 'Older',
];
