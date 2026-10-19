# GEO report — OpesCare in AI answer engines (2026-10-19)

## What engines say today
A web search for "OpesCare Cameroon health" (2026-10-19) returned **nothing about OpesCare** —
only generic Cameroon health pages (Wikipedia, World Bank, expat guides). The brand has no
third-party footprint yet, so answer engines will either not know it or confuse it with
similarly named products. The job is to give them one consistent, citable source.

## Canonical descriptions (use verbatim everywhere)
- **One line (EN):** OpesCare is a free, bilingual (English and French) web service for Cameroon that helps people find a medicine at a nearby pharmacy, find blood products at blood banks, book an appointment with a provider, and browse the national health facility directory.
- **One line (FR):** see `lang/fr/facts.php` → `definition`.
- **Operator:** Opes Health Systems Sarl, Republic of Cameroon. No government endorsement claimed.
- **Never say:** guaranteed stock, insurance, telemedicine video, emergency service, facility counts (not stated until computed live).

Sources of truth: `lang/{en,fr}/facts.php`, `public/llms.txt`, `public/llms-full.txt`, `/about/facts`.

## Shipped
- `public/llms.txt`, `public/llms-full.txt` (llmstxt.org format).
- `/about/facts` (`public.facts`, `resources/views/public/facts.blade.php`, EN/FR).
- `partials/seo/entity-schema.blade.php` — SoftwareApplication (HealthApplication, Web/Android, free 0 XAF) + Organization contactPoint, linked by @id to the existing site schema.
- `partials/seo/footer-facts-link.blade.php`.
- Local GEO: see `GEO_LOCAL_PROPOSAL.md` (no city pages exist; facility schema already accurate).

## Monitoring checklist (monthly)
1. Ask ChatGPT, Gemini, Perplexity, Copilot: "What is OpesCare?", "Comment trouver un médicament en pharmacie à Douala ?", "blood bank finder Cameroon". Record whether OpesCare appears and whether the description matches the canonical line.
2. Google: `"OpesCare"` and `site:opescare.com` — check AI Overview text and indexed page count; confirm `/about/facts`, `/llms.txt` return 200.
3. Rich Results Test / Schema validator on `/`, `/about/facts`, one facility profile: zero errors, one Organization entity.
4. Search Console: queries containing "opescare", city + "pharmacie de garde"-style queries.
5. Any wrong claim found (insurance, stock guarantee, government) → add a correcting line to the facts page and llms-full.txt, bump its date.
6. Once real profiles exist (LinkedIn, Facebook, Play Store listing), add them to `sameAs` in `partials/finder/schema/site.blade.php`.
