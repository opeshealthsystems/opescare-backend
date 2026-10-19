# AEO report, 2026-10-19

## 1. Question research
Method: web search (EN + FR) plus the product's own help/FAQ copy. Phrasings below are the People-Also-Ask style forms these searches and their result pages use. Search results for Cameroon-specific health queries are thin; that scarcity is itself the opportunity.

| # | Question (EN / FR) | Evidence |
|---|---|---|
| 1 | Where is the pharmacie de garde today? / *Quelle est la pharmacie de garde aujourd'hui à Yaoundé / Douala ?* | Search returned no reliable up-to-date Cameroonian rota; only a Douala directory entry (maligah.com). Rotas come from the Ordre des pharmaciens / door notices. Unmet demand. |
| 2 | How do I find a medicine in stock? / *Où trouver tel médicament ?* | Implied by the pharmacie de garde gap; no Cameroonian stock-search source ranks. |
| 3 | Where can I donate blood? / *Où donner son sang au Cameroun ?* | allodocteurs.fr, camer.be, le360.ma, Cameroon Tribune, WHO-hosted national transfusion policy (Jan 2025): shortages, hospital blood banks, CNTS, replacement donation. |
| 4 | Conditions to donate blood / *Conditions pour donner son sang* | Same sources; questionnaire + clinician interview described. |
| 5 | Which law protects health data in Cameroon? / *Loi protection des données Cameroun* | DLA Piper, lexafrica.com, techhiveadvisory.africa, afriwise: Law No. 2024/017 of 23 Dec 2024, health data is sensitive, transition to ~June 2026. |
| 6 | Who can see my medical records? / *Qui peut voir mon dossier médical ?* | Follows from #5; universal PAA pattern. |
| 7 | How does consent to medical records work? | Same. |
| 8 | How do I register a hospital/pharmacy online? / *Comment inscrire ma pharmacie en ligne ?* | Brief topic; no ranking Cameroonian answer found. |
| 9 | What is a health ID? / *Qu'est-ce qu'un identifiant de santé ?* | Brief topic; product core. |
| 10 | Can I book a doctor online in Cameroon? / *Prendre rendez-vous chez un médecin en ligne* | Brief topic. |
| 11 | Can I pay hospital bills with Mobile Money? | MoMo/Orange Money are the dominant rails; product supports both. |
| 12 | Nearest emergency / *Urgences les plus proches* | Universal voice query. |

Not invented: no search-volume numbers are claimed; none were available.

## 2. What changed
| File | Change |
|---|---|
| `content/faq/en.yaml` | NEW: 43 Q&As across all 10 categories, answers 40-53 words, all values quoted YAML (parses with PyYAML) |
| `content/faq/fr.yaml` | NEW: 43 Q&As, 1:1 with EN (same order/category/link), natural French, 40-54 words |
| `lang/en/finder_search.php` | `medicine.intro`, `blood.intro` rewritten answer-first (honesty caveat kept) |
| `lang/fr/finder_search.php` | same two keys, FR |
| `docs/seo/AEO_PAGE_COPY.md` | NEW: 10 pages, primary question, EN/FR intros, PAA, FAQ-schema picks |
| `docs/seo/AEO_REPORT.md` | NEW: this file |

`scripts/i18n-audit.php`: ALL FILES PASS. All FAQ links verified in `route:list` (one links to `/blog/health-data-law-innovation-cameroon`; the EN article exists, the FR one did not yet when checked).

Caveats for review: the pharmacy spreadsheet-import Q&A describes uncommitted work in the current tree (`PharmacyStockImportController`) — drop it if that does not ship. Answers mentioning forgot-password, consent withdrawal and patient notification on break-glass follow the project memory and CLAUDE.md; worth a quick product check.

## 3. Voice answers (29 words or fewer)
1. **What is OpesCare?** OpesCare is a free Cameroonian health platform to find medicines, blood and facilities, book appointments, and keep one Health ID linking your medical records. (25)
2. **How do I find a pharmacy with my medicine?** Search the medicine name on OpesCare's medicine finder, see pharmacies that reported it in stock, then call to confirm before you go. (23)
3. **Where is the pharmacie de garde?** Open the OpesCare care map for nearby pharmacies and their numbers, then call to check which one is on duty tonight. (21)
4. **How do I find blood type O negative?** Choose O negative in OpesCare's blood finder to see hospitals and blood banks reporting it, then call before travelling. (20)
5. **Where can I donate blood in Cameroon?** At hospital blood banks and transfusion centres. Find one near you on the OpesCare care map and call to confirm hours. (22)
6. **What is a Health ID?** A single personal identifier that links your medical records across OpesCare hospitals, pharmacies and labs, readable by clinicians only with your consent. (23)
7. **How do I get a Health ID?** Sign up free at opescare.com/signup/patient, or ask any facility on the OpesCare network to register you. (19)
8. **Who can see my medical records?** Only clinicians caring for you or those you approve. Every access is logged, and you can see who opened your record. (22)
9. **How does medical record consent work?** A clinician requests access, you approve or refuse, and an approved consent is limited in scope and time. You can withdraw it. (23)
10. **Which law protects health data in Cameroon?** Law No. 2024/017 of December 2024 on personal data protection, which treats health data as sensitive. (17)
11. **How do I register my pharmacy online?** Fill in the organization sign-up form on opescare.com and choose pharmacy. OpesCare reviews the application before activating it. (20)
12. **How do I register a hospital on OpesCare?** Complete the organization sign-up form with the facility's details. After review, you add staff accounts by role. (19)
13. **Can I book a doctor online in Cameroon?** Yes, at facilities publishing slots on OpesCare. Create a free patient account, pick a provider and choose a time. (21)
14. **Can I pay my hospital bill with Mobile Money?** Yes. Where the facility bills through OpesCare, you can pay with MTN Mobile Money or Orange Money, in FCFA. (20)
15. **Where is the nearest emergency room?** If it is life-threatening, go now. OpesCare's emergency page lists nearby emergency services with phone numbers and directions. (19)
