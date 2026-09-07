<?php

/*
|--------------------------------------------------------------------------
| Mail deliverability check (php artisan mail:health)
|--------------------------------------------------------------------------
|
| Settings for the pre-flight that decides whether this deployment can
| actually deliver an email — see App\Console\Commands\MailHealthCheck.
|
*/

return [

    /*
     | DKIM selectors to ask DNS about, most specific first.
     |
     | A selector cannot be enumerated from DNS, so a check that guesses will
     | eventually guess wrong: this one reported "no DKIM" on a domain that was
     | signing perfectly well under the selector `opescare`, simply because that
     | name was not on its list. Name yours here (or in MAIL_DKIM_SELECTORS,
     | comma-separated) and the check stops guessing.
     |
     | Left empty, the command still reads the local opendkim key table and
     | falls back to the conventional names, so most hosts need nothing here.
     */
    'dkim_selectors' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MAIL_DKIM_SELECTORS', ''))
    ))),

    /*
     | Where the signing MTA on this host records which selector it uses.
     | Read-only, and its absence is not an error — a deployment that relays
     | through a provider has no local signer at all.
     */
    'opendkim_key_table' => env('MAIL_OPENDKIM_KEY_TABLE', '/etc/opendkim/key.table'),

];
