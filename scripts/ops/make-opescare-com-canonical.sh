#!/usr/bin/env bash
# Make opescare.com the main address of the opescare.cloud Finder install.
# opescare.com + www serve the app; opescare.cloud + www 301 to opescare.com.
# Backs up both vhosts and the .env first; rolls back if `nginx -t` fails.
# Run from this machine:
#   ssh -i ~/.ssh/opescare_deploy_ed25519 root@finder.opescare.cloud 'bash -s' < scripts/ops/make-opescare-com-canonical.sh
set -e
TS=$(date +%Y%m%d-%H%M%S)
V=/etc/nginx/sites-enabled/opescare.cloud.conf
R=/etc/nginx/sites-enabled/opescare.com.conf
E=/home/opescare-app/htdocs/opescare.cloud-new/.env
cp $V /root/vhost-backup/opescare.cloud.conf.$TS
cp $R /root/vhost-backup/opescare.com.conf.$TS
cp $E /home/opescare-app/secrets/env.$TS

# Main vhost now serves opescare.com with the Let's Encrypt certificate.
sed -i 's#ssl_certificate_key /etc/nginx/ssl-certificates/opescare.cloud.key;#ssl_certificate_key /etc/letsencrypt/live/opescare.com/privkey.pem;#; s#ssl_certificate /etc/nginx/ssl-certificates/opescare.cloud.crt;#ssl_certificate /etc/letsencrypt/live/opescare.com/fullchain.pem;#; s#server_name opescare.cloud www.opescare.cloud;#server_name opescare.com www.opescare.com;#' $V
# Keep certbot renewals working (webroot used when the cert was issued).
sed -i 's#  location ~ /.well-known {#  location ^~ /.well-known/acme-challenge/ { root /var/www/acme-opescare-com; }\n\n  location ~ /.well-known {#' $V

# Redirect file now sends opescare.cloud to opescare.com.
cat > $R <<'EOF'
server {
  listen 80;
  listen [::]:80;
  server_name opescare.cloud www.opescare.cloud;
  location ^~ /.well-known/ { root /home/opescare-app/htdocs/opescare.cloud-new/public; }
  location / { return 301 https://opescare.com$request_uri; }
}
server {
  listen 443 ssl;
  listen [::]:443 ssl;
  http2 on;
  server_name opescare.cloud www.opescare.cloud;
  ssl_certificate /etc/nginx/ssl-certificates/opescare.cloud.crt;
  ssl_certificate_key /etc/nginx/ssl-certificates/opescare.cloud.key;
  return 301 https://opescare.com$request_uri;
}
EOF

if nginx -t 2>&1 | grep -q successful; then
  sed -i 's#^APP_URL=.*#APP_URL=https://opescare.com#; s#^CORS_ALLOWED_ORIGINS=.*#CORS_ALLOWED_ORIGINS=https://opescare.com#; s#^OPESCARE_OAUTH_ISSUER=.*#OPESCARE_OAUTH_ISSUER=https://opescare.com#' $E
  cd /home/opescare-app/htdocs/opescare.cloud-new
  sudo -u opescare-app php8.3 artisan config:cache >/dev/null
  sudo -u opescare-app php8.3 artisan route:cache >/dev/null
  systemctl reload nginx php8.3-fpm
  systemctl restart opescare-main-queue
  echo SWITCHED
else
  cp /root/vhost-backup/opescare.cloud.conf.$TS $V
  cp /root/vhost-backup/opescare.com.conf.$TS $R
  nginx -t
  echo ROLLED_BACK
fi
grep -E "^(APP_URL|CORS_ALLOWED_ORIGINS|OPESCARE_OAUTH_ISSUER)=" $E
