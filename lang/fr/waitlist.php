<?php

// Liste d'attente des rendez-vous — portail patient + guichet de l'établissement.

return [
    'nav'                  => "Liste d'attente",
    'title'                => "Liste d'attente des rendez-vous",
    'subtitle'             => "Demandez à être prévenu lorsqu'un créneau se libère dans un établissement.",
    'join_title'           => "Rejoindre la liste d'attente",
    'join_help'            => "Lorsqu'un rendez-vous est annulé dans cet établissement, le patient suivant sur la liste est notifié et peut réserver le créneau.",
    'field_facility'       => "Établissement",
    'field_type'           => "Type de rendez-vous",
    'field_earliest'       => "Date au plus tôt",
    'field_latest'         => "Date au plus tard",
    'window_help'          => "Les deux dates sont facultatives. Laissez-les vides pour accepter n'importe quelle date.",
    'field_urgency'        => "Urgence",
    'field_notes'          => "Remarques pour l'établissement",
    'notes_ph'             => "Tout ce que le guichet devrait savoir (facultatif)",
    'btn_join'             => "Rejoindre la liste d'attente",
    'btn_leave'            => "Quitter la liste d'attente",
    'my_entries'           => "Mes inscriptions en liste d'attente",
    'empty_title'          => "Vous n'êtes sur aucune liste d'attente",
    'empty_desc'           => "Inscrivez-vous ci-dessus et nous vous préviendrons dès qu'un créneau se libère.",
    'any_date'             => "N'importe quelle date",
    'joined_on'            => "Inscrit le :date",
    'notified_hint'        => "Un créneau s'est libéré — réservez-le maintenant.",
    'flash_joined'         => "Vous avez rejoint la liste d'attente. Nous vous préviendrons dès qu'un créneau se libère.",
    'flash_cancelled'      => "Vous avez quitté la liste d'attente.",
    'flash_not_cancellable' => "Cette inscription en liste d'attente est déjà close.",
    'notify_title'         => "Un créneau s'est libéré",
    'notify_body'          => "Un créneau s'est libéré à :facility le :date — réservez maintenant.",

    // Guichet de l'établissement
    'staff_title'          => "Liste d'attente",
    'staff_subtitle'       => "Patients en attente d'un créneau dans cet établissement, les plus urgents en premier.",
    'staff_empty_title'    => "Personne n'est en attente",
    'staff_empty_desc'     => "Aucun patient n'a rejoint la liste d'attente de cet établissement.",
    'col_patient'          => "Patient",
    'col_window'           => "Période souhaitée",
    'col_urgency'          => "Urgence",
    'col_joined'           => "Inscrit le",
    'btn_book'             => "Réserver",
    'booking_from'         => "Réservation depuis la liste d'attente pour :health_id — l'inscription sera close une fois ce rendez-vous enregistré.",

    // Console
    'expired_count'        => ":count inscription(s) en liste d'attente expirée(s).",
];
