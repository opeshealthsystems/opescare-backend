<?php

/*
 * Public developer documentation (/docs).
 *
 * public_url is the host every code sample, base-URL table and generated
 * spec (OpenAPI servers, Postman baseUrl) advertises. It is deliberately NOT
 * app.url: a local or staging install must still print the production host
 * in its docs, never its own .test / localhost name. There is no separate
 * sandbox host: sandbox access is a sandbox-scoped client on this same host.
 */
return [
    'public_url' => rtrim(env('DOCS_PUBLIC_URL', 'https://opescare.com'), '/'),
];
