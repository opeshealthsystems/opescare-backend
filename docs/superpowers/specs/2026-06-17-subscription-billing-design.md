# Design: Unified Subscription & Billing

**Date:** 2026-06-17 · **Status:** approved shape, pending spec review
**Context:** OpesCare (Laravel 13, XAF/FCFA, MTN MoMo + Orange Money). An
organization-centric subscription engine already exists; this design generalizes
it so patients, families, health facilities, and other organization types all
subscribe, are billed, and self-manage their plans through one pipeline.

> **Pricing in this document is placeholder** (illustrative XAF figures) for the
> business to set before launch. The architecture does not depend on the numbers.

---

## 1. Goals & non-goals

**Goals**
- One subscription engine serving four audiences: **patients, families (households),
  health facilities, other organizations** (insurer / lab / pharmacy / health-org /
  developer-API).
- Recurring billing (monthly + annual) via MoMo/Orange, with trials, renewals,
  grace periods, dunning, and graceful downgrade/suspend on lapse.
- Self-service in each portal: view plan, usage, invoices; upgrade, downgrade,
  switch cadence, toggle auto-renew, update payment method, renew, cancel.
- Feature/limit entitlements that gate platform capabilities by plan.

**Non-goals (this round)**
- Insurance *claims* billing (separate existing module).
- Per-visit patient *bill* payment (existing `Wallet` / `PaymentPlan`).
- Tax/e-invoicing compliance beyond basic receipts (revisit per regulation).

---

## 2. Existing building blocks (reused, not rebuilt)

Already present: `SubscriptionPlan`, `PlanFeature`, `PlanLimit`,
`OrganizationSubscription`, `SubscriptionInvoice`, `SubscriptionPayment`,
`SubscriptionUsageMetric`, `ModuleEntitlement`, `Wallet`/`WalletTransaction`,
`Modules/Subscription/Services/{SubscriptionService,PlanLimitService}`, admin
views for plans/subscriptions/invoices, and the `PaymentProvider` contract with
`MtnMomoService`/`OrangeMoneyService` (HTTP-hardened 2026-06-17).

The engine is keyed to `organization_id`. The work is to **generalize the
subscriber**, **tag the catalog by audience**, and **add B2C self-service +
checkout**.

---

## 3. Architecture — unified polymorphic subscriber (Approach A)

Generalize `OrganizationSubscription` into a polymorphic **`Subscription`**:

- Add `subscriber_type` + `subscriber_id` (morphTo). Subscriber is one of:
  `Organization` (facility/insurer/lab/pharmacy/healthorg/developer), `Patient`,
  or `Household`.
- **Migration safety:** keep the existing `organization_id`/`organization_name`
  columns; backfill `subscriber_type='App\\Models\\Organization'`,
  `subscriber_id = organization_id` for all current rows. New rows use the
  polymorphic pair. (No destructive change; old reads keep working during cutover.)
- New **`Household`** model: an `owner_patient_id` (payer) + `household_members`
  pivot to N patient profiles. A household's Premium covers its members.

Everything downstream (invoices, payments, usage, entitlements) already references
the subscription, so it is reused unchanged.

```
Subscription (polymorphic subscriber)
 ├─ plan_id ─────────► SubscriptionPlan ── PlanFeature / PlanLimit
 ├─ status, period, trial, auto_renew, payment_method
 ├─ invoices ────────► SubscriptionInvoice ── SubscriptionPayment
 ├─ usageMetrics ────► SubscriptionUsageMetric
 └─ entitlements ────► ModuleEntitlement
subscriber morphTo { Organization | Patient | Household }
```

---

## 4. Plan catalog

`SubscriptionPlan` gains:
- `audience` enum: `patient | household | facility | insurer | lab | pharmacy |
  healthorg | developer`.
- explicit cadence pricing: keep `price` for monthly; add `annual_price`;
  `billing_cycle` becomes a per-subscription choice (`monthly|annual`) rather than
  a per-plan fixed value, OR model monthly/annual as sibling plans sharing a
  `plan_group` — **decision: per-subscription `interval` field** (one plan row,
  two prices), simpler catalog.

**Proposed starting catalog (placeholder XAF):**

| Audience | Plan | Monthly | Annual | Entitlements (PlanFeature / PlanLimit) |
|---|---|---|---|---|
| patient | Free "Essentiel" | 0 | 0 | own_records, qr_health_id, facility_search, basic_booking |
| patient | Premium "Santé+" | 1,500 | 15,000 | teleconsult, full_history, document_vault, priority_queue, family_sharing(≤5), med_reminders, offline_card |
| household | Family add-on | per extra dependent (e.g. 300/mo) | — | extends family_sharing beyond 5 |
| facility | Starter | 25,000 | annual −2mo | max_facilities=1, max_staff=10, max_patients_per_month=500 |
| facility | Clinic | 100,000 | annual −2mo | max_facilities=5, max_staff=50, max_patients_per_month=5,000 |
| facility | Enterprise | quote | quote | unlimited / custom |
| insurer/lab/pharmacy/healthorg | per-type | quote/tiered | — | module entitlements + API limits |
| developer | Free / Growth / Scale | by call volume | — | Connect API quotas |

