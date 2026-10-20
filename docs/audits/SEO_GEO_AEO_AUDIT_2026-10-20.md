# SEO / GEO / AEO audit — opescare.com — 2026-10-20

Read-only audit. Evidence comes from live `curl` fetches of https://opescare.com on 2026-10-20 and from reading the repo. Every finding below was observed. Sitemap findings are marked **(sitemap rebuild in progress)** because another agent is rebuilding the sitemap now.

## Executive summary
The technical base is strong. Every public page checked has a unique title and description, a self-canonical, EN/FR/x-default hreflang, `og:locale`, a Twitter card and valid JSON-LD. The 404 page returns a real 404 with `noindex`. Facility profiles carry `Hospital` + `BreadcrumbList` + `GeoCoordinates`. Articles carry `BlogPosting` + `FAQPage` + `BreadcrumbList`, and French slugs are wired through a 301. `robots.txt` names the AI crawlers, and `llms.txt`, `llms-full.txt` and `/about/facts` exist.

The biggest gaps:
1. **www.opescare.com serves a full duplicate site with a self-canonical to www.**
2. **The blog is missing from the sitemap** (rebuild in progress).
3. **No author pages, and no `url`/`sameAs` for authors or the Organization.** This weakens E-E-A-T and entity resolution.
4. **Heavy PNG images** (7.1 MB of blog images).
5. **Small text defects** in titles and `og:title`.

## Scorecard
| Area | Score | Note |
|---|---|---|
| Crawl/index (robots, status, canonicals) | B | www duplicate host; otherwise clean |
| hreflang / i18n | A- | correct pairs plus x-default; FR article slugs mapped |
| Sitemap | C (rebuild in progress) | no blog URLs; facility count looks low |
| On-page (titles, descriptions, headings) | B+ | one H1 per page; a doubled brand suffix and an escaping bug |
| Structured data | B+ | rich and valid; no sameAs; no author URL; docs uses a different entity type |
| Core Web Vitals risk | B- | 3 render-blocking CSS files on articles; large PNGs; lazy loading present |
| GEO (citability, llms.txt, entity) | B+ | llms.txt and facts page are good; author entity is thin |
| AEO (FAQ, Q&A) | A- | /faq has 43 Q&A in FAQPage; articles have FAQ blocks; no speakable; few local-intent pages |

