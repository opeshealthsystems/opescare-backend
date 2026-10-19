---
title: "OpesCare : chaque module et le problème qu'il résout"
slug: "opescare-modules-fonctionnalites-guide"
description: "Guide complet des modules OpesCare pour patients, hôpitaux, pharmacies, laboratoires, centres d'imagerie, banques de sang et développeurs au Cameroun."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Produit"
tags: ["OpesCare", "guide produit", "Health ID", "pharmacie", "laboratoire", "banque de sang", "développeurs"]
translation_of: "opescare-modules-features-guide"
hero_alt: "Vue d'ensemble des modules OpesCare autour d'un Health ID central : patients, hôpitaux, pharmacies, laboratoires, imagerie, banques de sang, développeurs"
reading_minutes: 13
key_takeaways:
  - "OpesCare est un réseau de recherche, d'identité et de coordination, pas un système d'information hospitalier ni un dossier médical complet."
  - "Le patient dispose d'un Health ID gratuit, d'une carte QR, d'un tableau de consentement, d'un journal des accès, de documents, d'un Passeport santé et de comptes famille."
  - "Les structures disposent de postes de guichet : recherche par Health ID avec consentement, ordonnances et délivrance, analyses et résultats, références, file d'attente et triage, stock de sang."
  - "Les développeurs se connectent via l'ingestion FHIR R4, une API, des webhooks signés et un bac à sable documenté."
  - "Les soins ne sont jamais conditionnés au paiement ; le mobile money sert à régler une facture au guichet, là où la structure l'active."
faq:
  - q: "OpesCare est-il gratuit pour les patients ?"
    a: "Oui, le Health ID est gratuit. Des formules patient optionnelles existent, mais les soins, le dossier et les accès ne sont jamais bloqués faute de paiement."
  - q: "OpesCare est-il un dossier médical électronique ?"
    a: "Non. OpesCare est un réseau de recherche, d'identité et de coordination. Il conserve un dossier longitudinal (consultations, résultats, ordonnances, références) mais ne remplace pas le logiciel de l'hôpital pour la documentation clinique complète, les admissions ou la facturation."
  - q: "Quels moyens de paiement OpesCare accepte-t-il ?"
    a: "MTN Mobile Money et Orange Money, pour régler une facture de soins dans une structure qui les a activés. Les paiements s’activent une fois les comptes marchands mobile money connectés ; d’ici là, l’application indique qu’ils sont indisponibles. OpesCare ne traite pas les remboursements d'assurance."
  - q: "OpesCare fonctionne-t-il en français ?"
    a: "Oui. Tous les écrans existent en français et en anglais, sur le web comme dans l'application."
  - q: "Une pharmacie peut-elle importer son stock depuis un tableur ?"
    a: "Un outil d'import de stock lisant les fichiers CSV, Excel et OpenDocument, avec correspondance des colonnes et aperçu avant validation, est disponible dans le menu stock (« Importer le stock »). Le stock peut aussi se mettre à jour depuis les écrans de stock."
sources:
  - title: "HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
---

OpesCare se comprend mieux à partir des problèmes qu'il résout. Un patient est une personne différente dans chaque structure. Un clinicien décide à partir d'un demi-historique. Une famille appelle cinq hôpitaux pour trouver du sang. Une pharmacie a le médicament, mais personne ne le sait.

Ce guide présente chaque module OpesCare en service aujourd'hui, à qui il s'adresse et le problème qu'il résout. Lorsqu'une fonction est limitée, en cours de déploiement ou volontairement absente, nous le disons.

## Ce qu'est OpesCare — et ce qu'il n'est pas

OpesCare est un réseau de **recherche, d'identité et de coordination** pour le Cameroun, en français et en anglais. Les patients trouvent des soins et portent une identité unique ; les structures restent visibles et servent les détenteurs d'un Health ID au guichet.

Ce n'est **pas** un système d'information hospitalier ni un dossier médical électronique complet. La documentation clinique complète, les admissions et sorties, les achats, les ressources humaines et les remboursements d'assurance sont volontairement hors périmètre. OpesCare se connecte au logiciel que la structure utilise déjà au lieu de le remplacer. C'est un réseau privé exploité par OPES Health Systems, pas un service de l'État.

## Pour les patients

### Health ID et carte QR

**Problème :** un nouveau carnet et un nouveau numéro de dossier dans chaque structure.
**Module :** un Health ID gratuit, délivré à l'[inscription](https://opescare.com/signup/patient), avec une carte QR, un mode Présentation pour le guichet et une page publique de vérification permettant au personnel de s'assurer de son authenticité. Le personnel vous retrouve par identifiant exact ou par QR — jamais par recherche de nom.

### Consentement et journal des accès

