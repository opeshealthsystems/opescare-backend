# Mobile logo brief: one OpesCare mark everywhere

Owner request (2026-10-18): the app home screen must show the OpesCare logo, and
the logo must be the same across the whole platform. The web app now uses one
mark everywhere (`<x-brand-logo>`). This brief is for the session rebuilding
`C:\laragon\www\opescare-finder-mobile`. It was written from an audit only;
no mobile file was edited.

## The canonical mark

- Source: `C:\laragon\www\opescare-finder\public\brand\opescare-logo-512.png`
  (512x512, transparent; blue dot halo, blue ring, white disc, heartbeat line).
  The large master is `opescare-logo.png` (1302x1208). Do not use the circular
  security seal from `docs/design/login-2026-10-18`.
- Web downscales you can copy as-is: `public/brand/opescare-mark-64.png`,
  `-128.png`, `-192.png` (plus `.webp`).
- Brand colour `#0F4C81`.

## What is already right (leave it alone)

| File | State |
|---|---|
| `assets/icon.png` (1024) | Canonical mark on a white rounded tile. Matches web `apple-touch-icon.png`. OK. |
| `assets/android-icon-foreground.png` (1024) | Canonical mark, transparent, padded for the safe zone. OK. |
| `assets/android-icon-monochrome.png` (1024) | Single-colour silhouette of the mark. OK. |
| `assets/splash-icon.png` (1024) | Canonical mark, transparent. OK. |
| `assets/health-id/qr-logo.png` | Same mark on a tile. OK. |

## What is wrong, and exactly what to change

### 1. The in-app logo is drawn, not the real mark (highest priority: this is "the home screen logo")

Two hand-drawn stand-ins are in use. Neither is the OpesCare logo.

- `components/opes-ui/components/PulseLogo.tsx`: an SVG imitation (four rings
  of evenly spaced dots and a pulse line). It is close but not the same art.
  Used by:
  - `components/core/shared.tsx`, `BrandHeader`, at `<PulseLogo size={34} />`.
    **This is the home screen header** (`app/(tabs)/home.tsx` renders `BrandHeader`).
  - `components/finder/FinderChrome.tsx:74`, the finder screens' header.
  - `app/(auth)/welcome.tsx:70`, at size 104.
  - `app/(tabs)/profile.tsx`, `app/blood-finder.tsx`, `app/health-passport.tsx`,
    `components/health-id/SecureCredentialCard.tsx`,
    `components/passport/PassportCover.tsx`, `components/profile/ProfileMasthead.tsx`.
- `components/ui/Logo.tsx`: a gradient ring with a Lucide `HeartPulse` inside it
  (a Lucide icon used as a logo). Used by `app/index.tsx:251` (the boot/splash
  route). Its comments also refer to a "gold halo" and "cream disc", which are
  off-brand.

**Change:** make the real raster the only mark.
1. Add `assets/brand/opescare-mark.png`, a copy of
   `opescare-finder/public/brand/opescare-logo-512.png` (512 is enough up to a
   170pt render at 3x; do not upscale anything smaller).
2. Rewrite `PulseLogo` to render
   `<Image source={require('../../../assets/brand/opescare-mark.png')} style={{ width: size, height: size }} resizeMode="contain" accessibilityIgnoresInvertColors />`
   and keep its `size` prop, so every call site above is fixed with no other edit.
   On a dark background, pass a `tile` prop that wraps it in a white rounded
   `View` (radius about size/4, padding about size/10). The web does the same.
3. Change `components/ui/Logo.tsx` so the mark part is `<PulseLogo size={size} />`
   (drop the LinearGradient, halo and `HeartPulse`). Keep the wordmark and
   tagline. The wordmark colours should be `#0F4C81` for "Care" and the navy
   text colour for "Opes"; drop the gold rule (`colors.brand[200]`) if the
   theme's brand scale is still gold or amber.
4. Remove the `HeartPulse` import from `Logo.tsx`. `app/index.tsx:69` uses
   `HeartPulse` only as a floating background glyph. That is decoration, not
   the logo, so it can stay.

### 2. Web export favicon is a blurry upscale

`assets/favicon.png` is 48x48 and visibly pixelated (a low-resolution source,
scaled up). It is referenced by `app.json` `web.favicon`, and the export
writes `public/app/favicon.ico` in the web repo.
**Change:** replace it with a 48x48 PNG downscaled from `opescare-logo-512.png`,
or copy `opescare-finder/public/brand/favicon-48.png`, which is already that.

### 3. Notification icon has no heartbeat line

`assets/notification-icon.png` (96x96) is a white dot halo with a solid white
disc. Android draws it as a silhouette, so the disc becomes a blank blob with
no pulse line.
**Change:** generate a 96x96 white-on-transparent silhouette from
`assets/android-icon-monochrome.png` (1024, already the right silhouette):
downscale to 96, then set every opaque pixel to `#FFFFFF`. Keep the heartbeat
transparent (cut out of the disc) so it reads at 24dp. Leave `color: "#0F4C81"`
in the `expo-notifications` plugin as it is.

### 4. Background colours are cream, not brand

`app.json` has `"backgroundColor": "#FDF8F0"` at the top level and in the
`expo-splash-screen` plugin. The web uses white (`#FFFFFF`) behind the mark
(`site.webmanifest` `background_color`). Splash cream around a blue mark is the
"cream/gold" theme the logo component also carries.
**Change:** set both to `#FFFFFF`. `adaptiveIcon.backgroundColor` is already
`#FFFFFF`. `assets/android-icon-background.png` is plain white and is fine.

### 5. Naming (report only, decide with the owner)

`app.json` `name` is already `OpesCare`. `slug` is `opescare-finder` and
`scheme` is `opescarefinder`. These are identifiers that EAS and deep links depend
on, so **do not rename them**.

## After the change

- `npx tsc --noEmit`, then `npx expo export -p web --clear` (the Metro cache
  bakes assets and env, see memory `expo-metro-cache-bakes-env`).
- Check the home header, welcome, boot/splash, profile masthead, passport cover
  and Health ID card at phone width. Every one should show the same raster mark
  as finder.opescare.cloud's header.
- The native icon, splash and notification changes need a new EAS build. An OTA
  update does not ship them.
