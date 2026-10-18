/*
 * OPESCARD sign-in scanner.
 *
 * Scans the QR on a Health ID card, asks the server which Health ID it
 * belongs to (the QR holds an opaque verify token, not the ID), fills
 * input[name=login] and moves focus to the password. It never signs in:
 * the password is always required.
 *
 * Decoding: native BarcodeDetector when the browser has it, otherwise jsQR
 * (public/vendor/jsqr, Apache-2.0, served locally — CSP allows no CDN).
 */
(function () {
    'use strict';

    function init(root) {
        var dialog   = root.querySelector('[data-opescard-dialog]');
        var openBtn  = root.querySelector('[data-opescard-open]');
        var closeBtn = root.querySelector('[data-opescard-close]');
        var video    = root.querySelector('[data-opescard-video]');
        var wrap     = root.querySelector('[data-opescard-video-wrap]');
        var status   = root.querySelector('[data-opescard-status]');
        var announce = root.querySelector('[data-opescard-announce]');
        var fileIn   = root.querySelector('[data-opescard-file]');
        var msg      = root.dataset;
        var stream = null, timer = null, busy = false, detector = null;
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d', { willReadFrequently: true });

        if ('BarcodeDetector' in window) {
            try { detector = new window.BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { detector = null; }
        }

        function say(text, isError) {
            status.textContent = text || '';
            status.classList.toggle('is-error', !!isError);
        }

        function stop() {
            if (timer) { clearTimeout(timer); timer = null; }
            if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
            video.srcObject = null;
        }

        function close() {
            stop();
            if (dialog.open) { dialog.close(); }
        }

        function decodeFrom(source, w, h) {
            if (detector) {
                return detector.detect(source).then(function (codes) {
                    return codes.length ? codes[0].rawValue : decodeJs(source, w, h);
                }).catch(function () { return decodeJs(source, w, h); });
            }
            return Promise.resolve(decodeJs(source, w, h));
        }

        function decodeJs(source, w, h) {
            if (typeof window.jsQR !== 'function' || !w || !h) { return null; }
            var scale = Math.min(1, 1024 / Math.max(w, h));
            canvas.width = Math.round(w * scale);
            canvas.height = Math.round(h * scale);
            ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
            var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
            var code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'attemptBoth' });
            return code ? code.data : null;
        }

        function csrf() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) { return meta.getAttribute('content'); }
            var input = document.querySelector('input[name="_token"]');
            return input ? input.value : '';
        }

        function resolve(payload) {
            busy = true;
            stop();
            say(msg.msgChecking);
            return fetch(msg.resolveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ payload: payload })
            }).then(function (res) {
                if (res.ok) { return res.json(); }
                throw new Error(res.status === 404 || res.status === 422 ? 'not_recognised' : 'network');
            }).then(function (data) {
                var form = root.closest('form') || document;
                var login = form.querySelector('input[name="login"]') || document.querySelector('input[name="login"]')
                         || document.querySelector('input[name="email"]');
                var password = form.querySelector('input[name="password"]') || document.querySelector('input[name="password"]');
                if (login) {
                    login.value = data.health_id;
                    login.dispatchEvent(new Event('input', { bubbles: true }));
                }
                close();
                announce.textContent = msg.msgSuccess;
                if (password) { password.focus(); }
            }).catch(function (err) {
                say(err.message === 'not_recognised' ? msg.msgNotRecognised : msg.msgNetwork, true);
                busy = false;
                startCamera();
            });
        }

        function tick() {
            timer = null;
            if (!stream || busy) { return; }
            if (video.readyState < 2) { timer = setTimeout(tick, 200); return; }
            decodeFrom(video, video.videoWidth, video.videoHeight).then(function (text) {
                if (text && !busy) { resolve(text); }
                else if (stream && !busy) { timer = setTimeout(tick, 250); }
            });
        }

        function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                wrap.hidden = true;
                say(msg.msgCameraUnavailable, true);
                return;
            }
            wrap.hidden = false;
            if (!status.classList.contains('is-error')) { say(msg.msgStarting); }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(function (s) {
                    if (!dialog.open) { s.getTracks().forEach(function (t) { t.stop(); }); return; }
                    stream = s;
                    video.srcObject = s;
                    return video.play().then(function () {
                        if (!status.classList.contains('is-error')) { say(msg.msgScanning); }
                        tick();
                    });
                })
                .catch(function (err) {
                    wrap.hidden = true;
                    var denied = err && (err.name === 'NotAllowedError' || err.name === 'SecurityError');
                    say(denied ? msg.msgCameraDenied : msg.msgCameraUnavailable, true);
                });
        }

        openBtn.addEventListener('click', function () {
            busy = false;
            say('');
            announce.textContent = '';
            if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
            startCamera();
        });

        closeBtn.addEventListener('click', function () { close(); openBtn.focus(); });
        dialog.addEventListener('close', function () { stop(); });
        dialog.addEventListener('cancel', function () { stop(); });

        fileIn.addEventListener('change', function () {
            var file = fileIn.files && fileIn.files[0];
            fileIn.value = '';
            if (!file) { return; }
            // data: URL rather than blob: — img-src allows data: only.
            var reader = new FileReader();
            reader.onload = function () {
                var img = new Image();
                img.onload = function () {
                    decodeFrom(img, img.naturalWidth, img.naturalHeight).then(function (text) {
                        if (text) { resolve(text); } else { say(msg.msgNoQr, true); }
                    });
                };
                img.onerror = function () { say(msg.msgNoQr, true); };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function boot() {
        document.querySelectorAll('[data-opescard]').forEach(init);
        if (window.lucide && typeof window.lucide.createIcons === 'function') { window.lucide.createIcons(); }
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
