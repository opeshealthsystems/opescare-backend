# Local GEO for Cameroon — proposal (not built)

Status 2026-10-19: there are **no city or region landing pages**. `/care-map` accepts
`?region=` and `?city=` filters (CareMapController::publicDirectory, directory.blade.php),
but those are filtered views of one page: same title, same intro, same canonical.
Facility profiles (`/care-map/facility/{id}`) already carry accurate LocalBusiness-family
JSON-LD (`partials/finder/schema/facility.blade.php`: Pharmacy / Hospital / DiagnosticLab /
Dentist / MedicalClinic; telephone, geo, hours only when held; no ratings, no sameAs). No change needed there.

## Proposal
1. Clean routes `/care-map/region/{slug}` and `/care-map/city/{slug}` (slugs derived from the
   distinct `care_facilities.region` / `city` values; 404 on unknown slug, no thin pages
   below a minimum facility count, e.g. 5).
2. Each page: own `<title>` / H1 / intro via `__('care_map_local.*')` with `:place`
   ("Pharmacies and hospitals in :place" / "Pharmacies et hôpitaux à :place"), EN/FR,
   self-canonical + hreflang, BreadcrumbList, and an `ItemList` of the listed facilities' profile URLs.
3. Counts in the intro computed live from the register; never stated as stock availability.
4. Add region/city URLs to the sitemap (SEO agent) and a "Browse by region" block on `/care-map` (nav link rule).
5. Start with the 10 regions, then the largest cities (Douala, Yaoundé, Bamenda, Bafoussam, Garoua, ...), only where data exists.
