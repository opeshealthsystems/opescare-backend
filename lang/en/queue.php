<?php

return [
    'display' => [
        'title'          => 'Queue Display',
        'now_serving'    => 'Now Serving',
        'ticket'         => 'Ticket :number',
        'station'        => 'Station :name',
        'waiting'        => ':count waiting',
        'your_turn_soon' => 'Your turn is approaching.',
        'called'         => 'Please proceed to :station.',
    ],
    'status' => [
        'waiting'    => 'Waiting',
        'called'     => 'Called',
        'in_service' => 'In Service',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'no_show'    => 'No Show',
        'transferred' => 'Transferred',
    ],
    'actions' => [
        'check_in'    => 'Check In',
        'call_next'   => 'Call Next',
        'start'       => 'Start Service',
        'transfer'    => 'Transfer Patient',
        'complete'    => 'Complete',
        'prioritize'  => 'Prioritize',
    ],
    'priority' => [
        'emergency'    => 'Emergency',
        'urgent'       => 'Urgent',
        'normal'       => 'Normal',
        'scheduled'    => 'Scheduled',
    ],
    'privacy' => [
        'masked_display' => 'For privacy, only ticket numbers are shown on the public display.',
    ],
];