Families are served by the patient **Premium** plan's `family_sharing` feature; the
`household` audience exists only for the optional per-dependent add-on.

---

## 5. Billing lifecycle (state machine)

States on `Subscription.status`:
`trialing → active → past_due → {active | lapsed} → cancelled → expired`

- **Trial:** optional `trial_days`; converts to `active` on first successful charge.
- **Renewal:** a scheduled job (`subscriptions:renew`, runs daily via the existing
  scheduler) finds subscriptions whose `current_period_end` ≤ today with
  `auto_renew=true` and charges via the subscriber's saved provider.
- **Failed charge → `past_due`:** 7-day grace. Retry on D+1/D+3/D+7 with dunning
  notifications (in-app + SMS/WhatsApp via existing channels), localized EN/FR.
- **Grace expires → `lapsed`:** B2C (patient/household) → auto-switch to the Free
  plan (data retained, premium features gated off). B2B (facility/org) → `suspended`
  (read-only / blocked until paid; data retained).
- **Upgrade:** effective immediately; prorated credit for unused time applied to
  the new plan's first invoice.
- **Downgrade:** scheduled for `current_period_end` (no refund of current period).
- **Cancel:** `auto_renew=false`, remains `active` until `current_period_end`, then
  `expired` (B2B) / Free (B2C).
- **Switch cadence:** monthly↔annual treated as an upgrade/downgrade with proration.

All transitions write an audit event and emit a domain event
(`SubscriptionRenewed`, `SubscriptionPastDue`, `SubscriptionLapsed`, …) for
notifications.

---

## 6. Entitlements & enforcement

- `PlanFeature` = boolean capabilities; `PlanLimit` = numeric caps. On activation
  / plan change, `SubscriptionService` materializes these into `ModuleEntitlement`
  rows for the subscriber (existing pattern).
- **Gate:** a `subscription.feature:<key>` middleware + a `Gate::define` so both
  routes and Blade/UI can check `@can('feature', 'teleconsult')`. Patient API and
  portal use the same gate.
- **Metering:** `SubscriptionUsageMetric` increments per metered action
  (e.g. teleconsults used this cycle); `PlanLimitService` enforces caps and surfaces
  "X of N used" in the self-service UI.

---

## 7. Self-service account management

A **Subscription** page per portal (patient portal, facility admin, each org
portal), all backed by the same `SubscriptionService` + a shared
`SubscriptionController` (web) / API:

- **Shows:** current plan + status badge, next renewal date, cadence, payment
  method, usage-vs-limits, invoice/receipt history (download), dunning banner when
  `past_due`.
- **Actions:** Upgrade, Downgrade, Switch monthly/annual, Toggle auto-renew, Update
  payment method, Renew now, Cancel (with retention confirm).
- All strings EN/FR via the existing i18n keys (`public.*`).

---

## 8. Payments integration

- Checkout and renewals go through the `PaymentProvider` contract
  (`MtnMomoService` / `OrangeMoneyService`). Subscriber picks MoMo or Orange.
- A charge creates a `SubscriptionPayment` (pending) + `SubscriptionInvoice`;
  the provider callback confirms → `active` + receipt. Idempotency on the charge
  request (Idempotency-Key) prevents double-charge on ret/double-tap.
- Sandbox note: MoMo sandbox only honors `EUR` for `requesttopay`; production uses
  XAF. Credentials are operator-supplied in `.env` (not committed).

---

## 9. Data model changes (summary)

**Add:**
- `subscriptions.subscriber_type` + `subscriber_id` (+ backfill from
  `organization_id`); keep legacy columns through cutover.
- `subscription_plans.audience` (enum), `subscription_plans.annual_price`.
- `subscriptions.interval` (`monthly|annual`).
- `households` table + `household_members` pivot.
- (optional) `subscription_payment_methods` to store a subscriber's preferred
  provider + masked MSISDN for renewals.

**Reuse unchanged:** `SubscriptionInvoice`, `SubscriptionPayment`,
`SubscriptionUsageMetric`, `ModuleEntitlement`, `PlanFeature`, `PlanLimit`.

---

## 10. Phasing

Each phase is independently shippable.

1. **Engine generalization** — polymorphic `Subscription`, `audience` on plans,
   `interval`, entitlement gate, renewal/dunning jobs. No new UI. (Backfill +
   keep B2B working.)
2. **Patient Premium end-to-end** — Free/Premium plans seeded, patient
   self-service page, MoMo/Orange checkout, renewal + grace + downgrade, EN/FR.
3. **Family / household sharing** — `Household`, member management, per-dependent
   add-on.
4. **Facility/org tier refinement** — confirm B2B tiers, add their portal
   self-service on the unified engine.
5. **Other org-type catalogs** — insurer / lab / pharmacy / healthorg / developer
   plans + entitlements.

**This spec details Phases 1–2.** Phases 3–5 are outlined here and will get their
own specs.

---

## 11. Open decisions (for spec review)
- Confirm/replace placeholder prices.
- Teleconsults under Premium: unlimited vs metered (e.g. N free then pay-per).
- Dunning channel priority (SMS vs WhatsApp vs in-app only) and retry schedule.
- Whether B2B "suspend" blocks all access or read-only.
- Proration rounding rules (to the day vs whole periods).
