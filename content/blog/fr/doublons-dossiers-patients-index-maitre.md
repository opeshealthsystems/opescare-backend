---
title: "Doublons de dossiers patients : le danger, et la réponse du MPI"
slug: "doublons-dossiers-patients-index-maitre"
description: "Les doublons de dossiers scindent l'historique médical et causent des erreurs. Comment l'index maître d'OpesCare les repère et ne fusionne qu'après examen."
date: 2026-10-19
updated: 2026-10-19
author: "OPES Health Systems"
category: "Health ID"
tags: ["index maître des patients", "MPI", "doublons", "sécurité des patients", "identification des patients"]
translation_of: "duplicate-patient-records-mpi"
hero_alt: "Deux dossiers patients aux noms légèrement différents réunis par un examinateur en un seul dossier, l'ancien identifiant restant un alias"
reading_minutes: 10
key_takeaways:
  - "L'OMS cite l'erreur d'identification des patients comme cause d'erreurs de médication, de transfusion et d'analyses."
  - "Les doublons scindent l'historique d'une personne : allergies, résultats et traitements peuvent passer inaperçus."
  - "Là où les noms s'écrivent de plusieurs façons et où la date de naissance manque souvent, les doublons sont fréquents si rien ne les empêche."
  - "Le MPI d'OpesCare repère les candidats à l'inscription et lors d'analyses programmées, mais rien n'est fusionné automatiquement : une personne examine chaque paire."
  - "Les fusions sont réversibles, l'ancien Health ID reste valable comme alias, et les recherches suivent les fusions."
faq:
  - q: "Qu'est-ce qu'un doublon de dossier patient ?"
    a: "C'est lorsqu'une même personne possède deux dossiers ou plus dans un système, généralement parce qu'elle a été enregistrée plusieurs fois avec de petites différences de nom, de date de naissance ou de téléphone."
  - q: "Pourquoi les doublons sont-ils dangereux ?"
    a: "Parce que chaque dossier ne contient qu'une partie de l'historique. Un clinicien peut passer à côté d'une allergie ou d'un résultat récent, et un résultat peut être classé dans le mauvais dossier. L'OMS cite l'erreur d'identification comme cause d'erreurs de médication, de transfusion et d'analyses."
  - q: "OpesCare fusionne-t-il les dossiers automatiquement ?"
    a: "Non. L'index maître des patients signale les doublons probables, et un examinateur formé décide. Rien n'est fusionné sur une supposition, et chaque fusion peut être annulée."
  - q: "Que devient mon ancien Health ID après une fusion ?"
    a: "Il devient un alias du Health ID conservé et reste valable. Un résultat ou une ordonnance envoyé avec l'ancien identifiant vous parvient quand même."
sources:
  - title: "Patient Identification — Patient Safety Solutions, volume 1, solution 2 (mai 2007)"
    publisher: "Centre collaborateur de l'OMS pour les solutions de sécurité des patients"
    url: "https://mri.alexu.edu.eg/images/patiden.pdf"
    accessed: 2026-10-19
  - title: "WHO launches 'Nine patient safety solutions'"
    publisher: "Organisation mondiale de la Santé"
    url: "https://www.who.int/news/item/02-05-2007-who-launches-nine-patient-safety-solutions"
    accessed: 2026-10-19
  - title: "The 2020–2024 National Digital Health Strategic Plan"
    publisher: "Ministère de la Santé publique (MINSANTE), Cameroun"
    url: "https://www.minsante.cm/site/sites/default/files/EN_DOCUMENT_PLAN%20STRATEGIQUE%20NATIONAL%20DE%20SANTE%20NUMERIQUE_R%C3%A9duit.pdf"
    accessed: 2026-10-19
---

Imaginez deux dossiers. L'un indique « Mbarga Jean-Paul, né en 1984, 6 77 … ». L'autre, « Jean Paul Mbarga, date de naissance inconnue, 6 99 … ». S'agit-il du même homme ? Si oui, son allergie à la pénicilline figure dans un dossier, et sa nouvelle ordonnance d'amoxicilline dans l'autre.

Les doublons de dossiers patients sont l'un des problèmes les moins visibles et les plus dangereux de l'information de santé. Cet article explique pourquoi ils apparaissent, pourquoi ils comptent, et comment l'**index maître des patients (MPI)** d'OpesCare les traite — avec prudence, et jamais au hasard.

## Qu'est-ce qu'un doublon

Il y a doublon lorsqu'**une même personne possède plusieurs dossiers** dans un système. Cela se produit généralement à l'enregistrement :

