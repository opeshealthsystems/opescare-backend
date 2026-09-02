# OpesCare Finder — Extraction Record

This repository is a standalone product carved out of the OpesCare platform
monorepo. This file is the permanent record of that extraction.

## Source

| | |
|---|---|
| Source repository | OpesCare platform monorepo (`opescare`), `apps/api-laravel` |
| Source commit | `ff6aafc8` |
| Extraction date | 2026-09-02 |
| Local baseline commit | `f6e3053` — "baseline: OpesCare Finder extracted from opescare@ff6aafc8 — full tree before pruning" |

The baseline commit is the **full, unpruned** platform tree. Everything removed
since is visible with:

```bash
git diff --stat f6e3053            # what changed since the full-tree baseline
git diff f6e3053 -- <path>         # exact content of any pruning decision
```

Do not reconstruct pruned code from the baseline without checking `CLAUDE.md`
("Deliberately absent") first — most of it was removed on purpose.

## What was pruned (by intent)

The platform tree carried ~45 domain modules. Finder keeps only what the four
consumer services need: drug finder, blood finder, appointment booking,
facility directory. Removed as out of scope: clinical records/encounters,
Health-ID, FHIR R4 / HL7 / SMART-on-FHIR, consent-grant plumbing, insurance,
billing/subscriptions, inventory operations, telemedicine, DHIS2/PACS
integrations, and the platform knowledge base under `docs/` (this file replaced
it). The authoritative diff is `git diff f6e3053`, not this prose.

The platform's **sealed** security files remain sealed **in the platform
repository**. Their seal does not transfer here: files that survived the
extraction are ordinary files in this repo, governed by this product's own
invariants (see `CLAUDE.md`).

## Data carried over

Domain tables kept for the four services:

- `care_facilities` / `facilities` — national facility register + directory data
- `medicines` — medicine reference catalogue
- `stocks` — live pharmacy stock (provenance-gated via `source_system` allow-list)
- blood availability tables — blood products / stock by type
- `slots` / `providers` — bookable capacity for appointments
- `users` / `roles` — accounts and role-driven access

Seeded rows are demo data. The provenance allow-list means none of it is
published as live availability — an empty finder on a fresh install is correct.

## Secrets — fresh, never shared

- `APP_KEY`: regenerated for this product. Not the platform's key.
- JWT keypair (`storage/keys/jwt_private.pem` / `jwt_public.pem`): freshly
  generated for this product. Verified distinct from the platform's keypair at
  extraction time (different SHA-256 fingerprints).
- Tokens, sessions, or ciphertext from the platform are therefore **not** valid
  here, and vice versa. Never copy key material between the two repos.

## Known open items

- Pruning is tracked as uncommitted work on top of `f6e3053`; the first
  post-prune commit will freeze the real product tree.
- Config still carries platform-era files (e.g. `hl7.php`, `dhis2.php`,
  `health_id.php`, `kms.php`) pending removal alongside their domains.
- Coverage is the real launch blocker inherited from the platform: only a small
  fraction of facilities publish live stock (~9/379 pharmacies) or bookable
  slots (~17/897 facilities). The product is correct while sparse — growing the
  allow-listed feeds is a partnerships problem, not a code problem.
- Deploy target `finder.opescare.cloud` needs its own host provisioning
  (database, queue worker, scheduler); nothing is shared with the platform
  deployment.