**Problème :** le patient ne sait pas qui a ouvert son dossier ni pourquoi.
**Module :** chaque demande de consentement précise qui demande, pourquoi, quelles données et pour combien de temps ; vous acceptez, refusez ou révoquez. Chaque accès est inscrit dans un journal que vous pouvez consulter. L'accès d'urgence « bris de glace » n'ouvre qu'un profil limité, exige un motif et vous alerte. Deux réglages de confidentialité permettent de restreindre ce qui est affiché. Voir [qui peut voir votre dossier](/blog/consentement-patient-qui-voit-votre-dossier).

### Dossier longitudinal

**Problème :** un historique dispersé entre plusieurs structures.
**Module :** consultations, résultats, ordonnances, constantes, vaccinations et références dans une chronologie unique qui survit à toute structure. Vous pouvez aussi importer d'anciens documents et exporter votre dossier au format FHIR.

### Rendez-vous et file d'attente

**Problème :** des attentes longues et incertaines.
**Module :** prenez rendez-vous avec un professionnel, reprogrammez depuis l'application et suivez votre place dans la file de la structure. Le triage peut réordonner la file selon l'urgence ; il ne raccourcit pas l'attente à lui seul, et les soins ne dépendent jamais de votre rang.

### Documents avec QR de vérification

**Problème :** résultats et certificats papier faciles à falsifier, difficiles à vérifier.
**Module :** les PDF officiels — export du dossier, ordonnances et autres documents — portent un QR menant à une page de vérification sur opescare.com, scellée par l'empreinte du document. Les PDF patients sont générés au moment du téléchargement, et non stockés.

### Passeport santé

**Problème :** aucun résumé unique à présenter en voyage ou en changeant de médecin.
**Module :** un Passeport santé de plusieurs pages construit à partir de vos vrais dossiers. Les pages sans données restent vides ; rien n'est inventé pour les remplir.

### Famille et tuteurs

**Problème :** parents et aidants gèrent la santé de personnes qui ne peuvent pas le faire elles-mêmes.
**Module :** comptes tuteurs et liens familiaux permettant à un parent ou un aidant d'agir pour un enfant ou une personne à charge, avec un tableau de bord et une messagerie dédiés. Les tuteurs disposent d'une page d'inscription spécifique.

### Outils de recherche

