<?php

/*
|--------------------------------------------------------------------------
| Pharmacy orders desk (web)
|--------------------------------------------------------------------------
|
| Patient medicine reservations, handled from the browser. Until this file
| existed the accept / ready / collected flow lived only in the mobile API
| (routes/mobile_pharmacy_desk.php) — a pharmacy without the staff app never
| saw an order. Registered from AppServiceProvider; the middleware stack is
| declared here next to the routes it gates.
*/

use Illuminate\Support\Facades\Route;

// Routes are added by the orders-desk build (see docs/PHARMACY_DASHBOARD_AUDIT.md, P-2).
