/**
 * OpesCare Finder — notification SOUND ALERTS layer.
 *
 * Plays a short, pleasant, SYNTHESISED alert when a new notification arrives.
 * Distinct motifs for blood orders / pharmacy orders / everything else, with a
 * user mute toggle and respect for browser autoplay policy. No binary audio
 * files — every tone is generated with the Web Audio API.
 *
 * CSP note: the app ships a strict `script-src 'self'` policy, so this external
 * file (served from 'self') is the correct place for this logic. Web Audio is
 * not affected by script-src.
 *
 * Contracts this layer relies on (established elsewhere — do not change here):
 *   - notifications.js dispatches, for each genuinely NEW event:
 *       window.dispatchEvent(new CustomEvent('opescare:new-notification', {
 *           detail: { event, unread }
 *       }))
 *     where `event` has: category (blood|pharmacy|appointment|account|admin|
 *     general), event_type, title, body, priority.
 *   - The topbar bell renders a mute toggle button `[data-notif-sound-toggle]`
 *     (optionally containing a <span> label and/or data-on / data-off text).
 *
 * Public API:
 *   window.OpesNotifSound = {
 *       play(category),      // schedule the motif for a category (gated)
 *       setEnabled(bool),    // persist enabled/disabled
 *       isEnabled(),         // read persisted state (default: enabled)
 *       toggle()             // flip and persist, returns new state
 *   }
 *
 * localStorage key: `opes_notif_sound`  ('1' = on, '0' = off, unset = on).
 */
