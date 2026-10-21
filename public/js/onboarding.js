/*
 * OpesCare — facility onboarding wizard (/signup/organization).
 * Loaded as an external, nonced script: no inline handlers, everything wired
 * through data-ob-* attributes. Without JS the form renders all steps at once
 * and still submits normally.
 *
 * Autosave keeps answers in this browser's localStorage only (never sent
 * anywhere until the person submits); files are never stored.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-ob]');
    if (!root) return;

    var KEY = 'opescare.onboarding.org.v1';

    function store(fn) { try { return fn(window.localStorage); } catch (e) { return undefined; } }

    if (root.hasAttribute('data-ob-success')) {
        store(function (ls) { ls.removeItem(KEY); });
        return;
    }

    var form = root.querySelector('[data-ob-form]');
    if (!form) return;

    root.classList.add('ob-js');

    var total = parseInt(root.getAttribute('data-ob-total'), 10) || 6;
    var panes = Array.prototype.slice.call(root.querySelectorAll('[data-ob-pane]'));
    var prevBtn = root.querySelector('[data-ob-prev]');
    var nextBtn = root.querySelector('[data-ob-next]');
    var submitBtn = root.querySelector('[data-ob-submit]');
    var invalidBox = root.querySelector('[data-ob-invalid]');
    var savedLine = root.querySelector('[data-ob-saved-line]');
    var stepOf = root.getAttribute('data-ob-step-of') || ':current / :total';
    var notGiven = root.getAttribute('data-ob-not-given') || '-';
    var filesTpl = root.getAttribute('data-ob-files') || ':count';
    var step = 1;

    function txt(s, map) {
        return String(s).replace(/:(\w+)/g, function (m, k) { return map[k] !== undefined ? map[k] : m; });
    }

    /* ---------- Step display ---------- */
    function show(n, focus) {
        step = Math.max(1, Math.min(total, n));
        panes.forEach(function (p) {
            p.classList.toggle('is-active', parseInt(p.getAttribute('data-ob-pane'), 10) === step);
        });
        root.querySelectorAll('[data-ob-rail]').forEach(function (li) {
            var i = parseInt(li.getAttribute('data-ob-rail'), 10);
            li.classList.toggle('is-done', i < step);
            li.classList.toggle('is-now', i === step);
            li.classList.toggle('is-todo', i > step);
            if (i === step) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
        });
        root.querySelectorAll('[data-ob-seg]').forEach(function (seg) {
            var i = parseInt(seg.getAttribute('data-ob-seg'), 10);
            seg.classList.toggle('is-done', i < step);
            seg.classList.toggle('is-now', i === step);
        });
        var label = txt(stepOf, { current: step, total: total });
        root.querySelectorAll('[data-ob-count]').forEach(function (el) { el.textContent = label; });
        var bar = root.querySelector('[data-ob-bar]');
        if (bar) {
            bar.setAttribute('aria-valuenow', String(step));
            var fill = bar.querySelector('i');
            if (fill) fill.style.width = Math.round(step / total * 100) + '%';
        }
        var pane = panes[step - 1];
        var mt = root.querySelector('[data-ob-mtitle]');
        if (mt && pane) mt.textContent = pane.getAttribute('data-ob-title') || '';

        if (prevBtn) prevBtn.hidden = step === 1;
        if (nextBtn) nextBtn.hidden = step === total;
        if (submitBtn) submitBtn.hidden = step !== total;
        if (invalidBox) invalidBox.hidden = true;
        if (step === total) buildReview();
        if (focus && pane) {
            pane.focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: 'auto' });
        }
    }

    /* ---------- Validation ---------- */
    function invalidIn(pane) {
        var bad = [];
        pane.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            if (el.disabled) return;
            var ok = el.checkValidity();
            el.setAttribute('aria-invalid', ok ? 'false' : 'true');
            el.classList.toggle('is-invalid', !ok);
            if (!ok) bad.push(el);
        });
        return bad;
    }

    function check(pane) {
        var bad = invalidIn(pane);
        if (bad.length) {
            if (invalidBox) invalidBox.hidden = false;
            bad[0].focus();
            if (bad[0].reportValidity) bad[0].reportValidity();
            return false;
        }
        if (invalidBox) invalidBox.hidden = true;
        return true;
    }

    if (nextBtn) nextBtn.addEventListener('click', function () {
        if (!check(panes[step - 1])) return;
        show(step + 1, true);
        save();
    });
    if (prevBtn) prevBtn.addEventListener('click', function () {
        show(step - 1, true);
        save();
    });

    form.addEventListener('submit', function (e) {
        for (var i = 0; i < panes.length; i++) {
            if (invalidIn(panes[i]).length) {
                e.preventDefault();
                show(i + 1, false);
                check(panes[i]);
                return;
            }
        }
        // Phones are typed without the country code shown in the prefix.
        form.querySelectorAll('[data-ob-phone]').forEach(function (el) {
            var v = el.value.trim();
            if (v && v.charAt(0) !== '+') el.value = '+237 ' + v;
        });
        save();
    });

    /* ---------- Facility type ---------- */
    function currentType() {
        var r = form.querySelector('input[name="org_type"]:checked');
        return r || null;
    }

    function applyType() {
        var r = currentType();
        if (!r) return;
        var type = r.value;
        var msg = root.querySelector('[data-ob-type-msg] div');
        if (msg) msg.textContent = r.getAttribute('data-ob-msg') || '';
        var notice = root.querySelector('[data-ob-type-notice] div');
        if (notice) notice.textContent = r.getAttribute('data-ob-notice') || '';
        var hint = root.querySelector('[data-ob-type-hint]');
        if (hint) hint.textContent = r.getAttribute('data-ob-label') || '';
        root.querySelectorAll('[data-ob-services]').forEach(function (block) {
            var on = block.getAttribute('data-ob-services').split(' ').indexOf(type) !== -1;
            block.hidden = !on;
            block.querySelectorAll('input').forEach(function (cb) { cb.disabled = !on; });
        });
    }

    form.querySelectorAll('input[name="org_type"]').forEach(function (r) {
        r.addEventListener('change', applyType);
    });

    /* ---------- Review ---------- */
    function val(name) {
        var el = form.elements[name];
        return el && el.value ? String(el.value).trim() : '';
    }

    function setR(key, value, cls) {
        var dd = root.querySelector('[data-ob-r="' + key + '"]');
        if (!dd) return;
        dd.className = '';
        if (!value) { dd.textContent = notGiven; dd.classList.add('is-empty'); return; }
        dd.textContent = value;
        if (cls) dd.classList.add(cls);
    }

    function buildReview() {
        var r = currentType();
        setR('type', r ? r.getAttribute('data-ob-label') : '');
        ['legal_name', 'trade_name', 'license_number', 'reg_number', 'address', 'main_email'].forEach(function (k) { setR(k, val(k)); });
        setR('main_phone', val('main_phone') ? '+237 ' + val('main_phone').replace(/^\+?237\s*/, '') : '');
        var person = val('contact_name');
        if (person && val('contact_role')) person += ' (' + val('contact_role') + ')';
        setR('person', person);
        var pc = [val('contact_email'), val('contact_phone') ? '+237 ' + val('contact_phone').replace(/^\+?237\s*/, '') : ''].filter(Boolean).join(' · ');
        setR('person_contact', pc);
        var svc = [];
        form.querySelectorAll('input[name="services[]"]:checked:not(:disabled)').forEach(function (cb) {
            svc.push(cb.getAttribute('data-ob-svc-label') || cb.value);
        });
        setR('services', svc.join(', '));
        var files = 0;
        form.querySelectorAll('[data-ob-file]').forEach(function (f) { if (f.files && f.files.length) files++; });
        setR('documents', txt(filesTpl, { count: files }), files === 2 ? 'is-good' : '');
    }

    /* ---------- Autosave (this device only) ---------- */
    function collect() {
        var data = {};
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || el.name === '_token' || el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
            if (el.type === 'checkbox') {
                if (el.name.slice(-2) === '[]') {
                    data[el.name] = data[el.name] || [];
                    if (el.checked) data[el.name].push(el.value);
                } else {
                    data[el.name] = el.checked;
                }
            } else if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function setSaved(ok) {
        if (!savedLine) return;
        savedLine.classList.toggle('is-off', !ok);
        savedLine.textContent = root.getAttribute(ok ? 'data-ob-saved' : 'data-ob-not-saved') || '';
    }

    var saveTimer = null;
    function save() {
        var ok = store(function (ls) {
            ls.setItem(KEY, JSON.stringify({ v: 1, step: step, data: collect() }));
            return true;
        });
        setSaved(ok === true);
    }
    function saveSoon() {
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(save, 400);
    }
    form.addEventListener('input', saveSoon);
    form.addEventListener('change', saveSoon);

    function restore() {
        var raw = store(function (ls) { return ls.getItem(KEY); });
        if (!raw) return 0;
        var saved;
        try { saved = JSON.parse(raw); } catch (e) { return 0; }
        if (!saved || saved.v !== 1 || !saved.data) return 0;
        var d = saved.data;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || !(el.name in d) || el.type === 'file' || el.name === '_token') return;
            var v = d[el.name];
            if (el.type === 'checkbox') {
                el.checked = Array.isArray(v) ? v.indexOf(el.value) !== -1 : !!v;
            } else if (el.type === 'radio') {
                el.checked = el.value === v;
            } else if (typeof v === 'string') {
                el.value = v;
            }
        });
        setSaved(true);
        return parseInt(saved.step, 10) || 1;
    }

    /* ---------- Boot ---------- */
    var serverErrors = root.querySelector('.ob-error');
    var startStep = 1;
    if (serverErrors) {
        // Server-side validation failed: old() values are authoritative.
        var pane = serverErrors.closest('[data-ob-pane]');
        startStep = pane ? parseInt(pane.getAttribute('data-ob-pane'), 10) : 1;
    } else {
        var queryType = new URLSearchParams(window.location.search).get('type');
        startStep = restore();
        if (queryType) {
            var q = form.querySelector('input[name="org_type"][value="' + queryType.replace(/[^a-z_]/g, '') + '"]');
            if (q) { q.checked = true; startStep = 1; }
        }
        // Files never survive a reload, so never resume past the documents step.
        startStep = Math.min(startStep || 1, total - 1);
    }
    applyType();
    show(startStep, false);
})();
