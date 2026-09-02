<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Identity\QrTokenService;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function __construct(private QrTokenService $qrService) {}

    /**
     * Show the Health ID manual lookup form.
     */
    public function healthId()
    {
        return view('verify.health_id', [
            'result' => null,
            'error'  => null,
        ]);
    }

    /**
     * Handle Health ID lookup POST.
     */
    public function healthIdLookup(Request $request)
    {
        $request->validate([
            'health_id' => 'required|string|max:64',
            'purpose'   => 'required|string|max:60',
        ]);

        $patient = Patient::where('health_id', $request->health_id)
            ->where('is_demo', false)
            ->first();

        if (!$patient) {
            return view('verify.health_id', [
                'result' => null,
                'error'  => __('verify.error_not_found', [], app()->getLocale())
                    ?: 'No verified Health ID found for the provided identifier. Please check the ID and try again.',
            ]);
        }

        return view('verify.health_id', [
            'result' => [
                'health_id'     => $patient->health_id,
                'first_name'    => $patient->first_name,
                'last_name'     => $patient->last_name,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'sex'           => $patient->sex,
            ],
            'error' => null,
        ]);
    }

    /**
     * Handle QR token verification (scanned link).
     */
    public function qr(string $token)
    {
        $qrToken = $this->qrService->verifyToken($token);

        if (!$qrToken) {
            return view('verify.qr', [
                'token'  => $token,
                'result' => null,
                'error'  => 'QR code is invalid, expired, or has been revoked.',
            ]);
        }

        $patient = $qrToken->patient;

        return view('verify.qr', [
            'token'  => $token,
            'result' => $patient ? [
                'health_id'     => $patient->health_id,
                'first_name'    => $patient->first_name,
                'last_name'     => $patient->last_name,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'sex'           => $patient->sex,
            ] : null,
            'error'  => $patient ? null : 'Patient record not found for this QR token.',
        ]);
    }
}
