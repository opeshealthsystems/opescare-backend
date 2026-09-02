<?php

return [
    'org_description' => 'OpesCare est une plateforme d\x27identité de santé et d\x27interopérabilité centrée sur le patient, conçue pour le Cameroun. Le patient détient un identifiant de santé portable unique et un dossier médical longitudinal qui le suit d\x27un établissement à l\x27autre, sous son consentement. La plateforme relie les systèmes déjà en place au lieu de les remplacer : ce n\x27est pas un logiciel de gestion hospitalière.',

    // Per-page meta descriptions. Six pages previously inherited the
    // generic site description, which made them compete for one snippet
    // and gave answer engines nothing page-specific to quote.
    'meta' => [
        'how_it_works' => 'Comment fonctionne OpesCare : le patient est identifié par son identifiant de santé, rapproché de l\'index patient, le consentement est demandé et délimité, les dossiers approuvés sont échangés en HL7 FHIR R4, et chaque accès est inscrit dans un journal que le patient peut consulter.',
        'sol_patients' => 'Pour les patients : portez un identifiant de santé unique dans tous les établissements OpesCare du Cameroun, décidez qui peut consulter votre dossier, vérifiez qui y a accédé, et trouvez médicaments, sang et soins à proximité.',
        'sol_hospitals' => 'Pour les hôpitaux et cliniques : raccordez le système que vous utilisez déjà au réseau OpesCare via FHIR, l\'API Connect ou un Bridge Agent sur site. Enregistrez vos patients et échangez les dossiers sous consentement, sans changer de logiciel.',
        'sol_laboratories' => 'Pour les laboratoires : transmettez des résultats vérifiés directement dans le dossier du patient et recevez les demandes des établissements connectés, sans remplacer votre SGL.',
        'sol_pharmacies' => 'Pour les pharmacies : vérifiez les ordonnances, enregistrez la dispensation dans l\'historique médicamenteux du patient, et publiez la disponibilité des médicaments pour que les patients trouvent le stock avant de se déplacer.',
        'sol_insurers' => 'Pour les assureurs : consultez une couverture vérifiée comme attribut de l\'identifiant de santé du patient, avec accès au strict nécessaire, limitation de finalité et piste d\'audit complète.',
    ],
];
