---
title: "Le dossier est le produit : le Health ID d'OpesCare"
slug: "le-dossier-est-le-produit"
description: "Jude Nshome explique comment OpesCare fait du dossier médical du patient une infrastructure portable et régie par le consentement, au Cameroun."
date: 2026-10-19
updated: 2026-10-19
author: "Jude Nshome"
author_bio: "Jude Nshome est le fondateur d'OpesCare, la plateforme de santé bilingue conçue par OPES Health Systems au Cameroun. Il écrit sur les dossiers de santé détenus par les patients, le consentement et la construction d'infrastructures de santé numérique dans les contextes à ressources limitées."
featured: true
downloadable: true
category: "Health ID"
tags: ["Health ID", "consentement", "dossier détenu par le patient", "infrastructure de santé numérique", "Cameroun", "santé familiale", "hors ligne"]
translation_of: "the-record-is-the-product"
hero_alt: "Le dossier de santé d'un patient au centre, entouré des couches marché, gouvernance, famille et clinique d'OpesCare"
key_takeaways:
  - "OpesCare considère le dossier du patient — identité, antécédents, médicaments, allergies, analyses, réglages de consentement et historique des accès — comme le produit lui-même, et non comme un sous-produit d'une consultation."
  - "Le premier Health ID du Cameroun est une attestation vérifiable avec QR code : une nouvelle structure peut la vérifier et demander l'accès, mais ne peut pas lire le dossier sans relation de soins ou consentement du patient."
  - "Le consentement est un protocole : demandes, registre des consentements, révocation, journal des accès visible par le patient, demandes de rectification et accès d'urgence « bris de glace » audité."
  - "Un tuteur agit pour un enfant ou une personne à charge dans un contexte « agir pour » explicite ; un adulte n'est ajouté qu'après avoir accepté une invitation."
  - "La même application sert le patient, le médecin, l'accueil, le pharmacien et le technicien de laboratoire, tous sur le même dossier, celui du patient."
  - "Les limites sont dites clairement : le paiement mobile attend les comptes marchands, les notifications push attendent les identifiants de publication, l'épinglage TLS est réservé à Android, et les soins ne sont jamais conditionnés au paiement."
faq:
  - q: "Que signifie « le dossier est le produit » ?"
    a: "OpesCare est construit autour du dossier longitudinal du patient plutôt qu'autour d'une transaction isolée comme une réservation ou un paiement. La recherche de soins, le consentement, l'accès familial et les outils de la clinique reposent tous sur ce dossier unique, que le patient emporte avec lui."
  - q: "Le Health ID d'OpesCare est-il la carte nationale d'identité ?"
    a: "Non. C'est un identifiant de santé délivré par OpesCare. Il s'agit d'un réseau d'identifiants de santé détenus par les patients, inédit au Cameroun, mais ce n'est pas une pièce d'identité officielle et il ne revendique aucune approbation de l'État."
  - q: "Une clinique peut-elle lire mon dossier simplement parce qu'elle a scanné mon Health ID ?"
    a: "Non. Le scan vérifie votre identité. Pour lire votre dossier, un clinicien doit avoir une relation de soins avec vous — un rendez-vous, une place dans votre équipe soignante ou un épisode ouvert dans sa structure — ou une autorisation que vous avez approuvée. Chaque lecture est inscrite dans un journal que vous pouvez consulter."
  - q: "Que se passe-t-il en urgence si je ne peux pas consentir ?"
    a: "Un clinicien peut recourir à l'accès « bris de glace ». Il doit indiquer un motif ; seul un profil d'urgence limité s'ouvre ; vous êtes alerté et l'accès est contrôlé ensuite."
  - q: "Puis-je gérer le dossier de mon enfant ou de mon parent ?"
    a: "Oui. Un tuteur peut agir pour un enfant ou une personne à charge dans un contexte « agir pour » explicite. Un adulte n'est jamais ajouté à son insu : il reçoit une invitation qu'il peut accepter ou refuser."
  - q: "OpesCare fonctionne-t-il sans connexion ?"
    a: "En partie, et seulement si vous l'activez. L'accès hors ligne conserve une copie chiffrée des parties de votre dossier qu'autorise une politique émise et révocable par le serveur. Les modifications faites hors ligne sont mises en attente et réglées avec le serveur à la reconnexion."
  - q: "Dois-je payer pour être soigné via OpesCare ?"
    a: "Non. Les soins ne sont jamais conditionnés au paiement. Le règlement d'une facture par MTN Mobile Money ou Orange Money est intégré à l'application, mais reste indisponible tant que les comptes marchands ne sont pas raccordés."
  - q: "OpesCare est-il disponible sur iPhone ?"
    a: "Android passe en premier. Une version iOS est préparée et la version web fonctionne dans n'importe quel navigateur, mais la publication sur l'App Store reste à venir."
