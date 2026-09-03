<?php

/*
| Couche méta SEO d'OpesCare Finder — miroir 1:1 de lang/en/finder_seo.php.
| Les titres de pages sont SANS le nom du site (le gabarit ajoute
| « — OpesCare Finder ») ; les titres de fiches sont complets.
*/

return [

    'site' => [
        'title' => 'OpesCare Finder',
        'description' => 'Trouvez des médicaments en stock, la disponibilité du sang et les établissements de santé du Cameroun — gratuit, en français et en anglais.',
        'org_description' => 'OpesCare Finder est un annuaire de santé gratuit et bilingue pour le Cameroun : stock des pharmacies, disponibilité du sang et registre national des établissements de santé. La disponibilité est déclarée par les établissements et horodatée, jamais une garantie vérifiée de stock.',
    ],

    'pages' => [
        'home' => [
            'title' => 'Médicaments, sang et soins au Cameroun',
            'description' => 'Recherchez le stock des pharmacies, la disponibilité du sang et plus de 2 700 hôpitaux, cliniques et pharmacies au Cameroun. Gratuit, FR et EN.',
        ],
        'medicine_finder' => [
            'title' => 'Trouver un médicament — stock en pharmacie',
            'description' => 'Trouvez un médicament en stock dans une pharmacie proche. La disponibilité est déclarée par les pharmacies et horodatée — vérifiez avant de partir.',
        ],
        'blood_finder' => [
            'title' => 'Trouver du sang — disponibilité',
            'description' => 'Vérifiez la disponibilité du sang par groupe et composant dans les hôpitaux et banques de sang connectés, avec l\'heure de chaque rapport.',
        ],
        'care_map' => [
            'title' => 'Carte des soins — annuaire des établissements',
            'description' => 'Parcourez plus de 2 700 hôpitaux, cliniques, centres de santé, pharmacies et laboratoires du registre national du Cameroun, sur une carte.',
        ],
        'emergency' => [
            'title' => 'Accès aux urgences',
            'description' => 'Les hôpitaux du Cameroun joignables par téléphone dès maintenant, sur une carte. En cas d\'urgence, appelez ou allez à l\'hôpital le plus proche.',
        ],
        'profile' => [
            'title' => 'Fiche établissement',
            'description' => 'Adresse, téléphone, horaires et services des établissements de santé du Cameroun, sur OpesCare Finder.',
        ],
        'about' => [
            'title' => 'À propos',
            'description' => 'Ce qu\'est OpesCare Finder, d\'où viennent les données de l\'annuaire, et ce que la disponibilité déclarée signifie — et ne signifie pas.',
        ],
        'how_it_works' => [
            'title' => 'Comment ça marche',
            'description' => 'Comment fonctionne OpesCare Finder : les pharmacies déclarent leur stock, les hôpitaux la disponibilité du sang, l\'annuaire suit le registre national.',
        ],
        'faq' => [
            'title' => 'Questions fréquentes',
            'description' => 'Réponses sur la recherche de médicaments et de sang, les rapports de disponibilité, les fiches d\'établissements et l\'usage du site en français.',
        ],
        'contact' => [
            'title' => 'Nous contacter',
            'description' => 'Contactez l\'équipe OpesCare Finder — questions, corrections d\'une fiche d\'établissement, ou partenariats pharmacies et hôpitaux.',
        ],
        'help' => [
            'title' => 'Centre d\'aide',
            'description' => 'Guides pour rechercher médicaments et sang, comprendre les étiquettes de fraîcheur et tirer le meilleur de l\'annuaire des établissements.',
        ],
        'privacy' => [
            'title' => 'Politique de confidentialité',
            'description' => 'Comment OpesCare Finder traite les données personnelles : ce que nous collectons, ce que nous ne collectons jamais, et vos choix.',
        ],
        'terms' => [
            'title' => 'Conditions d\'utilisation',
            'description' => 'Les conditions d\'utilisation d\'OpesCare Finder, y compris ce que la disponibilité déclarée signifie et ne signifie pas.',
        ],
        'consent' => [
            'title' => 'Consentement et choix de données',
            'description' => 'Vos choix de consentement et de données sur OpesCare Finder, expliqués simplement.',
        ],
        'status' => [
            'title' => 'État du service',
            'description' => 'État opérationnel en direct des services OpesCare Finder.',
        ],
        'sla' => [
            'title' => 'Niveaux de service',
            'description' => 'Engagements de niveau de service pour les partenaires intégrant OpesCare Finder.',
        ],
    ],

    'profile' => [
        'title'            => ':name — :city, :region | OpesCare Finder',
        'title_city'       => ':name — :city | OpesCare Finder',
        'title_bare'       => ':name | OpesCare Finder',
        'description'      => ':name — :type à :city, Cameroun. Adresse, numéro de téléphone, horaires d\'ouverture et services sur OpesCare Finder.',
        'description_bare' => ':name — :type au Cameroun. Adresse, numéro de téléphone, horaires d\'ouverture et services sur OpesCare Finder.',
    ],

    'breadcrumbs' => [
        'home'     => 'Accueil',
        'care_map' => 'Annuaire',
    ],

    'types' => [
        'hospital'          => 'hôpital',
        'clinic'            => 'clinique',
        'pharmacy'          => 'pharmacie',
        'laboratory'        => 'laboratoire d\'analyses médicales',
        'health_center'     => 'centre de santé',
        'dental'            => 'cabinet dentaire',
        'imaging_center'    => 'centre d\'imagerie médicale',
        'maternity'         => 'maternité',
        'dispensary'        => 'dispensaire',
        'eye_clinic'        => 'clinique ophtalmologique',
        'diagnostic_center' => 'centre de diagnostic',
        'specialist'        => 'cabinet de spécialiste',
        'facility'          => 'établissement de santé',
    ],
];
