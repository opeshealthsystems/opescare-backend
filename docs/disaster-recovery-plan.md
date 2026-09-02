# OpesCare Disaster Recovery Plan

**Version:** 1.0
**Last Updated:** 2026-05-26
**Owner:** CTO / Platform Engineering
**Review Cycle:** Quarterly

---

## 1. Recovery Objectives

| Metric | Target |
|--------|--------|
| Recovery Time Objective (RTO) | ≤ 4 hours |
| Recovery Point Objective (RPO) | ≤ 1 hour |
| Max Tolerable Downtime (MTD) | 8 hours |

---

## 2. Backup Strategy

| Resource | Frequency | Retention | Storage |
|----------|-----------|-----------|---------|
| PostgreSQL full dump | Daily 01:00 UTC | 7 daily, 4 weekly, 2 yearly | AWS S3 (encrypted AES-256) |
| PostgreSQL WAL (point-in-time) | Continuous | 7 days | AWS S3 |
| App code + config | On every deploy | Last 5 deploys | AWS S3 |
| `.env` secrets | On change | Encrypted in Vault | HashiCorp Vault |

---

## 3. Failure Scenarios + Response Playbooks

### 3.1 Database Primary Failure

1. **Detect:** CloudWatch alarm fires when RDS primary becomes unreachable.
2. **Respond:** Promote RDS read replica to primary via `aws rds failover-db-cluster`.
3. **Update:** Change `DB_HOST` in AWS Parameter Store → ECS tasks auto-restart.
4. **Notify:** PagerDuty oncall alert fires.
5. **Document:** Open post-mortem ticket within 1 hour of resolution.

**Expected RTO:** ≤ 15 minutes (automated RDS Multi-AZ failover).

### 3.2 Application Server Total Failure

1. **Detect:** ALB health checks → all targets unhealthy → alarm fires.
2. **Respond:** ECS service auto-replacement starts new tasks within 2 minutes.
3. **Fallback:** Deploy from last known-good Docker image tag in ECR.
4. **Notify:** PagerDuty.

**Expected RTO:** ≤ 5 minutes (ECS auto-healing).

### 3.3 Full Region Failure (AWS eu-west-1 down)

1. **Detect:** Route 53 health check fails → DNS failover to eu-central-1 secondary.
2. **Restore DB:** Restore from S3 cross-region backup to eu-central-1 RDS.
3. **Deploy:** Run `./scripts/deploy-dr-region.sh eu-central-1` (see runbook).
4. **Notify:** All staff + patients via SMS broadcast.

**Expected RTO:** ≤ 4 hours.

### 3.4 Data Corruption / Ransomware

1. **Isolate:** Take all ECS tasks offline immediately.
2. **Assess:** Determine extent and last clean snapshot.
3. **Restore:** `aws rds restore-db-instance-to-point-in-time` to last clean point.
4. **Validate:** Run `php artisan opescare:enforce-data-retention --dry-run` + smoke tests.
5. **Notify:** Data Protection Authority within 72 hours if patient data affected.

**Expected RTO:** ≤ 4 hours.

---

## 4. Testing Schedule

| Test Type | Frequency | Last Tested | Owner |
|-----------|-----------|-------------|-------|
| Backup restore drill | Monthly | — | Platform Eng |
| DB failover test | Quarterly | — | Platform Eng |
| Full DR region failover | Annually | — | CTO |
| Table-top exercise | Semi-annually | — | CTO |

---

## 5. Contact Escalation

| Level | Contact | Response Time |
|-------|---------|---------------|
| L1 On-call engineer | PagerDuty | 5 min |
| L2 Platform lead | Mobile | 15 min |
| L3 CTO | Mobile | 30 min |
| External: AWS Support | AWS Console | Per SLA tier |

---

## 6. Recovery Validation Checklist

After any recovery action, verify:
- [ ] `GET /api/health` returns `{"status":"ok"}`
- [ ] Patient login works end-to-end
- [ ] Appointment booking creates records in DB
- [ ] Audit log entries are being created
- [ ] SMS notifications are sending
- [ ] No hardcoded credentials in environment
