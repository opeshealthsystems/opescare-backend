---
title: "Health ID, interopérabilité et index patients : explications"
slug: "identifiant-sante-interoperabilite-index-patients-explique"
description: "Fonctionnement du Health ID OpesCare, de l'accès par consentement, de l'ingestion FHIR R4 et de l'index maître des patients, avec les outils de recherche."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Interopérabilité"
tags: ["Health ID", "interopérabilité", "FHIR R4", "index maître des patients", "consentement", "Cameroun"]
translation_of: "health-id-interoperability-mpi-explained"
hero_alt: "Schéma d'un Health ID reliant un hôpital, un laboratoire et une pharmacie via la couche d'échange OpesCare"
reading_minutes: 11
key_takeaways:
  - "Le Health ID est un identifiant portable qui appartient au patient ; le personnel le retrouve par identifiant exact ou par QR, jamais par recherche de nom."
  - "Chaque lecture par un clinicien est vérifiée contre une relation de soins ou une autorisation approuvée par le patient, et chaque accès est journalisé, visible par le patient."
  - "Les partenaires peuvent envoyer des résultats à OpesCare en HL7 FHIR R4 (DiagnosticReport, Observation, Immunization, lots transactionnels) avec des jetons à périmètre limité."
  - "L'index maître des patients repère les doublons probables mais ne fusionne jamais seul ; les fusions sont validées par une personne et réversibles."
  - "Le Blood Finder, le Medicine Finder et l'annuaire des structures n'affichent que ce que les structures publient elles-mêmes ; un résultat vide est un résultat honnête."
faq:
  - q: "Qu'est-ce qu'un index maître des patients ?"
    a: "Un index maître des patients (MPI) est le registre qui garantit qu'une personne correspond à un seul dossier d'un système à l'autre. Le MPI d'OpesCare signale les doublons probables et laisse un examinateur formé les fusionner ; il ne fusionne jamais automatiquement."
  - q: "OpesCare prend-il en charge FHIR ?"
    a: "Oui, en entrée. Les systèmes partenaires peuvent envoyer des ressources DiagnosticReport, Observation et Immunization, ainsi que des lots transactionnels, au point d'accès FHIR R4 d'OpesCare avec des jetons à périmètre limité. Le patient peut aussi exporter son dossier en FHIR depuis l'application. OpesCare n'exploite pas de serveur FHIR sortant général ni HL7 v2."
  - q: "Un hôpital peut-il voir mon dossier simplement parce que j'ai un Health ID ?"
    a: "Non. Le clinicien doit avoir une relation de soins avec vous, par exemple un rendez-vous ou un épisode en cours dans la structure, ou une autorisation que vous avez approuvée. Chaque accès figure dans votre journal."
  - q: "Le Health ID OpesCare est-il une pièce d'identité nationale ?"
    a: "Non. C'est un identifiant santé privé, centré sur le patient, sur le réseau OpesCare. Il ne remplace aucune pièce d'identité officielle."
sources:
  - title: "HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
  - title: "The 2020–2024 National Digital Health Strategic Plan"
    publisher: "Ministère de la Santé publique (MINSANTE), Cameroun"
    url: "https://www.minsante.cm/site/sites/default/files/EN_DOCUMENT_PLAN%20STRATEGIQUE%20NATIONAL%20DE%20SANTE%20NUMERIQUE_R%C3%A9duit.pdf"
    accessed: 2026-10-19
  - title: "Cameroon begs civilians to donate blood on World Blood Donor Day"
    publisher: "VOA News"
    url: "https://www.voanews.com/a/africa_cameroon-begs-civilians-donate-blood-world-blood-donor-day/6207013.html"
    accessed: 2026-10-19
  - title: "Poor-Quality Medicines in Cameroon: A Critical Review"
    publisher: "American Journal of Tropical Medicine and Hygiene"
    url: "https://ajtmh.org/downloadpdf/journals/tpmd/105/2/article-p284.xml"
    accessed: 2026-10-19
---