sources:
  - title: "Flux de consentement — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/consent?lang=fr"
    accessed: 2026-10-19
  - title: "Vérification par QR — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/qr?lang=fr"
    accessed: 2026-10-19
  - title: "Ingestion FHIR R4 — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/flows/fhir?lang=fr"
    accessed: 2026-10-19
  - title: "Architecture de la plateforme — documentation OpesCare"
    publisher: "OpesCare"
    url: "https://opescare.com/docs/architecture?lang=fr"
    accessed: 2026-10-19
  - title: "HL7 FHIR Release 4"
    publisher: "HL7 International"
    url: "https://hl7.org/fhir/R4/"
    accessed: 2026-10-19
  - title: "Loi n° 2010/012 du 21 décembre 2010 relative à la cybersécurité et à la cybercriminalité au Cameroun"
    publisher: "Digital Watch Observatory"
    url: "https://dig.watch/resource/the-law-no-2010-012-of-21-december-2010-on-cybersecurity-and-cybercriminality-in-cameroon"
    accessed: 2026-10-19
  - title: "Configuration de la sécurité réseau (épinglage de certificats)"
    publisher: "Android Developers"
    url: "https://developer.android.com/privacy-and-security/security-config?hl=fr"
    accessed: 2026-10-19
---

> Pas une application de transactions de santé, mais une application de relations de santé. Le dossier circule entre le patient, sa famille, la clinique et l'aidant — et chaque passage de relais est consenti, tracé et révocable.

Dans la plupart des systèmes de santé d'Afrique subsaharienne — et, pour être franc, dans la plupart des systèmes de santé du monde —, le dossier médical appartient à l'établissement qui l'a créé. Les résultats d'analyse dorment dans un classeur d'hôpital. L'historique des ordonnances reste dans une seule pharmacie. Quand un patient déménage, part à l'étranger, consulte ailleurs ou perd son carnet, son dossier ne le suit pas. Les réglages par défaut sont à l'envers : l'établissement est durable, le patient est une source de friction.

**OpesCare renverse cette logique.** C'est une plateforme de santé bilingue (français et anglais) pour le Cameroun, dans laquelle le dossier du patient — identité, antécédents, médicaments, groupe sanguin, allergies, résultats d'analyses, réglages de consentement et historique des accès — est *le produit lui-même*. Autour de ce dossier s'organise une couche de marché pour trouver des soins dans le monde réel : les stocks de médicaments déclarés par les pharmacies proches, la disponibilité du sang, les cliniciens et l'annuaire national des structures de santé. Et la même application, sur le même téléphone, sert aussi de poste de travail à la clinique : médecin, accueil, pharmacien et technicien de laboratoire ont chacun leur espace, tous travaillant sur le dossier qui appartient au patient.

{{diagram:record-is-the-product}}

Cet article en fait le tour, fonction par fonction : ce qu'un patient peut faire dès le premier jour, ce que peut faire le personnel derrière le comptoir, et la mécanique de gouvernance qui rend l'ensemble digne de confiance pour héberger des données médicales.

