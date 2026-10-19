---
title: "La plateforme derrière l'application : chaque guichet d'OpesCare"
slug: "la-plateforme-derriere-l-application"
description: "Jude Nshome présente la plateforme web d'OpesCare : un index patient unique, l'ingestion FHIR et des guichets pharmacie, labo et sang sur un même dossier."
date: 2026-10-19
updated: 2026-10-19
author: "Jude Nshome"
author_bio: "Jude Nshome est le fondateur d'OpesCare, la plateforme de santé bilingue conçue par OPES Health Systems au Cameroun. Il écrit sur les dossiers de santé détenus par les patients, le consentement et la construction d'infrastructures de santé numérique dans les contextes à ressources limitées."
featured: false
downloadable: true
category: "Produit"
tags: ["plateforme", "index maître des patients", "interopérabilité", "FHIR", "pharmacie", "laboratoire", "banque de sang", "Cameroun"]
translation_of: "the-platform-behind-the-app"
hero_alt: "La plateforme web d'OpesCare : un index patient unique et un même dossier partagé par la pharmacie, le laboratoire, la banque de sang et l'accueil"
key_takeaways:
  - "La plateforme web est la moitié institutionnelle d'OpesCare : le dossier que le patient porte sur son téléphone est celui sur lequel travaillent la pharmacie, le laboratoire, la banque de sang et l'accueil."
  - "L'index maître des patients repère chaque nuit les doublons probables, mais rien ne fusionne automatiquement : une personne examine chaque paire, et toute fusion peut être annulée."
  - "L'interopérabilité commence par l'entrée : les partenaires envoient des données FHIR R4 par une ingestion restreinte et auditée, qui refuse ce qu'elle ne sait pas placer. Le patient peut exporter son propre dossier en FHIR ; il n'existe pas de serveur FHIR sortant."
  - "Les codes cliniques proviennent de vocabulaires publiés, chargés comme des données — CIM-10, LOINC, ATC et UCUM — et non saisis en texte libre."
  - "Chaque guichet est un circuit à étapes : les ordonnances sont acceptées, préparées puis délivrées ; les résultats sont saisis, validés puis publiés au patient et au prescripteur en même temps ; les demandes de sang sont décidées, préparées puis servies."
  - "Les limites sont dites clairement : le rapportage DHIS2 est agrégé et attend l'accès du MINSANTE, les paiements attendent les comptes marchands, et OpesCare n'est pas un système d'information hospitalier."
faq:
  - q: "Quelle différence entre l'application OpesCare et la plateforme OpesCare ?"
    a: "L'application est ce que le patient — ou le clinicien en déplacement — tient en main. La plateforme est l'application web d'opescare.com qu'utilisent les établissements : consoles de pharmacie, de laboratoire, d'imagerie, de banque de sang, d'accueil et d'administration, ainsi que l'API à laquelle parlent l'application et les partenaires. Toutes deux travaillent sur le même dossier."
  - q: "Comment OpesCare évite-t-il de créer des patients en double ?"
    a: "À l'accueil, l'enregistrement et la prise de rendez-vous se font avec le Health ID exact, et chaque chemin de création de patient vérifie d'abord qu'une identité n'existe pas déjà. Un balayage nocturne signale les doublons probables au guichet de l'index maître des patients. Une personne tranche chaque paire ; les fusions sont journalisées et réversibles."
  - q: "OpesCare échange-t-il des données avec les systèmes hospitaliers en FHIR ?"
    a: "En entrée, oui : les partenaires agréés peuvent envoyer, avec des identifiants restreints, des comptes rendus diagnostiques, des observations, des vaccinations et des transactions FHIR R4. OpesCare n'exploite pas de serveur FHIR sortant. Le patient peut exporter son propre dossier sous forme de bundle FHIR depuis l'application."
  - q: "Une pharmacie peut-elle gérer son stock dans OpesCare ?"
    a: "Oui, pour ce dont elle a besoin pour être trouvée et pour délivrer : niveaux de stock, lots, réceptions, ajustements, notes de réapprovisionnement, liste de prix et import par tableur. Les bons de commande, les achats et les contrats fournisseurs sont hors périmètre."
  - q: "Qui est prévenu quand un résultat d'analyse est publié ?"
    a: "Le patient et le clinicien qui a prescrit l'analyse, dans la même étape. Une valeur critique est signalée en priorité haute, et le laboratoire consigne son rappel téléphonique au clinicien."
  - q: "OpesCare envoie-t-il des données au DHIS2 ?"
    a: "Uniquement des chiffres agrégés, jamais de données individuelles, et seulement après qu'un administrateur de la plateforme a approuvé chaque brouillon mensuel. Les brouillons sont générés aujourd'hui ; rien n'est envoyé tant que le MINSANTE n'a pas ouvert l'accès à l'instance nationale."
  - q: "OpesCare gère-t-il les sinistres d'assurance ou les hospitalisations ?"
    a: "Non. Les sinistres d'assurance, les ententes préalables, les admissions, sorties, transferts et la gestion des lits sont volontairement hors périmètre. Un registre des services et des lits existe uniquement pour qu'un établissement puisse compter son occupation."
  - q: "Un développeur peut-il s'intégrer à OpesCare aujourd'hui ?"
    a: "Oui. Le portail développeurs délivre des identifiants d'application, permet de s'abonner aux webhooks, de consulter les livraisons et de les rejouer, et la documentation sur opescare.com/docs couvre l'API, l'authentification, les webhooks et les flux consentement, QR, FHIR et index maître des patients."
