#!/usr/bin/env bash
# OpesCare health check. Installed at /usr/local/bin/opescare-health, run every
# 15 minutes by opescare-health.timer. Emails ALERT_TO when a check starts
# failing, again every 6 hours while it stays failing, and once when it recovers.
# State and the last result live in /var/lib/opescare-health/.
set -uo pipefail

ALERT_TO=${ALERT_TO:-nshomejude@gmail.com}
ALERT_FROM=${ALERT_FROM:-noreply@opescare.com}
SITE=https://opescare.com
APP=/home/finder/htdocs/finder.opescare.cloud
BACKUP_DIR=/var/backups/opescare
STATE=/var/lib/opescare-health
REPEAT_SECONDS=$((6 * 3600))
mkdir -p "$STATE"

FAILS=()
fail() { FAILS+=("$1"); }

# 1. The site answers.
for p in / /login /up; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$SITE$p")
  [ "$code" = 200 ] || fail "$SITE$p returned $code"
done
code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 https://opescare.cloud/)
[ "$code" = 301 ] || fail "https://opescare.cloud/ should redirect (301) but returned $code"

# 2. Background work is running.
systemctl is-active --quiet finder-queue || fail "queue worker finder-queue is not running"
systemctl is-active --quiet finder-scheduler.timer || fail "scheduler timer finder-scheduler.timer is not active"
systemctl is-active --quiet php8.3-fpm || fail "php8.3-fpm is not running"
systemctl is-active --quiet postgresql || fail "postgresql is not running"
systemctl is-active --quiet redis-server || fail "redis-server is not running"

# 3. Disk.
used=$(df --output=pcent / | tail -1 | tr -dc '0-9')
[ "$used" -lt 85 ] || fail "disk / is ${used}% full"

# 4. Certificates (warn 14 days ahead; certbot renews at 30).
for d in opescare.com opescare.cloud; do
  pem=/etc/letsencrypt/live/$d/fullchain.pem
  if [ -f "$pem" ]; then
    openssl x509 -checkend $((14 * 86400)) -noout -in "$pem" >/dev/null || fail "certificate for $d expires within 14 days"
  else
    fail "certificate for $d is missing ($pem)"
  fi
done

# 5. Nightly backup is fresh (newer than 26 hours) and the weekly restore test passed.
latest=$(ls -1t "$BACKUP_DIR"/*/db.dump 2>/dev/null | head -1)
if [ -z "$latest" ]; then
  fail "no backup found in $BACKUP_DIR"
elif [ $(( $(date +%s) - $(stat -c %Y "$latest") )) -gt $((26 * 3600)) ]; then
  fail "latest backup $latest is older than 26 hours"
fi
if [ -f "$BACKUP_DIR/restore-test.status" ] && ! grep -q '^OK' "$BACKUP_DIR/restore-test.status"; then
  fail "weekly restore test failed: $(head -1 "$BACKUP_DIR/restore-test.status")"
fi

# 6. No unapplied migrations.
pending=$(cd "$APP" && sudo -u finder php8.3 artisan migrate:status 2>/dev/null | grep -c Pending)
[ "${pending:-0}" -eq 0 ] || fail "$pending migration(s) pending"

# Report.
now=$(date +%s)
{
  date -Is
  if [ ${#FAILS[@]} -eq 0 ]; then echo "OK"; else printf 'FAIL: %s\n' "${FAILS[@]}"; fi
} > "$STATE/last-result"

send() {
  printf '%s\n' "$2" | mail -s "$1" -a "From: OpesCare monitor <$ALERT_FROM>" "$ALERT_TO" || true
}

if [ ${#FAILS[@]} -eq 0 ]; then
  if [ -f "$STATE/failing" ]; then
    send "[OpesCare] RECOVERED: all checks pass" "All OpesCare health checks pass again at $(date -Is)."
    rm -f "$STATE/failing" "$STATE/last-alert"
  fi
  exit 0
fi

body=$(printf 'OpesCare health check failed at %s on %s:\n\n' "$(date -Is)" "$(hostname)"; printf ' - %s\n' "${FAILS[@]}")
last=$(cat "$STATE/last-alert" 2>/dev/null || echo 0)
if [ ! -f "$STATE/failing" ] || [ $((now - last)) -ge $REPEAT_SECONDS ]; then
  send "[OpesCare] ALERT: ${#FAILS[@]} check(s) failing" "$body"
  echo "$now" > "$STATE/last-alert"
fi
touch "$STATE/failing"
exit 1
