# OpesCare 100% Production Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the remaining gaps between the current strong release candidate and a GA-ready production deployment: error observability, fail-fast production config validation, payment-credential go-live verification, supervised background workers, and the one underdeveloped user-facing module (Tasks).

**Architecture:** Additive, low-risk changes layered onto a healthy baseline (800/800 tests green, 577 routes resolving, 5 prior blockers cleared). Each phase is independently shippable and independently verifiable. Code-buildable phases (1, 2, 3, 5) ship in this repo; infra/operator phases (4, 6) produce verified artifacts + a runbook, since standing up the production host and supplying live secrets is the operator's action.

**Tech Stack:** Laravel 13 / PHP 8.3, PostgreSQL, Redis (queue), Laravel Horizon, PHPUnit, Sentry (to be added), MTN MoMo + Orange Money, systemd.

---

## Baseline (verified 2026-06-18 — do NOT re-do, this is the gate we start from)

- ✅ Test suite: **800 passed / 800**, 2199 assertions (`php artisan test --env=testing`).
- ✅ Routes: 577 registered, **0 missing `route()` references** platform-wide.
- ✅ Dashboards: all 62 role navs resolve; Health-Org / Lab result-entry / Developer secret-lifecycle flows wired & live-tested.
- ✅ Blockers #1–#5 cleared (payment timeouts, Connect/billing throttle, HL7 fail-closed, atomic idempotency, deploy ordering).
- ✅ Health: `/up` (Laravel health) + `/api/health` + `DatabaseHealthMiddleware`.
- ✅ Queue: `QUEUE_CONNECTION=redis`; Horizon `^5.47` installed + `config/horizon.php`.
- ✅ `ProductionSafetyServiceProvider` throws on debug/demo in prod.
- ✅ CI: `.github/workflows/{deploy,security-scan,snyk-security}.yml`.
- ✅ Clean git tree on `codex/production-hardening` == `main` == `origin/main`.

## Gaps this plan closes