« Interopérabilité » fait partie de ces mots présents dans toutes les stratégies de santé et dans très peu de salles d'attente. Le Plan stratégique national de santé numérique 2020-2024 du Cameroun fait des normes et de l'interopérabilité l'un de ses axes. Mais à quoi cela ressemble-t-il quand un patient passe réellement d'un laboratoire à une clinique, puis à une pharmacie ?

Cet article explique simplement les briques qu'utilise OpesCare : le **Health ID**, l'**accès fondé sur le consentement**, la **couche d'interopérabilité FHIR R4**, l'**index maître des patients (MPI)**, et les trois services publics du réseau — le **Blood Finder**, le **Medicine Finder** et l'**annuaire national des structures**. Tout ce qui est décrit ici est en service sur [opescare.com](https://opescare.com).

## Le problème en une phrase

La même personne devient un patient différent dans chaque structure, et rien ne relie ces dossiers à un seul être humain.

Chaque couche décrite ci-dessous s'attaque à une partie de cette phrase. Chacune dépend de la précédente : on ne peut pas échanger des dossiers en sécurité sans savoir à qui ils appartiennent, et on ne peut pas le savoir tant que l'identité n'est pas résolue.

## Couche 1 : le Health ID

### De quoi s'agit-il

Le Health ID est un identifiant portable qui appartient au patient. Vous vous inscrivez une fois — sur le web à [opescare.com/signup/patient](https://opescare.com/signup/patient) ou dans l'application mobile — et vous présentez le même identifiant dans toutes les structures du réseau.

Votre Health ID s'accompagne :

- d'une **carte QR** à montrer sur votre téléphone ou à imprimer ;
- d'un **mode Présentation** dans l'application, conçu pour être montré au guichet ;
- d'une **page publique de vérification**, qui permet au personnel de contrôler qu'un QR est authentique.

### Comment le personnel l'utilise

Dans une structure connectée, le personnel retrouve le patient par son **Health ID exact** ou en **scannant le QR**. La **recherche par nom de patient est volontairement impossible**. Ce choix protège les patients : personne ne peut parcourir le registre à la recherche d'un voisin, d'un collègue ou d'une personnalité.

### Ce qu'il n'est pas

Le Health ID OpesCare n'est ni une carte nationale d'identité ni un document officiel. C'est un identifiant santé privé, centré sur le patient, propre au réseau OpesCare ; il ne remplace pas votre pièce d'identité.

## Couche 2 : l'accès fondé sur le consentement

Savoir qui est un patient ne donne à personne le droit de lire son dossier. OpesCare contrôle chaque lecture par un clinicien.

### La vérification de la relation de soins

Un clinicien ne peut ouvrir le dossier d'un patient que si l'une de ces conditions est remplie :

- le patient a un **rendez-vous** avec ce clinicien ;
- le clinicien fait partie de l'**équipe soignante** du patient ;
- le clinicien est le **médecin traitant** ;
- le patient a un **épisode de soins ouvert dans la structure** ;
- le patient a **approuvé une autorisation d'accès**.

Sinon, le clinicien peut envoyer une **demande de consentement**. Le patient voit qui demande, pourquoi, quelles données et pour combien de temps — et peut accepter, refuser ou révoquer plus tard.

### Le journal des accès

Chaque accès est enregistré et visible par le patient dans son journal. Vous voulez savoir qui a ouvert votre dossier et quand ? Vous pouvez le vérifier.

### L'accès d'urgence

Lorsque l'accès normal est impossible — patient inconscient, par exemple —, un clinicien peut recourir à l'**accès « bris de glace »**. Il n'ouvre qu'un profil d'urgence limité (identité, groupe sanguin, allergies, affections en cours, contact d'urgence), exige un motif, alerte le patient et fait l'objet d'un contrôle a posteriori. Le patient peut aussi le restreindre dans ses paramètres de confidentialité.

Pour aller plus loin : notre [guide du consentement pour les patients](/blog/consentement-patient-qui-voit-votre-dossier).

