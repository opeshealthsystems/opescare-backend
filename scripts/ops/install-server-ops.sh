#!/usr/bin/env bash
# Installs (or updates) the health check and nightly backup on the server.
#   bash scripts/ops/install-server-ops.sh
# Idempotent: re-run after editing anything in scripts/ops/server/.
set -euo pipefail
HOST=root@187.124.48.135
KEY="$HOME/.ssh/opescare_deploy_ed25519"
cd "$(git rev-parse --show-toplevel)/scripts/ops/server"

scp -q -i "$KEY" opescare-health.sh "$HOST:/usr/local/bin/opescare-health"
scp -q -i "$KEY" opescare-backup.sh "$HOST:/usr/local/bin/opescare-backup"

ssh -i "$KEY" "$HOST" 'bash -s' <<'REMOTE'
set -euo pipefail
chmod 755 /usr/local/bin/opescare-health /usr/local/bin/opescare-backup

cat > /etc/systemd/system/opescare-health.service <<'EOF'
[Unit]
Description=OpesCare health check (site, workers, disk, certificates, backups)
[Service]
Type=oneshot
ExecStart=/usr/local/bin/opescare-health
SuccessExitStatus=1
EOF
cat > /etc/systemd/system/opescare-health.timer <<'EOF'
[Unit]
Description=Run the OpesCare health check every 15 minutes
[Timer]
OnBootSec=5min
OnUnitActiveSec=15min
[Install]
WantedBy=timers.target
EOF

cat > /etc/systemd/system/opescare-backup.service <<'EOF'
[Unit]
Description=OpesCare nightly backup (database + files, 14 days, optional S3)
[Service]
Type=oneshot
ExecStart=/usr/local/bin/opescare-backup
EOF
cat > /etc/systemd/system/opescare-backup.timer <<'EOF'
[Unit]
Description=Run the OpesCare backup nightly at 02:30
[Timer]
OnCalendar=*-*-* 02:30:00
Persistent=true
[Install]
WantedBy=timers.target
EOF

cat > /etc/systemd/system/opescare-restore-test.service <<'EOF'
[Unit]
Description=OpesCare weekly restore test of the latest backup
[Service]
Type=oneshot
ExecStart=/usr/local/bin/opescare-backup restore-test
EOF
cat > /etc/systemd/system/opescare-restore-test.timer <<'EOF'
[Unit]
Description=Run the OpesCare restore test every Sunday at 04:00
[Timer]
OnCalendar=Sun *-*-* 04:00:00
Persistent=true
[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable --now opescare-health.timer opescare-backup.timer opescare-restore-test.timer
systemctl list-timers 'opescare-*' --no-pager
REMOTE