sources:
  - title: "Architecture de la plateforme — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/architecture?lang=fr"
    accessed: 2026-10-19
  - title: "Flux de l'index maître des patients — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/mpi?lang=fr"
    accessed: 2026-10-19
  - title: "Flux d'ingestion FHIR R4 — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/fhir?lang=fr"
    accessed: 2026-10-19
  - title: "Flux de consentement — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/consent?lang=fr"
    accessed: 2026-10-19
  - title: "Webhooks — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/webhooks?lang=fr"
    accessed: 2026-10-19
  - title: "Spécification HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
  - title: "LOINC — Logical Observation Identifiers Names and Codes"
    publisher: "Regenstrief Institute"
    url: "https://loinc.org/"
    accessed: 2026-10-19
  - title: "Classification internationale des maladies, 10e révision (CIM-10)"
    publisher: "Organisation mondiale de la Santé"
    url: "https://icd.who.int/browse10/2019/en"
    accessed: 2026-10-19
  - title: "Index ATC/DDD"
    publisher: "Centre collaborateur de l'OMS pour la méthodologie des statistiques sur les médicaments"
    url: "https://atcddd.fhi.no/atc_ddd_index/"
    accessed: 2026-10-19
  - title: "The Unified Code for Units of Measure (UCUM)"
    publisher: "Regenstrief Institute"
    url: "https://ucum.org/"
    accessed: 2026-10-19
---

> La plateforme web n'est pas un panneau d'administration greffé sur une application. C'est l'autre face du même dossier : un index patient unique, une seule porte d'entrée pour les données des partenaires, et le travail quotidien des pharmacies, des laboratoires et des banques de sang, repensé pour partager un dossier plutôt que le recopier.

L'application destinée aux patients, décrite dans [Le dossier est le produit](/blog/le-dossier-est-le-produit), est la moitié visible d'OpesCare. L'autre moitié est la **plateforme web** d'opescare.com, au service des établissements : la pharmacie indépendante, le centre de santé de district, le laboratoire d'analyses, le service d'imagerie, la banque de sang — et l'hôpital qui réunit plusieurs d'entre eux.

Cet article en fait la visite : l'**index maître des patients**, qui établit une fois pour toutes qui est le patient ; la **couche d'interopérabilité**, qui permet aux systèmes partenaires d'envoyer leurs données ; et les **circuits de guichet** — enregistrement, triage, file d'attente, prescription, délivrance, du prélèvement à la publication, demandes de sang — construits autour du dossier que le patient porte sur son téléphone. Il dit aussi clairement ce que la plateforme n'est pas. OpesCare est un produit de découverte, d'identité et de coordination. Ce n'est pas un système d'information hospitalier, et il ne prétend pas l'être.