- le nom est écrit dans un autre ordre (nom de famille puis prénom, ou l'inverse) ;
- l'orthographe change (« Ngo Bassa », « Ngobassa ») ;
- la date de naissance manque, est estimée ou saisie autrement ;
- le numéro de téléphone a changé ;
- le patient n'avait pas, ou n'a pas apporté, la carte d'une visite précédente.

Chaque nouvel enregistrement ouvre un nouvel historique partiel.

## Pourquoi les doublons sont dangereux

### L'identification des patients est une question de sécurité

En 2007, le Centre collaborateur de l'OMS pour les solutions de sécurité des patients a publié l'identification des patients parmi les neuf solutions de sécurité lancées par l'OMS. Le document indique qu'une identification incorrecte continue d'entraîner des erreurs de médication, de transfusion, d'analyses et des interventions sur la mauvaise personne. Les doublons sont une forme de cette défaillance.

### Un historique scindé cache l'essentiel

Lorsque l'historique est réparti sur deux dossiers, le clinicien qui en consulte un n'en voit qu'une partie :

- une **allergie** inscrite dans le premier dossier est invisible dans le second ;
- un **résultat récent** — test VIH, glycémie, test de grossesse — peut passer inaperçu ;
- un **traitement en cours** peut être prescrit deux fois, ou associé à un produit avec lequel il interagit ;
- des **vaccinations** peuvent être répétées ou oubliées.

### Des résultats peuvent atterrir chez la mauvaise personne

L'erreur inverse est tout aussi grave : deux personnes différentes traitées à tort comme une seule. Les fusionner place le diagnostic, le groupe sanguin ou les résultats de l'une dans le dossier de l'autre. C'est pourquoi « fusionner tout ce qui se ressemble » n'est pas une réponse sûre.

### La planification et les statistiques en pâtissent

Les doublons gonflent aussi les décomptes. Un système de santé qui veut des chiffres fiables — couverture vaccinale, enrôlement à la Couverture santé universelle — doit compter des personnes, pas des dossiers. Le Plan stratégique national de santé numérique 2020-2024 du Cameroun place les normes et l'interopérabilité parmi ses axes ; une identité patient fiable en est le socle.

## Pourquoi le contexte camerounais demande une attention particulière

Plusieurs réalités du quotidien rendent les doublons probables partout, et surtout là où :

- les noms peuvent s'écrire dans plusieurs ordres et orthographes ;
- de nombreux adultes n'ont pas de date de naissance précisément enregistrée ;
- les numéros de téléphone changent souvent ;
- les patients passent entre structures publiques, privées et confessionnelles qui ne partagent pas de registre.

Rien de cela n'est la faute des patients. C'est une raison de concevoir des systèmes capables de reconnaître une personne de façon fiable.

## Comment fonctionne le MPI d'OpesCare

### Étape 1 : prévenir avec le Health ID

Le meilleur doublon est celui qui n'est jamais créé. Avec un **Health ID**, le patient porte un identifiant unique et une carte QR. Le personnel le retrouve par **Health ID exact ou QR** — la recherche par nom n'existe pas — de sorte qu'un patient qui revient est retrouvé plutôt que réenregistré.

### Étape 2 : détecter

Des doublons peuvent encore apparaître — carte oubliée, nouvelle inscription. Le MPI d'OpesCare repère les doublons possibles :

- **à la création d'un patient**, par comparaison avec les dossiers existants ;
- lors d'**analyses programmées**, chaque nuit et chaque semaine ;
- **manuellement**, lorsqu'un administrateur repère une paire probable et la signale.

### Étape 3 : examen humain — aucune fusion automatique

Chaque paire candidate arrive sur un **poste MPI**. Un examinateur formé compare les dossiers et décide : même personne, ou personnes différentes. **OpesCare ne fusionne jamais automatiquement**, et une correspondance incertaine n'est jamais tranchée au hasard.

C'est plus lent qu'un rapprochement entièrement automatique, et c'est voulu. Une mauvaise fusion est plus difficile à détecter et plus nuisible qu'un doublon qui attend un jour d'être examiné.

### Étape 4 : une fusion réversible

Lorsque l'examinateur confirme la correspondance :

- un dossier devient le **dossier conservé** ;
- le Health ID de l'autre devient un **alias** qui reste valable ;
- l'historique est regroupé sous le dossier conservé.

Les fusions peuvent être **annulées**, la plus récente d'abord, depuis la liste des fusions du poste.

### Étape 5 : des recherches qui suivent les fusions

De nombreuses parties d'OpesCare — ordonnances, résultats d'analyses, rendez-vous, documents — **suivent les fusions**. Un laboratoire qui envoie un résultat avec l'ancien Health ID atteint quand même la bonne personne.

### Étape 6 : retenir « ce ne sont pas les mêmes »

Si l'examinateur conclut que deux dossiers appartiennent à des personnes différentes — deux cousins homonymes, par exemple —, la paire peut être **bloquée** pour ne plus être proposée.

## Pourquoi ne pas laisser l'ordinateur décider ?

Ailleurs, de nombreux systèmes recourent au « rapprochement probabiliste » : un algorithme évalue la ressemblance entre deux dossiers et fusionne automatiquement au-delà d'un seuil. C'est rapide, et cela peut bien fonctionner là où les noms sont orthographiés de manière constante et où chaque dossier porte une date de naissance et un identifiant national fiables.

Ces conditions font souvent défaut. Dans ce cas, un seuil automatique impose un arbitrage impossible :

- un seuil **bas** fusionne des personnes différentes — deux frères aux noms proches, une mère et sa fille partageant un téléphone ;
- un seuil **élevé** manque la plupart des vrais doublons, et le problème demeure.

OpesCare utilise les algorithmes pour **trouver** les candidats, car un ordinateur sait parcourir des milliers de dossiers en une nuit. Il laisse la **décision** à une personne, car une personne voit le contexte : un autre village, un autre nom de mère, une mention indiquant qu'un dossier appartient à un enfant.

## Ce qu'examine l'examinateur

Pour comparer une paire, l'examinateur considère généralement :

- les variantes et l'ordre du nom ;
- la date de naissance, ou son absence ;
- le sexe ;
- les numéros de téléphone et adresses, actuels et anciens ;
- les structures fréquentées et les dates de visite ;
- la possibilité que l'un des dossiers appartienne à un proche.

Si les éléments ne sont pas assez solides, la paire attend ou est marquée comme distincte. « Je ne suis pas sûr » est une réponse acceptable ; une mauvaise fusion ne l'est pas.

## Un exemple concret

Une patiente s'est enregistrée en 2025 dans un centre de santé sous « Ngo Bassa Esther », avec sa date de naissance. En 2026, elle s'est enregistrée dans un hôpital de district sous « Esther Ngobassa », sans date, car elle avait oublié sa carte.

1. L'analyse de la nuit signale la paire comme candidate.
2. Une examinatrice constate le même numéro de téléphone, le même village et aucune information contradictoire. Elle confirme la correspondance.
3. Le dossier de 2025 devient le dossier conservé ; le Health ID de 2026 devient un alias.
4. Un résultat d'analyse envoyé la semaine suivante par l'hôpital avec l'identifiant de 2026 arrive dans son dossier unique.
5. Si l'examinatrice s'était trompée, la fusion aurait pu être annulée depuis la liste des fusions.

## Ce que peuvent faire les patients

- **Présentez votre Health ID** — sur le téléphone ou imprimé — à chaque visite.
- **Signalez à l'accueil que vous avez déjà un Health ID**, même si vous avez oublié la carte.
- **Vérifiez vos informations** — ordre du nom, date de naissance, téléphone — à l'enregistrement.
- **Consultez votre dossier** dans l'application. Si vous voyez des résultats ou des visites qui ne sont pas les vôtres, ou s'il en manque, [contactez-nous](https://opescare.com/contact).

## Ce que le MPI ne fait pas

Pour être clair sur les limites :

- Il ne **vérifie pas** l'identité civile auprès d'un registre de l'État. Le Health ID OpesCare est un identifiant santé sur un réseau privé, pas une pièce d'identité nationale.
- Il ne **fusionne pas** les dossiers conservés dans le logiciel propre d'une structure. Il résout l'identité sur le réseau OpesCare ; les systèmes connectés se rattachent au Health ID.
- Il ne **garantit pas** que chaque doublon soit trouvé immédiatement. Les analyses ont lieu chaque nuit et chaque semaine, et l'examen prend du temps humain.
- Il ne **supprime pas** l'historique clinique. La fusion regroupe les dossiers sous un dossier conservé ; rien de clinique n'est effacé.

## Ce que peuvent faire les structures

- Rechercher d'abord le patient **par Health ID ou QR**, avant tout nouvel enregistrement.
- Saisir avec soin la **date de naissance** et le téléphone.
- Si votre système gère ses propres identifiants patients, connectez-le pour que les résultats arrivent liés au Health ID — voir [la couche d'interopérabilité expliquée](/blog/identifiant-sante-interoperabilite-index-patients-explique).

## Conclusion

Les doublons ne sont pas une gêne administrative ; ce sont un risque pour la sécurité des patients. OpesCare les traite par couches : un Health ID qui évite les réinscriptions, une détection à la création et de façon programmée, un examen humain sans fusion automatique, des fusions réversibles avec des alias valables, et des recherches qui suivent les fusions.

**[Obtenez votre Health ID gratuit](https://opescare.com/signup/patient)** pour que votre historique reste au même endroit. Structures : [rejoignez le réseau](https://opescare.com/signup/organization) et cessez de créer de nouveaux doublons à l'accueil.

À lire aussi : [tous les modules OpesCare expliqués](/blog/opescare-modules-fonctionnalites-guide).
