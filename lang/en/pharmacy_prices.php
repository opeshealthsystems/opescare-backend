<?php

/*
|--------------------------------------------------------------------------
| Pharmacy price list — one selling price per medicine, in whole francs
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/pharmacy_prices.php (scripts/i18n-audit.php).
*/

return [

    'nav_prices'          => "Price list",
    'no_facility_context' => "No active pharmacy is selected for this session.",

    // ── Index ─────────────────────────────────────────────────────────────
    'index_title'         => "Price list",
    'index_subtitle'      => "The selling price of every medicine on your shelf, in whole francs. Saving a price publishes it to the Medicine Finder at once.",
    'index_empty'         => "No item in the ledger yet. Record a goods receipt and the medicine appears here.",
    'search_placeholder'  => "Search a medicine by name",
    'search_empty'        => "No medicine matches this search.",
    'btn_search'          => "Search",
    'btn_clear'           => "Clear",
    'btn_save'            => "Save",
    'btn_history'         => "History",
    'btn_back'            => "Back to the price list",
    'sum_items'           => "Items on the shelf",
    'sum_unpriced'        => "Without a selling price",
    'sum_unpriced_hint'   => "Published to the finder without a price until one is set here.",
    'whole_francs'        => "Whole francs, no decimals.",
    'whole_francs_hint'   => "Prices are whole francs (FCFA). XAF has no minor unit, so 1500 is one thousand five hundred francs — never 15.00.",
    'no_listing'          => "This pharmacy has no linked public listing, so prices are kept here but not shown to patients yet.",

    'col_medicine'        => "Medicine",
    'col_price'           => "Selling price",
    'col_published'       => "Shown to patients",
    'col_last_changed'    => "Last changed",
    'col_history'         => "History",
    'lbl_reason'          => "Reason (optional)",

    'no_price_yet'        => "No price yet",
    'not_published'       => "Not published yet",
    'published_no_price'  => "Published without a price",
    'not_a_medicine'      => "Not a catalogue medicine",
    'differs'             => "Differs from the price list",
    'in_sync'             => "Up to date",
    'actor_unknown'       => "Unknown user",

    'flash_saved'         => "Price saved for :name and published to the finder.",
    'err_price'           => "A selling price is a whole franc amount between 0 and 99 999 999.",

    // ── History ───────────────────────────────────────────────────────────
    'history_title'       => "Price history",
    'history_subtitle'    => "Every change of the selling price of :name, oldest at the bottom.",
    'history_append_only' => "A price change is recorded, never edited or deleted. A wrong price is corrected by saving a new one.",
    'history_empty'       => "No price has been set for this item yet.",
    'sum_changes'         => "Changes recorded",
    'col_when'            => "When",
    'col_old'             => "Old price",
    'col_new'             => "New price",
    'col_by'              => "Changed by",

];
