#!/usr/bin/env bash
# One-command deploy of a committed tree to opescare.com.
#
#   scripts/ops/deploy.sh            # ship HEAD
#   scripts/ops/deploy.sh <commit>   # ship a specific commit
#   ALLOW_DIRTY=1 scripts/ops/deploy.sh   # ship the commit even though the working tree has uncommitted edits (they are never shipped)
#
# Ships only the files that changed since the commit recorded on the server
# (/home/finder/.deployed-commit), removes files deleted in git, runs migrations
# with php8.3 and verifies them, rebuilds caches, then smoke-tests the live site.
# If anything after the file swap fails, the previous files are restored and the
# caches rebuilt (auto-rollback). Migrations are never rolled back automatically:
# a failed deploy that already migrated says so and stops.
set -euo pipefail

HOST=root@187.124.48.135
KEY="$HOME/.ssh/opescare_deploy_ed25519"
APP=/home/finder/htdocs/finder.opescare.cloud
SITE=https://opescare.com
SMOKE_PATHS="/ /login /up /privacy /verify/health-id /finder/ ${EXTRA_SMOKE:-}"

cd "$(git rev-parse --show-toplevel)"
TARGET=$(git rev-parse --short "${1:-HEAD}")
SSH=(ssh -o ConnectTimeout=15 -i "$KEY" "$HOST")

say() { printf '\n== %s\n' "$*"; }

if [ -z "${ALLOW_DIRTY:-}" ] && [ -n "$(git status --porcelain --untracked-files=no -- app bootstrap config database lang public resources routes)" ]; then
  echo "Uncommitted changes in shipped folders. Commit first; deploy ships commits only." >&2
  exit 1
fi

BASE=$("${SSH[@]}" "cat /home/finder/.deployed-commit 2>/dev/null || true")
if [ -z "$BASE" ]; then
  echo "The server has no /home/finder/.deployed-commit. Record the live commit once:" >&2
  echo "  ssh -i $KEY $HOST 'echo <commit> > /home/finder/.deployed-commit'" >&2
  exit 1
fi
git cat-file -e "$BASE^{commit}" 2>/dev/null || { echo "Live commit $BASE is not in this repo." >&2; exit 1; }
[ "$(git rev-parse "$BASE")" = "$(git rev-parse "$TARGET")" ] && { echo "Live is already at $TARGET."; exit 0; }

say "Deploying $BASE -> $TARGET to $SITE"
TMP=$(mktemp -d)
git diff --name-only --diff-filter=AMR "$BASE" "$TARGET" > "$TMP/changed.txt"
git diff --name-only --diff-filter=D   "$BASE" "$TARGET" > "$TMP/deleted.txt"
git diff --name-only --diff-filter=A   "$BASE" "$TARGET" > "$TMP/added.txt"
NEW_MIGRATIONS=$(grep -c '^database/migrations/' "$TMP/added.txt" || true)
echo "$(wc -l < "$TMP/changed.txt") changed/added, $(wc -l < "$TMP/deleted.txt") deleted, $NEW_MIGRATIONS new migration(s)"

if [ -s "$TMP/changed.txt" ]; then
  git archive -o "$TMP/release.tar.gz" "$TARGET" $(cat "$TMP/changed.txt")
else
  tar czf "$TMP/release.tar.gz" -T /dev/null
fi
SUM=$(sha256sum "$TMP/release.tar.gz" | cut -c1-64)

REL=/root/deploys/$TARGET
"${SSH[@]}" "mkdir -p $REL"
scp -q -i "$KEY" "$TMP/release.tar.gz" "$TMP/changed.txt" "$TMP/deleted.txt" "$TMP/added.txt" "$HOST:$REL/"
REMOTE_SUM=$("${SSH[@]}" "sha256sum $REL/release.tar.gz | cut -c1-64")
[ "$SUM" = "$REMOTE_SUM" ] || { echo "Checksum mismatch after upload." >&2; exit 1; }
echo "Uploaded and verified ($SUM)"

say "Installing on the server"
{ printf 'APP=%q REL=%q TARGET=%q SITE=%q SMOKE=%q\n' "$APP" "$REL" "$TARGET" "$SITE" "$SMOKE_PATHS"; cat <<'REMOTE'
set -uo pipefail
cd "$APP"
art() { sudo -u finder php8.3 artisan "$@"; }

# Snapshot every file this release touches, so a rollback restores them exactly.
cat "$REL/changed.txt" "$REL/deleted.txt" | while read -r f; do [ -e "$f" ] && echo "$f"; done > "$REL/existing.txt"
tar czf "$REL/previous.tar.gz" -T "$REL/existing.txt"

rollback() {
  echo "!! $1. Rolling back files."
  # Files this release added did not exist before: remove them.
  while read -r f; do rm -f -- "$f"; done < "$REL/added.txt"
  tar xzf "$REL/previous.tar.gz"
  chown -R finder:finder "$APP"
  rm -f bootstrap/cache/*.php
  art config:cache >/dev/null; art route:cache >/dev/null; art view:cache >/dev/null; art event:cache >/dev/null
  systemctl reload php8.3-fpm; systemctl restart finder-queue
  [ "$MIGRATED" = 1 ] && echo "!! Migrations from this release HAVE run and were NOT rolled back. Check them before redeploying."
  echo "ROLLED_BACK"
  exit 1
}
MIGRATED=0

tar xzf "$REL/release.tar.gz"
while read -r f; do [ -n "$f" ] && rm -f -- "$f"; done < "$REL/deleted.txt"
chown -R finder:finder "$APP"
rm -f bootstrap/cache/*.php

if grep -q '^database/migrations/' "$REL/added.txt"; then
  art migrate --force || rollback "migrate failed"
  MIGRATED=1
  for m in $(grep '^database/migrations/' "$REL/added.txt" | xargs -n1 basename | sed 's/\.php$//'); do
    art migrate:status 2>/dev/null | grep -q "$m.*Ran" || rollback "migration $m did not run"
  done
fi

art config:cache >/dev/null || rollback "config:cache failed"
art route:cache  >/dev/null || rollback "route:cache failed"
art view:cache   >/dev/null || rollback "view:cache failed"
art event:cache  >/dev/null || rollback "event:cache failed"
systemctl reload php8.3-fpm
systemctl restart finder-queue

sleep 2
for p in $SMOKE; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$SITE$p")
  echo "  $p $code"
  [ "$code" = 200 ] || rollback "smoke check $p returned $code"
done

echo "$TARGET" > /home/finder/.deployed-commit
ls -1dt /root/deploys/*/ | tail -n +11 | xargs -r rm -rf   # keep the last 10 releases
echo "DEPLOYED $TARGET"
REMOTE
} | "${SSH[@]}" "bash -s"
rm -rf "$TMP"
