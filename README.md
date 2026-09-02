# OpesCare Finder

Find medicines in stock, blood availability, bookable appointments, and health
facilities across Cameroon. Bilingual EN/FR. Laravel + PostgreSQL,
server-rendered Blade with a REST API.

Extracted from the OpesCare platform (`opescare@ff6aafc8`, 2026-09-02) — see
`docs/EXTRACTION.md`. Product rules and invariants: `CLAUDE.md`.

## Requirements

- PHP 8.3 + Composer
- PostgreSQL 15+ (dev database: `opescare_finder`)
- Node 20+ (Vite / Tailwind)

## Local setup

```bash
composer install
npm install
cp .env.example .env          # then fill in DB_* credentials
php artisan key:generate
createdb opescare_finder      # or create it in pgAdmin
php artisan migrate --seed
npm run build                 # or `npm run dev` while working on assets
php artisan serve             # http://localhost:8000
```

Seeded stock is demo data. Finder results only surface rows whose
`source_system` is on the provenance allow-list, so an empty finder locally is
expected and correct — do not "fix" it by publishing seeds.

## Tests

```bash
php artisan test                                    # full suite (.env.testing -> its own test DB)
DB_DATABASE=opescare_finder_test php artisan test   # point the suite at a specific test DB
php artisan test tests/Feature/EndToEnd             # end-to-end smoke
php scripts/i18n-audit.php                          # EN/FR parity — must report 0 errors
```

The suite refreshes the database schema — never point it at `opescare_finder`
(the dev DB). When running suites in parallel (CI, concurrent agents), give
each runner its own database, e.g. `DB_DATABASE=opescare_finder_aN`.

## Deploy

Target: **https://finder.opescare.cloud** — a standalone host with its own
database and its own `APP_KEY` / JWT keys, never shared with the OpesCare
platform.
