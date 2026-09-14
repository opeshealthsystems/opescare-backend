# What the Finder facility dashboards ARE and ARE NOT

OpesCare Finder is a **discovery, identity and coordination** product, not a
hospital information system and not an EMR. Every facility dashboard exists so
an institution can keep its public presence accurate and run the day-to-day
interactions it has with OpesCare patients. Audit against this list; anything in
the "not" column is out of scope even if a table for it exists.

## A facility dashboard IS for
- **Being findable:** listing identity, address/GPS, hours, contacts, photo,
  services/tests/medicines offered with prices in whole XAF, verification state.
- **Being reachable:** appointment requests, waitlist, queue and triage order for
  the day, patient messages, referrals in/out, blood requests, medicine
  reservations, lab test requests — receive, accept, fulfil, close.
- **Serving a Health ID holder at the counter:** exact Health-ID lookup with
  consent, the consent-scoped chart view (read), prescriptions issued/dispensed,
  lab orders → results → validation → release to the patient, vitals at triage,
  immunisations, documents/PDF sheets, break-glass with audit.
- **Running the team:** staff invites, roles at this facility, deactivation,
  who-did-what audit, notification preferences.
- **Knowing the numbers:** counts, waits, turnaround, invoices issued/collected
  — aggregates from data the platform already holds; CSV of aggregates.
- **Money at the counter only:** invoices and MTN/Orange payments for services
  rendered here; never a gate on care.

## A facility dashboard is NOT for
- Full clinical documentation (SOAP notes, nursing charts, surgical records,
  dental charting, obstetric partographs, radiology reporting/PACS).
- Admissions, discharges, transfers, bed management workflows (a ward/bed
  *register* for occupancy counts is fine; ADT is not).
- Procurement, supplier contracts, purchase orders, general ledger, payroll,
  HR, rostering/shift planning, asset/equipment maintenance.
- Insurance claims adjudication, pre-authorisation (module removed).
- Laboratory instrument interfaces, QC/QA runs, reagent lot management beyond a
  simple reorder note.
- Public-health reporting pipelines (DHIS2 exports etc.).

## Rules every audit applies
- `facility_id` only from session/PortalContextService; cross-facility = 404;
  role refusal = 403 that names the reason; no patient LIKE/enumeration.
- Clinical rows are immutable (amend/void), nothing hard-deleted.
- Care is never gated on payment or on the queue.
- Every feature = route + nav link + a view on disk; no URL-only pages, no
  dead links, no placeholder text, no hard-coded English (EN/FR 1:1).
- Never invent data; an empty state says so.
