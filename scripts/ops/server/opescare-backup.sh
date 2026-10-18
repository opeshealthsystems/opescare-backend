#!/usr/bin/env bash
# Nightly OpesCare backup. Installed at /usr/local/bin/opescare-backup, run by
# opescare-backup.timer at 02:30 server time.
#
#   opescare-backup               # nightly: database + files, keep 14 days, upload if S3 is configured
#   opescare-backup restore-test  # weekly: restore the latest dump into a scratch DB and count rows
#
# Off-site copy: create /root/.opescare-s3.env (mode 600) with
#   S3_PROVIDER=Other          # or AWS, Backblaze (B2 S3 API), Wasabi ...
#   S3_ENDPOINT=https://s3.eu-central-003.backblazeb2.com
#   S3_REGION=eu-central-003
#   S3_BUCKET=opescare-backups
#   S3_ACCESS_KEY_ID=...
#   S3_SECRET_ACCESS_KEY=...
# and each nightly run also uploads to the bucket (rclone, no config file needed).
set -euo pipefail

APP=/home/finder/htdocs/finder.opescare.cloud
DB=opescare_finder
ROOT=/var/backups/opescare
KEEP_DAYS=14
S3_ENV=/root/.opescare-s3.env
mkdir -p "$ROOT"
chmod 700 "$ROOT"

restore_test() {
  local latest scratch=opescare_restore_test patients
  latest=$(ls -1t "$ROOT"/*/db.dump 2>/dev/null | head -1)
  [ -n "$latest" ] || { echo "FAIL no dump to test" > "$ROOT/restore-test.status"; exit 1; }
  sudo -u postgres dropdb --if-exists "$scratch"
  sudo -u postgres createdb "$scratch"
  if sudo -u postgres pg_restore --no-owner --no-privileges -d "$scratch" < "$latest" >/dev/null 2>"$ROOT/restore-test.log" || true; then
    patients=$(sudo -u postgres psql -tAc "select count(*) from patients" "$scratch" 2>/dev/null || echo "")
    live=$(sudo -u postgres psql -tAc "select count(*) from patients" "$DB")
    if [ -n "$patients" ] && [ "$patients" -gt 0 ] && [ "$patients" -le "$live" ]; then
      echo "OK $(date -Is) restored $(basename "$(dirname "$latest")"): $patients patients (live $live)" > "$ROOT/restore-test.status"
    else
      echo "FAIL $(date -Is) restore of $latest gave '$patients' patients (live $live); see restore-test.log" > "$ROOT/restore-test.status"
    fi
  fi
  sudo -u postgres dropdb --if-exists "$scratch"
  cat "$ROOT/restore-test.status"
  grep -q '^OK' "$ROOT/restore-test.status"
}

[ "${1:-}" = "restore-test" ] && { restore_test; exit $?; }

STAMP=$(date +%Y-%m-%d_%H%M)
DIR="$ROOT/$STAMP"
mkdir -p "$DIR"

# Database (custom format: compressed, restorable table by table).
sudo -u postgres pg_dump -Fc "$DB" > "$DIR/db.dump.part"
pg_restore --list "$DIR/db.dump.part" >/dev/null
mv "$DIR/db.dump.part" "$DIR/db.dump"

# Files that git cannot recreate: .env, uploaded/private files, JWT keys.
# Code and vendor come from git; logs, caches, the APK and the web app build are excluded.
tar czf "$DIR/files.tar.gz" -C "$APP" \
  --exclude='storage/logs' --exclude='storage/framework' --exclude='storage/debugbar' \
  .env storage
echo "$(cat /home/finder/.deployed-commit 2>/dev/null || echo unknown)" > "$DIR/deployed-commit.txt"
(cd "$DIR" && sha256sum db.dump files.tar.gz deployed-commit.txt > SHA256SUMS)

# Retention.
find "$ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_DAYS" -exec rm -rf {} +

# Off-site copy when configured.
if [ -f "$S3_ENV" ]; then
  # shellcheck disable=SC1090
  . "$S3_ENV"
  export RCLONE_CONFIG_OFFSITE_TYPE=s3
  export RCLONE_CONFIG_OFFSITE_PROVIDER="${S3_PROVIDER:-Other}"
  export RCLONE_CONFIG_OFFSITE_ENDPOINT="${S3_ENDPOINT:-}"
  export RCLONE_CONFIG_OFFSITE_REGION="${S3_REGION:-}"
  export RCLONE_CONFIG_OFFSITE_ACCESS_KEY_ID="$S3_ACCESS_KEY_ID"
  export RCLONE_CONFIG_OFFSITE_SECRET_ACCESS_KEY="$S3_SECRET_ACCESS_KEY"
  rclone copy "$DIR" "offsite:$S3_BUCKET/$STAMP" --s3-no-check-bucket
  rclone delete "offsite:$S3_BUCKET" --min-age "${KEEP_DAYS}d" --rmdirs || true
  echo "uploaded to s3://$S3_BUCKET/$STAMP" > "$DIR/offsite.txt"
fi

du -sh "$DIR"