## Findings
| ID | Sev | Evidence | Fix |
|---|---|---|---|
| F1 | **High** | `https://www.opescare.com/` returns **200**, not a redirect. It carries `<link rel="canonical" href="https://www.opescare.com">` and hreflang pointing at www. This is a full duplicate host that canonicalises to itself. | 301 www to the apex in nginx, or force the canonical/hreflang base to `https://opescare.com` (set APP_URL and stop taking the host from the request). |
| F2 | **High** (sitemap rebuild in progress) | `sitemap-pages.xml` has 84 `<url>` entries and **0 contain `/blog`**. The 10 articles, their FR slugs and the 5 categories are not listed. | Add the blog index, each article in EN and FR, and the categories with `lastmod` = dateModified. |
| F3 | Medium (sitemap rebuild in progress) | The facility sitemaps hold 2,000 + 736 = 2,736 URLs, EN only; the `?lang=fr` variants are not listed. The pages sitemap does list `?lang=fr` variants. `sitemap-pages.xml` has no `<lastmod>` in the index. | Either add `xhtml:link` alternates or list the FR URLs; add `lastmod` to the pages entry. |
| F4 | High (E-E-A-T/GEO) | `/blog/author/jude-nshome` returns 404. The article JSON-LD author is `{"@type":"Person","name":"Jude Nshome","description":…}` with no `url` and no `sameAs` (`resources/views/blog/show.blade.php:17-19`). Other posts use an Organization author "OPES Health Systems", which differs from the publisher name "OpesCare". | Add `/blog/author/{slug}` pages (bio, photo, role, posts) and set the author `url`/`sameAs`. Use one consistent org name, or link with `@id` to `#organization`. |
| F5 | Medium (GEO) | The Organization has no `sameAs`. This is deliberate: `partials/seo/entity-schema.blade.php:16` and `partials/finder/schema/site.blade.php:17` say there are no real social profiles. | When real profiles exist (LinkedIn, Facebook, Play Store listing, company registry), add them to `sameAs`. The Play Store app URL is legitimate now if the app is published. |
| F6 | Medium | `/privacy` title is `Privacy Policy &amp; Patient Data Protection \| OpesCare — OpesCare`: the brand appears twice (`lang/en/public.php:3009` already includes "\| OpesCare" and the layout appends "— OpesCare"). Its `og:title` is double-escaped: `&amp;amp;`. | Drop the brand from `page_title`. Output `og:title` with `{!! !!}` over an already-escaped value, or stop escaping it twice. Grep the other `page_title` strings for the same doubled suffix. |
| F7 | Medium | `/docs` uses a different head: its `og:title` is just "Getting Started" (no brand), its JSON-LD type is `MedicalOrganization` + `WebPage` instead of the shared `#organization` graph, and the title is generic ("Getting Started — OpesCare"). | Reuse the shared entity schema on docs and use a single `@id`. Use a developer-docs type (`TechArticle`/`WebPage`), not MedicalOrganization. Prefix titles, e.g. "OpesCare API docs — Getting Started". |
| F8 | Medium (CWV) | `public/blog-images` is 7.1 MB of PNG; for example `duplicate-patient-records-mpi.png` is 341 KB. Images do have width/height, `loading="lazy"` and `decoding="async"`. | Serve WebP/AVIF with `<picture>`. Keep the hero image eager with `fetchpriority="high"`. |
| F9 | Low (CWV) | Article pages load 3 blocking stylesheets: `assets/finder.css`, `css/od-public.css`, `css/blog.css`. Scripts are `defer`, which is good. | Inline critical CSS or merge the files; preload the main CSS. |
| F10 | Low (a11y/SEO) | The related-post cards on articles use `<img … alt="">` (e.g. how-hospitals-join-opescare.png). | If the card title sits next to the image, empty alt is acceptable. Otherwise give the article title as alt. |
| F11 | Low | Query-string variants such as `/?utm_source=x` and `/network/medicine-finder?q=paracetamol` return 200 with the correct canonical. `/blog/` with a trailing slash returns 200 (canonical `/blog`), not a 301. | Acceptable thanks to the canonicals. A 301 for the trailing slash is optional hardening. |
| F12 | Low | `/drugs`, `/blood` and `/network/dental-finder` return 404. The brief mentions a dental finder; none is live. llms.txt lists medicine, blood, lab-test and imaging finders only. | Add the dental finder if it is planned; consider 301s from the obvious short paths (/drugs to /network/medicine-finder, /blood to /network/blood-finder). |
| F13 | Low (AEO) | No `speakable` markup anywhere (grep of resources/views finds none). There are no local-intent landing pages, e.g. "pharmacies in Yaoundé" or "open now". Facility pages exist per facility, but there are no city/type hub pages. | Add city × type hub pages (Yaoundé pharmacies, Douala hospitals…) with a short answer paragraph, `ItemList` and FAQ. Optionally add speakable to /about/facts. |
| F14 | Low | The finder pages use generic titles: "Find medicine — OpesCare". | Use "Find a medicine in stock at a pharmacy in Cameroon — OpesCare" (and the FR equivalent). |
| F15 | Info | The homepage meta description says "2,736 facilities" and the Care Map says "2,700+"; llms.txt does not state a count. | Keep the numbers consistent and generated from one source. |

## What is already good (verified)
- HTTP redirects to HTTPS (301). opescare.cloud redirects to opescare.com with the path kept (301).
- Each page has a unique title and description. Every page checked has exactly one `<h1>`. `<html lang>` switches to `fr` with `og:locale` `fr_CM` under `?lang=fr`.
- hreflang en/fr/x-default is present on every page checked. EN articles point to their FR slug (`/blog/trouver-medicament-en-stock-cameroun?lang=fr`), and the EN slug with `?lang=fr` 301s to the FR slug.
- FR pages self-canonicalise to the `?lang=fr` URL. This is correct for the query-parameter language scheme.
- The 404 page returns status 404 with `noindex`.
- robots.txt blocks the private areas, allows AI crawlers explicitly per agent, and lists the sitemap.
- Home JSON-LD: Organization + WebSite + SearchAction. The target `/care-map?query=` works: it returns 200, and the page uses `name="query"`.
- Facility profile JSON-LD: Hospital + PostalAddress + GeoCoordinates + BreadcrumbList, with localised descriptions and no invented ratings.
- Articles: BlogPosting with dates, image, wordCount, inLanguage; FAQPage (5 Q&A); BreadcrumbList; RSS feed; sources and key takeaways in the controller; an author bio box.
- /faq: FAQPage with 43 Question/Answer pairs. /about/facts is a citable facts page.
- llms.txt (3.3 KB) and llms-full.txt (7 KB) contain clear factual statements: operator, bilingual, free, the provenance rule, "not an emergency service", and the `?lang=fr` convention.

## Prioritised fix list
1. F1: 301 www to the apex, and pin the canonical base to the apex.
2. F2/F3: put the blog (EN+FR) into the sitemap and add the FR facility alternates (coordinate with the sitemap rebuild).
3. F4: build author pages and give the author entity a url/sameAs; make the org naming consistent.
4. F6/F7: fix the doubled brand and double-escaped `og:title`; align the docs schema and title.
5. F8/F9: convert blog images to WebP and cut down the render-blocking CSS.
6. F13/F14: add city/type hub pages with answer paragraphs and FAQ; sharpen the finder titles.
7. F5: add Organization `sameAs` once real profiles exist.