## Couche 3 : l'interopérabilité avec FHIR R4

### Pourquoi une norme

Un logiciel de laboratoire, un logiciel hospitalier et un logiciel de pharmacie stockent chacun les données à leur manière. Une norme leur donne un format commun. **HL7 FHIR** (Fast Healthcare Interoperability Resources) est la norme internationale d'échange de données de santé sous forme de « ressources » structurées, via des API web. OpesCare utilise **FHIR Release 4 (R4)**.

### Ce qu'OpesCare reçoit aujourd'hui

Les systèmes partenaires peuvent envoyer des données **vers** OpesCare par un point d'accès FHIR R4 :

- **DiagnosticReport** — par exemple un compte rendu de laboratoire ;
- **Observation** — résultats et mesures individuels ;
- **Immunization** — vaccinations ;
- **lots transactionnels** — plusieurs ressources envoyées ensemble.

Chaque partenaire reçoit un **jeton limité à des périmètres précis**. Un partenaire autorisé à envoyer des observations ne peut pas envoyer de vaccinations sans ce périmètre. Les données entrantes passent par un contrôle d'ingestion qui vérifie le lien avec le patient avant tout ajout au dossier. Un point d'accès public `metadata` décrit ce que le serveur prend en charge.

Le patient peut également **exporter son propre dossier au format FHIR** depuis l'application mobile.

### Ce qui n'existe pas encore

Pour être précis : OpesCare n'exploite pas de serveur FHIR **sortant** généraliste interrogeable par des tiers, et ne prend pas en charge la messagerie **HL7 v2**. Si votre intégration en a besoin, [contactez-nous](https://opescare.com/contact) — mais ces fonctions ne sont pas en service.

