# Hospital / clinic / health-centre administration dashboard — audit (2026-09-14)

Scope: what a facility's own administrator sees after signing in. Roles as
seeded by `database/seeders/RolesSeeder.php:121-125`:

| Role | Sidebar (`dashboard_profile_key`) | Seeded? |
|---|---|---|
| `facility_admin`, `clinic_admin`, `hospital_admin` | `facility_admin` | yes |
| `facility_ceo` | `facility_executive` | yes |
| `branch_admin` | `multi_facility_admin` | yes |
| `department_manager` | — | **no** — a `dashboard_profiles` row (`DashboardProfilesSeeder.php:260`) and an `EnsurePortalAccess` bucket entry, but no `roles` row; nobody can hold it |
| `multi_facility_admin` | — | **no** — it is a *profile key*, not a role; `branch_admin` is the role that renders it |

Every claim below cites the file and line it was read from. Nothing is
inferred from a sidebar alone.

## Verdict

**A facility administrator can invite staff and set the service catalogue.
That is the whole job the portal lets them do.** Their landing page is the
platform governance page with the god-mode blocks hidden (`portals/admin/index`),
whose four remaining tiles are Health-ID lookup counters, one of which is
platform-wide. There is no facility dashboard (appointments, queue, lab,
beds, billing, team size), no way to end a posting or change a role, no
way to see a colleague's qualifications, no departments page, no reports,
and the "executive" tier is byte-for-byte the admin tier minus the queue
link.

## What works (route → evidence)

| Page | Route | Evidence |
|---|---|---|
| Landing | `GET /admin` → `portals.admin` | `routes/web.php:423`; `AdminPortalController@index` (`app/Http/Controllers/MedicalId/AdminPortalController.php:18-66`) |
| Team: invite / reissue / revoke / approve / decline join requests | `/facility/team*` | `routes/web.php:371-381`; `FacilityStaffController` — facility from `PortalContextService` only (`:62-68`), roles from `FacilityStaffInvite::INVITABLE_ROLES` |
| Service catalogue | `/facility/catalogue` | `capability:service_catalogue` held by `$facilityAdmins` (`config/staff_capabilities.php:158`) |
| Patient queue (read + act) | `/facility/queue` | `capability:queue` held by `$facilityAdmins` (`config/staff_capabilities.php:104`); NOT linked on `facility_executive` |
| Facility switcher | `/select-facility` | `partials/sidebars/_switch_facility.blade.php` self-gates on `FacilityMembershipService::hasChoice()` |

## Findings, with evidence

### (a) The landing page is the platform page with the lights off — and one number leaks
`AdminPortalController@index` (`:20-31`) builds four tiles:

| Tile | Query | Facility-scoped? |
|---|---|---|
| Total Health IDs | `Patient::whereNotNull('health_id')->when($facilityId, …)` | yes |
| **Active tokens** | `HealthIdQrToken::where('status','active')->count()` | **no — platform-wide** (`:26`) |
| Total lookups | `MedicalIdAccessEvent::when($facilityId, …)` | yes |
| Denied lookups | same, `result = denied` | yes |

`facilityId()` (`:13-16`) reads `session('active_facility_id') ?? primary_facility_id`
— acceptable, but it is a private re-implementation of `PortalContextService::facilityId()`
and, unlike `FacilityStaffController`, a null facility does **not** abort: the
`when()` guards silently drop the scope and a facility-tier account with no
context sees platform totals for three of four tiles. Platform revenue/user
counts are correctly gated (`:36-46`, `@platformadmin` in the view).

The view (`resources/views/portals/admin/index.blade.php`) is titled "Admin
Governance Portal" and its facility-visible content is: four Health-ID counters
and a "Recent Security Events" table of Health-ID lookups. **None of the
numbers an administrator answers for** — today's appointments, no-shows, queue
wait, lab backlog, beds, money, headcount — appears anywhere.

### (b) Departments / wards: tables exist, nothing writes them, no page reads them
- `staff_profiles.department` is a free-text string
  (`database/migrations/2026_05_27_000002_create_staff_hr_tables.php:22`);
  `department_assignments` (`:51-54`) is also free-text. There is **no
  Department model** and no `departments` table.
