/*
 * OPES desk tables — one responsive pattern for every portal table.
 * Copies each <thead> header text into data-label on the body cells that lack
 * one, then adds .od-stack so od.css can turn rows into cards below 640px.
 * Opt out per table with data-stack="off". Loaded by layouts/portal (CSP: an
 * external file, no inline handlers).
 */
(function () {
  'use strict';
  var SEL = 'table.od-table, table.data-table, table.pf-table, table.table, table.odc-stack, table.odp-stack-sm';

  function labels(table) {
    var row = table.tHead && table.tHead.rows[table.tHead.rows.length - 1];
    if (!row) return null;
    var out = [];
    Array.prototype.forEach.call(row.cells, function (th) {
      var text = (th.getAttribute('data-label') || th.textContent || '').replace(/\s+/g, ' ').trim();
      var span = parseInt(th.getAttribute('colspan') || '1', 10) || 1;
      for (var i = 0; i < span; i++) out.push(text);
    });
    return out;
  }

  function enhance(table) {
    if (table.getAttribute('data-stack') === 'off' || table.classList.contains('od-stack')) return;
    var heads = labels(table);
    if (!heads || heads.length < 2) return;   // no header row: nothing to label, leave it scrolling
    Array.prototype.forEach.call(table.tBodies, function (tbody) {
      Array.prototype.forEach.call(tbody.rows, function (tr) {
        var col = 0;
        Array.prototype.forEach.call(tr.cells, function (cell) {
          if (!cell.hasAttribute('data-label') && !cell.hasAttribute('colspan') && heads[col] !== undefined) {
            cell.setAttribute('data-label', heads[col]);
          }
          col += parseInt(cell.getAttribute('colspan') || '1', 10) || 1;
        });
      });
    });
    table.classList.add('od-stack');
  }

  function run(root) {
    Array.prototype.forEach.call((root || document).querySelectorAll(SEL), enhance);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { run(); });
  else run();
  window.odTables = { enhance: run };
})();
