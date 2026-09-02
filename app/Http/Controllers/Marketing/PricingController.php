<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

/**
 * Public pricing page. Patient plans are pulled live from the subscription
 * catalog; organization pricing is quote-based (links to the demo request).
 */
class PricingController extends Controller
{
    public function index()
    {
        $patientPlans = SubscriptionPlan::forAudience('patient')
            ->active()
            ->public()
            ->with('planFeatures')
            ->orderBy('sort_order')
            ->get();

        return view('public.pricing', compact('patientPlans'));
    }
}
