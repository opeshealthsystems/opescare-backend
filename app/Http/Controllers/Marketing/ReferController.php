<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ReferralInvite;
use App\Modules\Subscription\Services\ReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Patient "Refer & Earn" growth page. Distinct from the clinical referrals
 * feature (portals.patient.referrals / ReferralCase).
 */
class ReferController extends Controller
{
    public function __construct(private readonly ReferralRewardService $rewards)
    {
    }

    public function index(Request $request)
    {
        $patient = Auth::user()?->patient;
        abort_if($patient === null, 403);

        $code = $this->rewards->codeFor($patient);
        $link = rtrim(config('app.url'), '/') . '/signup/patient?ref=' . $code;

        $invites = ReferralInvite::where('referrer_patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        $rewardedDays = $invites->where('status', 'rewarded')->sum('referrer_reward_days');

        return view('portals.patient.refer', [
            'patient'        => $patient,
            'code'           => $code,
            'link'           => $link,
            'invites'        => $invites,
            'rewardedCount'  => $invites->where('status', 'rewarded')->count(),
            'joinedCount'    => $invites->whereIn('status', ['joined', 'rewarded'])->count(),
            'rewardedDays'   => $rewardedDays,
            'referrerDays'   => ReferralRewardService::REFERRER_REWARD_DAYS,
            'refereeDays'    => ReferralRewardService::REFEREE_REWARD_DAYS,
        ]);
    }
}
