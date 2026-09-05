<?php

namespace Tests\Feature\Identity;

use App\Models\Patient;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Does this human already hold a Health ID?"
 *
 * Production carries TWO Health IDs for one person — same first name, same
 * last name, same date of birth — created a day apart through two different
 * screens. The guard that should have caught it existed the whole time.
 *
 * These tests pin each way it could miss. Every one of them is a silent miss:
 * the check returns "no duplicate", a second Health ID is minted, and the
 * platform whose entire premise is one portable record per person now has two
 * for that person, each holding half their history.
 */
class DuplicateIdentityGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** An existing patient, created on whichever side of the demo partition. */
    private function existing(array $overrides = [], bool $demo = true): Patient
    {
        $patient = app(HealthIdGeneratorService::class)->generate('CM', function (string $healthId) use ($overrides) {
            return Patient::create(array_merge([
                'health_id'           => $healthId,
                'first_name'          => 'Jude',
                'last_name'           => 'Nshome',
                'date_of_birth'       => '1990-04-17',
                'sex'                 => 'male',
                'phone_number'        => '+237600111222',
                'identity_status'     => 'provisional',
                'verification_status' => 'unverified',
            ], $overrides));
        });

        // is_demo is deliberately not fillable — it is set by the trait's
        // creating hook from config, so place the row explicitly.
        $patient->forceFill(['is_demo' => $demo])->save();

        return $patient;
    }

    // ─── 1. The demo partition must not hide an identity from the check ─────

    public function test_an_identity_on_the_other_side_of_the_demo_partition_is_still_found(): void
    {
        Config::set('demo.enabled', true);
        $this->existing(demo: false);   // a "real" row while the app runs in demo

        // IsDemoRecord splits the table in two, so a plain query sees nothing…
        $this->assertNull(
            Patient::where('first_name', 'Jude')->first(),
            'sanity: the partition really does hide it from an ordinary query'
        );

        // …but "does this person exist?" is a question about the register, not
        // about the half of it this request may browse.
        $this->assertNotNull(
            Patient::findExistingIdentity('Jude', 'Nshome', '1990-04-17'),
            'the identity check must see across the demo partition'
        );
    }

    // ─── 2. The submitted date must be normalised before comparing ──────────

    /**
     * Every one of these is the same day and passes `required|date`. The old
     * check compared the raw request string to `->toDateString()`, so only the
     * first of them could ever match.
     */
    public static function equivalentDateFormats(): array
    {
        return [
            'ISO date'            => ['1990-04-17'],
            'ISO datetime with Z' => ['1990-04-17T00:00:00.000Z'],
            'slashed'             => ['04/17/1990'],
            'written out'         => ['17 April 1990'],
        ];
    }

    #[DataProvider('equivalentDateFormats')]
    public function test_any_accepted_date_format_matches_the_same_stored_day(string $submitted): void
    {
        $this->existing();

        $this->assertNotNull(
            Patient::findExistingIdentity('Jude', 'Nshome', $submitted),
            "submitted as '{$submitted}' — same day, so it is the same person"
        );
    }

    public function test_a_genuinely_different_date_is_not_a_match(): void
    {
        $this->existing();

        $this->assertNull(Patient::findExistingIdentity('Jude', 'Nshome', '1991-04-17'));
    }

    // ─── 3. Names must match the way people write them, not the way SQL does ─

    /** @return array<string, array{0: string, 1: string}> */
    public static function nameSpellings(): array
    {
        return [
            'lower case'       => ['jude', 'nshome'],
            'upper case'       => ['JUDE', 'NSHOME'],
            'trailing space'   => ['Jude ', 'Nshome'],
            'leading space'    => [' Jude', ' Nshome'],
        ];
    }

    #[DataProvider('nameSpellings')]
    public function test_case_and_whitespace_do_not_make_a_new_person(string $first, string $last): void
    {
        $this->existing();

        $this->assertNotNull(
            Patient::findExistingIdentity($first, $last, '1990-04-17'),
            "'{$first} {$last}' is the same human as 'Jude Nshome'"
        );
    }

    public function test_a_different_person_with_a_similar_name_is_not_a_match(): void
    {
        $this->existing();

        $this->assertNull(Patient::findExistingIdentity('Jude', 'Nshomé', '1990-04-17'));
        $this->assertNull(Patient::findExistingIdentity('Judith', 'Nshome', '1990-04-17'));
    }

    // ─── 4. A phone number is an exact match on its own ─────────────────────

    public function test_a_known_phone_number_is_a_match_without_any_name(): void
    {
        $this->existing();

        $this->assertNotNull(Patient::findExistingIdentity(null, null, null, '+237600111222'));
        $this->assertNull(Patient::findExistingIdentity(null, null, null, '+237699999999'));
    }

    public function test_a_phone_number_is_found_across_the_demo_partition_too(): void
    {
        Config::set('demo.enabled', true);
        $this->existing(demo: false);

        $this->assertNull(Patient::findByPhone('+237600111222'), 'sanity: the scoped helper cannot see it');
        $this->assertNotNull(Patient::findExistingIdentity(null, null, null, '+237600111222'));
    }

    // ─── 5. Nothing is ever merged, and nothing throws ──────────────────────

    public function test_an_empty_register_and_unparseable_input_both_return_null(): void
    {
        $this->assertNull(Patient::findExistingIdentity('Nobody', 'Here', '1990-04-17'));
        $this->assertNull(Patient::findExistingIdentity('Jude', 'Nshome', 'not a date at all'));
        $this->assertNull(Patient::findExistingIdentity(null, null, null, null));
    }

    // ─── 6. The registration endpoint actually refuses ──────────────────────

    public function test_registering_a_second_time_as_the_same_person_is_refused(): void
    {
        $this->existing();

        $response = $this->postJson('/api/mobile/auth/register', [
            'first_name'             => 'jude',                       // different case
            'last_name'              => ' Nshome ',                   // stray spaces
            'dob'                    => '1990-04-17T00:00:00.000Z',   // different format
            'sex'                    => 'male',
            'phone'                  => '+237600333444',              // a different phone
            'email'                  => 'second.account@example.cm',
            'password'               => 'correct horse battery',
            'password_confirmation'  => 'correct horse battery',
            'emergency_name'         => 'Next Of Kin',
            'emergency_relationship' => 'sibling',
            'emergency_phone'        => '+237600555666',
        ]);

        $response->assertStatus(409);

        // And crucially: no second Health ID was minted.
        $this->assertSame(1, Patient::withoutGlobalScope('isolate_demo')->count());
    }

    public function test_a_genuinely_new_person_still_registers(): void
    {
        $this->existing();

        $this->postJson('/api/mobile/auth/register', [
            'first_name'             => 'Amara',
            'last_name'              => 'Diallo',
            'dob'                    => '1985-02-03',
            'sex'                    => 'female',
            'phone'                  => '+237600777888',
            'email'                  => 'amara@example.cm',
            'password'               => 'correct horse battery',
            'password_confirmation'  => 'correct horse battery',
            'emergency_name'         => 'Next Of Kin',
            'emergency_relationship' => 'sibling',
            'emergency_phone'        => '+237600555666',
        ])->assertSuccessful();

        $this->assertSame(2, Patient::withoutGlobalScope('isolate_demo')->count());
    }
}
