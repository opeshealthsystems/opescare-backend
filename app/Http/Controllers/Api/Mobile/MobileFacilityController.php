<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Models\AppointmentSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileFacilityController extends Controller
{
    /**
     * GET /api/mobile/facilities
     * Query: ?type=hospital|clinic|pharmacy  ?city=Yaounde  ?q=search_term  ?page=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = CareFacility::where('listing_status', 'active');

        if ($type = $request->query('type')) {
            $query->where('facility_type', $type);
        }

        if ($city = $request->query('city')) {
            $query->where('city', 'like', '%' . $city . '%');
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('facility_name', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%')
                    ->orWhere('city', 'like', '%' . $q . '%');
            });
        }

        $facilities = $query->select([
            'id', 'facility_name', 'facility_type', 'ownership_type',
            'city', 'region', 'address', 'latitude', 'longitude',
            'phone_primary', 'phone_secondary', 'email', 'website',
            'integration_status', 'listing_status',
        ])->paginate(20);

        return response()->json([
            'data'       => $facilities->items(),
            'pagination' => [
                'total'        => $facilities->total(),
                'per_page'     => $facilities->perPage(),
                'current_page' => $facilities->currentPage(),
                'last_page'    => $facilities->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mobile/facilities/{id}
     */
    public function show(string $id): JsonResponse
    {
        $facility = CareFacility::with(['services', 'hours', 'insurances'])
            ->where('listing_status', 'active')
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id'                 => $facility->id,
                'facility_name'      => $facility->facility_name,
                'facility_type'      => $facility->facility_type,
                'ownership_type'     => $facility->ownership_type,
                'city'               => $facility->city,
                'region'             => $facility->region,
                'address'            => $facility->address,
                'latitude'           => $facility->latitude,
                'longitude'          => $facility->longitude,
                'phone_primary'      => $facility->phone_primary,
                'phone_secondary'    => $facility->phone_secondary,
                'email'              => $facility->email,
                'website'            => $facility->website,
                'integration_status' => $facility->integration_status,
                'description'        => $facility->description,
                'services'           => $facility->services->map(fn ($s) => [
                    'service_name'        => $s->service_name,
                    'service_category'    => $s->service_category,
                    'specialty'           => $s->specialty,
                    'appointment_required' => $s->appointment_required,
                    'walk_in_allowed'     => $s->walk_in_allowed,
                    'availability_status' => $s->availability_status,
                ]),
                'hours' => $facility->hours->map(fn ($h) => [
                    'day_of_week'     => $h->day_of_week,
                    'opens_at'        => $h->opens_at,
                    'closes_at'       => $h->closes_at,
                    'is_closed'       => $h->is_closed,
                    'is_24_hours'     => $h->is_24_hours,
                    'service_context' => $h->service_context,
                ]),
                'insurances' => $facility->insurances->map(fn ($i) => [
                    'insurance_name'   => $i->insurance_name,
                    'cashless_available' => $i->cashless_available,
                    'status'           => $i->status,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/mobile/facilities/{careFacilityId}/slots
     *
     * Lists upcoming open appointment slots for a care facility from the directory.
     * The care_facilities record must have a linked facility_id (facilities table)
     * for slots to appear. Returns empty data if no link exists.
     *
     * Query: ?date=YYYY-MM-DD  (optional, defaults to today onward)
     */
    public function slots(Request $request, string $careFacilityId): JsonResponse
    {
        $request->validate(['date' => 'nullable|date']);

        $careFacility = CareFacility::where('listing_status', 'active')
            ->findOrFail($careFacilityId);

        // No linked internal facility — return empty gracefully
        if (!$careFacility->facility_id) {
            return response()->json(['facility_id' => null, 'data' => []]);
        }

        $from = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))->startOfDay()
            : now();

        $slots = AppointmentSlot::where('facility_id', $careFacility->facility_id)
            ->where('status', 'open')
            ->where('starts_at', '>=', $from)
            ->whereRaw('booked_count < capacity')
            ->orderBy('starts_at')
            ->limit(50)
            ->get();

        return response()->json([
            // Internal `facilities` table id — the mobile client must echo this back
            // as `facility_id` when it calls POST /mobile/appointments (that endpoint
            // validates against `facilities`, not the public `care_facilities` directory
            // the patient actually browsed), so it has to be surfaced here.
            'facility_id' => $careFacility->facility_id,
            'data' => $slots->map(fn ($s) => [
                'id'              => $s->id,
                'starts_at'       => $s->starts_at->toIso8601String(),
                'ends_at'         => $s->ends_at->toIso8601String(),
                'available_count' => $s->capacity - $s->booked_count,
                'provider_id'     => $s->provider_id,
            ]),
        ]);
    }
}