{{diagram:architecture}}

## 1. Ce qu'est la plateforme

Une seule application Laravel sur PostgreSQL sert le site public, les consoles des établissements et une surface `/api` commune à l'application mobile et aux intégrations partenaires. Chaque établissement est accueilli, accompagné par un administrateur de la plateforme à travers une liste de mise en service et un score de préparation, puis participe à l'index patient partagé tout en faisant tourner ses propres guichets.

{{diagram:facility-go-live}}

| Qui | Ce qu'il utilise sur le web aujourd'hui |
|---|---|
| Direction de l'établissement | Invitations et rôles de l'équipe, réglages de l'établissement, registre des services et des lits pour compter l'occupation, factures émises |
| Accueil | Guichet de triage avec réévaluation ; la file du jour — arrivée, appel, début, fin, absence |
| Clinicien | Prescription avec contrôle des interactions, demandes d'analyses, orientations, vaccinations, dossier consulté dans les limites du consentement |
| Pharmacie | Ordonnances et délivrance, commandes de médicaments des patients, stock, lots, réceptions, ajustements, notes de réapprovisionnement, prix, import de stock, registre des médicaments contrôlés, rapports |
| Laboratoire | Demandes d'analyses, prélèvement, résultats, validation, publication, tableau de bord du responsable avec rappels des valeurs critiques |
| Imagerie | Demandes d'imagerie planifiées, réalisées et rapportées, et un moteur public de recherche d'examens |
| Banque de sang | Inventaire par produit sanguin et par groupe, et guichet des demandes de sang |
| Administration de la plateforme | Guichet de l'index maître des patients, accueil et préparation des établissements, identifiants partenaires, certifications d'intégration, rapportage agrégé DHIS2, registre des violations, documents juridiques |
| Développeurs | Applications et identifiants, abonnements webhooks, historique des livraisons et rejeu, documentation |

Rien de tout cela n'est une maquette. Chaque élément a une route enregistrée, un contrôleur, une page et un lien dans la navigation du rôle qui l'utilise. Et surtout, ce ne sont pas des produits séparés : un patient, un modèle de consentement, une piste d'audit.

## 2. L'index maître des patients : qui est cette personne

Un réseau national échoue en silence si la même personne y existe deux fois. L'index maître des patients (IMP) est donc un processus encadré, pas une barre de recherche.

- **Des identifiants exacts au guichet.** À l'accueil, l'enregistrement et la prise de rendez-vous se font avec le Health ID exact. Un guichet ne peut pas parcourir la population en tapant un bout de nom ; si un enregistrement ressemble à une identité existante, il est refusé plutôt que dédoublé. Tous les chemins de création de patient passent par le même contrôle de doublon.
- **La détection est planifiée.** Un balayage nocturne examine les inscriptions récentes, un balayage hebdomadaire regarde plus large. Tous deux signalent des *paires candidates* ; aucun ne fusionne quoi que ce soit.
- **Une personne tranche.** Au guichet de l'IMP, un administrateur examine chaque paire, éléments à l'appui, puis fusionne ou rejette. Le personnel peut aussi signaler une paire à la main.
- **Les fusions sont réversibles.** Une fusion note quel dossier est conservé et garde l'autre identifiant comme alias, si bien qu'un ancien Health ID ou un ancien QR code renvoie toujours à la bonne personne. Une fusion peut être annulée, la plus récente d'abord, depuis le même guichet.
- **La carte correspond à une seule ligne.** Les jetons QR ont une courte durée de vie et le Health ID se résout par la table des alias : la carte que tient le patient désigne exactement une entrée de l'index.

{{diagram:mpi}}

C'est là que repose l'argument « une personne, plusieurs établissements ». Les questions auxquelles un index national doit répondre — *qui est-ce ?* et, un an plus tard, *figure-t-il deux fois ?* — trouvent leur réponse dans un processus avec revue humaine et piste d'audit, et non dans la manière dont quelqu'un a tapé un nom.