**Problème :** appeler partout pour trouver un médicament, du sang, une analyse ou une structure.
**Module :** le [Medicine Finder](https://opescare.com/network/medicine-finder), le [Blood Finder](https://opescare.com/network/blood-finder), la recherche d'analyses, la recherche d'examens d'imagerie et l'annuaire [Care Map](https://opescare.com/care-map), avec cartes et itinéraires intégrés. Les résultats proviennent uniquement de ce que publient les structures ; un résultat vide s'affiche comme tel.

### Paiements et formules

**Problème :** payer une facture, c'est encore faire la queue à la caisse.
**Module :** réglez une facture avec **MTN Mobile Money** ou **Orange Money** lorsque la structure l'a activé. Les paiements s’activent une fois les comptes marchands mobile money connectés ; d’ici là, l’application indique qu’ils sont indisponibles. Des formules patient optionnelles se paient à la journée, au mois ou à l'année, en francs entiers. **Aucun soin n'est jamais bloqué faute de paiement** — des tests automatisés le garantissent.

### Application mobile, hors-ligne et langues

L'application mobile couvre le parcours patient, signale quand vous êtes hors ligne et synchronise certaines saisies en attente au retour de la connexion. L'ensemble du produit existe en **français et en anglais**.

## Pour les hôpitaux, cliniques et centres de santé

### Portail clinicien

**Problème :** le clinicien n'a pas accès, en toute sécurité, à l'historique dont il a besoin.
**Module :** un portail clinicien sur le web et dans l'application — changement de structure, listes de patients, agenda, résultats, références, messagerie —, chaque lecture du dossier étant contrôlée par une relation de soins ou un consentement. Le clinicien peut scanner le QR du patient depuis l'application.

### Ordonnances

**Problème :** ordonnances manuscrites perdues, mal lues ou falsifiées.
**Module :** un poste prescripteur émet les ordonnances sur le Health ID, avec alertes d'interactions médicamenteuses. Chaque ordonnance a son propre lien de vérification, et les pharmacies délivrent sur cette base.

### Références

**Problème :** le patient référé arrive sans information.
**Module :** un poste de références pour envoyer et recevoir les références, limitées à ce dont le prochain soignant a besoin.

### File d'attente, triage, vaccination et constantes

Gestion de la file à l'accueil, ordre de triage, poste de vaccination et saisie des constantes avec des valeurs de référence adultes cohérentes.

### Administration de la structure

Invitations et rôles du personnel, services, registre des lits pour les taux d'occupation, factures, rapports avec export CSV d'agrégats, messages internes, tâches et liste de contrôle de mise en service. Les structures peuvent aussi préparer des **décomptes agrégés** pour le DHIS2 national, validés par un administrateur avant envoi — jamais de données individuelles.

## Pour les pharmacies

### Délivrance par Health ID

**Problème :** aucun lien entre l'ordonnance et ce qui a réellement été délivré.
**Module :** au comptoir, le pharmacien retrouve le patient par son Health ID, voit l'ordonnance et enregistre une délivrance totale ou partielle. Le stock est décrémenté au retrait.

### Stock, prix et commandes

**Problème :** le patient ne peut pas savoir qu'une pharmacie a son médicament.
**Module :** les écrans de stock alimentent le Medicine Finder et indiquent la date de dernière confirmation de chaque ligne. Une liste de prix en FCFA entiers conserve son historique. Un poste de commandes reçoit les réservations des patients.

### Import de stock (en déploiement)

Un outil d'import lit les fichiers CSV, Excel (.xlsx) et OpenDocument (.ods), laisse la pharmacie faire correspondre ses propres colonnes, affiche un aperçu, puis seulement enregistre les lignes comme des mouvements de stock ordinaires. Vous le trouverez dans le menu stock de votre pharmacie, sous « Importer le stock ».

## Pour les laboratoires

**Problème :** résultats en retard, perdus, ou reçus par le patient avant que le médecin puisse les expliquer.
**Module :** un poste laboratoire pour les demandes, le suivi et le rejet d'échantillons, la validation et la diffusion des résultats au patient et au prescripteur, un compte rendu imprimable, la relecture des valeurs critiques et les délais de rendu. Le catalogue d'analyses publié alimente la recherche publique d'analyses.

## Pour les centres d'imagerie

Un poste imagerie pour les demandes et la diffusion des résultats, avec les résultats de radiologie liés au dossier patient, et une liste d'examens publiée qui alimente la recherche d'examens d'imagerie. Le logiciel de compte rendu et le PACS restent dans les systèmes du centre.

## Pour les banques de sang

**Problème :** personne ne sait où se trouve du sang compatible.
**Module :** un poste banque de sang pour tenir à jour l'inventaire par groupe et par composant, et pour recevoir les demandes et y répondre. L'inventaire publié apparaît dans le Blood Finder. Voir [comment fonctionne la recherche de sang](/blog/recherche-disponibilite-sang-cameroun).

## Pour les développeurs et partenaires

**Problème :** chaque intégration est un projet sur mesure.
**Module :**

- **ingestion FHIR R4** pour DiagnosticReport, Observation, Immunization et lots transactionnels, avec jetons à périmètre limité ;
- une API REST et sa documentation sur [opescare.com/docs](https://opescare.com/docs) ;
- des **webhooks signés et rejouables** (les adresses réseau internes sont refusées) ;
- un tableau de bord développeur avec journal d'activité, démarrage rapide et statistiques ;
- un widget intégrable et un bac à sable.

Les développeurs s'inscrivent via la page d'inscription dédiée. Un serveur FHIR sortant et HL7 v2 ne sont pas disponibles.

## En coulisses : identité et sécurité

### Index maître des patients

Les doublons sont détectés à l'inscription et lors d'analyses programmées, puis examinés par une personne. Rien n'est fusionné automatiquement, et les fusions sont réversibles. Voir [pourquoi les doublons sont dangereux](/blog/doublons-dossiers-patients-index-maitre).

### Sécurité et confidentialité

- L'identité de la structure provient toujours de la session connectée, jamais de ce qu'affirme une requête.
- L'accès d'une structure aux données d'une autre est refusé.
- Les saisies cliniques ne sont jamais supprimées ; elles sont corrigées ou annulées avec une trace.
- Les administrateurs de la plateforme doivent utiliser l'authentification multifacteur.
- Une politique de sécurité du contenu stricte et des webhooks renforcés protègent l'application web.

Consultez notre [politique de confidentialité](https://opescare.com/privacy) et notre [guide sur la protection des données](/blog/protection-donnees-sante-numerique-cameroun).

## Ce qui est volontairement absent

Pour éviter toute confusion : OpesCare ne gère **pas** les remboursements ni les accords préalables d'assurance, les achats ou fournisseurs, la paie et les RH, les flux d'admission et de sortie, les interfaces d'automates de laboratoire, ni les notes cliniques complètes. La téléconsultation se limite à la prise de rendez-vous ; il n'y a pas de vidéo dans l'application.

## Conclusion

Chaque module OpesCare répond à une question concrète : qui est ce patient, qui peut voir son dossier, où est le médicament, où est le sang, et comment l'information passe d'une structure à l'autre. Ensemble, ils forment un réseau d'identifiants santé détenus par le patient, d'un genre nouveau au Cameroun.

**Patients :** [obtenez votre Health ID gratuit](https://opescare.com/signup/patient). **Structures :** [demandez à rejoindre le réseau](https://opescare.com/signup/organization). **Développeurs :** [lisez la documentation](https://opescare.com/docs). Une question ? Rendez-vous sur [opescare.com/help](https://opescare.com/help).