- `wards`, `beds`, `admissions`, `bed_transfers` exist
  (`2026_05_27_000006_create_ward_management_tables.php`) with models `Ward`,
  `Bed`, `Admission`. **No controller, route or seeder writes them**
  (`grep -rln "Ward::create\|Bed::create\|Admission::create" app database` → nothing)
  and no route reads them. `Ward::occupancyRate()` is dead code.
- `DemoDepartmentsSeeder` seeds `staff_profiles` + `department_assignments`
  for the demo hospital only; no page displays them.

### (c) Staff roster is read-only beyond invitations
`FacilityStaffController@index` lists staff (`:88-118`) with name, email, role
at this facility, account status. There is **no** action to:
- end a posting (`FacilityRoleAssignment.is_active` has no web writer other than approval),
- change a role at this facility,
- view a member's profile / qualifications. The professional module
  (`ProfessionalProfileController`, `/professional/profile`) is **self-service
  only** (`routes/web.php:242-245`); `ProviderCredential` rows (verified_at,
  expiry) are visible to nobody but the holder.

Consequence: a doctor who leaves keeps their login and their posting until a
platform admin intervenes. Login refuses `status === 'suspended'`
(`PublicPageController.php:350`), so the switch exists; nothing facility-side
throws it. Note `User::roleAtFacility()` (`app/Models/User.php:104-116`)
**falls back to the account role** when no active assignment exists, so ending
the posting alone does not lock a home-facility user out — a deactivation must
also suspend the account when this is their home facility.

### (d) Facility KPIs — every source exists, nothing reads them for an admin
| KPI | Source of truth | Read by an admin page? |
|---|---|---|
| Appointments today / no-shows | `appointments.scheduled_at`, `status` (`no_show` written at `StaffPortalController.php:317`) | no |
| Queue: checked-in, waiting, median wait | `PatientQueueService::summaryFor()` (`app/Services/Queue/PatientQueueService.php:313-357`) — already computed, Postgres percentile | only on `/facility/queue`, which `facility_executive` does not link |
| Lab: open orders, resulted today, turnaround | `lab_orders.ordered_at / resulted_at`, `LabOrder::OPEN_STATUSES` | no |
| Beds / occupancy | `wards`, `beds.status` | no (and empty everywhere, see (b)) |
| Billing: collected today, outstanding | `invoices.paid_amount / balance_amount / paid_at / facility_id` | `/facility/billing` exists (`routes/billing.php:44`) but `capability:billing` is `$billing + $frontDesk` only (`staff_capabilities.php:130`) — **an admin is 403 there and it is not linked** |
| Team size, open invites, pending joins | `facility_role_assignments`, `facility_staff_invites`, `facility_join_requests` | counted nowhere |

### (e) Reports / exports
None for the facility tier. `FacilityReport`, `AuditExport`, `KpiDashboard`
models exist; every route under `/admin/reports`, `/admin/kpi` is platform-only
(`RequirePlatformAdmin::PLATFORM_ONLY_PREFIXES`, `:45-66`).

### (f) Multi-facility switching
Works for anyone with >1 active posting via the shared `_switch_facility`
partial. `multi_facility_admin.blade.php` links `select-facility`
unconditionally (`:27`) and does **not** include `_switch_facility`, so a
`branch_admin` with one posting sees a switch to nowhere. It also omits the
catalogue and queue links its capabilities grant, although `branch_admin` is
admitted to both buckets (`EnsurePortalAccess.php:66,88`) and holds both
capabilities — only the sidebar forgets.

### (g) `facility_executive` vs `facility_admin`
`facility_executive.blade.php` is `facility_admin.blade.php` minus the queue
link. Same capabilities (`facility_ceo` ∈ `$facilityAdmins`), same landing,
same 403s. There is no executive content (trend, occupancy, revenue) to justify
a separate profile; the difference today is one missing link.

### (h) Navigation
- Every sidebar link on the three partials resolves (`SidebarLinksAreReachableTest`
  covers `facility_admin`, `hospital_admin`; `facility_ceo` and `branch_admin`
  are **not** in `rolesUnderTest()`).
- URL-only routes reachable by admins with no link: `/facility/queue` (executive),
  `/facility/catalogue` (branch_admin), `/select-facility` (admin/executive
  when single-posted — correctly hidden).
- `/admin/*` beneath the landing is all 403 for this tier by design.