| # | Gap | Phase |
|---|-----|-------|
| G1 | No error tracking / observability (no Sentry; pending task #30) | Phase 1 |
| G2 | No fail-fast validation that required prod config/env is present; `CACHE_STORE=database` default | Phase 2 |
| G3 | No way to verify MoMo/Orange credentials are live before go-live; billing silently fails without keys | Phase 3 |
| G4 | Horizon + scheduler systemd units exist but aren't stood up / health-checked | Phase 4 (ops) |
| G5 | Tasks module: real `TaskService`/`ActionTask` backend but **no dashboard surface** | Phase 5 |
| G6 | No single production go-live runbook + smoke checklist + sign-off gate | Phase 6 (ops) |

---

## Phase 1 — Error observability (Sentry)

**Files:**
- Modify: `composer.json` (add `sentry/sentry-laravel`)
- Create: `config/sentry.php` (published)
- Modify: `bootstrap/app.php` (report to Sentry in `withExceptions`)
- Modify: `.env.example` (Sentry knobs)
- Test: `tests/Feature/Observability/SentryConfigTest.php`

- [ ] **Step 1: Install the SDK**

Run:
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn= 2>/dev/null || php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```
Expected: `config/sentry.php` created; package in `composer.json`.

- [ ] **Step 2: Add Sentry env to `.env.example`**

Append to `.env.example`:
```dotenv

# Observability (Sentry)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false
SENTRY_ENVIRONMENT="${APP_ENV}"
SENTRY_RELEASE=
```

- [ ] **Step 3: Harden `config/sentry.php` for PHI safety**

In `config/sentry.php` set these keys (PHI must never leave the box):
```php
'send_default_pii' => false,
'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
    // Drop request bodies and known PHI-bearing keys from the payload.
    $request = $event->getRequest();
    unset($request['data'], $request['cookies'], $request['query_string']);
    $event->setRequest($request);
    return $event;
},
```

- [ ] **Step 4: Report exceptions to Sentry in `bootstrap/app.php`**

In the `->withExceptions(function (Exceptions $exceptions) {` block, add at the top:
```php
$exceptions->reportable(function (\Throwable $e) {
    if (app()->bound('sentry') && app()->environment('production', 'staging')) {
        app('sentry')->captureException($e);
    }
});
```

- [ ] **Step 5: Write the failing test**

Create `tests/Feature/Observability/SentryConfigTest.php`:
```php
<?php

namespace Tests\Feature\Observability;

use Tests\TestCase;

class SentryConfigTest extends TestCase
{
    public function test_sentry_never_sends_pii_by_default(): void
    {
        $this->assertFalse((bool) config('sentry.send_default_pii'));
    }

    public function test_before_send_scrubs_request_data(): void
    {
        $scrub = config('sentry.before_send');
        $this->assertIsCallable($scrub);
        $event = \Sentry\Event::createEvent();
        $req = $event->getRequest();
        $req['data'] = ['health_id' => 'CM-HID-XXXX'];
        $event->setRequest($req);
        $out = $scrub($event);
        $this->assertArrayNotHasKey('data', $out->getRequest());
    }
}
```

- [ ] **Step 6: Run the test**

Run: `php artisan test --env=testing --filter=SentryConfigTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Wire release tagging into deploy**

In `.github/workflows/deploy.yml`, in the build step set `SENTRY_RELEASE` to the commit SHA (export `SENTRY_RELEASE=${{ github.sha }}` before `artisan up`). This ties errors to releases.

- [ ] **Step 8: Commit**

```bash
git add composer.json composer.lock config/sentry.php bootstrap/app.php .env.example tests/Feature/Observability/SentryConfigTest.php .github/workflows/deploy.yml
git commit -m "feat(observability): wire Sentry error tracking with PHI-safe scrubbing"
```

**Operator action (documented, not code):** set `SENTRY_LARAVEL_DSN` in production `.env` from the Sentry project DSN. Until set, the SDK is a no-op (safe).

---

## Phase 2 — Fail-fast production config validation

**Files:**
- Create: `app/Console/Commands/AssertProductionConfig.php`
- Modify: `.env.example` (`CACHE_STORE=redis` for the prod template comment)
- Modify: `.github/workflows/deploy.yml` (run the check before serving)
- Test: `tests/Feature/Config/AssertProductionConfigTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Config/AssertProductionConfigTest.php`:
```php
<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class AssertProductionConfigTest extends TestCase
{
    public function test_passes_when_required_config_present(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
                'cache.default' => 'redis', 'queue.default' => 'redis',
                'session.driver' => 'redis', 'app.debug' => false]);
        $this->artisan('config:assert-production')->assertExitCode(0);
    }

    public function test_fails_when_debug_on(): void
    {
        config(['app.debug' => true]);
        $this->artisan('config:assert-production')->assertExitCode(1);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --env=testing --filter=AssertProductionConfigTest`
Expected: FAIL with "command config:assert-production not defined".

- [ ] **Step 3: Implement the command**

Create `app/Console/Commands/AssertProductionConfig.php`:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AssertProductionConfig extends Command
{
    protected $signature = 'config:assert-production';
    protected $description = 'Fail fast if required production configuration is missing or unsafe.';

    public function handle(): int
    {
        $errors = [];

        if (empty(config('app.key')))            $errors[] = 'APP_KEY is not set.';
        if (config('app.debug') === true)        $errors[] = 'APP_DEBUG must be false in production.';
        if (config('cache.default') !== 'redis') $errors[] = 'CACHE_STORE should be redis in production.';
        if (config('queue.default') !== 'redis') $errors[] = 'QUEUE_CONNECTION should be redis in production.';
        if (config('session.driver') === 'array') $errors[] = 'SESSION_DRIVER is not durable.';
        if (empty(config('mail.default')))       $errors[] = 'MAIL_MAILER is not set.';

        // Billing providers: if either is partially configured, require it complete.
        $momoKey = config('services.mtn_momo.subscription_key');
        if ($momoKey !== null && $momoKey !== '' && empty(config('services.mtn_momo.api_key'))) {
            $errors[] = 'MTN MoMo partially configured (subscription_key set, api_key missing).';
        }

        if ($errors) {
            foreach ($errors as $e) { $this->error('  ✗ '.$e); }
            $this->error(count($errors).' production config problem(s) found.');
            return self::FAILURE;
        }

        $this->info('✓ Production configuration looks good.');
        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --env=testing --filter=AssertProductionConfigTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Gate the deploy on it**

In `.github/workflows/deploy.yml`, after `composer install` and before `artisan up`, add:
```yaml
- name: Assert production config
  run: php artisan config:assert-production
```

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/AssertProductionConfig.php tests/Feature/Config/AssertProductionConfigTest.php .github/workflows/deploy.yml .env.example
git commit -m "feat(config): fail-fast production config validation gate"
```

---

## Phase 3 — Payment credential go-live smoke check

**Files:**
- Create: `app/Console/Commands/PaymentsSmoke.php`
- Test: `tests/Feature/Payments/PaymentsSmokeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Payments/PaymentsSmokeTest.php`:
```php
<?php

namespace Tests\Feature\Payments;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentsSmokeTest extends TestCase
{
    public function test_reports_failure_when_momo_token_rejected(): void
    {
        config(['services.mtn_momo.subscription_key' => 'k', 'services.mtn_momo.api_key' => 'x', 'services.mtn_momo.user_id' => 'u']);
        Http::fake(['*/collection/token/' => Http::response([], 401)]);
        $this->artisan('payments:smoke')->assertExitCode(1);
    }

    public function test_skips_unconfigured_provider(): void
    {
        config(['services.mtn_momo.subscription_key' => '', 'services.orange_money.client_id' => '']);
        $this->artisan('payments:smoke')->assertExitCode(0);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --env=testing --filter=PaymentsSmokeTest`
Expected: FAIL with "command payments:smoke not defined".

- [ ] **Step 3: Implement the command**

Create `app/Console/Commands/PaymentsSmoke.php`:
```php
<?php

namespace App\Console\Commands;

use App\Services\Payments\MtnMomoService;
use App\Services\Payments\OrangeMoneyService;
use Illuminate\Console\Command;

class PaymentsSmoke extends Command
{
    protected $signature = 'payments:smoke';
    protected $description = 'Verify configured Mobile Money providers can obtain an access token (go-live check).';

    public function handle(MtnMomoService $momo, OrangeMoneyService $orange): int
    {
        $failures = 0;

        if (config('services.mtn_momo.subscription_key')) {
            $ok = $momo->canAuthenticate();
            $this->line(($ok ? '✓' : '✗').' MTN MoMo token');
            $failures += $ok ? 0 : 1;
        } else {
            $this->comment('• MTN MoMo not configured — skipped.');
        }

        if (config('services.orange_money.client_id')) {
            $ok = $orange->canAuthenticate();
            $this->line(($ok ? '✓' : '✗').' Orange Money token');
            $failures += $ok ? 0 : 1;
        } else {
            $this->comment('• Orange Money not configured — skipped.');
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
```

- [ ] **Step 4: Add `canAuthenticate()` to both services**

In `app/Services/Payments/MtnMomoService.php` add a public method that wraps the existing private `getAccessToken()`:
```php
public function canAuthenticate(): bool
{
    return $this->getAccessToken() !== null;
}
```
Do the equivalent in `app/Services/Payments/OrangeMoneyService.php` (wrap its token method; return false on any failure).

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --env=testing --filter=PaymentsSmokeTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/PaymentsSmoke.php app/Services/Payments/MtnMomoService.php app/Services/Payments/OrangeMoneyService.php tests/Feature/Payments/PaymentsSmokeTest.php
git commit -m "feat(payments): payments:smoke go-live credential check"
```

**Operator action:** once MTN issues a working Collections key, run `php artisan payments:smoke` on the host; it must print `✓ MTN MoMo token` before enabling paid plans.

---

## Phase 4 — Supervised background workers (ops, verify + document)

**Files:**
- Verify: `deploy/systemd/opescare-horizon.service`, `opescare-scheduler.service`, `opescare-scheduler.timer`
- Modify: `deploy/README.md` (standup + health-check steps)

- [ ] **Step 1: Verify the units reference correct paths/user**

Read each unit; confirm `WorkingDirectory`, `ExecStart` (`php artisan horizon` / `schedule:run`), `User`, and `Restart=always`. Fix any placeholder paths.

- [ ] **Step 2: Add a queue-health assertion to the runbook**

In `deploy/README.md` document:
```bash
sudo systemctl enable --now opescare-horizon.service
sudo systemctl enable --now opescare-scheduler.timer
php artisan horizon:status      # must print "running"
systemctl is-active opescare-horizon.service
```

- [ ] **Step 3: Commit**

```bash
git add deploy/README.md deploy/systemd/
git commit -m "docs(ops): supervise Horizon + scheduler standup and health checks"
```

**Operator action:** run the systemctl commands on the production host.

---

## Phase 5 — Tasks module dashboard surface (close the underdeveloped module)

The `TaskService` (create/acknowledge/complete/escalate) and `ActionTask` model exist but have **no UI**. Surface an action-task inbox in the staff and admin portals.

**Files:**
- Create: `app/Http/Controllers/MedicalId/TaskInboxController.php`
- Modify: `routes/web.php` (4 routes under the staff portal group)
- Modify: `resources/views/partials/sidebars/{doctor,nurse,facility_admin}.blade.php` (nav link)
- Create: `resources/views/portals/staff/tasks/index.blade.php`
- Create: `lang/en/tasks.php`, `lang/fr/tasks.php`
- Test: `tests/Feature/Tasks/TaskInboxTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Tasks/TaskInboxTest.php`:
```php
<?php

namespace Tests\Feature\Tasks;

use App\Modules\Tasks\Models\ActionTask;
use App\Models\User;
use Tests\TestCase;

class TaskInboxTest extends TestCase
{
    public function test_actor_can_acknowledge_an_assigned_task(): void
    {
        $user = User::factory()->create();
        $task = ActionTask::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'task_type' => 'follow_up', 'title' => 'Call patient',
            'assigned_to' => $user->id, 'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('portals.staff.tasks.acknowledge', $task->uuid))
            ->assertRedirect();

        $this->assertSame('acknowledged', $task->fresh()->status);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --env=testing --filter=TaskInboxTest`
Expected: FAIL ("route ... not defined").

- [ ] **Step 3: Implement the controller**

Create `app/Http/Controllers/MedicalId/TaskInboxController.php`:
```php
<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskInboxController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(): View
    {
        $userId = (string) Auth::id();
        $open = ActionTask::where('assigned_to', $userId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at")
            ->paginate(25);

        return view('portals.staff.tasks.index', ['tasks' => $open]);
    }

    public function acknowledge(string $uuid): RedirectResponse
    {
        $this->tasks->acknowledgeTask($uuid, (string) Auth::id());
        return back()->with('success', __('tasks.acknowledged'));
    }

    public function complete(string $uuid): RedirectResponse
    {
        $this->tasks->completeTask($uuid);
        return back()->with('success', __('tasks.completed'));
    }

    public function escalate(string $uuid): RedirectResponse
    {
        $this->tasks->escalateTask($uuid);
        return back()->with('success', __('tasks.escalated'));
    }
}
```

(Confirm the `TaskService` method signatures match — `acknowledgeTask(string $uuid, string $userId)`, `completeTask(string $uuid)`, `escalateTask(string $uuid)` per the module — and the `ActionTask` namespace `App\Modules\Tasks\Models\ActionTask`.)

- [ ] **Step 4: Register routes** (inside the existing `web, auth, mfa.verified, portal.access` staff group)

```php
Route::get('/portals/staff/tasks', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'index'])->name('portals.staff.tasks');
Route::post('/portals/staff/tasks/{uuid}/acknowledge', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'acknowledge'])->name('portals.staff.tasks.acknowledge');
Route::post('/portals/staff/tasks/{uuid}/complete', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'complete'])->name('portals.staff.tasks.complete');
Route::post('/portals/staff/tasks/{uuid}/escalate', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'escalate'])->name('portals.staff.tasks.escalate');
```

- [ ] **Step 5: Create the view** `resources/views/portals/staff/tasks/index.blade.php`

A `@extends('layouts.portal')` page: a table of `$tasks` (title, type, due_at, status badge) with per-row Acknowledge / Complete / Escalate `@csrf` forms posting to the routes above, plus an empty-state. Use `__('tasks.*')` keys. (Follow the exact structure of `resources/views/portals/healthorg/signals.blade.php`, which already renders per-row action forms.)

- [ ] **Step 6: Create lang files** `lang/en/tasks.php` and `lang/fr/tasks.php`

EN keys: `nav`, `title`, `subtitle`, `col_task`, `col_type`, `col_due`, `col_status`, `acknowledge`, `complete`, `escalate`, `empty_title`, `empty_body`, `acknowledged`, `completed`, `escalated`. Mirror 1:1 in FR.

- [ ] **Step 7: Add nav link** to `resources/views/partials/sidebars/{doctor,nurse,facility_admin}.blade.php`

```blade
<a href="{{ route('portals.staff.tasks') }}" class="sidebar-link {{ request()->routeIs('portals.staff.tasks*') ? 'active' : '' }}">
    <i data-lucide="check-square"></i> <span>{{ __('tasks.nav', [], $l) ?: 'Tasks' }}</span>
</a>
```

- [ ] **Step 8: Run the test + i18n parity**

Run:
```bash
php artisan test --env=testing --filter=TaskInboxTest
php scripts/i18n-audit.php
```
Expected: test PASS; parity `✓ ALL FILES HAVE PERFECT 1:1 PARITY`.

- [ ] **Step 9: Live-verify** (Laragon must be running)

Log in as a staff user, seed an `ActionTask` assigned to them, open `/portals/staff/tasks`, click Acknowledge → status flips; Complete → leaves the open list.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/MedicalId/TaskInboxController.php routes/web.php resources/views/portals/staff/tasks/index.blade.php resources/views/partials/sidebars/doctor.blade.php resources/views/partials/sidebars/nurse.blade.php resources/views/partials/sidebars/facility_admin.blade.php lang/en/tasks.php lang/fr/tasks.php tests/Feature/Tasks/TaskInboxTest.php
git commit -m "feat(tasks): action-task inbox in staff/admin portals"
```

---

## Phase 6 — Production go-live runbook + sign-off gate (ops)

**Files:**
- Create: `docs/RUNBOOK-PRODUCTION.md`

- [ ] **Step 1: Write the runbook** with these sections, each a copy-pasteable command list:
  1. **Pre-deploy gate:** `php artisan test`, `php artisan config:assert-production`, `php artisan payments:smoke`, i18n parity, `route:list` sanity.
  2. **Secrets checklist:** `APP_KEY`, DB creds, `REDIS_*`, `SENTRY_LARAVEL_DSN`, `MAIL_*`, `MTN_MOMO_*`, `ORANGE_MONEY_*`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`.
  3. **Deploy:** the `deploy.yml` flow (maintenance window → migrate → cache → fpm reload → health-check → up).
  4. **Post-deploy smoke:** `curl -f https://<host>/up`, `curl -f https://<host>/api/health`, `php artisan horizon:status`, log in to one portal per role tier.
  5. **Rollback:** revert to previous tag, `migrate:rollback` policy, restore note.
  6. **Backups:** nightly Postgres dump + retention; restore drill command.

- [ ] **Step 2: Commit**

```bash
git add docs/RUNBOOK-PRODUCTION.md
git commit -m "docs(ops): production go-live runbook + sign-off gate"
```

---

## Definition of Done (100% gate)

- [ ] Phases 1–3, 5 merged; `php artisan test` still **800+/all green** (new tests included).
- [ ] `php artisan config:assert-production` exits 0 against the production `.env`.
- [ ] `php artisan payments:smoke` prints `✓` for every provider the operator intends to enable.
- [ ] Sentry receiving events from staging (DSN set; trigger a test exception).
- [ ] `horizon:status` = running and `opescare-scheduler.timer` active on the host.
- [ ] Tasks inbox reachable + functional for staff/admin; i18n parity holds.
- [ ] `docs/RUNBOOK-PRODUCTION.md` followed end-to-end on staging once.
- [ ] Tag `v1.0.0-ga` on the green commit.

## Operator-only items (cannot be done in-repo; tracked, not blocking code merge)

- Supply live **MTN MoMo Collections** + **Orange Money** credentials.
- Set **`SENTRY_LARAVEL_DSN`** from the Sentry project.
- Provision production host: Redis, TLS/DNS, systemd standup, Postgres backups.