(function () {
    'use strict';

    var STORAGE_KEY  = 'opes_notif_sound';
    var MASTER_GAIN  = 0.20;                 // modest master level (0.15–0.25)
    var TOGGLE_SEL   = '[data-notif-sound-toggle]';

    var AudioCtxCtor = window.AudioContext || window.webkitAudioContext || null;
    var ctx          = null;                 // lazily created AudioContext

    // ---------------------------------------------------------------------
    // Persistence
    // ---------------------------------------------------------------------
    function isEnabled() {
        try {
            var v = window.localStorage.getItem(STORAGE_KEY);
            // Default ON when unset; only an explicit '0' disables.
            return v === null || typeof v === 'undefined' ? true : v !== '0';
        } catch (e) {
            return true;
        }
    }

    function setEnabled(on) {
        on = !!on;
        try {
            window.localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
        } catch (e) { /* private mode / storage disabled — ignore */ }
        refreshToggles();
        return on;
    }

    function toggle() {
        var next = !isEnabled();
        setEnabled(next);
        return next;
    }

    // ---------------------------------------------------------------------
    // AudioContext lifecycle (autoplay policy aware)
    //
    // Browsers block audio until a user gesture. We create/resume the context
    // only from within a gesture (the one-time unlock listener, or the toggle
    // click). Until then, play() is a silent no-op — it never creates a
    // context and never throws.
    // ---------------------------------------------------------------------
    function ensureContext() {
        if (!AudioCtxCtor) return null;
        try {
            if (!ctx) ctx = new AudioCtxCtor();
            if (ctx.state === 'suspended' && typeof ctx.resume === 'function') {
                ctx.resume();
            }
            return ctx;
        } catch (e) {
            return null;
        }
    }

    function unlockOnce() {
        ensureContext();
        try {
            document.removeEventListener('click', unlockOnce, true);
            document.removeEventListener('keydown', unlockOnce, true);
        } catch (e) { /* noop */ }
    }

    // ---------------------------------------------------------------------
    // Tone synthesis — one oscillator + gain envelope per note.
    // Quick attack, smooth exponential decay → no clicks.
    // ---------------------------------------------------------------------
    function tone(startAt, freq, dur, peak, type) {
        if (!ctx) return;
        try {
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            var g    = (typeof peak === 'number' ? peak : MASTER_GAIN);
            var attack = 0.012;

            osc.type = type || 'sine';
            osc.frequency.setValueAtTime(freq, startAt);

            // Envelope: start near-silent, ramp up, then decay smoothly.
            gain.gain.setValueAtTime(0.0001, startAt);
            gain.gain.linearRampToValueAtTime(g, startAt + attack);
            gain.gain.exponentialRampToValueAtTime(0.0001, startAt + dur);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(startAt);
            osc.stop(startAt + dur + 0.03);
        } catch (e) { /* skip this note, never throw */ }
    }

    // Render the motif for a category. Each motif stays <= ~450ms.
    function render(category) {
        if (!ctx) return;
        var t = ctx.currentTime + 0.02;   // tiny lead so scheduling is clean

        switch (category) {
            case 'blood':
                // Urgent, attention-grabbing rising two-note motif (higher).
                tone(t,        784,  0.13, MASTER_GAIN,        'triangle'); // G5
                tone(t + 0.12, 1175, 0.20, MASTER_GAIN,        'triangle'); // D6 (rising 5th)
                break;

            case 'pharmacy':
                // Softer, gentler, lower descending two-note motif.
                tone(t,        523,  0.16, MASTER_GAIN * 0.85, 'sine');     // C5
                tone(t + 0.15, 392,  0.24, MASTER_GAIN * 0.85, 'sine');     // G4 (gentle fall)
                break;

            case '__confirm__':
                // Tiny confirmation ping when the user turns sound ON.
                tone(t,        880,  0.10, MASTER_GAIN * 0.70, 'sine');     // A5
                break;

            default:
                // Everything else: a single soft "ping".
                tone(t,        660,  0.22, MASTER_GAIN * 0.80, 'sine');     // ~E5
                break;
        }
    }

    // Public play(): gated no-op unless enabled AND a context already exists
    // (i.e. a user gesture has unlocked audio). Scheduled tones fire once the
    // context is running, so a transient 'suspended' state self-heals.
    function play(category) {
        try {
            if (!isEnabled()) return;
            if (!ctx) return;                 // not unlocked yet → silent no-op
            if (ctx.state === 'suspended' && typeof ctx.resume === 'function') {
                ctx.resume();
            }
            render(typeof category === 'string' && category ? category : 'general');
        } catch (e) { /* never throw from an alert */ }
    }

    // Called from within the toggle click (a user gesture), so it can create
    // and resume the context and be reliably audible on first use.
    function confirmPing() {
        try {
            if (!ensureContext()) return;
            render('__confirm__');
        } catch (e) { /* noop */ }
    }

    // ---------------------------------------------------------------------
    // Toggle button wiring
    // ---------------------------------------------------------------------
    function reflect(btn) {
        if (!btn) return;
        try {
            var on = isEnabled();

            btn.classList.toggle('is-on', on);
            btn.classList.toggle('is-off', !on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');

            // Update a text label only when we know the wording: either an
            // explicit data-on / data-off, or an existing <span> paired with them.
            var onLabel  = btn.getAttribute('data-on');
            var offLabel = btn.getAttribute('data-off');
            if (onLabel !== null || offLabel !== null) {
                var label = on ? (onLabel || '') : (offLabel || '');
                var span  = btn.querySelector('span');
                if (span) {
                    span.textContent = label;
                } else if (label) {
                    btn.setAttribute('aria-label', label);
                    btn.setAttribute('title', label);
                }
            }
        } catch (e) { /* noop */ }
    }

    function refreshToggles() {
        try {
            var btns = document.querySelectorAll(TOGGLE_SEL);
            for (var i = 0; i < btns.length; i++) reflect(btns[i]);
        } catch (e) { /* noop */ }
    }

    function onToggleClick(e) {
        var target = e && e.target;
        var btn = target && typeof target.closest === 'function'
            ? target.closest(TOGGLE_SEL)
            : null;
        if (!btn) return;

        var nowOn = toggle();     // persists + refreshes every toggle
        reflect(btn);             // reflect this one immediately
        if (nowOn) confirmPing(); // audible confirmation only when turning ON
    }

    // ---------------------------------------------------------------------
    // Bootstrapping
    // ---------------------------------------------------------------------
    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    // Unlock audio on the first user gesture (once).
    try {
        document.addEventListener('click', unlockOnce, true);
        document.addEventListener('keydown', unlockOnce, true);
    } catch (e) { /* noop */ }

    // Listen for new notifications. Guard everything so a malformed detail
    // (missing event / category / null detail) never throws.
    try {
        window.addEventListener('opescare:new-notification', function (e) {
            try {
                if (!isEnabled()) return;
                var detail   = (e && e.detail) ? e.detail : {};
                var ev       = detail.event || {};
                var category = (ev && typeof ev.category === 'string' && ev.category)
                    ? ev.category
                    : 'general';
                play(category);
            } catch (err) { /* never throw */ }
        });
    } catch (e) { /* noop */ }

    // Wire the toggle(s). Event delegation on document means late-rendered
    // bells still work; a MutationObserver reflects state on toggles that
    // appear after this script runs.
    onReady(function () {
        try {
            document.addEventListener('click', onToggleClick);
        } catch (e) { /* noop */ }

        refreshToggles();

        if (window.MutationObserver) {
            try {
                var mo = new MutationObserver(function (records) {
                    for (var i = 0; i < records.length; i++) {
                        var added = records[i].addedNodes;
                        if (!added) continue;
                        for (var j = 0; j < added.length; j++) {
                            var n = added[j];
                            if (!n || n.nodeType !== 1) continue;
                            if (typeof n.matches === 'function' && n.matches(TOGGLE_SEL)) {
                                reflect(n);
                            }
                            if (typeof n.querySelectorAll === 'function') {
                                var inner = n.querySelectorAll(TOGGLE_SEL);
                                for (var k = 0; k < inner.length; k++) reflect(inner[k]);
                            }
                        }
                    }
                });
                mo.observe(document.documentElement, { childList: true, subtree: true });
            } catch (e) { /* observer unsupported — delegation still works */ }
        }
    });

    // ---------------------------------------------------------------------
    // Public surface
    // ---------------------------------------------------------------------
    window.OpesNotifSound = {
        play: play,
        setEnabled: function (on) { return setEnabled(!!on); },
        isEnabled: isEnabled,
        toggle: toggle
    };
})();
