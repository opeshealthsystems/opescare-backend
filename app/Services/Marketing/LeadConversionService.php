<?php

namespace App\Services\Marketing;

use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityStaffInvite;
use App\Models\FacilityUpdateAudit;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Facility\StaffInviteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lead → tenant (PHARMACY_DASHBOARD_AUDIT P-6).
 *
 * A lead is what the public "Organisation" signup writes. Until now the only
 * path from there to a working tenant was a platform admin creating the
 * facility, the listing and the manager by hand. This service does it in one
 * transaction, from the admin leads desk, and creates NO user: the lead's
 * contact receives the same staff invite the team page sends
 * (StaffInviteService) and the account is created when they accept it.
 *
 * ── What one conversion writes ───────────────────────────────────────────
 *   1. a `facilities` tenant (type = the directory vocabulary, as
 *      DemoFacilityTypesSeeder does — so FacilitySettingsService reads the
 *      right title even before a listing is linked);
 *   2. the public `care_facilities` listing — EITHER an existing UNLINKED
 *      listing of that type the admin picked from a short candidate list
 *      (never one that already has a facility_id), OR a new self-reported
 *      row (source_system 'portal') from what the admin typed;
 *   3. a FacilityStaffInvite for the lead's email in the manager role for
 *      the type (pharmacy → pharmacy_manager, laboratory → lab_manager,
 *      else facility_admin — a role only a platform admin may put on an
 *      invite, see FacilityStaffInvite::PLATFORM_ONLY_INVITABLE_ROLES);
 *   4. the lead marked `converted` with the tenant id — which is the
 *      idempotency key: a converted lead is never converted again;
 *   5. an AuditEvent and a facility_update_audits row for the link.
 *
 * Nothing here is invented: the name, city, region and address are what the
 * admin confirmed on the form; the listing is `self_reported` and carries no
 * coordinates until the facility sets them on its own settings page.
 */
class LeadConversionService
{
    /** Written to care_facilities.source_system on listings created here. */
    public const SOURCE_SYSTEM = 'portal';

    /** Tenant / directory type vocabulary offered on the form (finder_directory.types minus `other`). */
    public const TYPES = [
        'hospital', 'clinic', 'pharmacy', 'health_center', 'laboratory', 'dental',
        'imaging_center', 'eye_clinic', 'specialist', 'diagnostic_center', 'maternity',
        'dispensary', 'blood_bank',
    ];

    /** How many unlinked listings the form offers at once. Never unbounded. */
    public const CANDIDATE_LIMIT = 20;

    public function __construct(private readonly StaffInviteService $invites)
    {
    }

    /** The role the first person at a tenant of this type is invited into. */
    public static function managerRoleFor(string $type): string
    {
        return match ($type) {
            'pharmacy'   => 'pharmacy_manager',
            'laboratory' => 'lab_manager',
            default      => 'facility_admin',
        };
    }

    /**
     * A type to preselect from the lead's organisation type — only where the
     * lead's own word maps to exactly one directory type. `facility` and
     * `other` are left for the admin to choose.
     */
    public static function suggestedTypeFor(?string $organizationType): ?string
    {
        return match ($organizationType) {
            'pharmacy' => 'pharmacy',
            'lab'      => 'laboratory',
            default    => null,
        };
    }

