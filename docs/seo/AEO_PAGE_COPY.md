# AEO page copy: answer-first intros for key public pages

Owner: AEO agent, 2026-10-19. Every claim matches a feature that exists in `routes/` and `app/`.
No invented figures, partners or government endorsements. FAQ schema items reuse Q&As from
`content/faq/{en,fr}.yaml` so the visible FAQ and the JSON-LD stay identical (Google requires the
FAQPage markup to match what the page shows).

**Applied so far:** medicine finder and blood finder intros, in `lang/{en,fr}/finder_search.php`
(`medicine.intro`, `blood.intro`). The other pages' lead text sits in view files or in keys shared
across pages (landing hero, owner-approved), so it is listed here for the page owners to apply.

---

## 1. Landing `/`
- **Primary question:** What is OpesCare?
- **EN (48 w):** OpesCare is a free health platform for Cameroon that helps you find a medicine in stock, blood by group, a nearby facility or an appointment, and gives you one Health ID that links your records across hospitals, pharmacies and laboratories. Clinicians see your history only with your consent.
- **FR:** OpesCare est une plateforme de santé gratuite pour le Cameroun : trouvez un médicament en stock, du sang par groupe, une structure proche ou un rendez-vous, et obtenez un Health ID unique qui relie votre dossier entre hôpitaux, pharmacies et laboratoires. Les soignants ne voient votre historique qu'avec votre consentement.
- **PAA:** Is OpesCare free? Yes, for patients. / Do I need an account to search? No. / Is it in French? Yes, fully bilingual. / Who sees my records? Only clinicians with a care relationship or your consent.
- **FAQ schema:** "What is an OpesCare Health ID?", "Is the OpesCare Health ID free?", "Do I need an account to search for medicines or blood?"
- Note: hero headline untouched (owner-approved). Candidate for supporting lead only.

## 2. Medicine finder `/network/medicine-finder` (APPLIED)
- **Primary question:** How can I find a pharmacy that has my medicine in stock?
- **EN:** Find a pharmacy that has your medicine: type its name to see pharmacies across Cameroon that have reported it in stock, with the report time and a number to call. OpesCare does not check shelves — every result is the pharmacy's own report.
- **FR:** Trouvez une pharmacie qui a votre médicament : saisissez son nom pour voir les pharmacies du Cameroun qui l'ont déclaré en stock, avec l'heure de la déclaration et un numéro à appeler. OpesCare ne vérifie aucun rayon — chaque résultat est la déclaration de la pharmacie elle-même.
- **PAA:** Why no results? No pharmacy has reported it yet, which does not mean it is unavailable. / Are prices shown? Yes in FCFA when reported. / Can I reserve? No, call the pharmacy. / Pharmacie de garde? Use the care map, then call.
- **FAQ schema:** the four "Finding medicines" items above.

## 3. Blood finder `/network/blood-finder` (APPLIED)
- **Primary question:** How can I find blood of a specific group in Cameroon?
- **EN:** Find blood by group: choose a blood group to see hospitals and blood banks that have reported holding it, with a number to call before you travel. OpesCare relays their reports — it does not count units itself.
- **FR:** Trouvez du sang par groupe : choisissez un groupe sanguin pour voir les hôpitaux et banques de sang qui ont déclaré en disposer, avec un numéro à appeler avant de vous déplacer. OpesCare relaie leurs déclarations — il ne compte pas les poches lui-même.
- **PAA:** Is availability guaranteed? No, call first. / Why empty? Nothing reported recently. / Where to donate? Hospital blood banks and transfusion centres, call ahead.
- **FAQ schema:** all four "Blood" items.

## 4. Care map directory `/care-map`
- **Primary question:** How do I find a hospital, clinic or pharmacy near me?
- **EN (44 w):** Search health facilities across Cameroon by name, type and location, then get the phone number and directions on the map. Listings come from the national facility directory and from facilities themselves, so call before you travel — opening hours and services can change.
- **FR:** Recherchez les structures de santé du Cameroun par nom, type et lieu, puis obtenez leur numéro et l'itinéraire sur la carte. Les fiches viennent du répertoire national des structures et des structures elles-mêmes : appelez avant de vous déplacer, horaires et services peuvent changer.
- **PAA:** Pharmacie de garde near me? / Wrong number, how to report? Contact page. / Can I book here? Where the facility publishes slots.
- **FAQ schema:** "How do I find a hospital or clinic near me?", "How do I find a pharmacie de garde near me?", "A facility's phone number or hours are wrong..."

