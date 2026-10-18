<?php

return [
    'button_title'    => 'Sign in with OPESCARD',
    'button_subtitle' => 'Scan your OPESCARD QR code',
    'modal_title'     => 'Scan your OPESCARD',
    'modal_intro'     => 'Hold the QR code on your Health ID card inside the frame. We only read your Health ID; you will still enter your password.',
    'close'           => 'Close',
    'starting'        => 'Starting the camera…',
    'scanning'        => 'Looking for a QR code…',
    'checking'        => 'Checking your card…',
    'success'         => 'Health ID filled in. Now enter your password.',
    'upload_label'    => 'Upload a photo of your card',
    'upload_hint'     => 'Use this if your camera is unavailable.',
    'or'              => 'or',
    'errors' => [
        'camera_unavailable' => 'We could not use your camera. You can upload a photo of your card instead.',
        'camera_denied'      => 'Camera access was refused. Allow the camera in your browser settings, or upload a photo of your card.',
        'no_qr_in_photo'     => 'No QR code was found in that photo. Try a sharper, closer picture.',
        'not_recognised'     => 'This QR code is not a valid OPESCARD. Type your Health ID or email instead.',
        'network'            => 'We could not check the card right now. Please try again.',
    ],
    'login' => [
        'throttled' => 'Too many sign-in attempts. Please try again in :seconds seconds.',
    ],
];
