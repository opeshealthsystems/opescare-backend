<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use Illuminate\Support\Str;

/**
 * Reading the facility's appointment book from the front desk app: the day's
 * counts, the whole facility's list (every clinician, never another
 * facility), the clinicians it can route to, and who may open the desk at all.
 */
class FrontDeskBookTest extends FrontDeskTestCase
{
    public function test_home_counts_are_real_counts_of_todays_book(): void
    {
        // Today, this facility, one row per state the desk tracks — including
        // a patient-app booking written `booked`, which the desk must read as
        // "awaiting confirmation".
        $this->appointment(['status' => 'scheduled', 'scheduled_at' => $this->today(9)]);
        $this->appointment(['status' => 'booked', 'booked_by_type' => 'patient', 'scheduled_at' => $this->today(9, 30)]);
        $this->appointment(['status' => 'confirmed', 'scheduled_at' => $this->today(10)]);
        $this->appointment(['status' => 'checked_in', 'checked_in_at' => now(), 'scheduled_at' => $this->today(10, 30)]);
        $this->appointment(['status' => 'no_show', 'no_show_at' => now(), 'scheduled_at' => $this->today(11)]);
        $this->appointment(['status' => 'cancelled', 'cancelled_at' => now(), 'scheduled_at' => $this->today(11, 30)]);
        $this->appointment(['status' => 'scheduled', 'provider_id' => null, 'scheduled_at' => $this->today(12)]);

        // Noise the counts must ignore: tomorrow, another facility, a replaced row.
        $this->appointment(['status' => 'scheduled', 'scheduled_at' => $this->tomorrow(9)]);
        $this->appointment(['status' => 'scheduled', 'facility_id' => $this->otherFacility->id, 'provider_id' => $this->otherDoctor->id]);
        $this->appointment(['status' => 'rescheduled', 'scheduled_at' => $this->today(13)]);

        $this->hit('receptionist', 'GET', '/api/mobile/desk/home')
            ->assertOk()
            ->assertJsonPath('data.facility.id', $this->facility->id)
            ->assertJsonPath('data.facility.name', $this->facility->name)
            ->assertJsonPath('data.counts.today_total', 7)
            ->assertJsonPath('data.counts.awaiting_confirmation', 3)
            ->assertJsonPath('data.counts.confirmed', 1)
            ->assertJsonPath('data.counts.checked_in', 1)
            ->assertJsonPath('data.counts.no_show', 1)
            ->assertJsonPath('data.counts.cancelled', 1)
            ->assertJsonPath('data.counts.unassigned', 1)
            ->assertJsonStructure(['meta' => ['date', 'counts_basis']]);
    }