## 5. Emergency `/care-map/emergency`
- **Primary question:** Where is the nearest emergency service?
- **EN (45 w):** In a life-threatening situation, go to the nearest emergency department now. This page lists nearby facilities that offer emergency care, with phone numbers and directions. Calling ahead helps the team prepare, but never delay travel to search or to call.
- **FR:** En cas de danger vital, rendez-vous immédiatement aux urgences les plus proches. Cette page liste les structures proches qui assurent les urgences, avec leur numéro et l'itinéraire. Appeler aide l'équipe à se préparer, mais ne retardez jamais le départ pour chercher ou appeler.
- **PAA:** Can doctors see my records in an emergency? Yes, logged break-glass access. / Is blood available? Check the blood finder and call.
- **FAQ schema:** "How do I find the nearest emergency service?", "Can a doctor access a patient's records in an emergency?"

## 6. Solutions: hospitals `/solutions/hospitals`
- **Primary question:** What can a hospital do with OpesCare?
- **EN (50 w):** OpesCare gives a hospital one Health ID per patient, a longitudinal record, appointments and queues, prescriptions, lab results, vitals and referrals, and works alongside the software it already runs through FHIR R4. Clinicians see history from other facilities only with the patient's consent or a recorded legal basis.
- **FR:** OpesCare donne à l'hôpital un Health ID par patient, un dossier longitudinal, rendez-vous et files d'attente, ordonnances, résultats d'analyses, constantes et orientations, en complément de ses logiciels existants grâce à FHIR R4. L'historique venu d'autres structures n'est visible qu'avec le consentement du patient ou un motif légal enregistré.
- **PAA:** Replace our software? No. / Emergency access? Break-glass, logged. / How to register? Organization sign-up, reviewed.
- **FAQ schema:** the four "For hospitals" items.

## 7. Solutions: pharmacies `/solutions/pharmacies`
- **Primary question:** Why should my pharmacy join OpesCare?
- **EN (46 w):** Patients search OpesCare for medicines; when your pharmacy reports its stock, they see you have it, call your number and come to you. Reports carry their time, prices show in FCFA, and you can dispense OpesCare prescriptions from the patient's Health ID.
- **FR:** Les patients cherchent leurs médicaments sur OpesCare ; quand votre pharmacie déclare son stock, ils voient que vous l'avez, vous appellent et viennent chez vous. Chaque déclaration est horodatée, les prix s'affichent en FCFA et vous délivrez les ordonnances OpesCare à partir du Health ID du patient.
- **PAA:** How to register? / Can we import a spreadsheet? Yes (feature in the current working tree; confirm it ships before publishing). / Cost? See pricing.
- **FAQ schema:** the three "For pharmacies" items.

## 8. Signup: organization `/signup/organization`
- **Primary question:** How do I register a hospital or pharmacy online in Cameroon?
- **EN (42 w):** Register your hospital, clinic, laboratory or pharmacy by filling in this form with the facility's details and a responsible contact. OpesCare reviews every application before activation; once approved, you create staff accounts by role and manage your listing.
- **FR:** Inscrivez votre hôpital, clinique, laboratoire ou pharmacie en remplissant ce formulaire avec les informations de la structure et d'un responsable. OpesCare examine chaque demande avant activation ; une fois validée, vous créez les comptes du personnel par rôle et gérez votre fiche.
- **FAQ schema:** "How do I register a hospital or clinic on OpesCare?", "How do I register my pharmacy on OpesCare?"

## 9. Signup: patient `/signup/patient`
- **Primary question:** How do I get a Health ID?
- **EN (44 w):** Create your free OpesCare Health ID here with your name, date of birth and a contact. It links your records across facilities on the network; it starts as provisional and becomes verified once a facility confirms your identity. Clinicians read it only with your consent.
- **FR:** Créez ici votre Health ID OpesCare gratuit avec votre nom, votre date de naissance et un contact. Il relie votre dossier entre les structures du réseau ; provisoire au départ, il devient vérifié dès qu'une structure confirme votre identité. Les soignants ne le consultent qu'avec votre consentement.
- **FAQ schema:** "How do I get a Health ID in Cameroon?", "Is the OpesCare Health ID free?", "What if I lose my Health ID card or number?"

## 10. Help `/help`
- **Primary question:** How does OpesCare work?
- **EN (40 w):** OpesCare shows you nearby facilities, what they have reported and a number to call, and gives you one Health ID for your records. Searching is free without an account; booking, records and consent need a free patient account. Every answer is below.
- **FR:** OpesCare vous montre les structures proches, ce qu'elles ont déclaré et un numéro à appeler, et vous donne un Health ID unique pour votre dossier. La recherche est gratuite sans compte ; rendez-vous, dossier et consentements demandent un compte patient gratuit. Toutes les réponses sont ci-dessous.
- **FAQ schema:** the "Account & app" and "Payments" items.
- Existing `finder_story` leads (help/faq/contact) are already answer-first; left as is.
