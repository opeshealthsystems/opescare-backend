<?php

/*
|--------------------------------------------------------------------------
| Pages « histoire » du Finder — miroir 1:1 de lang/en/finder_story.php
|--------------------------------------------------------------------------
| Ton : service public — calme, direct, sans discours commercial.
| Mêmes règles que la version anglaise : jamais « vérifié » (le fait exact
| est « inscrit au registre national des formations sanitaires ») et les
| limites honnêtes restent visibles (« appelez avant de vous déplacer »).
*/

return [

    // ── À propos — ce qu'est le Finder, l'origine des données, sans fard ───
    'about' => [
        'page_title'       => "À propos d'OpesCare",
        'meta_description' => "Ce qu'est OpesCare, d'où viennent ses données et à qui il s'adresse — un annuaire gratuit des pharmacies, hôpitaux, cliniques et laboratoires du Cameroun.",
        'eyebrow'          => 'À propos',
        'title'            => 'Un moyen gratuit de trouver des soins au Cameroun',
        'lead'             => "OpesCare vous aide à localiser une pharmacie, un hôpital, du sang ou un rendez-vous — et vous donne un numéro de téléphone pour confirmer avant de vous déplacer.",

        'what_title' => "Ce qu'est le Finder",
        'what_p1'    => "Le Finder est un annuaire public des établissements de santé des dix régions du Cameroun — pharmacies, hôpitaux, cliniques et laboratoires — avec leurs coordonnées et, lorsque les établissements les déclarent, leurs horaires, services, stocks de médicaments et disponibilités de sang.",
        'what_p2'    => "Il est gratuit, en français et en anglais, depuis le navigateur de n'importe quel téléphone. Rien à installer, et aucun compte n'est nécessaire pour chercher.",

        'free_title' => 'Gratuit veut dire gratuit',
        'free_p'     => "Chercher, parcourir l'annuaire et réserver sont gratuits pour les patients et leurs familles. L'inscription est gratuite pour les établissements aussi : une pharmacie ou une clinique revendique sa fiche et la tient à jour sans rien payer.",

        'data_title'          => "D'où viennent les données",
        'data_lead'           => "Trois sources, chacune avec son rôle — et il est important de savoir laquelle fait quoi.",
        'data_registry_title' => 'Le registre national des formations sanitaires du Cameroun',
        'data_registry_p'     => "La colonne vertébrale de l'annuaire. Les établissements figurent sur le Finder parce qu'ils sont inscrits au registre national des formations sanitaires — c'est ce qui place un hôpital ou une pharmacie sur la carte au départ.",
        'data_osm_title'      => 'Les contributeurs OpenStreetMap',
        'data_osm_p'          => "Les positions sur la carte et les détails d'adresse sont améliorés grâce aux données apportées par des bénévoles à OpenStreetMap — © les contributeurs OpenStreetMap.",
        'data_self_title'     => 'Ce que les établissements déclarent eux-mêmes',
        'data_self_p'         => "Les horaires, les numéros corrigés, les services, les stocks de médicaments et les disponibilités de sang viennent des établissements qui ont revendiqué leur fiche et les déclarent. Rien d'autre n'alimente ces informations : si une pharmacie n'a pas déclaré un médicament, le Finder ne l'affichera pas — un résultat vide signifie « non déclaré », pas « non disponible ».",

        'honest_title' => "Ce qu'une fiche est — et n'est pas",
        'honest_p1'    => "Une fiche signifie que l'établissement figure dans les sources ci-dessus. Ce n'est ni un contrôle d'agrément, ni une inspection, ni une recommandation d'OpesCare.",
        'honest_p2'    => "Les informations changent : les numéros sont réattribués, les horaires évoluent, les stocks s'épuisent dans la journée. Appelez avant de vous déplacer dès que le trajet compte.",

        'who_title'            => "À qui s'adresse le Finder",
        'who_patients_title'   => 'Les patients et leurs familles',
        'who_patients_p'       => "Trouvez la pharmacie la plus proche qui a votre médicament, un hôpital avec service de transfusion, ou une clinique à réserver ou à appeler.",
        'who_caregivers_title' => 'Les aidants',
        'who_caregivers_p'     => "Cherchez pour un parent ou un enfant, comparez les établissements près de chez eux, et appelez avant que quiconque ne se déplace.",
        'who_facilities_title' => 'Les pharmacies et les établissements',
        'who_facilities_p'     => "Revendiquez votre fiche pour tenir à jour votre numéro, vos horaires et vos stocks — afin que les patients vous joignent avec des informations exactes.",
        'who_facilities_cta'   => 'Revendiquer votre fiche',

        'cta_title' => "Commencez par ce qu'il vous faut",
    ],

    // ── Comment ça marche — le parcours patient, limites honnêtes incluses ─
    'how' => [
        'page_title'       => 'Comment ça marche',
        'meta_description' => "Comment trouver médicaments, sang, établissements et rendez-vous avec OpesCare — et les limites honnêtes des données : les stocks sont déclarés par les pharmacies, appelez avant de vous déplacer.",
        'eyebrow'          => 'Comment ça marche',
        'title'            => "De « il me faut… » à une visite confirmée",
        'lead'             => "Le Finder fait une seule chose : il vous montre les établissements près de chez vous, ce qu'ils ont déclaré, et vous donne un numéro à appeler. Voici chaque outil, étape par étape.",

        'medicine_title' => 'Trouver un médicament',
        'medicine_step1' => "Recherchez le médicament, ou ouvrez l'annuaire des pharmacies de votre zone.",
        'medicine_step2' => "Consultez la fiche — chaque stock déclaré est horodaté, vous voyez donc la fraîcheur de l'information.",
        'medicine_step3' => "Appelez la pharmacie pour confirmer le stock et le prix, puis déplacez-vous.",
        'medicine_note'  => "Seules les pharmacies qui déclarent leurs stocks apparaissent dans les résultats de stock. Un médicament absent des résultats peut très bien être en rayon tout près — la pharmacie ne l'a simplement pas déclaré.",

        'blood_title' => 'Trouver du sang',
        'blood_step1' => 'Ouvrez la recherche de sang et choisissez le groupe sanguin qu\'il vous faut.',
        'blood_step2' => 'Voyez les hôpitaux avec service de transfusion près de chez vous, et ce qu\'ils ont déclaré.',
        'blood_step3' => "Appelez d'abord l'hôpital. En situation urgente, ne vous déplacez jamais sur la seule foi d'une fiche.",

        'facility_title' => 'Trouver un établissement',
        'facility_step1' => "Parcourez l'annuaire, ou filtrez par type — pharmacie, hôpital, clinique, laboratoire.",
        'facility_step2' => "Ouvrez la fiche d'un établissement : adresse, téléphone, et les services et horaires qu'il a déclarés.",
        'facility_step3' => "En urgence, la vue urgences montre les établissements les plus proches disposant d'un service d'urgence — mais partez, ne consultez pas longuement.",

        'booking_title' => 'Prendre rendez-vous',
        'booking_step1' => "Créez un compte gratuit — la réservation est la seule chose qui en demande un.",
        'booking_step2' => "Ouvrez la fiche de l'établissement et choisissez un créneau là où la réservation en ligne est proposée.",
        'booking_step3' => "Tous les établissements n'acceptent pas encore la réservation en ligne. Là où elle manque, la fiche vous donne un numéro de téléphone à appeler.",

        'limits_title' => 'Les limites honnêtes',
        'limits_lead'  => "Le Finder ne vaut que ce qu'on lui déclare. Gardez quatre choses en tête :",
        'limit_1'      => "Les informations changent. Numéros, horaires et services peuvent être dépassés — appelez avant de vous déplacer dès que le trajet compte.",
        'limit_2'      => "Les stocks sont déclarés par les pharmacies elles-mêmes et évoluent dans la journée. L'horodatage indique la dernière déclaration.",
        'limit_3'      => "Une fiche n'est ni une recommandation ni une inspection — elle signifie que l'établissement figure au registre national et dans les sources cartographiques.",
        'limit_4'      => "En urgence, ne comptez sur aucune application. Rendez-vous au service d'urgence le plus proche — la vue urgences sert seulement à le trouver plus vite.",

        'cta_title' => "Essayez avec ce qu'il vous faut aujourd'hui",
    ],

    // ── FAQ — les questions qu'on pose vraiment, répondues simplement ──────
    'faq' => [
        'page_title'       => 'Questions fréquentes',
        'meta_description' => "OpesCare est-il gratuit ? D'où viennent les données ? Les établissements sont-ils vérifiés ? Comment une pharmacie revendique-t-elle sa fiche ? Des réponses claires, en français et en anglais.",
        'eyebrow'          => 'FAQ',
        'title'            => "Les questions qu'on nous pose vraiment",
        'lead'             => "Des réponses courtes et honnêtes. Si la vôtre n'y est pas, la page contact joint une vraie personne.",

        'items' => [
            [
                'q' => 'OpesCare est-il gratuit ?',
                'a' => "Oui. Chercher un médicament, du sang ou un établissement, parcourir l'annuaire et prendre rendez-vous sont gratuits pour les patients et leurs familles. C'est gratuit pour les établissements aussi — revendiquer sa fiche et déclarer ses stocks ne coûtent rien.",
            ],
            [
                'q' => "Faut-il un compte ?",
                'a' => "Pas pour chercher. Toutes les recherches, toutes les fiches et tous les numéros de téléphone fonctionnent sans compte. Prendre rendez-vous est la seule chose qui demande un compte gratuit, pour que l'établissement sache qui attendre.",
            ],
            [
                'q' => "D'où viennent les informations sur les établissements ?",
                'a' => "De trois endroits : le registre national des formations sanitaires du Cameroun, qui place chaque établissement sur la carte ; les contributeurs OpenStreetMap, qui améliorent positions et adresses ; et les établissements eux-mêmes — horaires, services, stocks de médicaments et disponibilités de sang viennent uniquement des établissements qui ont revendiqué leur fiche et les déclarent.",
            ],
            [
                'q' => 'Les établissements sont-ils vérifiés ?',
                'a' => "Non — inscrits, pas vérifiés. Une fiche signifie que l'établissement figure au registre national et dans les sources cartographiques. Ce n'est ni un contrôle d'agrément, ni une inspection, ni une recommandation d'OpesCare. Utilisez le Finder comme n'importe quel annuaire : appelez avant de vous déplacer et gardez votre jugement.",
            ],
            [
                'q' => 'Les stocks de médicaments sont-ils à jour ?',
                'a' => "Les stocks sont déclarés par les pharmacies elles-mêmes, et chaque déclaration est horodatée pour que vous voyiez sa fraîcheur. Les stocks évoluent dans la journée — appelez toujours la pharmacie avant de vous déplacer.",
            ],
            [
                'q' => "J'ai cherché un médicament sans rien trouver. Aucune pharmacie ne l'a donc ?",
                'a' => "Non. Seules les pharmacies qui déclarent leurs stocks apparaissent dans les résultats, et toutes ne le font pas encore. Un résultat vide signifie « non déclaré », pas « non disponible » — ouvrez l'annuaire des pharmacies de votre zone et appelez autour de vous.",
            ],
            [
                'q' => 'Comment signaler un mauvais numéro ou un établissement fermé ?',
                'a' => "Passez par la page contact en indiquant le nom de l'établissement et ce qui ne va pas. Les corrections sont examinées puis la fiche est mise à jour. Si vous travaillez dans cet établissement, revendiquez la fiche et corrigez-la vous-même — elle restera juste durablement.",
            ],
            [
                'q' => 'Comment une pharmacie ou une clinique revendique-t-elle sa fiche ?',
                'a' => "Créez un compte gratuit, retrouvez votre établissement dans l'annuaire et lancez la revendication depuis sa fiche. Un examinateur vérifie votre lien avec l'établissement ; une fois la demande approuvée, vous pouvez corriger le numéro, les horaires et les services, et déclarer vos stocks. La page « Pour les pharmacies » donne le détail.",
            ],
            [
                'q' => 'Mes données personnelles sont-elles protégées ?',
                'a' => "Chercher ne demande ni compte ni donnée personnelle. Si vous créez un compte pour réserver, vous gardez la main sur vos informations : les réglages de confidentialité de votre compte décident qui voit quoi, et la politique de confidentialité explique précisément ce qui est collecté et pourquoi.",
            ],
            [
                'q' => 'Le Finder fonctionne-t-il en français ?',
                'a' => "Oui, entièrement. Chaque page existe en français et en anglais — changez de langue à tout moment depuis l'en-tête de n'importe quelle page.",
            ],
            [
                'q' => "Puis-je réserver dans n'importe quel établissement ?",
                'a' => "Pas encore. Chaque établissement choisit d'accepter ou non la réservation en ligne. Là où elle manque, la fiche vous donne un numéro de téléphone pour appeler et convenir d'une visite.",
            ],
            [
                'q' => 'Que faire en cas d\'urgence ?',
                'a' => "En urgence, ne comptez sur aucune application. Appelez ou rendez-vous au service d'urgence le plus proche. La vue urgences sert uniquement à trouver plus vite l'établissement le plus proche disposant d'un service d'urgence.",
            ],
        ],

        'still_title' => 'Encore une question ?',
        'still_p'     => 'La page contact joint une vraie personne, en français ou en anglais.',
        'still_cta'   => 'Nous contacter',
    ],

    // ── Contact — accroche + cadre « signaler une erreur » ─────────────────
    'contact' => [
        'page_title'       => 'Contact',
        'meta_description' => "Posez une question ou signalez une erreur de fiche — mauvais numéro, horaires changés, établissement fermé. Gratuit, réponse en français ou en anglais.",
        'eyebrow'          => 'Contact',
        'title'            => 'Questions et corrections',
        'lead'             => "Un mauvais numéro, un établissement fermé, une question sur une fiche ou sur le Finder lui-même — vous êtes au bon endroit. Les messages sont lus en français et en anglais.",

        'report_title' => 'Vous signalez une erreur de fiche ?',
        'report_p'     => "Dites-nous quel établissement et ce qui ne va pas — le numéro, les horaires, l'adresse. Les corrections sont examinées puis la fiche est mise à jour.",

        'email_title' => 'Email',
        'email_p'     => 'Vous préférez écrire directement ? Envoyez-nous un email et nous vous répondrons.',

        'privacy_title' => 'Votre message reste privé',
        'privacy_p'     => "Ce que vous envoyez ici sert uniquement à vous répondre et à corriger les fiches. Rien n'est publié, rien n'alimente de liste marketing.",
    ],

    // ── Pour les pharmacies — revendiquer sa fiche ─────────────────────────
    'pharmacies' => [
        'page_title'       => 'Pour les pharmacies',
        'meta_description' => "Revendiquez votre pharmacie sur OpesCare — gratuitement. Soyez trouvé par les patients qui cherchent un médicament, corrigez numéro et horaires, déclarez vos stocks depuis un téléphone.",
        'eyebrow'          => 'Pour les pharmacies',
        'title'            => 'Soyez la pharmacie que les patients trouvent',
        'lead'             => "Chaque jour, des patients cherchent des médicaments sur le Finder. Une fiche revendiquée, c'est vos vrais horaires, un numéro qui répond — et des patients qui savent que vous avez ce qu'il leur faut avant de se déplacer.",

        'found_title' => 'Soyez trouvé',
        'found_p'     => "Votre pharmacie est déjà inscrite via le registre national des formations sanitaires. La revendiquer fait de la fiche la vôtre : position, téléphone, horaires et services exacts.",
        'edit_title'  => 'Corrigez vos informations vous-même',
        'edit_p'      => "Un mauvais numéro ou des horaires dépassés envoient les patients ailleurs. Une fois votre revendication approuvée, vous modifiez votre fiche directement et les changements sont immédiats.",
        'stock_title' => 'Déclarez vos stocks depuis votre téléphone',
        'stock_p'     => "Déclarez les médicaments disponibles en une minute environ, depuis le navigateur de n'importe quel téléphone — rien à installer. Les déclarations sont horodatées, et les patients sont toujours invités à appeler avant de venir.",
        'free_title'  => 'Gratuit, et ça le reste',
        'free_p'      => "Revendiquer votre fiche, la modifier et déclarer vos stocks ne coûtent rien.",

        'steps_title' => 'Comment revendiquer votre fiche',
        'step1'       => 'Créez un compte gratuit.',
        'step2'       => "Retrouvez votre pharmacie dans l'annuaire et ouvrez sa fiche.",
        'step3'       => 'Lancez la revendication et montrez votre lien avec la pharmacie.',
        'step4'       => "Dès qu'un examinateur approuve la demande, mettez votre fiche à jour et commencez à déclarer vos stocks.",

        'note' => "Les revendications sont examinées avant approbation, et seul ce que vous déclarez est affiché. Le Finder n'invente jamais un stock — c'est exactement pourquoi une pharmacie revendiquée qui déclare se démarque.",

        'cta_primary'   => 'Retrouver votre pharmacie',
        'cta_secondary' => 'Créer un compte',
    ],

    // ── Pour les hôpitaux et cliniques — fiche + réservation + encart plateforme ─
    'hospitals' => [
        'page_title'       => 'Pour les hôpitaux et cliniques',
        'meta_description' => "Revendiquez votre établissement sur OpesCare — gratuitement. Gardez téléphone, horaires et services exacts, déclarez les disponibilités de sang, acceptez des rendez-vous.",
        'eyebrow'          => 'Pour les hôpitaux et cliniques',
        'title'            => 'Restez joignable pour les patients',
        'lead'             => "Les patients utilisent le Finder pour choisir un établissement, vérifier les disponibilités de sang, réserver ou appeler. Une fiche revendiquée garde tout cela exact — et fait sonner votre téléphone pour les bonnes raisons.",

        'listing_title' => 'Une fiche exacte',
        'listing_p'     => "Votre établissement est déjà inscrit via le registre national des formations sanitaires. Revendiquez-le pour corriger vous-même numéro, horaires, adresse et services.",
        'blood_title'   => 'Des disponibilités de sang déclarées',
        'blood_p'       => "Si vous assurez un service de transfusion, déclarez vos disponibilités pour que les patients et soignants qui cherchent du sang vous trouvent — avec la consigne claire d'appeler d'abord.",
        'booking_title' => 'Des rendez-vous en ligne',
        'booking_p'     => "Acceptez la réservation en ligne si vous le souhaitez. Sinon, votre fiche donne quand même aux patients le bon numéro à appeler.",

        'steps_title' => 'Comment revendiquer votre établissement',
        'step1'       => 'Créez un compte gratuit.',
        'step2'       => "Retrouvez votre établissement dans l'annuaire et ouvrez sa fiche.",
        'step3'       => "Lancez la revendication et montrez votre lien avec l'établissement.",
        'step4'       => "Une fois la demande approuvée, mettez votre fiche à jour, déclarez vos disponibilités et gérez vos rendez-vous.",

        'platform_title' => 'Vous cherchez la plateforme OpesCare ?',
        'platform_p'     => "La plateforme de santé OpesCare complète pour les hôpitaux et institutions — dossiers de santé, interopérabilité, API développeurs et accords institutionnels — est un produit distinct. Elle vit sur opescare.cloud.",
        'platform_cta'   => 'Aller sur opescare.cloud',

        'cta_primary'   => 'Retrouver votre établissement',
        'cta_secondary' => 'Créer un compte',
    ],

    // ── Pour les patients — un court panneau indicateur vers les recherches ─
    'patients' => [
        'page_title'       => 'Pour les patients et leurs familles',
        'meta_description' => "Ce que vous pouvez faire avec OpesCare : trouver un médicament en pharmacie, du sang à l'hôpital, et des établissements à réserver ou appeler — gratuit, en français et en anglais.",
        'eyebrow'          => 'Pour les patients et leurs familles',
        'title'            => 'Le Finder est fait pour vous',
        'lead'             => "Trouvez un médicament, du sang ou un établissement près de chez vous, voyez ce qui a été déclaré, et appelez avant de vous déplacer. Gratuit, sans compte pour chercher.",
        'honest_p'         => "Une habitude fait bien fonctionner le Finder : appelez avant de venir. Les informations et les stocks sont déclarés par les établissements et peuvent changer dans la journée.",
    ],

    // ── Pages plateforme qui n'ont plus leur place sur ce produit ──────────
    'redirect' => [
        'meta_description' => "Cette page appartient à la plateforme de santé OpesCare pour les hôpitaux et institutions. OpesCare est l'outil gratuit pour trouver des soins au Cameroun.",
        'eyebrow'          => 'OpesCare',
        'heading'          => 'Cette page appartient à la plateforme OpesCare',
        'lead'             => "Vous êtes sur OpesCare — l'outil gratuit pour trouver médicaments, sang et soins au Cameroun. Ce que vous cherchiez fait partie de la plateforme de santé OpesCare pour les hôpitaux et institutions, qui est un produit distinct.",
        'platform_title'   => 'Vous cherchez la plateforme OpesCare ?',
        'platform_p'       => "L'interopérabilité, les API et la documentation développeurs, les tarifs institutionnels, les démonstrations et les accords de service vivent sur le site de la plateforme.",
        'platform_cta'     => 'Aller sur opescare.cloud',
        'consumer_title'   => 'Vous cherchez des soins ?',
        'consumer_p'       => "Le Finder vous aide à localiser établissements, médicaments et sang près de chez vous — gratuitement, en français et en anglais.",
        'privacy_note'     => "Vous vous demandez comment le Finder traite vos données ? La politique de confidentialité l'explique en langage clair.",
        'privacy_cta'      => 'Lire la politique de confidentialité',
        'titles'           => [
            'interoperability' => 'Interopérabilité',
            'developers'       => 'Développeurs',
            'api_pricing'      => 'Tarifs API',
            'request_demo'     => 'Demander une démo',
            'pricing'          => 'Tarifs',
            'security'         => 'Sécurité',
            'sla'              => 'Accord de niveau de service',
            'insurers'         => 'Pour les assureurs',
            'laboratories'     => 'Pour les laboratoires',
            'public_health'    => 'Pour la santé publique',
        ],
    ],

    // ── Boutons consommateur partagés ──────────────────────────────────────
    'cta' => [
        'medicine'  => 'Trouver un médicament',
        'blood'     => 'Trouver du sang',
        'directory' => 'Parcourir les établissements',
        'how'       => 'Comment marche le Finder',
    ],

];
