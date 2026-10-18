{{--
    Shared admin modal behaviour — the ONE script behind every `.modal-fixed`
    dialog under /admin.

    Seven admin views used to carry a near-identical nonced script each
    (open/close by id, click-the-backdrop-to-close, Escape-to-close). Opening
    and closing are now plain op-actions attributes on the trigger:

        <button data-show="my-modal" data-display="flex">…</button>
        <button data-hide="my-modal">…</button>

    `.modal-fixed` is `display:none` at rest and flex when shown, so every
    data-show carries data-display="flex". A page that must fill the dialog
    before showing it keeps a tiny nonced fill function and puts BOTH
    attributes on the trigger (`data-call="fillX" data-args=… data-show=…`);
    op-actions runs the call first, then the show.

    This partial supplies only what an attribute cannot: closing on a backdrop
    click and on Escape. Include it once, inside @section('scripts').
--}}
<script @cspNonce>
(function () {
    'use strict';
    function close(m) { m.style.display = 'none'; m.classList.remove('open'); }
    document.addEventListener('click', function (e) {
        if (e.target instanceof Element && e.target.classList.contains('modal-fixed')) close(e.target);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal-fixed').forEach(close);
    });
})();
</script>
