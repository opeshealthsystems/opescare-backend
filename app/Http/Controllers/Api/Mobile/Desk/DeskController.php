<?php

namespace App\Http\Controllers\Api\Mobile\Desk;

use App\Http\Controllers\Controller;
use App\Http\Resources\Desk\DeskAppointmentResource;
use App\Http\Resources\Desk\DeskHomeResource;
use App\Http\Resources\Desk\DeskProviderResource;
use App\Services\FrontDesk\DeskActor;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Front desk mobile API — reading the facility's book.
 *
 *   GET /api/mobile/desk/home          the day's counts
 *   GET /api/mobile/desk/appointments  the whole facility's book for a day
 *   GET /api/mobile/desk/providers     the clinicians appointments can go to
 *
 * Every route sits behind `auth.provider` + `capability:front_desk`. The
 * facility is the one the bearer token was minted for
 * (`provider_facility_id` on the request attributes) and nothing else — no
 * validator here declares a facility key, and a body carrying one is not
 * consulted. The desk sees every clinician's list at its own facility and
 * nothing at any other.
 *
 * No patient enumeration: there is no search parameter and no LIKE. The only
 * patients that appear are the ones already on this facility's book.
 */
class DeskController extends Controller
{
    public function __construct(private readonly FrontDeskService $desk)
    {
    }

    /** GET /api/mobile/desk/home → {data:{facility:{id,name}, counts:{…}}, meta:{counts_basis}} */
    public function home(Request $request): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        $today = Carbon::now(config('app.timezone'));

        return response()->json([
            'data' => DeskHomeResource::make([
                'facility' => $this->desk->facility($actor->facilityId),
                'counts'   => $this->desk->counts($actor->facilityId, $today),
            ]),
            'meta' => [
                'date'         => $today->toDateString(),
                'counts_basis' => $this->desk->countsBasis(),
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/desk/appointments?date=YYYY-MM-DD&status=&provider_id=
     *
     * `date` defaults to today, so a bare call is "the day", never the whole
     * table. `status` is the desk's wire vocabulary (one value or a comma
     * list); `provider_id` narrows to one clinician's column of the book.
     * Both only ever narrow the facility bound.
     */
    public function appointments(Request $request): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        $validated = $request->validate([
            'date'        => ['nullable', 'date_format:Y-m-d'],
            'status'      => ['nullable', 'string', 'max:200'],
            'provider_id' => ['nullable', 'uuid'],
        ]);

        $tz  = config('app.timezone');
        $day = Carbon::parse($validated['date'] ?? Carbon::now($tz)->toDateString(), $tz);

        $rows = $this->desk->dayBook(
            $actor->facilityId,
            $day,
            $this->desk->columnStatusesFor($validated['status'] ?? null),
            $validated['provider_id'] ?? null,
        );

        return response()->json([
            'data' => DeskAppointmentResource::collection($rows),
            'meta' => [
                'date'  => $day->toDateString(),
                'total' => $rows->count(),
            ],
        ], 200);
    }

    /** GET /api/mobile/desk/providers → {data:[{id,name,role}]} — clinicians posted at this facility. */
    public function providers(Request $request): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        return response()->json([
            'data' => DeskProviderResource::collection($this->desk->clinicians($actor->facilityId)),
        ], 200);
    }

    /**
     * One refusal shape for the desk API: a stable machine code plus the
     * translated sentence. SetLocale is not on the `api` group, so `message`
     * comes back in the application's default locale and `error_code` is what
     * lets the phone render the refusal in the receptionist's own language.
     */
    private function refuse(string $key, int $status): JsonResponse
    {
        return response()->json([
            'error_code' => $key,
            'message'    => __('desk_api.' . $key),
        ], $status);
    }
}