### (i) Placeholders / hardcoded English / facility_id from input
- `portals/admin/index.blade.php` uses `__('…') ?: 'English'` fallbacks on
  every string — bilingual, but the fallback pattern hides a missing FR key.
- `Ward::wardTypes()` returns hardcoded English labels (dead code today).
- `facility_id` from input: **none found** in the admin-tier controllers;
  `AdminPortalController` reads session/user, `FacilityStaffController` reads
  `PortalContextService`. ✔
- No invented numbers: the only dishonest figure is the platform-wide
  `active_tokens` tile.

## Gaps, ranked

| # | Gap | Size |
|---|---|---|
| H-1 | No facility dashboard: admin lands on Health-ID governance counters; one tile is platform-wide | M |
| H-2 | Roster is read-only: cannot end a posting, change a role, or see qualifications | M |
| H-3 | Executive sidebar = admin sidebar minus a link; branch_admin sidebar misses catalogue/queue and shows an unconditional switch | S (sidebars — lead) |
| H-4 | Billing totals invisible to the people who answer for them (`capability:billing` excludes admins) | S (capability map — human call) |
| H-5 | Wards/beds/departments: schema without a writer or a reader | L (needs a product decision) |
| H-6 | No facility-tier reports/exports | L |
| H-7 | `department_manager` bucket/profile with no role | S (delete or seed) |
| H-8 | `facility_ceo`/`branch_admin` not in `SidebarLinksAreReachableTest` | S |

## Build (same day) — what was done and where

Portal bucket constraint: `EnsurePortalAccess` is off-limits and admits these
roles only to `facility/team`, `facility/catalogue`, `facility/queue` and the
`/admin` landing (`/admin/*` is platform-only). So the new pages live under
**`/facility/team/...`**, gated by the existing `capability:facility_team`.

| Gap | Status | Where |
|---|---|---|
| H-1 | **built** — `/facility/team/overview` (`portals.facility.admin.overview`): facility-scoped tiles from existing tables only — appointments today / no-shows / checked-in, queue (via `PatientQueueService::summaryFor`), lab open / resulted today / median turnaround, beds (only when the facility has wards), billing collected today / outstanding, team active / open invites / pending joins. Every tile is a count over one `facility_id`; nothing is invented, empty stays empty. | `routes/facility_admin.php`, `app/Http/Controllers/MedicalId/FacilityAdminDeskController.php`, `app/Services/Portal/FacilityAdminDeskService.php`, `resources/views/portals/facility/admin/overview.blade.php` |
| H-2 | **built** — member page `/facility/team/members/{user}` (profile, role here, postings, credentials with verified/expiry, staff profile + department); `POST …/role` (change role at this facility, allow-list = `INVITABLE_ROLES`, never self, never a non-invitable holder); `POST …/deactivate` (ends the posting; suspends the account only when this is their home facility and they hold no other active posting); `POST …/reactivate`. Every write is an `AuditEvent` with before/after state. Cross-facility → 404. | same controller/service; `resources/views/portals/facility/admin/member.blade.php`; "Manage" column added to `portals/facility/team.blade.php` |
| H-3 | sidebar snippets recommended below — **not applied** (sidebars off-limits) | |
| H-4 | not built — widening `capability:billing` is a human call; the overview reads invoice totals directly instead | |
| H-5 | not built — no Department model; wards have no writer. The overview shows bed occupancy only if wards exist, so the tile appears the day the ward desk does | |
| H-6, H-7, H-8 | not built — flagged | |

### Sidebar snippets for the lead (Lucide only, `#0F4C81`)
Add to `facility_admin.blade.php`, `facility_executive.blade.php` and
`multi_facility_admin.blade.php` inside the Facility/Administration section,
before the Team link:

```blade
@capability('facility_team')
<a href="{{ route('portals.facility.admin.overview') }}" class="sidebar-link {{ request()->routeIs('portals.facility.admin.overview') ? 'active' : '' }}">
    <i data-lucide="gauge"></i><span>{{ __('facility_admin.nav_overview', [], $l) }}</span>
</a>
@endcapability
```
and, for `multi_facility_admin.blade.php`, replace the unconditional
`select-facility` link with `@include('partials.sidebars._switch_facility')`
and add the `@capability('service_catalogue')` / `@capability('queue')` links
the other two partials carry.
