# Android App Links — setup (prepared, NOT published)

Status 2026-10-19: prepared only. `public/.well-known/assetlinks.json` does **not**
exist on purpose: publishing it with a made-up fingerprint would make Android
verification fail, and a wrong file is cached by devices. Do the steps below only
once the real Play App Signing certificate exists. Play readiness item D1 in
`docs/design/PLAY_STORE_READINESS.md`.

## Facts this is built on

| Fact | Source |
|---|---|
| Android package `com.opescare.patientid` | `C:\laragon\www\opescare-finder-mobile\app.json` → `expo.android.package` |
| App already has custom scheme `opescarefinder` (not an App Link) | same file, `expo.scheme` |
| Host is `opescare.com` (+ `www.opescare.com`) | memory `opescare-cloud-now-finder.md`; finder.opescare.cloud was deleted 2026-10-18 |
| Links worth opening in the app: `/verify/qr`, `/verify/doc/{token}` | Play readiness D1 |
| nginx vhost `/etc/nginx/sites-enabled/opescare.cloud.conf` keeps CloudPanel's `location ~ /.well-known { ... }` block and adds `location ^~ /.well-known/acme-challenge/` (certbot webroot) before it | `scripts/ops/move-finder-to-opescare-com.sh` |

## 1. Get the fingerprint (owner, Play Console)

Play Console → the app → **Test and release → App integrity → App signing** →
"App signing key certificate" → **SHA-256 certificate fingerprint** (colon-separated
upper-case hex, 32 bytes). Use the *App signing key*, not the upload key: Play
re-signs every install with it. Optionally add the upload-key fingerprint as a
second entry so sideloaded/internal EAS builds also verify.

## 2. The file — `public/.well-known/assetlinks.json`

```json
[
  {
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
      "namespace": "android_app",
      "package_name": "com.opescare.patientid",
      "sha256_cert_fingerprints": [
        "REPLACE_WITH_PLAY_APP_SIGNING_SHA256"
      ]
    }
  }
]
```

Commit it in `public/.well-known/` (it ships with the normal deploy; `public/` is
the vhost root). Laravel never sees the request: nginx serves the static file.

## 3. Check nginx serves it (after deploy)

`.well-known/acme-challenge/` is routed to the certbot webroot; every other
`/.well-known/*` path falls through CloudPanel's `location ~ /.well-known` block to
the vhost root (`public/`). This was **not verified live** from this repo — confirm:

```bash
curl -sI https://opescare.com/.well-known/assetlinks.json   # expect 200, Content-Type: application/json, no redirect
curl -s  https://opescare.com/.well-known/assetlinks.json | python -m json.tool
curl -sI https://www.opescare.com/.well-known/assetlinks.json  # only if www is in the intent filter
```

Requirements (Android): HTTPS, status 200, `application/json`, **no redirect**
(a 301 from www to apex fails verification for www — so either leave www out of the
intent filter, or serve the file on www without redirect). If the response is 404,
add to the opescare.com vhost, above the generic block:

```nginx
location = /.well-known/assetlinks.json {
    root /home/finder/htdocs/finder.opescare.cloud/public;
    default_type application/json;
    add_header Cache-Control "public, max-age=3600";
}
```

Then `nginx -t && systemctl reload nginx`. Google's checker:
`https://digitalassetlinks.googleapis.com/v1/statements:list?source.web.site=https://opescare.com&relation=delegate_permission/common.handle_all_urls`

## 4. The app — `app.json` (`expo.android`)

```json
"intentFilters": [
  {
    "action": "VIEW",
    "autoVerify": true,
    "data": [
      { "scheme": "https", "host": "opescare.com", "pathPrefix": "/verify" }
    ],
    "category": ["BROWSABLE", "DEFAULT"]
  }
]
```

This needs a new EAS build (intent filters are native config; an OTA update does
not apply them). The app must also route `/verify/qr` and `/verify/doc/{token}`
to screens that exist in expo-router, or the link opens an unmatched route.

After installing from Play: `adb shell pm get-app-links com.opescare.patientid`
should show `opescare.com: verified`.