## 1. La colonne vertébrale : un Health ID portable

Chaque patient sur OpesCare reçoit **le premier Health ID du Cameroun** — une attestation vérifiable, présentée comme le document qu'elle est, avec un QR code et un lien de vérification qu'un clinicien peut scanner au moment du soin. Elle se prolonge en **Passeport santé** : une présentation longue et vivante du dossier (identité et synthèse médicale, médicaments, allergies et alertes, consultations, actes, vaccinations, santé maternelle et infantile), mise en page comme un passeport plutôt que comme un tableau de bord administratif, car c'est le format auquel les populations rurales et à faibles ressources accordent encore spontanément leur confiance : un document que l'on tient en main. Une page sans données reste vide ; rien n'est inventé pour la remplir.

Deux choix de conception comptent plus qu'il n'y paraît.

1. **L'identifiant fonctionne d'un établissement à l'autre.** Le patient présente sa carte dans une nouvelle clinique, qui peut la vérifier et demander l'accès. Aucun patient n'est lié à « sa » clinique.
2. **La carte est conçue pour être montrée sans risque.** Elle dispose d'un mode présentation ; les écrans qui affichent des données de santé bloquent les captures et l'enregistrement d'écran ; et l'application peut être verrouillée par la biométrie du téléphone, de sorte qu'un appareil laissé à un point de recharge ne s'ouvre pas directement sur le statut VIH ou l'historique de grossesse de quelqu'un.

{{diagram:qr}}

Le sceptique posera la question évidente, et c'est la bonne : au Cameroun, le téléphone est souvent partagé, et le meilleur chiffrement ne vaut rien si la personne qui ramasse l'appareil hérite de tout ce que voyait la session de son propriétaire. OpesCare fait de cette réalité une contrainte de conception, pas un cas marginal. Les jetons sont conservés dans le stockage sécurisé du système ; la déconnexion ou l'expiration de la session efface tout — requêtes en cache, cache hors ligne, file d'attente des envois et PDF exportés — et la protection de la confidentialité s'applique à chaque écran qui affiche des données du patient.

## 2. La couche de marché : trouver les soins là où ils sont

Un annuaire qui se contente de lister des établissements n'est qu'un bottin. OpesCare traite l'offre de santé comme *un inventaire qui existe maintenant*, et l'expose à travers trois services.

**Recherche de médicaments.** Le patient cherche un médicament, voit les stocks déclarés par les pharmacies du réseau, classés par distance, et peut réserver une quantité dans la pharmacie qui l'a réellement. La réservation arrive dans la file de travail de cette pharmacie, où le pharmacien la confirme et la délivre. La disponibilité provient uniquement de relevés de stock en direct dont la source figure sur une liste autorisée : si aucune pharmacie n'a déclaré le médicament, la recherche le dit, au lieu d'afficher des données de remplissage.

**Recherche de sang.** Le sang est le stock le plus périssable qu'un système de santé ait à gérer. OpesCare affiche la disponibilité par groupe sanguin et par produit sur une carte, prend en charge les **demandes urgentes** et suit chaque demande jusqu'à son issue. Puis il fait ce qu'un outil d'approvisionnement fait rarement : il invite le patient à **devenir donneur**, avec un annuaire de donneurs et des contenus de sensibilisation — car augmenter le nombre de donneurs est la seule stratégie qui ait jamais résolu une pénurie de sang à grande échelle.

**Recherche de soins.** Cliniciens, établissements et annuaire national partagent une même interface cartographique : on trouve un clinicien disponible et proche, on réserve le créneau et on s'y rend. La carte n'est pas un ornement ; elle montre l'offre de soins en réseau d'un pays selon un autre axe que les rendez-vous — la géographie plutôt que l'agenda.

