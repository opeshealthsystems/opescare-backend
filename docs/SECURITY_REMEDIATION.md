# Security remediation plan (scan of 2026-09-15)

Source: security scan of the Finder codebase + passive checks of
finder.opescare.cloud. Each item is fixed one at a time, with a regression
test, gated, deployed and verified on prod before the next starts.

| # | Item | Severity | Status |
|---|---|---|---|
| 1 | Public one-click demo login granted a `platform_admin` session (no password, no MFA) that could act on 345 real facilities through admin screens reading across the demo boundary | High | **live** (`72d3592`, verified on prod: tile gone, demo admin login 403, no admin session) — `RequirePlatformAdmin::isPlatformTier()` refuses any `is_demo` user (middleware + nav, ends open sessions); `DemoAccessController::loginAs` refuses platform-tier roles by the account's real role and regenerates the session; Platform Admin tile removed. Test: `tests/Feature/Security/DemoLoginCannotReachPlatformTierTest.php` |
| 2 | Stored XSS: facility name/address break out of JSON-LD `<script>` on the public profile (`JSON_UNESCAPED_SLASHES` without `JSON_HEX_TAG`); edits go live without review; CSP allows inline script | High | **live** (verified on prod) — all seven raw JSON-LD blocks encode with `JSON_HEX_TAG | JSON_HEX_AMP`; facility name/address/city/region reject `<` `>`. Test: `tests/Feature/Security/FacilityJsonLdCannotBreakOutTest.php` |
| 3 | MFA not required for `platform_admin` (`MFA_REQUIRED_ROLES=super_admin,admin`); `routes/imports_ops.php` admin group lacks `mfa.verified` | Medium | **live** (verified on prod) — `User::ALWAYS_MFA_ROLES` (super_admin, platform_admin, system_admin) always require MFA whatever `MFA_REQUIRED_ROLES` says; `routes/imports_ops.php` now requires `mfa.verified`. No real platform admin existed on prod (only real account is a patient), so nobody was locked out; a future admin is sent to MFA setup. Test: `tests/Feature/Security/PlatformAdminsAlwaysNeedMfaTest.php` |
| 4 | JWT private/public keys committed to git (`storage/keys/*.pem`); prod uses different keys; path hardcoded | Low (hygiene) | **live** — keys untracked and git-ignored; paths from `config/jwt.php` (`JWT_PRIVATE_KEY_PATH`/`JWT_PUBLIC_KEY_PATH`); `php artisan opescare:jwt-keys` generates a pair per environment. Prod keys untouched. Still in git **history** (not rewritten — needs owner go-ahead). Test: `tests/Feature/Security/JwtKeysAreNotCommittedTest.php` |
| 5 | Webhook callback URL accepts private/loopback/metadata hosts and follows redirects (admin-gated, blind) | Low | pending |
| 6 | Hardening: session cookie `domain=opescare.cloud` (use host-only); duplicate `X-Frame-Options`/`Referrer-Policy` from nginx + app; CSP `'unsafe-inline'` → nonces | Low | pending |