## 3. La couche d'interopérabilité

OpesCare traite l'interopérabilité comme une discipline qui commence par ce qui entre.

### FHIR en entrée, et l'export du patient en sortie

- **L'ingestion des partenaires.** Les partenaires agréés envoient des ressources FHIR R4 — DiagnosticReport, Observation, Immunization et bundles de transaction — à un point d'entrée restreint doté d'une déclaration de capacités publiée. Chaque partenaire détient des identifiants porteurs limités aux périmètres accordés par un administrateur. Une garde vérifie que le partenaire a le droit d'écrire au sujet de ce patient et que la ressource peut être placée ; sinon, l'ingestion **refuse ouvertement** au lieu de ranger la donnée au mauvais endroit.
- **L'export du patient.** Depuis l'application mobile, le patient peut exporter son propre dossier en bundle FHIR, et en importer un.
- **Ce qui est absent.** Il n'existe pas de serveur FHIR sortant interrogeable par des tiers, ni d'interface HL7 v2. C'est un choix : le dossier quitte OpesCare quand le patient l'emporte, pas quand un système le demande.

{{diagram:fhir}}

### Des vocabulaires comme données, pas comme texte

Diagnostics, analyses, médicaments et unités sont codés selon des vocabulaires publiés, importés comme des données : la **CIM-10** de l'Organisation mondiale de la Santé, avec ses libellés français ; **LOINC** pour les observations de laboratoire ; **ATC** pour les médicaments ; et **UCUM** pour les unités de mesure, afin que milligrammes et micromoles par litre ne se confondent jamais lors d'une transmission. Un dictionnaire de codes les coiffe, pour qu'un code reçu par ingestion et un code choisi au guichet veuillent dire la même chose.

### Une surface développeurs avec garde-fous

Le portail développeurs permet à une organisation d'enregistrer des applications et d'obtenir des identifiants, de s'abonner aux **webhooks**, de consulter chaque livraison et d'en **rejouer** une qui a échoué. Un partenaire peut aussi transmettre ses niveaux de stock de pharmacie et de sang par l'API Connect, qui n'alimente la recherche que si la source figure sur la liste autorisée. Les administrateurs de la plateforme tiennent une liste de contrôle de certification des intégrations. La documentation est sur [opescare.com/docs](/docs/architecture?lang=fr). Un éditeur de logiciel de laboratoire peut s'intégrer à une surface autorisée et auditée plutôt qu'à un fil de courriels.

### Rapportage national, agrégé uniquement

OpesCare peut transmettre ses propres **chiffres mensuels agrégés** — jamais de données individuelles — à l'instance nationale DHIS2 du MINSANTE. Les établissements sont rattachés à des unités d'organisation et les chiffres à des éléments de données ; un brouillon est généré le premier de chaque mois, et un administrateur de la plateforme l'examine et l'approuve avant tout envoi. Aujourd'hui, les brouillons sont générés mais rien n'est transmis : cela attend que le MINSANTE ouvre l'accès à l'instance nationale. Il s'agit d'un rapportage à sens unique, pas d'un échange de données de santé.

## 4. L'accueil et la consultation

Pour un centre de santé, la plateforme couvre les moments de la journée qui concernent les patients d'OpesCare :

- **Le triage** enregistre les constantes au regard des normes adultes et permet la réévaluation. Il réordonne la file selon l'urgence ; il ne raccourcit l'attente de personne et ne bloque jamais les soins.
- **La file d'attente** mène le patient de l'arrivée à l'appel, au début puis à la fin de la prise en charge, ou le note absent ; elle est triée côté serveur, si bien que tous les écrans voient le même ordre.
- **La prescription** passe par un contrôle des interactions avant l'émission, et l'ordonnance imprimée porte un code de vérification.
- **Les orientations** sont envoyées, acceptées, menées à terme ou refusées entre cliniciens ; le patient peut en demander une depuis l'application.
- **Les vaccinations** sont enregistrées au nom du patient et peuvent être annulées, jamais modifiées en silence.
- **Les factures** peuvent être émises et réglées par mobile money — indisponible tant que les comptes marchands ne sont pas connectés — et les soins ne sont jamais conditionnés au paiement.

