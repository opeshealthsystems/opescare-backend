# MARKETING PLAN — OpesCare Finder (Hermes agent operations)

> Companion to `docs/HERMES_PLAYBOOK.md` (platform facts + claims contract).
> Hermes executes this plan; the owner approves modifications. Anything
> Hermes publishes must pass the playbook §11 hygiene checklist first.
> Last review: 2026-10-20.

## 0. Objectives (90-day horizon)

| # | Objective | Measure |
|---|---|---|
| O1 | **Unlock the supply loop**: first live stock reporters | N facilities with non-demo source_system rows on the finder (provenance-gated) — weekly SQL count from admin desk |
| O2 | Fill the top of funnel | Leads created in `/admin/leads` by week × segment |
| O3 | Convert evaluation → onboarding | demo-login → onboarding-started → go-live readiness scores |
| O4 | Brand presence in Cameroon health & tech circles | LinkedIn followers/engagement; directory claim completions |
| O5 | First B2B integration conversation (FHIR) | certification-desk partner created |

**True north = O1.** Every week's activity should attempt to add at least one
*real* shelf to a finder query. Reporting story: "a patient found medicine in
our query because a pharmacy entered it" is the message that converts the
next pharmacy.

## 1. Non-negotiable prerequisite (owner action, blocks Phase 2 email)

Production **email is currently broken** (null MX at Hostinger, recorded in
`docs/HANDOVER.md`). Consequences: any email campaign bounces, no email
invites, password resets for new staff dead. **Hermes runs Phase 1
(LinkedIn/DM/WhatsApp/call) regardless.** Do not schedule bulk email before
the owner confirms MX fix + SPF/DKIM.

## 2. Phases

### Phase 0 — Assets week (now)
- [ ] Brand kit: #0F4C81 palette, logo pack (from repo public assets), Lucide
      icon set reference — one folder the agent can screenshot from.
- [ ] Screenshot library from the **demo host only** (one per segment desk:
      pharmacy stock, lab worklist, imaging worklist, queue, billing, blood
      inventory, public finder results, patient app from Play listing).
- [ ] 10-minute demo walk script per segment (from DEMO_ACCOUNTS landing
      pages) + 30-second pitch EN/FR.
- [ ] Landing URLs to give prospects: `/` (public finder), `/login` (one-click
      demo), `/privacy`, `/verify/health-id`.
- [ ] LinkedIn company page + personal founder profile copy (EN/FR bios,
      claims-checked §3).
- [ ] (Owner) Kill-demo-decision: confirm whether prospects demo on
      opescare.com (current state) or a dedicated eval host.

### Phase 1 — Warm network + founder-led (weeks 1–4)
- Founder posts (LinkedIn) 2–3/week. Pillars (rotate):
  1. **Product truths** — screenshot + one honest line ("empty means empty").
  2. **Supply-loop parables** — the pharmacy that joined and why.
  3. **Honest health-tech** — consent, provenance, offline healthcare
     realities; the "we don't fabricate availability" stance.
  4. **Market education** — how to use the finder, Health ID explainer.
- 20 warm-network founder DMs/week (prior clinics/hospitals the owner knows).
- 10 cold outreach messages/day split per §5 audience table
  (LinkedIn DM for hospitals; WhatsApp/call for pharmacies; email once MX fixed).
- Every conversation logged in `/admin/leads` (source=hermes_outreach).

### Phase 2 — Segment campaigns (weeks 5–8)
- **Pharmacy sprint**: partner with one Douala pharmacy as the pilot; goal =
  first non-demo stock rows in the finder this phase. Leverage: free listing
  edits + prices + counter sales desk.
- **Blood bank first-response**: blood banks convert fastest (single table
  to update → instantly public). 5 pitches/week target to blood banks.
- **Diagnostic/imaging cluster**: pitch lab+imaging together within one
  district (they share the results flow).
- Referral mechanic: every onboarded facility gets one referral credit
  tracked via leads desk (manual while early).
- LinkedIn: 1 feature-of-the-week post + 1 customer-story post (once the
  first real facility ships — never fabricate one; playbook §2 rules).

### Phase 3 — Institutions & partnerships (weeks 9–12)
- Map & approach (owner-led, Hermes prepares dossiers in EN/FR):
  national professional bodies (medical/dental/pharmacy councils &
  associations), district health services, insurance mutuals for future
  rails (positioning only — do not sell insurance features: playbook §3),
  MINSANTE DHIS2 contact once the aggregate pipeline is approved.
- Showcase the **onboarding + readiness scoring**: a district or a chain
  onboards as a programme with metrics from day one.
- First partner-API/FHIR conversation target (one integration partner).

### Phase 4 — Compounding (quarter 2)
- Paid amplification only on proven organic angles.
- Case study pack (EN/FR PDF) from 2–3 live facilities.
- Directory-badge campaign: claims flow drives listing verification growth
  (public trust asset) — coordinated with `admin.care-map.review` staffing.

