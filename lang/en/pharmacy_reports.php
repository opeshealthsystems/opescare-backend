<?php

/*
|--------------------------------------------------------------------------
| Pharmacy reports — expiry, valuation, dispensing volume, low stock, and
| the monthly controlled-substance register
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/pharmacy_reports.php (scripts/i18n-audit.php).
*/

return [

    'nav_reports'         => "Reports",
    'no_facility_context' => "No active pharmacy is selected for this session.",

    // ── Page ──────────────────────────────────────────────────────────────
    'index_title'         => "Reports",
    'index_subtitle'      => "Four views of your own ledger. Every table reads the lots you recorded — nothing is estimated, and an empty table means there is nothing to report.",
    'preview_hint'        => "Showing the first :count rows. The export carries every row.",
    'btn_csv'             => "CSV",
    'btn_pdf'             => "PDF",
    'btn_apply'           => "Apply",
    'empty'               => "Nothing to report.",
    'rows_total'          => ":count rows",
    'currency'            => "FCFA",
    'unpriced'            => "no price",

    // ── Filters ───────────────────────────────────────────────────────────
    'lbl_window'          => "Expiring within",
    'window_days'         => ":days days",
    'lbl_from'            => "From",
    'lbl_to'              => "To",

    // ── Cards ─────────────────────────────────────────────────────────────
    'card_expiry'         => "Expiring stock",
    'card_expiry_sub'     => "Open lots still holding packs that reach their expiry date within the window.",
    'card_valuation'      => "Stock valuation",
    'card_valuation_sub'  => "Usable packs × selling price, in whole francs. Items without a price are listed but not valued.",
    'card_dispensing'     => "Dispensing volume",
    'card_dispensing_sub' => "Scripts and lines handed over at this counter, by day and by pharmacist. Counts only — no patient appears here.",
    'card_low_stock'      => "Low and zero stock",
    'card_low_stock_sub'  => "Items under their active minimum, with the reorder rule beside the count.",

    // ── Summary lines ─────────────────────────────────────────────────────
    'sum_lots'            => ":count lots · :packs packs",
    'sum_valuation'       => ":items items · :packs packs · :total FCFA",
    'sum_unpriced'        => ":count without a price",
    'sum_dispensing'      => ":scripts scripts · :lines lines · :days active days",
    'sum_low'             => ":count items · :out out of stock",

    // ── Columns ───────────────────────────────────────────────────────────
    'col_medicine'        => "Medicine",
    'col_lot'             => "Lot",
    'col_expiry'          => "Expiry",
    'col_days_left'       => "Days left",
    'col_packs'           => "Packs",
    'col_usable_packs'    => "Usable packs",
    'col_price_xaf'       => "Price (FCFA)",
    'col_value_xaf'       => "Value (FCFA)",
    'col_group'           => "Grouping",
    'col_day_or_pharmacist' => "Day / pharmacist",
    'col_scripts'         => "Scripts",
    'col_lines'           => "Lines",
    'col_reorder_level'   => "Minimum",
    'col_reorder_qty'     => "Reorder qty",
    'col_state'           => "State",
    'group_day'           => "Day",
    'group_pharmacist'    => "Pharmacist",
    'unknown_pharmacist'  => "Not recorded",
    'state_low'           => "Low",
    'state_out'           => "Out of stock",

    // ── PDF sheet ─────────────────────────────────────────────────────────
    'pdf_title_expiry'      => "Expiring stock report",
    'pdf_title_valuation'   => "Stock valuation report",
    'pdf_title_dispensing'  => "Dispensing volume report",
    'pdf_title_low_stock'   => "Low stock report",
    'pdf_facility'          => "Pharmacy",
    'pdf_window'            => "Window",
    'pdf_period'            => "Period",
    'pdf_generated'         => "Generated",
    'pdf_total'             => "Total",
    'pdf_no_rows'           => "No entry for this report.",
    'pdf_truncated'         => "Showing the first :shown of :total rows. Download the CSV for the full report.",

    // ── Controlled register ───────────────────────────────────────────────
    'register_title'        => "Controlled substances register",
    'register_subtitle'     => "Dispense events, witness confirmations and reconciliations for one month.",
    'register_month'        => "Month",
    'register_export_pdf'   => "Register PDF",
    'register_export_csv'   => "Register CSV",
    'register_sum'          => ":dispensed dispensed · :witnessed witnessed · :reconciliations reconciliations · :awaiting awaiting witness",
    'register_no_rows'      => "No register entry this month.",
    'entry_dispense'        => "Dispense",
    'entry_witness'         => "Witness",
    'entry_reconciliation'  => "Reconciliation",
    'witness_confirmed'     => "Confirmed",
    'witness_pending'       => "Awaiting",
    'witness_none'          => "Not required",
    'col_when'              => "When",
    'col_entry'             => "Entry",
    'col_drug'              => "Drug",
    'col_schedule'          => "Schedule",
    'col_quantity'          => "Quantity",
    'col_unit'              => "Unit",
    'col_balance_before'    => "Balance before",
    'col_balance_after'     => "Balance after",
    'col_health_id'         => "Health ID",
    'col_by'                => "By",
    'col_witness'           => "Witness",
];