Ce que le côté clinique *n'est pas* : une documentation clinique complète, les admissions, sorties et transferts, la gestion des lits, les sinistres d'assurance et les ententes préalables. Un registre des services et des lits existe pour qu'un établissement puisse déclarer son occupation ; c'est un registre, pas un système d'admission.

## 5. La pharmacie : pas une liste de stock, un circuit

Une pharmacie indépendante dispose d'une console complète, construite autour de la délivrance.

- **Les ordonnances arrivent dans une seule liste** — des cliniciens sur OpesCare comme des patients qui les partagent — et passent par *acceptée → prête → délivrée*. Une ordonnance rédigée dans un établissement peut être servie dans un autre : le patient l'emporte avec lui.
- **Les commandes des patients** — les réservations de médicaments faites depuis l'application — suivent leur propre parcours : acceptée, prête, puis retirée ou expédiée et livrée, ou annulée.
- **Le stock, c'est ce que la pharmacie détient vraiment** : niveaux et lots, réceptions des livraisons, ajustements motivés, notes de réapprovisionnement, liste de prix avec historique, et import par tableur avec aperçu, correspondance des colonnes et rapport d'erreurs avant toute validation.
- **Les médicaments contrôlés** ont leur propre registre.
- **Les rapports** exportent les chiffres de délivrance et de stock en CSV ou en PDF.

{{diagram:pharmacy-dispense}}

Deux limites comptent. Le stock déclaré par une pharmacie ne devient une disponibilité *trouvable* que si sa source figure sur la liste autorisée : une recherche vide est la réponse honnête, plutôt qu'une démonstration remplie d'un stock inventé. Et les achats — bons de commande, contrats fournisseurs — ne font pas partie d'OpesCare ; une simple liste de fournisseurs existe pour que les réceptions indiquent d'où vient le stock.

## 6. Le laboratoire : du prélèvement à la publication

Les laboratoires suivent un circuit de publication, pas une boîte de réception.

- **Les demandes** des cliniciens ou des patients sont acceptées ou refusées par le laboratoire.
- **Les analyses** passent par le prélèvement, la saisie des résultats, la validation et la **publication**. Un résultat publié peut être corrigé, mais la correction est consignée, jamais écrasée.
- **La publication prévient les deux côtés à la fois.** Le patient reçoit le résultat dans son application, et le clinicien prescripteur est informé dans la même étape. Une valeur critique est signalée en priorité haute, et le laboratoire consigne son rappel téléphonique au clinicien.
- **Un tableau de bord du responsable** affiche les délais, le travail en attente et les alertes critiques.

{{diagram:lab-release}}

**L'imagerie** suit la même logique pour les demandes : demandée, planifiée, commencée, réalisée, rapportée, puis finalisée et diffusée, avec une possibilité d'amendement. OpesCare conserve le compte rendu et sa pièce jointe ; ce n'est pas une archive d'images, et il n'y a ni PACS ni visionneuse DICOM.

## 7. La banque de sang : l'offre rencontre la demande

- **L'inventaire** est tenu par produit sanguin et par groupe, avec ajustements et signalements, et peut aussi être synchronisé depuis un système partenaire.
- **Le guichet des demandes** mène chaque demande d'un clinicien ou d'un patient de la décision à la préparation puis au service, et imprime un bon de demande.
- **Les donneurs** peuvent déclarer leur disponibilité depuis l'application.
- **Le côté patient referme la boucle.** La recherche de sang qu'utilise le patient est alimentée par l'inventaire que tient la banque de sang, et une demande faite dans l'application arrive au même guichet.

{{diagram:blood-request-queue}}

## 8. La colonne vertébrale de la gouvernance

Tout ce qui précède repose sur la même gouvernance que l'application.