C'est un marché parce que cela ne fonctionne que si *chaque* côté de la transaction est instrumenté, et pas seulement la barre de recherche. Les annuaires échouent précisément quand le stock, l'urgence et la confiance ne coïncident pas.

## 3. La couche de gouvernance : le consentement comme protocole à part entière

C'est ici qu'OpesCare cesse de ressembler à une application bien faite pour ressembler à une infrastructure de santé.

Un clinicien qui veut consulter un dossier qui ne lui a pas été ouvert n'obtient pas de contournement : il fait une **demande**. Le téléphone du patient l'affiche sur une fiche de consentement — qui demande, pourquoi, quelles données et pour combien de temps — et le patient accepte ou refuse. Les autorisations sont conservées dans un **registre des consentements**. Chaque accès au dossier est inscrit dans un **journal des accès consultable par le patient** : la personne à qui appartiennent les données voit qui les a ouvertes, et quand. Le consentement peut être **révoqué**, ce qui fait du « consentement » un mot plutôt qu'une case à cocher. Enfin, la **demande de rectification** reconnaît que les dossiers contiennent des erreurs : le patient dépose sa demande depuis l'application et elle est examinée par le service des droits des patients d'OpesCare.

{{diagram:consent}}

Il existe aussi une voie d'urgence. L'accès **« bris de glace »**, pour un patient inconscient qui ne peut pas consentir, exige un motif, n'ouvre qu'un profil d'urgence limité, alerte le patient et fait l'objet d'un contrôle a posteriori — car un système de gouvernance sans accès d'urgence est un système qui coûte des vies.

À l'export, le dossier a deux portes : le **PDF** pour les personnes et **FHIR R4** pour les systèmes, de sorte qu'un autre prestataire reçoit un format normalisé plutôt qu'une capture d'écran. Les dossiers peuvent aussi être **importés** — un lot FHIR ou des documents numérisés — avec une honnêteté rare dans les applications de santé : les données importées sont conservées et *étiquetées comme fournies par le patient et non vérifiées*. La provenance compte trop pour être maquillée.

Le même esprit traverse tout le produit : la piste d'audit est **tournée vers le patient**, et non vers un journal serveur que personne ne lit. Dans la plupart des dossiers informatisés, « qui a consulté ce dossier » relève d'une base de conformité. Dans OpesCare, c'est un écran que le patient peut interroger. Quand la gouvernance est lisible par ceux qu'elle gouverne, elle cesse d'être du théâtre.

## 4. La couche familiale : agir pour ses proches

Une grand-mère qui gère son diabète n'a ni les mêmes compétences numériques, ni le même niveau de lecture, ni le même téléphone que ses petits-enfants. Derrière beaucoup de dossiers se cache une réalité familiale : un aidant qui manipule le téléphone, ou un proche qui arrive en pleine urgence alors que personne ne peut consentir.

OpesCare dispose d'une véritable **architecture familiale**, pas d'un simple bouton « partager mon dossier ». Un tuteur peut agir pour ses enfants et les personnes à sa charge dans un **contexte « agir pour »** explicite, et les écrans de la personne à charge indiquent à qui appartient le dossier ouvert, ce qui évite les confusions d'identité qui mettent les patients en danger. Le dossier d'une personne à charge se lit par les mêmes points d'accès que celui du tuteur : un parent qui s'occupe d'un enfant dispose d'un vrai accès au dossier, et non d'un compte lié cosmétique dont on aurait retiré l'essentiel.

{{diagram:family-act-as}}

La délégation elle-même est encadrée. Un adulte peut être invité à aider à gérer les soins d'une personne, et l'invitation peut être acceptée ou refusée — tant qu'elle n'est pas acceptée, elle ne donne aucun droit. Il n'y a pas de réseau social, et personne n'est ajouté aux soins de quelqu'un sans avoir dit oui.

Pour les équipes produit, c'est la couche à étudier. Beaucoup d'applications de santé sont conçues pour le titulaire du compte, et la grand-mère reste sans solution. OpesCare a été conçu pour le foyer dès le départ.