    public function test_the_day_list_is_the_whole_facility_and_never_another_one(): void
    {
        $mine   = $this->appointment(['scheduled_at' => $this->today(9)]);
        $nurses = $this->appointment(['provider_id' => $this->users['nurse']->id, 'scheduled_at' => $this->today(9, 30)]);
        $nobody = $this->appointment(['provider_id' => null, 'scheduled_at' => $this->today(10)]);
        $this->appointment(['facility_id' => $this->otherFacility->id, 'provider_id' => $this->otherDoctor->id, 'scheduled_at' => $this->today(9)]);
        $this->appointment(['scheduled_at' => $this->tomorrow(9)]);

        $response = $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$mine->id, $nurses->id, $nobody->id], $ids, 'ordered by time, every clinician included');

        // The contract's row shape.
        $response->assertJsonPath('data.0.status', 'scheduled')
            ->assertJsonPath('data.0.appointment_type', 'consultation')
            ->assertJsonPath('data.0.patient.id', $this->patient->id)
            ->assertJsonPath('data.0.patient.health_id', self::HEALTH_ID)
            ->assertJsonPath('data.0.patient.display_name', 'Amara Ndi')
            ->assertJsonPath('data.0.provider.id', $this->users['doctor']->id)
            ->assertJsonPath('data.0.provider.role', 'doctor')
            ->assertJsonPath('data.1.provider.role', 'nurse')
            ->assertJsonPath('data.2.provider', null)
            ->assertJsonStructure(['data' => [['id', 'scheduled_at', 'status', 'appointment_type', 'reason', 'checked_in_at', 'patient', 'provider']]]);

        // Another facility's day is not this desk's to read, under any filter.
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?provider_id=' . $this->otherDoctor->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_provider_role_is_the_one_held_at_this_facility(): void
    {
        // A doctor elsewhere who works here as a nurse is listed as a nurse.
        $visiting = $this->professional('doctor');
        $this->postAt($visiting, $this->facility, 'nurse');
        $this->appointment(['provider_id' => $visiting->id]);

        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments')
            ->assertOk()
            ->assertJsonPath('data.0.provider.role', 'nurse');
    }

    public function test_the_list_narrows_by_date_status_and_clinician_and_never_widens(): void
    {
        $this->appointment(['status' => 'confirmed', 'scheduled_at' => $this->today(9)]);
        $this->appointment(['status' => 'booked', 'scheduled_at' => $this->today(9, 30)]);
        $this->appointment(['status' => 'checked_in', 'scheduled_at' => $this->today(10)]);
        $this->appointment(['status' => 'arrived', 'scheduled_at' => $this->today(10, 15)]);
        $nurse = $this->appointment(['provider_id' => $this->users['nurse']->id, 'scheduled_at' => $this->today(10, 30)]);
        $tmrw  = $this->appointment(['scheduled_at' => $this->tomorrow(9)]);

        // The wire vocabulary covers the column vocabulary: `scheduled` finds
        // `booked` rows, `checked_in` finds `arrived` rows.
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?status=scheduled')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?status=checked_in')
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'checked_in')
            ->assertJsonPath('data.1.status', 'checked_in');
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?status=confirmed,checked_in')
            ->assertOk()->assertJsonCount(3, 'data');

        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?provider_id=' . $this->users['nurse']->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $nurse->id);

        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?date=' . $this->tomorrow(0)->toDateString())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $tmrw->id)
            ->assertJsonPath('meta.date', $this->tomorrow(0)->toDateString());

        // A status the server does not understand narrows to nothing.
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?status=bogus')
            ->assertOk()->assertJsonCount(0, 'data');

        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?date=yesterday')->assertStatus(422);
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?provider_id=not-a-uuid')->assertStatus(422);
    }

    public function test_providers_are_the_clinicians_posted_at_this_facility(): void
    {
        // Posted here but not clinical: not routable. Clinical but posted
        // elsewhere: not this desk's. A retired posting: gone.
        $retired = $this->professional('doctor');
        $this->postAt($retired, $this->facility, 'doctor', active: false);

        $response = $this->hit('receptionist', 'GET', '/api/mobile/desk/providers')->assertOk();

        $listed = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($listed->has($this->users['doctor']->id));
        $this->assertTrue($listed->has($this->users['nurse']->id));
        $this->assertTrue($listed->has($this->users['specialist']->id));
        $this->assertSame('doctor', $listed[$this->users['doctor']->id]['role']);
        $this->assertSame('nurse', $listed[$this->users['nurse']->id]['role']);
        $this->assertSame('specialist', $listed[$this->users['specialist']->id]['role']);

        foreach (['receptionist', 'pharmacist', 'labtech', 'cashier'] as $notClinical) {
            $this->assertFalse($listed->has($this->users[$notClinical]->id), "{$notClinical} is not a clinician");
        }
        $this->assertFalse($listed->has($this->otherDoctor->id), 'posted at another facility');
        $this->assertFalse($listed->has($retired->id), 'posting retired');
        $this->assertCount(3, $listed);

        $response->assertJsonStructure(['data' => [['id', 'name', 'role']]]);
    }

    public function test_the_desk_is_for_front_desk_roles_only(): void
    {
        $paths = [
            ['GET', '/api/mobile/desk/home'],
            ['GET', '/api/mobile/desk/appointments'],
            ['GET', '/api/mobile/desk/providers'],
            ['POST', '/api/mobile/desk/appointments'],
            ['PUT', '/api/mobile/desk/appointments/' . Str::uuid()],
            ['PATCH', '/api/mobile/desk/appointments/' . Str::uuid() . '/route'],
            ['POST', '/api/mobile/desk/appointments/' . Str::uuid() . '/confirm'],
            ['POST', '/api/mobile/desk/appointments/' . Str::uuid() . '/check-in'],
            ['POST', '/api/mobile/desk/appointments/' . Str::uuid() . '/no-show'],
            ['POST', '/api/mobile/desk/appointments/' . Str::uuid() . '/cancel'],
            ['POST', '/api/mobile/desk/patients'],
        ];

        foreach ($paths as [$method, $path]) {
            foreach (['doctor', 'nurse', 'specialist', 'pharmacist', 'labtech', 'cashier'] as $role) {
                $this->hit($role, $method, $path)
                    ->assertForbidden()
                    ->assertJsonPath('error_code', 'role_forbidden')
                    ->assertJsonPath('required', ['front_desk']);
            }

            // The receptionist gets PAST the gate — to the feature (200), to
            // validation (422) or to a uuid nobody owns (404); never to 403.
            $response = $this->hit('receptionist', $method, $path);
            $this->assertNotSame(403, $response->status(), "receptionist {$method} {$path}: " . $response->getContent());

            // No token at all: unauthenticated. (withHeaders() persists for
            // the test instance, so the bearer has to be flushed explicitly.)
            $this->flushHeaders()->json($method, $path)->assertUnauthorized();
        }

        // A refused role with a complete booking leaves nothing behind.
        $this->hit('doctor', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertForbidden();

        $this->assertSame(0, Appointment::count());
    }

    // ── a session without a facility ──────────────────────────────────────

    public function test_a_token_without_a_facility_is_refused_with_the_one_shape(): void
    {
        $appointment = $this->appointment();
        \Illuminate\Support\Facades\DB::table('provider_access_tokens')
            ->where('user_id', $this->users['receptionist']->id)
            ->update(['facility_id' => null]);

        $calls = [
            ['GET', '/api/mobile/desk/home', []],
            ['GET', '/api/mobile/desk/appointments', []],
            ['GET', '/api/mobile/desk/providers', []],
            ['POST', '/api/mobile/desk/patients', []],
            ['POST', '/api/mobile/desk/appointments', [
                'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'appointment_type' => 'consultation',
            ]],
        ];

        foreach ($calls as [$method, $path, $body]) {
            $response = $this->hit('receptionist', $method, $path, $body)
                ->assertStatus(422)
                ->assertJsonPath('error_code', 'facility_required')
                ->assertJsonPath('message', __('desk_api.facility_required'));
            $this->assertSame(['error_code', 'message'], array_keys($response->json()), "{$method} {$path}");
        }

        $this->assertSame('scheduled', $appointment->fresh()->status);
        $this->assertSame(1, Appointment::count());
        $this->assertSame(1, \App\Models\Patient::withoutGlobalScopes()->count(), 'no walk-in was registered');
    }
}
