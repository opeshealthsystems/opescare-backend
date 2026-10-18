{{--
    OPESCARD sign-in: scan the QR on a Health ID card to FILL the identifier
    box. The QR is never a credential; the password is still required.
    Wired by public/js/opescard-scan.js (no inline handlers: CSP is nonce-only).
--}}
<style>
    .opc-btn{display:flex;align-items:center;gap:.85rem;width:100%;padding:.8rem 1rem;border:1.5px solid #0F4C81;border-radius:.6rem;background:#fff;color:#0F4C81;text-align:left;cursor:pointer;font:inherit}
    .opc-btn:hover{background:#f0f6fb}
    .opc-btn:focus-visible{outline:3px solid #0F4C81;outline-offset:2px}
    .opc-btn svg,.opc-btn i{width:28px;height:28px;flex:none}
    .opc-btn strong{display:block;font-size:.95rem}
    .opc-btn small{display:block;color:#475569;font-size:.8rem}
    .opc-dialog{border:0;border-radius:.8rem;padding:0;width:min(440px,calc(100vw - 32px));box-shadow:0 20px 50px rgba(15,76,129,.25)}
    .opc-dialog::backdrop{background:rgba(15,23,42,.55)}
    .opc-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem;background:#0F4C81;color:#fff}
    .opc-head h2{margin:0;font-size:1rem}
    .opc-close{background:none;border:0;color:#fff;cursor:pointer;padding:.25rem;display:flex}
    .opc-close:focus-visible{outline:2px solid #fff}
    .opc-body{padding:1rem}
    .opc-body p{margin:0 0 .75rem;font-size:.85rem;color:#334155}
    .opc-video-wrap{position:relative;background:#0f172a;border-radius:.5rem;overflow:hidden;aspect-ratio:1/1}
    .opc-video-wrap video{width:100%;height:100%;object-fit:cover;display:block}
    .opc-frame{position:absolute;inset:18%;border:3px solid rgba(255,255,255,.9);border-radius:.6rem;pointer-events:none}
    .opc-status{min-height:1.3rem;margin:.75rem 0;font-size:.85rem;color:#0F4C81}
    .opc-status.is-error{color:#b91c1c}
    .opc-or{text-align:center;font-size:.8rem;color:#64748b;margin:.5rem 0}
    .opc-upload{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem;border:1.5px dashed #0F4C81;border-radius:.5rem;color:#0F4C81;cursor:pointer;font-size:.9rem}
    .opc-upload:focus-within{outline:3px solid #0F4C81;outline-offset:2px}
    .opc-upload input{position:absolute;width:1px;height:1px;opacity:0}
    .opc-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
    .opc-hint{display:block;text-align:center;font-size:.75rem;color:#64748b;margin-top:.35rem}
</style>

<div class="opc" data-opescard
     data-resolve-url="{{ route('login.opescard.resolve') }}"
     data-msg-starting="{{ __('opescard.starting') }}"
     data-msg-scanning="{{ __('opescard.scanning') }}"
     data-msg-checking="{{ __('opescard.checking') }}"
     data-msg-success="{{ __('opescard.success') }}"
     data-msg-camera-unavailable="{{ __('opescard.errors.camera_unavailable') }}"
     data-msg-camera-denied="{{ __('opescard.errors.camera_denied') }}"
     data-msg-no-qr="{{ __('opescard.errors.no_qr_in_photo') }}"
     data-msg-not-recognised="{{ __('opescard.errors.not_recognised') }}"
     data-msg-network="{{ __('opescard.errors.network') }}">

    <button type="button" class="opc-btn" data-opescard-open aria-haspopup="dialog">
        <i data-lucide="qr-code" aria-hidden="true"></i>
        <span>
            <strong>{{ __('opescard.button_title') }}</strong>
            <small>{{ __('opescard.button_subtitle') }}</small>
        </span>
    </button>

    <p class="opc-sr" role="status" aria-live="polite" data-opescard-announce></p>

    <dialog class="opc-dialog" data-opescard-dialog aria-labelledby="opescard-title">
        <div class="opc-head">
            <h2 id="opescard-title">{{ __('opescard.modal_title') }}</h2>
            <button type="button" class="opc-close" data-opescard-close aria-label="{{ __('opescard.close') }}">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>
        <div class="opc-body">
            <p>{{ __('opescard.modal_intro') }}</p>
            <div class="opc-video-wrap" data-opescard-video-wrap>
                <video data-opescard-video playsinline muted aria-hidden="true"></video>
                <span class="opc-frame" aria-hidden="true"></span>
            </div>
            <div class="opc-status" role="status" aria-live="polite" data-opescard-status></div>
            <div class="opc-or">{{ __('opescard.or') }}</div>
            <label class="opc-upload">
                <i data-lucide="image-up" aria-hidden="true"></i>
                <span>{{ __('opescard.upload_label') }}</span>
                <input type="file" accept="image/*" data-opescard-file>
            </label>
            <span class="opc-hint">{{ __('opescard.upload_hint') }}</span>
        </div>
    </dialog>
</div>

<script src="{{ asset('vendor/jsqr/jsQR.js') }}" @cspNonce defer></script>
<script src="{{ asset('js/opescard-scan.js') }}" @cspNonce defer></script>
