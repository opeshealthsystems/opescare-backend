# OpesCare WAF Configuration Guide

**Version:** 1.0
**Owner:** Platform Engineering
**Review cadence:** Quarterly or after any security incident

---

## Overview

OpesCare uses a Web Application Firewall (WAF) in front of all public API
endpoints and the patient portal. This guide covers Cloudflare WAF (primary
recommendation) and AWS WAF (alternative for AWS-hosted deployments).

**Architecture:**

```
Internet → Cloudflare WAF → Load Balancer → Laravel API (Nginx/PHP-FPM)
```

---

## OWASP Core Rule Set

Enable the OWASP CRS (Core Rule Set) at paranoia level 2 for OpesCare.

### Cloudflare WAF

1. Cloudflare Dashboard → Security → WAF → Managed Rules
2. Enable **Cloudflare OWASP Core Ruleset**
3. Set sensitivity to **Medium**
4. Enable **Cloudflare Managed Ruleset**

Rules to enable explicitly:

| Rule ID | Description |
|---------|-------------|
| 100000 | SQL Injection |
| 100001 | XSS (Cross-Site Scripting) |
| 100002 | Local/Remote File Inclusion |
| 100003 | Command Injection |

### AWS WAF

```hcl
resource "aws_wafv2_web_acl" "opescare" {
  name  = "opescare-waf"
  scope = "REGIONAL"

  default_action { allow {} }

  rule {
    name     = "AWSManagedRulesCommonRuleSet"
    priority = 1
    override_action { none {} }
    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesCommonRuleSet"
        vendor_name = "AWS"
      }
    }
    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "CommonRuleSetMetric"
      sampled_requests_enabled   = true
    }
  }
}
```

---

## Rate Limiting

Rate limiting is enforced at two layers:

### Layer 1 — Application (Laravel)

Configured in `app/Providers/AppServiceProvider.php`:

| Client type | Limit |
|-------------|-------|
| Unauthenticated (by IP) | 60 req/min |
| Authenticated user | 600 req/min |
| Integration partner (`X-Integration-Client-Id`) | 1200 req/min |

### Layer 2 — WAF (Cloudflare)

Create a rate-limiting rule in Cloudflare Dashboard → Security → WAF → Rate Limiting Rules:

| Field | Value |
|-------|-------|
| Rule name | OpesCare API Rate Limit |
| When incoming requests match | URI path contains `/api/` |
| Rate | 500 requests per 10 seconds (per IP) |
| Action | Block for 60 seconds |

For `/api/auth/` endpoints apply a stricter rule:

| Field | Value |
|-------|-------|
| URI path | starts with `/api/auth/` |
| Rate | 10 requests per 60 seconds (per IP) |
| Action | Block for 300 seconds |

---

## Bot Management

### Cloudflare Bot Management

1. Cloudflare Dashboard → Security → Bots
2. Enable **Bot Fight Mode** (free tier) or **Super Bot Fight Mode** (Pro+)
3. Create a custom rule to challenge suspicious bots on sensitive paths:

```
(cf.bot_management.score lt 30) and (http.request.uri.path contains "/api/auth")
→ Action: Challenge (Turnstile)
```

4. Allowlist known integration partners by IP in the WAF Bypass rule.

### Protecting Health Check Endpoint

The `/api/health` endpoint is public and unauthenticated. Configure a cache rule:

- Cache `/api/health` responses for 60 seconds at the edge
- Rate limit to 60 req/min per IP

---

## IP Allowlist

### Admin Panel Access

Restrict `/horizon` and `/admin` routes to known office CIDRs at the WAF level.

**Cloudflare — Create an IP Access Rule:**

```
Cloudflare Dashboard → Security → WAF → Tools → IP Access Rules
Action: Allow
Value: <office-CIDR>, <VPN-CIDR>
Zone: opescare.com
```

**Example CIDRs to allowlist:**

| Location | CIDR | Purpose |
|----------|------|---------|
| HQ Libreville | 197.x.x.x/24 | Admin access |
| VPN Gateway | 10.0.0.0/8 | Remote admin |
| CI/CD runners | (GitHub meta IPs) | Deployment |

GitHub Actions IP ranges: https://api.github.com/meta → `actions` key.

### Blocking High-Risk Countries

If OpesCare is Gabon-only, consider blocking traffic from outside the expected
service area via Cloudflare → Security → WAF → Tools → IP Access Rules:

- Action: Block
- Value: All countries except GA, FR (diaspora access), and known partner countries

---

## TLS / HTTPS Configuration

| Setting | Recommended Value |
|---------|------------------|
| TLS minimum version | TLS 1.2 |
| TLS 1.3 | Enabled |
| HSTS max-age | 31536000 (1 year) |
| HSTS includeSubDomains | Yes |
| Certificate | Cloudflare Universal SSL or ACM (AWS) |

---

## Monitoring and Alerting

Configure alerts in Cloudflare or AWS CloudWatch for:

| Metric | Threshold | Action |
|--------|-----------|--------|
| WAF block rate | > 5% of requests in 5 min | Page on-call |
| Rate limit triggers | > 100/min | Alert Slack #security |
| OWASP rule triggers | Any CRITICAL | Page on-call + open incident |
| Bot score average | < 50 for 5+ min | Review logs manually |

---

## Regular Review Checklist

- [ ] Review WAF logs weekly for false positives
- [ ] Review blocked IPs monthly — unblock legitimate users
- [ ] Update OWASP CRS version quarterly
- [ ] Review rate limit thresholds after capacity changes
- [ ] Confirm IP allowlist is current after staff changes
- [ ] Test WAF bypass for known integration partners after rule changes