## 5. Une application, cinq postes de la clinique

Le choix de produit le moins visible d'OpesCare — et sans doute le plus audacieux — est que **la même application sert toute la clinique**.

Quand un membre du personnel se connecte, l'application prend une forme différente selon les **capacités de son rôle**, définies par le serveur. La journée d'un médecin, c'est un agenda, une boîte de demandes de rendez-vous, une liste de patients et un dossier découpé en sections. **L'accueil** dispose de l'agenda de l'établissement : enregistrement des arrivées, inscription des patients sans rendez-vous et orientation vers un clinicien. Le **pharmacien** reçoit les ordonnances, délivre, gère les réservations et ajuste les stocks avec une piste d'audit. Le **technicien de laboratoire** enregistre les prélèvements, saisit et valide les résultats — et la validation d'un résultat prévient à la fois le patient et le clinicien prescripteur.

{{diagram:one-app-five-roles}}

Pourquoi est-ce audacieux ? Parce que le dossier est un objet unique, partagé par ces cinq personnes. Quand la même application fonctionne à l'accueil, chez le médecin, à la pharmacie et au laboratoire, le dossier n'a pas à faire le tour de la clinique : il y est déjà. Les passages de relais qui se perdent sur papier (un bon qui ne suit pas le prélèvement, une ordonnance qui ne suit pas la consultation) deviennent la norme.

À ces rôles s'ajoutent les capacités cliniques qu'exige le dossier : **prescription**, **demandes d'analyses**, **demandes de suivi**, **boîte de résultats**, **scan du QR code du Health ID** au moment du soin, et la voie **« bris de glace »** décrite plus haut. L'application du clinicien est un véritable outil de rédaction du dossier — pas un tableau de facturation plaqué dessus.

## 6. Sous le capot (pour les ingénieurs)

Pour un public technique, l'architecture est propre et les choix d'ingénierie sont prudents là où il le faut.

- **Expo SDK 57, React Native 0.86, React 19 et TypeScript strict :** une seule base de code TypeScript pour l'application Android, avec un profil de compilation iOS préparé et une version web.
- **Routage d'abord :** un routage par fichiers sur plus de quatre-vingt-dix écrans, avec un audit de navigation dans le dépôt qui signale tout écran vers lequel rien ne mène.
- **État serveur et état client séparés :** TanStack Query et Zustand, avec un seul client HTTP et un seul endroit où les jetons sont joints et renouvelés.
- **Un modèle de session explicite :** un seul emplacement de jeton par appareil, avec un marqueur de type de session, pour qu'une session de clinicien et une session de patient ne puissent pas se contaminer.
- **Hors ligne, avec un circuit de règlement :** un cache local **chiffré**, activé sur demande et délimité par une politique par appareil émise et révocable par le serveur, et une **file d'envoi** qui capte les écritures échouées faute de réseau et les règle dans une file chiffrée côté serveur — dédoublonnée, auditée et ouverte comme tâche de rapprochement. Les requêtes d'authentification ne sont jamais écrites sur l'appareil.
- **Sécurité du transport :** sur Android, une configuration de sécurité réseau épingle TLS sur les clés des autorités de certification racines plutôt que sur le certificat du site, pour que les renouvellements de routine ne cassent pas les applications installées ; aucun trafic en clair hors d'un profil de développement local, sauvegardes de l'application désactivées, et une liste d'autorisations courte qui bloque explicitement le stockage, le micro et la superposition d'écran dont l'application n'a pas besoin.
- **Contrôle qualité :** TypeScript, ESLint et une suite Jest ciblant les chemins critiques pour la sécurité — effacement de session, stockage des jetons, renouvellement d'un jeton expiré une seule fois, verrouillage de l'application, ouverture sûre des liens externes et stockage hors ligne —, le tout exécuté en intégration continue à chaque envoi et à chaque demande de fusion.
- **L'internationalisation est systémique,** pas cosmétique : français et anglais à parité dans toute l'application, et montants en XAF (FCFA).
- **Des paiements pensés pour le marché :** le règlement d'une facture par MTN Mobile Money ou Orange Money en XAF est intégré — et la règle du produit est inscrite dans le code et ses tests : les soins ne sont jamais conditionnés au paiement.

