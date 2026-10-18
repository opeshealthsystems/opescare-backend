#!/usr/bin/env bash
# Serve the REAL Finder install (/home/finder, DB opescare_finder) at opescare.com.
# Owner decision 2026-10-18: everything moves to opescare.com; finder.opescare.cloud
# is deleted completely afterwards (separate step), the empty opescare_main copy too.
# This script only switches. It deletes nothing and rolls back if nginx -t fails.
# Run: ssh -i ~/.ssh/opescare_deploy_ed25519 root@finder.opescare.cloud 'bash -s' < scripts/ops/move-finder-to-opescare-com.sh
set -e
TS=$(date +%Y%m%d-%H%M%S)
F=/home/finder/htdocs/finder.opescare.cloud
FV=/etc/nginx/sites-enabled/finder.opescare.cloud.conf
MV=/etc/nginx/sites-enabled/opescare.cloud.conf     # currently serves opescare.com from the empty copy
mkdir -p /root/vhost-backup
cp $FV /root/vhost-backup/finder.opescare.cloud.conf.$TS
cp $MV /root/vhost-backup/opescare.cloud.conf.$TS
cp $F/.env /root/vhost-backup/finder.env.$TS

# New opescare.com vhost = the finder vhost, renamed to opescare.com with the certbot cert.
sed -e 's#server_name finder.opescare.cloud;#server_name opescare.com www.opescare.com;#' \
    -e 's#ssl_certificate_key /etc/nginx/ssl-certificates/finder.opescare.cloud.key;#ssl_certificate_key /etc/letsencrypt/live/opescare.com/privkey.pem;#' \
    -e 's#ssl_certificate /etc/nginx/ssl-certificates/finder.opescare.cloud.crt;#ssl_certificate /etc/letsencrypt/live/opescare.com/fullchain.pem;#' \
    -e 's#  location ~ /.well-known {#  location ^~ /.well-known/acme-challenge/ { root /var/www/acme-opescare-com; }\n\n  location ~ /.well-known {#' \
    /root/vhost-backup/finder.opescare.cloud.conf.$TS > $MV
# finder.opescare.cloud stops being served (owner: delete completely, no redirect).
rm -f $FV

if nginx -t 2>&1 | grep -q successful; then
  sed -i -e 's#^APP_URL=.*#APP_URL=https://opescare.com#' \
         -e 's#^OPESCARE_OAUTH_ISSUER=.*#OPESCARE_OAUTH_ISSUER=https://opescare.com#' \
         -e 's#^SESSION_DOMAIN=.*#SESSION_DOMAIN=opescare.com#' \
         -e 's#^CORS_ALLOWED_ORIGINS=.*#CORS_ALLOWED_ORIGINS=https://opescare.com,https://www.opescare.com,http://localhost:8081,http://localhost:8082#' \
         -e 's#^MOBILE_STORE_URL=.*#MOBILE_STORE_URL=https://opescare.com/downloads/opescare-finder.apk#' $F/.env
  cd $F
  sudo -u finder php8.3 artisan config:cache >/dev/null
  sudo -u finder php8.3 artisan route:cache >/dev/null
  sudo -u finder php8.3 artisan view:cache >/dev/null
  systemctl reload nginx php8.3-fpm
  systemctl restart finder-queue
  # The empty copy no longer serves anything; stop its workers (deleted in the next step).
  systemctl disable --now opescare-main-queue.service opescare-main-scheduler.timer >/dev/null 2>&1 || true
  echo SWITCHED
else
  cp /root/vhost-backup/finder.opescare.cloud.conf.$TS $FV
  cp /root/vhost-backup/opescare.cloud.conf.$TS $MV
  nginx -t
  echo ROLLED_BACK
fi
grep -E "^(APP_URL|OPESCARE_OAUTH_ISSUER|SESSION_DOMAIN|CORS_ALLOWED_ORIGINS|MOBILE_STORE_URL)=" $F/.env
