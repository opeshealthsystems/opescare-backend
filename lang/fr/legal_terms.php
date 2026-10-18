<?php

// Corps des Conditions générales publiques (resources/views/public/terms.blade.php).
// Chaînes HTML de confiance, rédigées par l'éditeur (<strong>/<em>/<a> en ligne
// uniquement), rendues sans échappement par cette vue. Jamais de saisie utilisateur ici.
return [
    'effective_date_value' => '1er juin 2026',
    'last_updated_value'   => '7 juin 2026',
    'governing_law_value'  => 'République du Cameroun',
    'jurisdiction_value'   => 'Tribunaux de Yaoundé, Cameroun',
    'languages_value'      => 'Anglais (version française disponible sur demande)',

    'nav' => [
        'parties'              => 'Parties',
        'platform'             => 'Périmètre de la plateforme',
        'regulatory'           => 'Cadre réglementaire',
        'eligibility'          => 'Éligibilité',
        'provider_obligations' => 'Obligations des professionnels',
        'patient_obligations'  => 'Obligations des patients',
        'data'                 => 'Traitement des données',
        'aup'                  => 'Utilisation acceptable',
        'ip'                   => 'Propriété intellectuelle',
        'availability'         => 'Disponibilité',
        'liability'            => 'Responsabilité',
        'indemnity'            => 'Indemnisation',
        'governing_law'        => 'Droit applicable',
        'termination'          => 'Résiliation',
        'contact'              => 'Contact',
    ],

    'acceptance' => '<strong>En créant un compte, en enregistrant un établissement ou en utilisant tout service OpesCare, vous confirmez avoir lu et compris les présentes Conditions générales et acceptez d\'être lié par elles dans leur intégralité.</strong> Si vous ne les acceptez pas, vous ne devez pas utiliser la plateforme OpesCare. Les institutions qui utilisent la plateforme pour le compte d\'un employeur ou d\'un organisme de santé confirment qu\'un représentant dûment habilité a accepté ces conditions au nom de cette entité.',

    's1_title' => '1. Parties au présent contrat',
    's1_provider' => '<strong>Prestataire de services :</strong> Opes Health Systems Sarl, société constituée selon le droit de la République du Cameroun, exploitant la plateforme d\'interopérabilité en santé OpesCare (« OpesCare », « nous », « notre », « nos »).',
    's1_user'  => '<strong>Utilisateur :</strong> toute personne physique, tout professionnel de santé, toute institution, organisation ou tout développeur (« vous », « votre ») qui accède à la plateforme OpesCare ou l\'utilise à quelque titre que ce soit, notamment en tant que :',
    's1_items' => [
        '<strong>Patient</strong> — personne s\'inscrivant pour obtenir ou gérer un ID de Santé OpesCare',
        '<strong>Professionnel de santé</strong> — professionnel médical agréé ou membre du personnel d\'un établissement de santé enregistré',
        '<strong>Administrateur institutionnel</strong> — entité enregistrant un hôpital, une clinique, une pharmacie, un laboratoire ou un assureur sur la plateforme',
        '<strong>Développeur ou partenaire technologique</strong> — personne ou organisation s\'intégrant à l\'API, au SDK ou aux services associés d\'OpesCare',
    ],

    's2_title' => '2. Nature et périmètre de la plateforme',
    's2_intro' => 'OpesCare est une <strong>plateforme numérique d\'interopérabilité et d\'identité en santé</strong>. Nos services comprennent :',
    's2_items' => [
        'L\'émission et la gestion des ID de Santé numériques OpesCare',
        'L\'échange sécurisé, encadré par le consentement, des dossiers de santé des patients entre établissements de santé enregistrés',
        'Un portail patient pour consulter son dossier de santé, gérer ses consentements et auditer les accès',
        'Des portails pour le personnel et les établissements, en appui aux processus cliniques',
        'API, SDK, widget, agent passerelle (Bridge Agent) et webhooks pour l\'intégration de tiers',
        'Des outils de vérification de la disponibilité des médicaments et du sang',
        'Des rapports de santé publique anonymes et agrégés',
    ],
    's2_not_provider' => '<strong>OpesCare n\'est pas un prestataire de soins.</strong> Nous ne posons aucun diagnostic, ne prescrivons pas, ne traitons pas et ne donnons aucun conseil clinique. Toutes les décisions cliniques relèvent de la seule responsabilité de professionnels de santé qualifiés et agréés. Nous facilitons l\'échange d\'informations de santé pour appuyer — et non remplacer — le jugement clinique.',
    's2_strategy' => 'OpesCare agit en appui à la <strong>Stratégie nationale de santé numérique 2026–2030 du ministère de la Santé publique du Cameroun (MINSANTE)</strong> et à la Stratégie mondiale de l\'OMS pour la santé numérique 2020–2025, avec pour objectif de contribuer à la couverture sanitaire universelle grâce à une infrastructure de santé numérique interopérable et centrée sur le patient.',

    's3_title' => '3. Cadre réglementaire applicable',
    's3_intro' => 'OpesCare opère dans le cadre et dans le respect des instruments juridiques et réglementaires suivants :',
    's3_laws' => [
        ['law' => 'Loi camerounaise n° 2010/012 du 21 décembre 2010', 'desc' => 'Relative à la cybersécurité et à la cybercriminalité — régit la protection des données personnelles, la preuve électronique et la cybercriminalité.'],
        ['law' => 'Loi camerounaise n° 2010/021 du 21 décembre 2010', 'desc' => 'Régissant le commerce électronique — encadre la fourniture de services en ligne, les contrats électroniques et les droits des consommateurs numériques.'],
        ['law' => 'Loi camerounaise n° 2010/013 du 21 décembre 2010', 'desc' => 'Régissant les communications électroniques — encadre les télécommunications et l\'infrastructure des services électroniques.'],
        ['law' => 'Loi camerounaise n° 96/03 du 4 janvier 1996', 'desc' => 'Loi-cadre dans le domaine de la santé — définit l\'organisation du système de santé, les droits des patients et les obligations des acteurs de santé au Cameroun.'],
        ['law' => 'Réglementation de la CNAMGS', 'desc' => 'Caisse Nationale d\'Assurance Maladie et de Garantie Sociale — régit les données d\'assurance maladie et de couverture sociale au Cameroun.'],
        ['law' => 'Stratégie mondiale de l\'OMS pour la santé numérique 2020–2025', 'desc' => 'Cadre international pour la mise en œuvre de la santé numérique, l\'interopérabilité, la gouvernance des données et les droits des patients.'],
        ['law' => 'HL7 FHIR R4 / ISO 27799', 'desc' => 'Normes internationales d\'interopérabilité des données de santé et de management de la sécurité de l\'information dans les organismes de santé.'],
        ['law' => 'Convention de Malabo de l\'Union africaine (2014)', 'desc' => 'Convention de l\'Union africaine sur la cybersécurité et la protection des données à caractère personnel (EX.CL/846(XXV)) — cadre régional de protection des données.'],
        ['law' => 'Stratégie nationale de santé numérique 2026–2030 du MINSANTE', 'desc' => 'Feuille de route nationale du Cameroun pour la numérisation de la santé, le déploiement du dossier médical électronique, l\'interopérabilité et l\'encadrement de la télémédecine.'],
    ],

    's4_title' => '4. Éligibilité et inscription',
    's4_intro' => 'Pour vous inscrire et utiliser la plateforme OpesCare :',
    's4_items' => [
        'Vous devez être âgé d\'au moins 18 ans, ou être un parent ou tuteur inscrivant un mineur.',
        'Vous devez fournir des informations d\'inscription exactes, complètes et à jour, et vous engagez à les mettre à jour rapidement en cas de changement.',
        'Les professionnels de santé doivent détenir une autorisation d\'exercer valide délivrée par l\'autorité professionnelle camerounaise compétente (Ordre des Médecins, Ordre des Pharmaciens, Ordre National des Infirmiers ou équivalent).',
        'Les établissements de santé doivent détenir une autorisation d\'exploitation valide délivrée par le MINSANTE ou l\'autorité sanitaire régionale compétente.',
        'Les développeurs et partenaires technologiques doivent signer un Contrat de partenariat OpesCare distinct avant d\'accéder à l\'environnement API de production.',
        'Aucune loi applicable ne doit vous interdire d\'utiliser la plateforme.',
    ],
    's4_outro' => 'Nous nous réservons le droit de vérifier à tout moment les qualifications et le statut d\'autorisation de tout utilisateur ou de toute institution, et de suspendre ou de résilier les comptes qui ne satisfont pas aux conditions d\'éligibilité.',

    's5_title' => '5. Obligations des professionnels de santé et des institutions',
    's5_intro' => 'Les professionnels de santé et administrateurs institutionnels qui accèdent aux données des patients via OpesCare s\'engagent à :',
    's5_items' => [
        '<strong>Finalité autorisée uniquement :</strong> n\'accéder aux dossiers des patients qu\'à des fins cliniques ou administratives directes et légitimes, liées à la prise en charge du patient ayant donné son consentement.',
        '<strong>Gestion du personnel :</strong> veiller à ce que chaque membre du personnel ayant accès à la plateforme soit enregistré individuellement, formé et autorisé. Il vous appartient de révoquer l\'accès du personnel qui quitte ses fonctions ou change de rôle.',
        '<strong>Intégrité du consentement :</strong> ne soumettre de demandes de consentement qu\'en cas de besoin clinique réel et documenté. La falsification ou la présentation mensongère d\'une finalité clinique constitue une infraction pénale en droit camerounais.',
        '<strong>Exactitude des dossiers :</strong> veiller à ce que les données cliniques transmises à OpesCare soient exactes, complètes et enregistrées en temps utile, conformément aux obligations professionnelles.',
        '<strong>Minimisation des données :</strong> ne demander et ne consulter que les catégories de données cliniques nécessaires à la finalité clinique déclarée.',
        '<strong>Responsabilité en cas d\'accès d\'urgence :</strong> les professionnels qui recourent à l\'accès d\'urgence doivent consigner une justification clinique documentée et font l\'objet d\'un examen a posteriori par l\'équipe de gouvernance clinique de leur établissement et par les responsables de la conformité d\'OpesCare.',
        '<strong>Signalement des violations :</strong> informer OpesCare immédiatement (et en tout état de cause dans les 24 heures) dès qu\'ils ont connaissance d\'une violation de données avérée ou suspectée, d\'un accès non autorisé ou d\'une utilisation abusive des identifiants de la plateforme.',
        '<strong>Conformité réglementaire :</strong> maintenir en règle toutes les autorisations professionnelles, autorisations d\'exploitation et inscriptions réglementaires pendant toute la durée d\'utilisation de la plateforme.',
        '<strong>Aucun partage ultérieur :</strong> ne pas transférer, copier, imprimer, photographier ni divulguer de toute autre manière à un tiers non autorisé les données de patients obtenues via OpesCare.',
    ],

    's6_title' => '6. Obligations des patients utilisateurs',
    's6_intro' => 'Les patients qui utilisent la plateforme OpesCare s\'engagent à :',
    's6_items' => [
        '<strong>Informations d\'identité exactes :</strong> fournir des informations d\'identité exactes lors de l\'inscription. Vous ne devez pas créer d\'ID de Santé en utilisant l\'identité d\'une autre personne.',
        '<strong>Sécurité des identifiants :</strong> protéger vos identifiants de connexion, votre ID de Santé et votre code QR. Vous ne devez pas partager l\'accès à votre compte avec une autre personne. Prévenez-nous immédiatement à <a href="mailto:support@opescare.com">support@opescare.com</a> si vous pensez que votre compte a été compromis.',
        '<strong>Gestion responsable du consentement :</strong> exercer vos droits en matière de consentement de manière responsable. Vous reconnaissez que la révocation du consentement accordé à un professionnel participant à vos soins en cours peut affecter la qualité ou la continuité de votre traitement.',
        '<strong>Signalements de bonne foi :</strong> ne pas soumettre, via la plateforme, de signalements, réclamations ou plaintes mensongers concernant des établissements ou des professionnels.',
        '<strong>Un seul compte par personne :</strong> ne détenir qu\'un seul ID de Santé OpesCare par personne. La création d\'identités en double compromet la sécurité des patients et est interdite.',
    ],

    's7_title' => '7. Traitement des données de santé',
    's7_privacy' => 'Le traitement des données personnelles et de santé via OpesCare est régi en détail par notre <a href=":url" class="text-primary font-bold">Politique de confidentialité</a>, qui fait partie intégrante des présentes Conditions. En acceptant ces Conditions, vous prenez acte du traitement décrit dans la Politique de confidentialité et y consentez.',
    's7_dpa'   => '<strong>Accord institutionnel de traitement des données :</strong> les établissements de santé enregistrés sont réputés avoir conclu un accord de traitement des données (DPA) avec Opes Health Systems Sarl en acceptant les présentes Conditions. Ce DPA régit le rôle de sous-traitant d\'OpesCare pour les dossiers cliniques provenant des établissements. Les institutions qui ont besoin d\'un DPA signé distinct pour leurs propres besoins de conformité doivent contacter <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.',
    's7_ownership' => '<strong>Propriété des dossiers :</strong> les dossiers cliniques hébergés sur OpesCare restent la propriété du patient. L\'établissement de santé d\'origine demeure responsable de l\'exactitude et de l\'exhaustivité des dossiers qu\'il a transmis. OpesCare agit en tant qu\'intermédiaire de données et ne détient ni ne revendique aucun droit de propriété intellectuelle sur les données de santé des patients.',

    's8_title'  => '8. Politique d\'utilisation acceptable',
    's81_title' => '8.1 Utilisations autorisées',
    's81_items' => [
        'Consulter et gérer votre propre ID de Santé et vos dossiers médicaux en tant que patient',
        'Demander, accorder ou révoquer un consentement d\'accès aux dossiers cliniques',
        'Consulter, en tant que professionnel de santé autorisé, les dossiers de patients pour lesquels vous disposez d\'un consentement valide et en cours',
        'Intégrer les services OpesCare dans des applications de santé dans le cadre d\'un Contrat de partenariat valide',
        'Utiliser les outils de disponibilité des médicaments et du sang comme aide à la décision clinique',
        'Examiner les journaux d\'accès et les entrées de la piste d\'audit et y donner suite',
    ],
    's82_title' => '8.2 Utilisations interdites',
    's82_intro' => 'Les activités suivantes sont strictement interdites et peuvent entraîner la suspension immédiate du compte, sa résiliation définitive et un signalement aux autorités compétentes, notamment l\'ANTIC, le MINSANTE, les ordres professionnels et les juridictions camerounaises :',
    's82_items' => [
        'Accéder aux dossiers des patients sans consentement valide, en dehors du protocole documenté d\'accès d\'urgence',
        'Utiliser les identifiants, le code QR ou l\'ID de Santé d\'une autre personne sans son autorisation explicite',
        'Créer ou utiliser de fausses justifications cliniques pour accéder aux données des patients',
        'Tenter de faire de l\'ingénierie inverse, de contourner ou d\'attaquer la plateforme OpesCare ou ses contrôles de sécurité',
        'Extraire par moissonnage, télécharger en masse ou extraire systématiquement des données de patients au-delà de ce qui est nécessaire à une prise en charge clinique individuelle',
        'Utiliser des données de patients obtenues via OpesCare à des fins de souscription d\'assurance, de décisions d\'emploi, de répression ou de marketing sans base légale ni consentement explicite du patient',
        'Partager des identifiants d\'API, des jetons d\'accès ou des données d\'authentification avec des personnes non autorisées',
        'Soumettre à la plateforme des dossiers médicaux faux, trompeurs ou fabriqués',
        'Utiliser la plateforme pour faciliter toute activité contraire au droit camerounais, aux normes internationales en matière de données de santé ou à la déontologie médicale',
        'Reproduire, publier ou diffuser tout ou partie de la plateforme OpesCare, de sa documentation ou des données des patients sans autorisation écrite',
    ],

    's9_title' => '9. Propriété intellectuelle',
    's9_paras' => [
        'Tous les droits relatifs à la plateforme OpesCare, y compris les logiciels, la conception, les marques, les logos, la documentation, les bases de données et les éléments d\'interface utilisateur, sont la propriété intellectuelle exclusive d\'Opes Health Systems Sarl ou de ses concédants, protégée par les lois de la République du Cameroun, par l\'Organisation Africaine de la Propriété Intellectuelle (OAPI) et par les traités internationaux applicables en matière de propriété intellectuelle.',
        'Aucune utilisation, reproduction, diffusion ni création d\'œuvres dérivées de la propriété intellectuelle d\'OpesCare n\'est autorisée sans le consentement écrit préalable d\'Opes Health Systems Sarl.',
        'Les données de santé des patients et les dossiers produits par les cliniciens sont expressément exclus de ce qui précède ; ils demeurent respectivement la propriété des patients et des établissements d\'origine.',
        'Les composants open source utilisés dans la plateforme OpesCare sont soumis à leurs licences open source respectives. Une liste est disponible sur demande écrite.',
    ],

    's10_title' => '10. Disponibilité du service et maintenance',
    's10_target' => 'OpesCare vise une disponibilité minimale de 99,5 % pour ses services essentiels (registre des ID de Santé, moteur de consentement, portail patient et API). Les fenêtres de maintenance planifiée seront annoncées au moins 48 heures à l\'avance sur la page <a href=":url" class="text-primary">État du système</a> et par notification sur la plateforme.',
    's10_paras' => [
        'Nous nous réservons le droit d\'effectuer une maintenance d\'urgence à tout moment lorsque cela est nécessaire pour protéger la sécurité ou l\'intégrité de la plateforme ou des données des patients.',
        'OpesCare ne garantit pas une disponibilité continue et ininterrompue. Nous ne sommes pas responsables des interruptions de service causées par des fournisseurs d\'infrastructure tiers, des réseaux de télécommunications, des cas de force majeure ou des facteurs échappant à notre contrôle raisonnable.',
    ],

    's11_title' => '11. Limitation de responsabilité',
    's11_intro' => 'Dans toute la mesure permise par le droit camerounais et la réglementation applicable :',
    's11_items' => [
        '<strong>Rôle de la plateforme :</strong> OpesCare fournit une infrastructure d\'échange de données de santé et n\'est pas responsable de l\'exactitude clinique, de l\'exhaustivité ou de l\'actualité des dossiers médicaux transmis par les établissements de santé. Toutes les décisions cliniques doivent être prises par des professionnels de santé qualifiés et agréés, selon leur jugement professionnel.',
        '<strong>Aucune responsabilité clinique :</strong> OpesCare décline expressément toute responsabilité quant aux résultats cliniques, qu\'ils découlent de l\'utilisation ou de l\'impossibilité d\'utiliser les dossiers de patients échangés via la plateforme.',
        '<strong>Dommages indirects :</strong> OpesCare ne saurait être tenue responsable de dommages indirects, consécutifs, accessoires, spéciaux ou exemplaires résultant de l\'utilisation ou de l\'impossibilité d\'utiliser la plateforme, y compris la perte de bénéfices, la perte de données ou l\'interruption d\'activité.',
        '<strong>Plafond de responsabilité :</strong> dans la mesure permise par la loi, notre responsabilité totale envers un utilisateur au titre des présentes Conditions ne saurait excéder les sommes versées par cet utilisateur à OpesCare au cours des douze (12) mois précédant le fait générateur de la réclamation.',
        '<strong>Exception relative à la sécurité des patients :</strong> aucune disposition de la présente clause ne limite notre responsabilité en cas de décès ou de dommage corporel causé par notre faute lourde ou intentionnelle, en cas de fraude ou de déclaration frauduleuse, ni pour toute responsabilité qui ne peut être exclue ou limitée en droit camerounais.',
    ],
    's11_outro' => 'Les professionnels de santé assument l\'entière et seule responsabilité professionnelle des décisions cliniques prises sur la base d\'informations consultées via OpesCare, conformément à leurs obligations au titre de la loi camerounaise n° 96/03 et aux normes de leur ordre professionnel.',

    's12_title' => '12. Indemnisation',
    's12_intro' => 'Vous vous engagez à défendre, indemniser et garantir Opes Health Systems Sarl, ses dirigeants, administrateurs, employés, mandataires et sous-traitants contre toute réclamation, responsabilité, dommage, jugement, condamnation, perte, coût, dépense et frais juridiques découlant de ou liés à :',
    's12_items' => [
        'Votre violation des présentes Conditions',
        'Votre utilisation abusive des données de patients consultées via la plateforme',
        'Votre accès non autorisé aux dossiers de patients',
        'Tout manquement à vos obligations professionnelles en tant que professionnel de santé',
        'Toute réclamation d\'un tiers découlant de votre utilisation des services OpesCare',
    ],

    's13_title' => '13. Force majeure',
    's13_body'  => 'Aucune des parties ne saurait être considérée comme ayant manqué aux présentes Conditions en raison d\'un retard ou d\'une inexécution causés par des circonstances échappant à son contrôle raisonnable, notamment : catastrophes naturelles, guerre, terrorisme, troubles civils, mesures des autorités publiques, urgences de santé publique déclarées par l\'OMS ou le MINSANTE, défaillances des télécommunications ou pannes généralisées de l\'infrastructure Internet. Les cas de force majeure seront notifiés sans délai et les obligations d\'exécution seront suspendues pendant toute la durée de l\'événement.',

    's14_title' => '14. Droit applicable et règlement des litiges',
    's14_paras' => [
        '<strong>Droit applicable :</strong> les présentes Conditions sont régies et interprétées conformément au droit de la <strong>République du Cameroun</strong>, notamment la loi n° 2010/012, la loi n° 2010/021 et la loi-cadre dans le domaine de la santé n° 96/03.',
        '<strong>Règlement amiable :</strong> en cas de litige découlant des présentes Conditions ou en lien avec elles, les parties conviennent de tenter d\'abord de le régler à l\'amiable par voie de négociation directe pendant une période de trente (30) jours à compter de la notification écrite du litige.',
        '<strong>Juridiction :</strong> à défaut de règlement amiable dans ce délai, le litige sera soumis à la compétence exclusive des juridictions compétentes de <strong>Yaoundé, Cameroun</strong>. Les deux parties se soumettent irrévocablement à la compétence de ces juridictions.',
        '<strong>Langue :</strong> la procédure se déroulera en français ou en anglais, selon ce que décidera la juridiction saisie.',
    ],

    's15_title'  => '15. Suspension et résiliation',
    's151_title' => '15.1 Par OpesCare',
    's151_intro' => 'Nous nous réservons le droit de suspendre ou de résilier votre accès à OpesCare immédiatement et sans préavis si :',
    's151_items' => [
        'Vous enfreignez une disposition des présentes Conditions',
        'Nous recevons de l\'ANTIC, du MINSANTE ou d\'une juridiction une décision légale exigeant la suspension ou la fermeture de votre compte',
        'Vous adoptez un comportement présentant un risque pour la sécurité ou l\'intégrité de la plateforme ou des données des patients',
        'Votre autorisation d\'exercer ou l\'autorisation d\'exploitation de votre établissement est suspendue ou retirée par l\'autorité compétente',
        'Nous soupçonnons raisonnablement une utilisation frauduleuse, abusive ou illicite de la plateforme',
    ],
    's151_outro' => 'Pour les situations moins urgentes, nous nous efforcerons de respecter un préavis de 14 jours avant la résiliation.',
    's152_title' => '15.2 Par vous',
    's152_body'  => 'Les patients peuvent demander la clôture de leur compte à tout moment en contactant <a href="mailto:support@opescare.com">support@opescare.com</a>. Les établissements de santé peuvent résilier leur contrat de plateforme moyennant un préavis écrit de 60 jours adressé à <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.',
    's153_title' => '15.3 Effets de la résiliation',
    's153_intro' => 'En cas de résiliation :',
    's153_items' => [
        'Votre accès à la plateforme et à toutes les données associées sera révoqué',
        'Les dossiers de santé restent accessibles au patient dans un format portable (export HL7 FHIR R4 / JSON) pendant 90 jours après la clôture du compte',
        'Les données sont conservées pendant les durées minimales exigées par la loi (voir la section 6 de la Politique de confidentialité) avant leur destruction sécurisée',
        'Les clauses relatives à la propriété intellectuelle, à la protection des données, à la responsabilité, à l\'indemnisation et au droit applicable survivent à la résiliation',
    ],

    's16_title' => '16. Modifications des présentes Conditions',
    's16_body'  => 'Nous pouvons mettre à jour les présentes Conditions pour tenir compte de l\'évolution de la loi, de nos services ou des exigences réglementaires. Nous vous informerons de toute modification importante au moins <strong>30 jours</strong> avant son entrée en vigueur, par e-mail et par notification sur la plateforme. La poursuite de votre utilisation d\'OpesCare après la date d\'effet des Conditions mises à jour vaut acceptation. Si vous n\'acceptez pas les Conditions mises à jour, vous devez cesser d\'utiliser la plateforme et demander la clôture de votre compte.',

    's17_title' => '17. Dispositions diverses',
    's17_paras' => [
        '<strong>Divisibilité :</strong> si une disposition des présentes Conditions est jugée invalide, illicite ou inapplicable par une juridiction compétente, les autres dispositions conservent leur plein effet.',
        '<strong>Renonciation :</strong> le fait pour OpesCare de ne pas exercer, ou d\'exercer tardivement, un droit prévu par les présentes Conditions ne vaut pas renonciation à ce droit.',
        '<strong>Intégralité de l\'accord :</strong> les présentes Conditions, ainsi que la Politique de confidentialité, le Cadre de consentement OpesCare et tout Contrat de partenariat applicable, constituent l\'intégralité de l\'accord entre vous et Opes Health Systems Sarl concernant la plateforme OpesCare et remplacent tous accords, déclarations et arrangements antérieurs.',
        '<strong>Cession :</strong> vous ne pouvez céder vos droits ou obligations au titre des présentes Conditions sans notre consentement écrit préalable. OpesCare peut céder ses droits à une entité qui lui succède en cas de fusion, d\'acquisition ou de restructuration, à condition que celle-ci assume l\'ensemble des obligations prévues par les présentes Conditions.',
        '<strong>Langue :</strong> les présentes Conditions sont publiées en anglais. Une traduction française est disponible sur demande. En cas de contradiction entre les versions anglaise et française, la version anglaise prévaut, sauf décision contraire des juridictions camerounaises.',
    ],

    's18_title' => '18. Contact',
    's18_card'  => '<strong>Questions juridiques :</strong> <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a><br><strong>Protection des données :</strong> <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a><br><strong>Assistance technique :</strong> <a href="mailto:support@opescare.com">support@opescare.com</a><br><strong>Partenariats :</strong> <a href="mailto:partners@opescare.com">partners@opescare.com</a><br><strong>Signalement de vulnérabilités de sécurité :</strong> <a href="mailto:security@opeshealthsystems.com">security@opeshealthsystems.com</a>',
];
