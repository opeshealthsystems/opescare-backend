<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityRegistry;
use App\Models\Prescription;
use App\Models\ProfessionalLicense;
use App\Models\StaffProfile;
use App\Services\Clinical\PrescriptionAccessService;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * The paper half of the prescriber desk.
 *
 * Cameroon runs on paper: a patient leaves the consulting room with a sheet in
 * their hand and walks it to a pharmacy that may have no connection to this
 * system at all. This controller renders that sheet — and puts a QR on it so
 * the pharmacy that DOES have a phone can jump straight from the paper to the
 * live record instead of trusting the ink.
 *
 * Two rules the sheet exists to satisfy:
 *
 *  1. The QR encodes a URL, never a data blob. A prescription's contents in a
 *     base64 JSON QR is a payload no camera app can do anything with, and it
 *     freezes the clinical state at print time — a script voided an hour later
 *     still scans as valid. A URL defers the answer to the server, so a
 *     withdrawn script stops verifying even on paper already handed over.
 *  2. A prescription that is void, cancelled, entered-in-error, superseded or
 *     expired must never come off the printer looking like a normal script.
 *     The sheet says so across the top and repeats it beside the signature.
 *  3. The sheet must look issued and be awkward to forge. It carries the
 *     platform mark, the facility's own contact block, and BOTH licence
 *     numbers — the prescriber's and the facility's — printed as "not
 *     recorded" when the record does not hold them, because a blank where a
 *     licence should be is itself information. Behind the content runs a
 *     watermark woven from this prescription's own reference, so a sheet
 *     photocopied blank still names the script it came from.
 *
 * Everything the view needs for that is already in this payload: `facility`
 * carries `license_number`, `registry` carries the MINSANTE code and the
 * postal/telephone contact, and `licenseNumber` is the prescriber's. Nothing
 * on the sheet is invented — a missing value prints as missing.
 *
 * Scope matches PrescriberController exactly — same facility resolution, same
 * middleware — so nothing is printable here that is not viewable there.
 */
class PrescriptionPrintController extends Controller
{
    /**
     * States in which the prescription is a historical record, not an order.
     * `expired` is handled separately because it is also derivable from a date.
     */
    private const NOT_VALID_STATUSES = ['voided', 'entered_in_error', 'cancelled', 'amended'];

    /** The printable sheet for one prescription. */
    public function show(string $id, PrescriptionAccessService $access): View
    {
        $prescription = Prescription::with(['patient', 'items', 'prescriber'])
            ->where('facility_id', $this->facilityId())
            ->findOrFail($id);

        $facility = $prescription->facility_id
            ? Facility::find($prescription->facility_id)
            : null;

        // Facilities carry only name/type/licence; the postal and telephone
        // contact a regulator expects on a script lives on the care-map
        // registry row that claimed this facility.
        $registry = $facility
            ? FacilityRegistry::where('claimed_facility_id', $facility->id)->first()
            : null;

        [$credential, $licenseNumber] = $this->prescriberCredential($prescription);

        $status  = (string) ($prescription->status ?? '');
        $expired = $status === 'expired'
            || ($prescription->expires_at !== null && $prescription->expires_at->isPast());

        [$verifyUrl, $qrSvg] = $this->presentation($prescription, $access);

        return view('portals.prescriber.print', [
            'prescription'   => $prescription,
            'patient'        => $prescription->patient,
            'items'          => $prescription->items ?? collect(),
            'facility'       => $facility,
            'registry'       => $registry,
            'prescriber'     => $prescription->prescriber,
            'credential'     => $credential,
            'licenseNumber'  => $licenseNumber,
            'rxStatus'       => $status,
            'isNotValid'     => in_array($status, self::NOT_VALID_STATUSES, true),
            'isExpired'      => $expired,
            'verifyUrl'      => $verifyUrl,
            'qrSvg'          => $qrSvg,
        ]);
    }

    /**
     * The scannable half of the sheet: a live verify URL and the QR that
     * carries it.
     *
     * Returns nulls rather than throwing. The access service mints its token
     * row BEFORE it resolves route('verify.rx'), so calling it while that
     * public verify endpoint is unregistered would leave an orphan capability
     * behind and then 500 the print page; the Route::has() check keeps both
     * from happening. When there is no URL the view falls back to the printed
     * reference — it never renders a placeholder or a broken image.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function presentation(Prescription $prescription, PrescriptionAccessService $access): array
    {
        if (! Route::has('verify.rx')) {
            return [null, null];
        }

        try {
            $verifyUrl = $access->currentOrIssue($prescription, auth()->id())['verify_url'];

            return [$verifyUrl, $this->qrSvg($verifyUrl)];
        } catch (\Throwable $e) {
            report($e);

            return [null, null];
        }
    }

    /**
     * A real, scannable QR as inline SVG.
     *
     * Rendered server-side on purpose. The page is printed, so it cannot depend
     * on a script running at the right moment, and AddSecurityHeaders serves a
     * CSP that a client-side QR library would have to be allow-listed into.
     * Inline SVG needs neither: no script, no network fetch, and it scales to
     * whatever size the print stylesheet asks for without going fuzzy.
     *
     * Light modules are left undrawn so the QR sits on the paper's own white —
     * half the markup, and nothing for a printer to lay down but the dark ones.
     * The URL is repeated as a data-url attribute so a test can read what the
     * matrix encodes without decoding it.
     */
    private function qrSvg(string $url): ?string
    {
        try {
            $svg = (new QRCode(new QROptions([
                'outputInterface'  => QRMarkupSVG::class,
                'eccLevel'         => EccLevel::M,
                'addQuietzone'     => true,
                'quietzoneSize'    => 2,
                'cssClass'         => 'rx-qr',
                'svgAddXmlHeader'  => false,
                'outputBase64'     => false,
                'drawLightModules' => false,
            ])))->render($url);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        return str_replace(
            '<svg ',
            '<svg data-url="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" ',
            $svg
        );
    }

    /**
     * What the prescriber is licensed as, if the record knows.
     *
     * A Cameroonian script is signed by a named, licensed prescriber, so the
     * sheet prints the profession and licence number when a staff profile
     * carries them and falls back to the job title. Nothing is invented: a user
     * with no staff profile prints as a name alone.
     *
     * @return array{0: ?string, 1: ?string} [credential, licence number]
     */
    private function prescriberCredential(Prescription $prescription): array
    {
        if (! $prescription->prescribed_by) {
            return [null, null];
        }

        $staff = StaffProfile::where('user_id', $prescription->prescribed_by)->first();

        if (! $staff) {
            return [null, null];
        }

        $license = ProfessionalLicense::where('staff_profile_id', $staff->id)
            ->orderByDesc('created_at')
            ->first();

        $credential = $license?->profession ?: ($staff->job_title ?: null);

        return [$credential, $license?->license_number ?: null];
    }

    /**
     * The facility this sheet is printed for. Mirrors PrescriberController's
     * resolution exactly — printing must not widen the scope of what is
     * readable at the desk.
     */
    private function facilityId(): ?string
    {
        return session('active_facility_id')
            ?? auth()->user()?->primary_facility_id
            ?? Facility::value('id');
    }
}