La documentation pour les développeurs se trouve sur [opescare.com/docs](https://opescare.com/docs).

## Couche 4 : l'index maître des patients

### À quoi sert un MPI

Un index maître des patients est le registre qui garantit qu'une personne égale un dossier. Sans lui, une patiente enregistrée deux fois — une fois « Ngono Marie », une fois « Marie Ngono », avec un autre numéro et sans date de naissance — se retrouve avec deux demi-historiques.

### Comment fonctionne le MPI d'OpesCare

- **Détection.** Les doublons possibles sont repérés à la création des patients et lors d'analyses programmées (chaque nuit et chaque semaine). Un administrateur peut aussi signaler une paire manuellement.
- **Examen humain.** Les candidats arrivent sur un poste MPI où un examinateur formé les compare. **Rien n'est fusionné automatiquement.** Une correspondance incertaine n'est jamais tranchée au hasard.
- **Fusions réversibles.** Lors d'une fusion, un dossier devient le dossier principal et le Health ID de l'autre devient un **alias** qui reste valable. Les fusions peuvent être annulées, la plus récente d'abord.
- **Recherches qui suivent les fusions.** Un résultat de laboratoire envoyé avec l'ancien identifiant parvient quand même à la bonne personne.
- **Blocage.** Une paire reconnue comme deux personnes distinctes peut être marquée pour ne plus être proposée.

Cette règle — ne jamais fusionner sur une supposition — est plus lente qu'un rapprochement automatique, et c'est voulu. Une mauvaise fusion place les allergies ou le statut sérologique d'une personne dans le dossier d'une autre. Notre article sur [les doublons de dossiers patients](/blog/doublons-dossiers-patients-index-maitre) approfondit le sujet.

## Les services du réseau

L'identité et l'échange sont le socle. Par-dessus, OpesCare propose trois services publics accessibles sans compte.

### Le Medicine Finder (recherche de médicaments)

Le [Medicine Finder](https://opescare.com/network/medicine-finder) indique quelles pharmacies du réseau détiennent un médicament, à quelle distance elles se trouvent, et **quand elles ont mis à jour** ce stock pour la dernière fois.

Pourquoi c'est important : les études sur la qualité des médicaments au Cameroun montrent que la plupart des échantillons proviennent du secteur informel, où des produits falsifiés ont été documentés. Faciliter l'accès à une pharmacie agréée qui a le médicament est un moyen concret de réduire ce risque.

Les résultats proviennent uniquement de **registres de stock en direct** dont la source figure sur une liste autorisée. Les données d'exemple ou de démonstration ne sont jamais présentées comme disponibles. Si aucune pharmacie n'a signalé le médicament, la page vous le dit. Guide complet : [trouver un médicament en stock près de chez vous](/blog/trouver-medicament-en-stock-cameroun).

### Le Blood Finder (recherche de sang)

Le [Blood Finder](https://opescare.com/network/blood-finder) indique le sang disponible par **groupe** (A, B, AB, O, rhésus) et par **composant** dans les hôpitaux et banques de sang connectés. Les banques de sang tiennent leur inventaire à jour depuis un poste dédié et peuvent recevoir les demandes de sang et y répondre.

Pourquoi c'est important : les dons de sang au Cameroun sont historiquement inférieurs aux besoins — VOA a rapporté une forte baisse des unités collectées en 2020. Quand les stocks sont bas, savoir où se trouve une poche compatible fait gagner du temps. Une disponibilité est une information, pas une réservation : appelez toujours la structure. Guide complet : [comment fonctionne la recherche de sang](/blog/recherche-disponibilite-sang-cameroun).

### L'annuaire des structures et la Care Map

La [Care Map](https://opescare.com/care-map) est un annuaire consultable des structures de santé du Cameroun — hôpitaux, cliniques, centres de santé, pharmacies, laboratoires, centres d'imagerie, banques de sang — avec adresse, localisation, horaires et services lorsque la structure les a fournis. Elle comprend les mots de tous les jours comme *dentiste*, *maternité* ou *ophtalmologue*, et les cartes et itinéraires s'ouvrent dans OpesCare.

Deux outils complètent l'annuaire : une **recherche d'analyses de laboratoire** et une **recherche d'examens d'imagerie**, alimentées par les catalogues que publient les structures.

Une structure présente dans l'annuaire n'est pas forcément connectée. Les structures qui rejoignent OpesCare sont vérifiées puis affichent leurs services en direct ; les autres apparaissent comme simples fiches.

## Comment les couches s'articulent : un parcours

1. Awa s'inscrit et reçoit son Health ID.
2. Au laboratoire, le personnel scanne son QR. Le logiciel du laboratoire envoie ses résultats à OpesCare sous forme de DiagnosticReport FHIR.
3. Elle prend rendez-vous avec un médecin dans une clinique. Grâce à ce rendez-vous, le médecin peut lire son dossier — et cette lecture apparaît dans son journal.
4. Le médecin rédige une ordonnance. Awa utilise le Medicine Finder pour trouver une pharmacie qui signale le médicament, et le pharmacien le délivre sur présentation de son Health ID.
5. Des mois plus tard, Awa s'enregistre dans un hôpital sous un nom légèrement différent. Le MPI signale la paire ; un examinateur confirme qu'il s'agit de la même personne et fusionne. Son historique est de nouveau complet.

## Conclusion

L'interopérabilité n'est pas une fonction isolée. C'est un empilement : un Health ID détenu par le patient, un consentement vérifié à chaque lecture, un format d'échange normalisé, un index qui refuse de deviner, et des services publics qui disent la vérité sur ce qui est disponible.

OpesCare est un réseau privé d'identifiants santé centré sur le patient — premier identifiant santé du Cameroun et une première en son genre — qui fonctionne aux côtés des systèmes déjà en place dans les structures.

**[Obtenez votre Health ID](https://opescare.com/signup/patient)** ou, si vous êtes développeur ou structure de santé, [consultez la documentation](https://opescare.com/docs) et [connectez votre système](https://opescare.com/signup/organization).

Pour comprendre la vision qui sous-tend tout cela, lisez l'essai de Jude Nshome [Le dossier est le produit](/blog/le-dossier-est-le-produit).