- **Le consentement comme protocole.** Chaque lecture d'un patient par un clinicien franchit une barrière qui exige une relation de soins — un rendez-vous, une équipe soignante, un épisode ouvert dans l'établissement — ou une autorisation approuvée par le patient.
- **Les journaux d'accès.** Les lectures sont journalisées, et la vue des accès du patient est ce même journal.
- **L'accès d'urgence.** Le bris de glace exige un motif, n'ouvre qu'un profil limité, alerte le patient et fait l'objet d'une revue.
- **Les opérations de sécurité.** Les administrateurs tiennent un registre des violations dans lequel un incident est ouvert, contenu, notifié puis clos.
- **Les documents juridiques** — conditions et politiques — sont versionnés, publiés depuis la console d'administration et consultables par tous à une adresse publique.

{{diagram:consent}}

## 9. Sous le capot (pour les ingénieurs)

- **Back-end :** Laravel sur PHP 8.3 et PostgreSQL. Les tâches de fond tournent sur un worker de file géré par le système d'exploitation, avec des files distinctes pour les imports et les webhooks.
- **Documents :** les PDF — ordonnances, demandes d'analyses, factures, reçus, étiquettes — sont produits à la demande sur une feuille sécurisée portant un QR code de vérification, si bien qu'une page imprimée peut être confrontée au serveur.
- **API :** l'application mobile est un client de l'API comme un autre ; la description OpenAPI est générée à partir du code.
- **Audit :** l'accès au dossier patient depuis chaque guichet est inscrit dans la piste d'audit, avec le motif et l'auteur.

{{diagram:qr}}

## 10. Les limites, honnêtement

- **En production, sans prétendre à l'échelle.** OpesCare sert opescare.com en production depuis le 19 octobre 2026. Nous ne publions pas ici de chiffres d'adoption.
- **Un seul pays.** OpesCare est conçu pour le Cameroun, en français et en anglais. Il n'y a pas de déploiement multi-pays.
- **Le DHIS2 attend le MINSANTE.** Les brouillons mensuels sont générés ; rien n'est envoyé tant que l'accès à l'instance nationale n'est pas accordé.
- **Les paiements attendent les comptes marchands.** Le paiement par mobile money est développé et reste indisponible tant que ces comptes ne sont pas connectés.
- **Les notifications push** ne sont pas encore dans la version des stores ; elles attendent les identifiants des stores.
- **Pas un système d'information hospitalier.** Pas d'admissions, pas de soins infirmiers d'hospitalisation, pas de sinistres d'assurance, pas d'achats, pas de PACS. Ces fonctions relèvent d'autres outils, et le dire garde la plateforme honnête.

## 11. La plateforme en un paragraphe

**Un index patient unique** — vérifié chaque nuit, fusionné seulement par une personne, réversible, adressé par le Health ID — alimente **une couche d'interopérabilité** — ingestion FHIR des partenaires, export FHIR par le patient, CIM-10, LOINC, ATC et UCUM comme données, identifiants restreints et webhooks rejouables, rapportage agrégé au DHIS2 dès qu'il sera ouvert — qui sert **les circuits qui existent déjà** : triage, file d'attente, prescription, délivrance, du prélèvement à la publication, comptes rendus d'imagerie et demandes de sang. Le téléphone du patient parle à la même colonne vertébrale que le comptoir de la pharmacie.

**Le dossier du patient n'a besoin d'être recopié à aucun guichet, parce que chaque guichet travaille sur le même dossier.**

---

*OpesCare est développé par OPES Health Systems pour les soins au Cameroun. Il est disponible en français et en anglais, pour les patients comme pour le personnel, sur Android et sur le web dès aujourd'hui, iOS étant prévu. Cet article accompagne [Le dossier est le produit](/blog/le-dossier-est-le-produit), qui décrit le côté patient. Pour aller plus loin : [comment les hôpitaux rejoignent OpesCare](/blog/comment-hopitaux-rejoignent-opescare), [le Health ID, FHIR et l'index maître des patients](/blog/identifiant-sante-interoperabilite-index-patients-explique), ou la [documentation développeurs](/docs/architecture?lang=fr).*