## 3. Weekly operating rhythm (Hermes)

| Day | Activity |
|---|---|
| Mon | Pipeline review in leads desk; pick 10 highest-intent follow-ups |
| Tue | 10 cold DMs (LinkedIn) — segments per mix |
| Wed | Content day: draft 2 posts (EN+FR), pass playbook §11 check, schedule |
| Thu | 10 WhatsApp/pharmacy touches + 5 blood-bank/diagnostic touches |
| Fri | 10 DMs + referral asks; log everything; update KPI sheet |
| Sun | Weekly report to owner: leads, demos opened, objections heard, O1 count |

Message discipline: **1 message per channel per week until reply**; max 3
touches total; always offer the one-click demo (no attachments; LinkedIn
normalises them poorly) and end with a specific next step.

## 4. Message templates (EN + FR, same skeleton; claims are playbook-bound)

### A. LinkedIn cold DM — pharmacy
- EN: *"Hello [Name] — I work on OpesCare Finder (opescare.com), a national
  health search for Cameroon. Patients can already look up your pharmacy on
  our directory. Every listing is free — the next step, if you want it, is
  your own stock desk so patients searching a medicine see when you have it
  (and we never show a stock claim you didn't make). Login demo takes 3 min —
  want me to send the demo login?"*
- FR: « Bonjour [Nom] — je travaille sur OpesCare Finder (opescare.com), un
  moteur de recherche santé national pour le Cameroun. Votre pharmacie est
  déjà visible dans notre annuaire. C'est gratuit. Si vous voulez, un guichet
  stock à vous, relié à la recherche publique — et nous n'affichons jamais
  une donnée de stock que vous n'avez pas saisie. 3 minutes pour le voir :
  je vous envoie l'accès démo ? »

### B. LinkedIn cold DM — hospital / clinic / diagnostic centre
- EN: *"Hello [Name] — OpesCare Finder (opescare.com) gives your facility a
  free public listing plus the desks your staff already do by hand: reception
  & queue, appointments, lab/imaging worklists, billing, referrals, patient
  records with consent. Regional leaders in either Douala or Yaoundé are
  onboarding early — 10-minute demo?",
  plus one punchy fact (§2 table; e.g. directory live).*
- FR equivalent via the FR skeleton above.

### C. Follow-up (touch 2, 3 days later)
Give one new fact + the demo credentials, never "just bumping".

### D. Post body (product-truth post) — structure
1. one-line hook (audience pain, no superlatives)
2. screenshot of the honest state (empty state OK — that IS the brand)
3. one platform fact from §2 (numbered claim, no invented stats)
4. CTA: claim your listing / request demo → opescare.com

### E. WhatsApp/call opener (pharmacy, courier-culture channels)
Short FR voice note + text pair. Voice script ≤ 30 s: who we are, what they
get free, the finder visibility ask, the call ask.

## 5. KPI dashboard (Hermes reports weekly, owner reviews monthly)

| Metric | Source | Target (90 d) |
|---|---|---|
| Facilities with live provenance rows (O1) | admin KPI desk / SQL | pharmacies ×5, labs ×3, blood banks ×2 |
| Leads created (`/admin/leads`) | leads desk | 200 (60% pharmacy/diagnostic mix) |
| Demo sessions opened | one-click demo logins (anon-safe count is possible via notifications/analytics desk) | 80 |
| Onboarding started → go-live readiness ≥ threshold | `/admin/onboarding`, `facilities.readiness` | 20 → 8 |
| Directory claims submitted | care-map review desk | 40 |
| LinkedIn (followers, qualified comments, DMs→demo) | platform | steady +weekly; record raw counts |
| Reply rate / meet rate on DM + WA sequences | leads desk notes | ≥10% / ≥5% |
| B2B partner certification conversations | certifications desk | 1 |

Cadence: KPI Sunday report + monthly narrative to owner (what messaging
converted; what objections repeat; small price-of-copy adjustments).

## 6. Risk register (live, top-5)

| Risk | Mitigation |
|---|---|
| Email broken MX persists into Phase 2 | Don't ramp email; LinkedIn/WA/call carry the plan; owner action escalated weekly in KPI report |
| Overclaim drift under outreach pressure | PLAYBOOK §3 + §11 tests; Hermes re-checks before each send; owner audits a weekly sample |
| Demo host confusion (prospects meeting demo tenants) | Script the explanation; request a dedicated eval host decision (owner) |
| Regulatory sensitivity (health data) | Lead with consent/access-log story in prospectus; never generalize; route legal questions to owner + Legal desk docs |
| Capacity: facility onboarding outruns support | Cap go-live starts per week using readiness-desk score; convert extra demand into Phase-3 pilot backlog |

## 7. Definition of done (this plan)
- 90 days: O1 met, ≥8 facilities live with readiness ≥ threshold, ≥40 claims
  submitted, case-study material exists, first FHIR partner in-flight.
- Every claim published on the brand matched §2/§3 verbatim at publish time.