    /**
     * Unlinked listings the admin may link instead of creating a new one:
     * same type, same city (case-insensitive), exact-name matches first, at
     * most CANDIDATE_LIMIT rows. A listing that already has a facility_id is
     * never offered.
     *
     * @return Collection<int, CareFacility>
     */
    public function candidateListings(string $type, ?string $city, ?string $name): Collection
    {
        if (! in_array($type, self::TYPES, true)) {
            return collect();
        }

        $city = trim((string) $city);
        $name = trim((string) $name);

        $query = CareFacility::query()
            ->whereNull('facility_id')
            ->where('facility_type', $type)
            ->orderBy('facility_name')
            ->limit(self::CANDIDATE_LIMIT);

        if ($city !== '') {
            $query->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]);
        }

        $rows = $query->get();

        if ($name !== '') {
            $lower = mb_strtolower($name);
            $rows  = $rows->sortBy(fn (CareFacility $row) => mb_strtolower($row->facility_name) === $lower ? 0 : 1)->values();
        }

        return $rows;
    }

    /**
     * Validation rules for the conversion form.
     *
     * @return array<string,array<int,mixed>>
     */
    public static function rules(): array
    {
        return [
            'facility_type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'name'          => ['required', 'string', 'max:150'],
            'city'          => ['required', 'string', 'max:120'],
            'region'        => ['nullable', 'string', 'max:120'],
            // Only read when no listing is linked (a new listing needs a street).
            'address'       => ['nullable', 'string', 'max:255', 'required_without:listing_id'],
            'listing_id'    => ['nullable', 'uuid'],
        ];
    }

    /**
     * Convert. Throws a \RuntimeException whose message is one of the
     * LEAD_* / LISTING_* codes below; the controller turns it into words.
     *
     * @param  array<string,mixed> $data  output of rules()
     * @return array{facility: Facility, listing: CareFacility, invite: FacilityStaffInvite, rawToken: string, mail: array{ok: bool, message: string}}
     */
    public function convert(Lead $lead, array $data, User $admin, ?string $ip = null): array
    {
        $email = trim((string) $lead->email);
        if ($email === '') {
            throw new \RuntimeException('LEAD_NO_EMAIL');
        }

        $roleName = self::managerRoleFor($data['facility_type']);
        if (! FacilityStaffInvite::isRedeemableRole($roleName)) {
            throw new \RuntimeException('ROLE_UNKNOWN');
        }
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            throw new \RuntimeException('ROLE_UNKNOWN');
        }

        $result = DB::transaction(function () use ($lead, $data, $admin, $role, $email, $ip): array {
            /** @var Lead $locked */
            $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($locked->isConverted()) {
                throw new \RuntimeException('LEAD_ALREADY_CONVERTED');
            }

            if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
                throw new \RuntimeException('LEAD_EMAIL_TAKEN');
            }

            $name = trim($data['name']);

            // 1. The tenant.
            $facility = Facility::create([
                'name'   => $name,
                'type'   => $data['facility_type'],
                'status' => 'active',
            ]);

            // 2. The listing — link or create.
            $listing = ! empty($data['listing_id'])
                ? $this->linkExisting((string) $data['listing_id'], $facility, $data['facility_type'])
                : $this->createListing($facility, $data, $locked);

            FacilityUpdateAudit::create([
                'facility_id'     => $listing->id,
                'actor_id'        => $admin->id,
                'actor_type'      => 'admin',
                'field_changed'   => 'facility_id',
                'old_value'       => null,
                'new_value'       => (string) $facility->id,
                'source'          => 'admin_lead_conversion',
                'requires_review' => false,
                'created_at'      => now(),
            ]);

            // 3. The invite — the real one, same row and token as the team page.
            ['invite' => $invite, 'rawToken' => $rawToken] = $this->invites->issue(
                $facility->id, $role, $email, $locked->name ?: null, $admin->id
            );

            // 4. The lead.
            $stamp = now()->format('Y-m-d H:i');
            $entry = "[{$stamp}] Converted to facility {$facility->id} ({$name}); invite sent to {$email} as {$role->name}.";
            $locked->forceFill([
                'status'                => 'converted',
                'converted_facility_id' => $facility->id,
                'converted_at'          => now(),
                'converted_by'          => $admin->id,
                'notes'                 => $locked->notes ? $locked->notes . "\n" . $entry : $entry,
            ])->save();

            // 5. The audit row.
            AuditEvent::create([
                'actor_id'           => $admin->id,
                'actor_role'         => $admin->role?->name,
                'facility_id'        => $facility->id,
                'action_type'        => 'lead_converted_to_facility',
                'resource_type'      => 'Lead',
                'resource_id'        => $locked->id,
                'source_system'      => 'portal',
                'ip_address'         => $ip,
                'emergency_override' => false,
                'after_state'        => [
                    'facility_id' => $facility->id,
                    'listing_id'  => $listing->id,
                    'listing'     => ! empty($data['listing_id']) ? 'linked' : 'created',
                    'invite_id'   => $invite->id,
                    'role'        => $role->name,
                ],
                'created_at'         => now(),
            ]);

            return compact('facility', 'listing', 'invite', 'rawToken');
        });

        // Mail only once everything is committed; its outcome is reported
        // separately and the link is shown either way.
        $result['mail'] = $this->invites->mail($result['invite'], $result['rawToken'], $admin->name);

        return $result;
    }

    private function linkExisting(string $listingId, Facility $facility, string $type): CareFacility
    {
        /** @var CareFacility|null $listing */
        $listing = CareFacility::whereKey($listingId)->lockForUpdate()->first();

        if (! $listing) {
            throw new \RuntimeException('LISTING_NOT_FOUND');
        }
        if ($listing->facility_id !== null) {
            throw new \RuntimeException('LISTING_ALREADY_LINKED');
        }
        if ($listing->facility_type !== $type) {
            throw new \RuntimeException('LISTING_TYPE_MISMATCH');
        }

        $listing->forceFill(['facility_id' => $facility->id])->save();

        return $listing;
    }

    /** @param array<string,mixed> $data */
    private function createListing(Facility $facility, array $data, Lead $lead): CareFacility
    {
        $phone = trim((string) $lead->phone);

        return CareFacility::create([
            'facility_id'         => $facility->id,
            'facility_name'       => $facility->name,
            'facility_type'       => $data['facility_type'],
            'verification_status' => 'self_reported',
            'listing_status'      => 'active',
            'country_code'        => 'CM',
            'region'              => trim((string) ($data['region'] ?? '')) ?: null,
            'city'                => trim($data['city']),
            'address'             => trim((string) $data['address']),
            'latitude'            => null,
            'longitude'           => null,
            // The column is NOT NULL; the registry placeholder is what every
            // other row without a number carries and nothing ever renders it.
            'phone_primary'       => $phone !== '' ? $phone : CareFacility::PHONE_PLACEHOLDER,
            'email'               => $lead->email,
            'source_system'       => self::SOURCE_SYSTEM,
            'source_attribution'  => 'Organisation signup (lead ' . $lead->id . ')',
        ]);
    }
}
