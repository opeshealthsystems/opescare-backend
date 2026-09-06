{{--
    The OpesCare Secure seal — the emblem the Health ID carries — shown under
    the heading of the sign-in and "Create your Health ID" pages.

    Layered like the app's SealSystem (components/health-id/Layers.tsx): a
    breathing aura, the holographic ring turning slowly behind the seal, the
    seal itself. Pure CSS — see ".f-auth-seal" in public/css/auth.css — with
    both animations switched off under prefers-reduced-motion. No JS, no inline
    styles. The artwork is decorative (empty alt, aria-hidden); the caption is
    the one line a screen reader hears. The box is a fixed 116px so the heading
    and the form never move while the bitmaps load.
--}}
<div class="f-auth-seal">
    <div class="f-auth-seal__art" aria-hidden="true">
        <span class="f-auth-seal__aura"></span>
        <img class="f-auth-seal__ring" src="{{ asset('images/health-id/holo-ring.png') }}" alt="" width="89" height="89" decoding="async">
        <img class="f-auth-seal__mark" src="{{ asset('images/health-id/seal.png') }}" alt="" width="72" height="72" decoding="async">
    </div>
    <p class="f-auth-seal__caption">{{ __('onboarding.common.secure_seal') }}</p>
</div>