{{diagram:offline-outbox}}

## 7. Les limites, en toute honnêteté

Une publication sérieuse ne prétend pas qu'un jeune produit est achevé. Voici ce que demandera un relecteur technique, et où en sont les choses.

- **Le paiement mobile n'est pas encore disponible.** Les parcours MTN Mobile Money et Orange Money sont construits, mais les vrais paiements exigent d'abord le raccordement des comptes marchands d'OpesCare. D'ici là, l'application indique que le paiement est indisponible — et aucun rendez-vous, dossier ou consultation n'en dépend.
- **Les notifications push n'atteignent pas encore la version publiée.** Le dispositif existe de bout en bout, et l'application dit honnêtement ce qu'une version donnée peut ou ne peut pas faire plutôt que de promettre des alertes qui n'arrivent pas, mais les identifiants push doivent être livrés avec une version avant que rappels et alertes de résultats ne parviennent aux utilisateurs.
- **L'épinglage TLS ne concerne qu'Android.** La version iOS est par ailleurs préparée ; sur iPhone, les connexions reposent sur la validation standard des certificats, et la publication sur l'App Store reste à venir.
- **La version web est la petite sœur, par choix.** Les navigateurs n'ont pas de coffre sécurisé du système, si bien que la version web refuse le cache hors ligne et garde les jetons en mémoire, adossés uniquement à un stockage limité à l'onglet.

Une limite citée dans les premières versions de ce texte ne s'applique plus : depuis le 19 octobre 2026, l'application dialogue avec le **serveur de production d'opescare.com**. Une version de production doit nommer explicitement son API, et la compilation refuse d'être publiée par erreur vers un serveur de démonstration.

Nous préférons que ces limites soient visibles et corrigées en vol plutôt que passées sous silence.

## 8. Pourquoi cela compte au-delà du Cameroun

Il y a un schéma qui mérite d'être nommé. La technologie de santé conçue dans les pays à hauts revenus optimise en général la *transaction* : prendre rendez-vous, payer une facture, écrire à un médecin. Ce qu'elle manque, c'est la *relation* en dessous : à qui sont les données, qui peut les voir, qui agit pour vous quand vous ne le pouvez pas, et ce qui se passe quand le réseau tombe au moment précis où vous avez besoin de soins.

OpesCare construit d'abord ces fondations — propriété, consentement, délégation, accès hors ligne, identité — puis y accroche les transactions. Cet ordre traduit un pari précis : sur les marchés de santé émergents, l'infrastructure de confiance est la ressource rare, et les parcours de réservation sont des commodités que chacun peut acheter ou refaire.

Le Cameroun est le terrain de la preuve, mais le modèle ne lui est pas propre. Quelle que soit l'étiquette choisie — *infrastructure de santé numérique*, *identité médicale portable*, *dossier de santé fondé sur le consentement*, *réseau d'identifiants de santé détenus par les patients, inédit en son genre* —, l'idée de fond est simple :

**Le dossier appartient au patient. Tout le reste découle du protocole.**

---

*OpesCare est développé par OPES Health Systems pour les soins au Cameroun. Il est disponible en français et en anglais, pour les patients comme pour le personnel, sur Android et sur le web dès aujourd'hui, iOS étant prévu. Pour aller plus loin : [le consentement expliqué aux patients](/blog/consentement-patient-qui-voit-votre-dossier), [le Health ID, FHIR et l'index maître des patients](/blog/identifiant-sante-interoperabilite-index-patients-explique), ou la [documentation développeurs](/docs/architecture?lang=fr).*
