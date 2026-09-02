<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\User;
use App\Notifications\HealthIdExpiryNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Mobile Patient API — Notification Center.
 *
 * Covers GET /mobile/notifications, GET /mobile/notifications/unread-count,
 * POST /mobile/notifications/{id}/read and POST /mobile/notifications/mark-all-read.
 *
 * Two things these tests exist to pin down:
 *
 *  1. **Scoping.** MobileNotificationController::scopedQuery() ORs together the
 *     authenticated Patient's own rows and the rows of that patient's linked
 *     User account. An OR inside a query builder is exactly the shape that
 *     silently loses its grouping parentheses during a refactor and turns into
 *     a cross-patient IDOR, so every endpoint is asserted against a second
 *     patient's data.
 *
 *  2. **UUID morph keys.** `notifications.notifiable_id` was originally created
 *     by $table->morphs('notifiable') as a BIGINT, but every notifiable in this
 *     application (Patient, User) has a UUID primary key. The column could never
 *     hold a valid id: reads threw SQLSTATE[22P02] and both GET endpoints 500'd.
 *     test_notifications_table_stores_uuid_notifiable_ids() fails loudly if the
 *     column ever reverts to an integer type.
 */
class MobileNotificationTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;
    private Patient $otherPatient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8300-0001-01',
            'first_name'    => 'Nadia',
            'last_name'     => 'Notify',
            'sex'           => 'female',
            'date_of_birth' => '1991-03-03',
            'is_demo'       => false,
        ]);

        $this->otherPatient = Patient::create([
            'health_id'     => 'OC-TST-8300-0002-01',
            'first_name'    => 'Etienne',
            'last_name'     => 'Elsewhere',
            'sex'           => 'male',
            'date_of_birth' => '1985-11-11',
            'is_demo'       => false,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Write one database notification for a UUID-keyed notifiable, through the
     * real morph relation (so the morph key actually round-trips the column).
     */
    private function seedNotification(
        Model $notifiable,
        array $data = [],
        ?string $createdAt = null,
        bool $read = false,
    ): DatabaseNotification {
        return $notifiable->notifications()->create([
            'id'         => (string) Str::uuid(),
            'type'       => HealthIdExpiryNotification::class,
            'data'       => array_merge([
                'type'    => 'health_id_expiry_warning',
                'title'   => 'Your Health ID expires soon',
                'message' => 'Please renew before the expiry date.',
            ], $data),
            'read_at'    => $read ? Carbon::now() : null,
            'created_at' => $createdAt ? Carbon::parse($createdAt) : Carbon::now(),
            'updated_at' => $createdAt ? Carbon::parse($createdAt) : Carbon::now(),
        ]);
    }

    private function linkedUserFor(Patient $patient, string $email): User
    {
        return User::create([
            'name'       => trim($patient->first_name . ' ' . $patient->last_name),
            'email'      => $email,
            'password'   => 'password',
            'patient_id' => $patient->id,
        ]);
    }

    // ── Auth ─────────────────────────────────────────────────────────────

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/mobile/notifications')->assertStatus(401);
        $this->getJson('/api/mobile/notifications/unread-count')->assertStatus(401);
        $this->postJson('/api/mobile/notifications/mark-all-read')->assertStatus(401);
        $this->postJson('/api/mobile/notifications/' . Str::uuid() . '/read')->assertStatus(401);
    }

    // ── unread-count ─────────────────────────────────────────────────────

    public function test_unread_count_is_zero_when_the_patient_has_no_notifications(): void
    {
        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 0]);
    }

    public function test_unread_count_returns_the_number_of_unread_rows(): void
    {
        $this->seedNotification($this->patient);
        $this->seedNotification($this->patient);
        $this->seedNotification($this->patient);
        // A read row must not be counted.
        $this->seedNotification($this->patient, read: true);

        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 3]);
    }

    // ── index ────────────────────────────────────────────────────────────

    public function test_index_returns_the_patients_notifications_newest_first(): void
    {
        $this->seedNotification($this->patient, ['message' => 'oldest'], '2026-01-01 08:00:00');
        $this->seedNotification($this->patient, ['message' => 'middle'], '2026-02-01 08:00:00');
        $this->seedNotification($this->patient, ['message' => 'newest'], '2026-03-01 08:00:00');

        $response = $this->mobileGetJson($this->patient, '/api/mobile/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'type', 'category', 'title', 'message', 'severity', 'read', 'created_at']],
                'unread_count',
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('unread_count', 3)
            ->assertJsonPath('data.0.message', 'newest')
            ->assertJsonPath('data.2.message', 'oldest')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.0.category', 'health');
    }

    public function test_index_respects_the_limit_parameter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->seedNotification($this->patient, ['message' => "n{$i}"], '2026-01-0' . ($i + 1) . ' 08:00:00');
        }

        $response = $this->mobileGetJson($this->patient, '/api/mobile/notifications?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            // limit truncates the page, it must not truncate the unread badge
            ->assertJsonPath('unread_count', 5)
            ->assertJsonPath('data.0.message', 'n4')
            ->assertJsonPath('data.1.message', 'n3');
    }

    public function test_index_includes_notifications_sent_to_the_linked_user_account(): void
    {
        $user = $this->linkedUserFor($this->patient, 'nadia.notify@opescare.test');

        $this->seedNotification($this->patient, ['message' => 'to the patient'], '2026-01-01 08:00:00');
        $this->seedNotification($user, ['message' => 'to the linked user'], '2026-02-01 08:00:00');

        $response = $this->mobileGetJson($this->patient, '/api/mobile/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('data.0.message', 'to the linked user')
            ->assertJsonPath('data.1.message', 'to the patient');
    }

    // ── mark read ────────────────────────────────────────────────────────

    public function test_mark_read_flips_a_single_notification_and_drops_the_unread_count(): void
    {
        $target = $this->seedNotification($this->patient, ['message' => 'target']);
        $untouched = $this->seedNotification($this->patient, ['message' => 'untouched']);

        $this->mobilePostJson($this->patient, "/api/mobile/notifications/{$target->id}/read")
            ->assertOk()
            ->assertJsonPath('message', __('api.notification_read'));

        $this->assertNotNull($target->fresh()->read_at);
        $this->assertNull($untouched->fresh()->read_at);

        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 1]);
    }

    public function test_mark_read_on_an_unknown_id_returns_404(): void
    {
        $this->mobilePostJson($this->patient, '/api/mobile/notifications/' . Str::uuid() . '/read')
            ->assertStatus(404);
    }

    public function test_mark_all_read_clears_every_notification_for_the_patient(): void
    {
        $user = $this->linkedUserFor($this->patient, 'nadia.markall@opescare.test');

        $this->seedNotification($this->patient);
        $this->seedNotification($this->patient);
        $this->seedNotification($user);
        $foreign = $this->seedNotification($this->otherPatient);

        $this->mobilePostJson($this->patient, '/api/mobile/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('message', __('api.all_notifications_read'));

        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 0]);

        $this->mobileGetJson($this->patient, '/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.read', true);

        // mark-all-read must never spill past the caller's own scope.
        $this->assertNull($foreign->fresh()->read_at);
    }

    // ── Cross-patient isolation (IDOR) ───────────────────────────────────

    public function test_another_patients_notification_is_never_listed_or_counted(): void
    {
        $this->seedNotification($this->otherPatient, ['message' => 'foreign patient row']);
        $this->seedNotification(
            $this->linkedUserFor($this->otherPatient, 'etienne.elsewhere@opescare.test'),
            ['message' => 'foreign user row'],
        );
        $this->seedNotification($this->patient, ['message' => 'mine']);

        $index = $this->mobileGetJson($this->patient, '/api/mobile/notifications');
        $index->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'mine')
            ->assertJsonPath('unread_count', 1);

        $index->assertJsonMissing(['message' => 'foreign patient row']);
        $index->assertJsonMissing(['message' => 'foreign user row']);

        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 1]);
    }

    public function test_patient_cannot_mark_another_patients_notification_as_read(): void
    {
        $foreign = $this->seedNotification($this->otherPatient, ['message' => 'foreign patient row']);
        $foreignUser = $this->seedNotification(
            $this->linkedUserFor($this->otherPatient, 'etienne.idor@opescare.test'),
            ['message' => 'foreign user row'],
        );

        $this->mobilePostJson($this->patient, "/api/mobile/notifications/{$foreign->id}/read")
            ->assertStatus(404);
        $this->mobilePostJson($this->patient, "/api/mobile/notifications/{$foreignUser->id}/read")
            ->assertStatus(404);

        $this->assertNull($foreign->fresh()->read_at);
        $this->assertNull($foreignUser->fresh()->read_at);
    }

    public function test_a_patient_without_a_linked_user_cannot_see_another_patients_user_notifications(): void
    {
        // The linked-user OR branch is only added when patient_user_id resolves.
        // With no linked user it must not degrade into an unscoped match.
        $foreignUser = $this->linkedUserFor($this->otherPatient, 'etienne.nolink@opescare.test');
        $this->seedNotification($foreignUser, ['message' => 'foreign user row']);

        $this->assertNull(User::where('patient_id', $this->patient->id)->value('id'));

        $this->mobileGetJson($this->patient, '/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('unread_count', 0);
    }

    // ── Regression: UUID morph keys ──────────────────────────────────────

    /**
     * Regression guard for the original defect: notifications.notifiable_id was
     * a BIGINT while every notifiable has a UUID primary key, so no valid row
     * could ever be written and every read 500'd with SQLSTATE[22P02].
     *
     * If the column type ever reverts, this test fails loudly.
     */
    public function test_notifications_table_stores_uuid_notifiable_ids(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $type = DB::selectOne(
                "SELECT data_type FROM information_schema.columns
                 WHERE table_name = 'notifications' AND column_name = 'notifiable_id'"
            )?->data_type;

            $this->assertNotNull($type, 'notifications.notifiable_id column is missing.');
            $this->assertNotContains(
                $type,
                ['bigint', 'integer', 'smallint', 'numeric'],
                "notifications.notifiable_id is `{$type}`. Notifiables (Patient, User) have UUID "
                . 'primary keys, so an integer column can never hold a valid morph key: every mobile '
                . 'notification read will 500 with SQLSTATE[22P02].'
            );
        }

        // Round-trip a UUID morph key through a real write and a real read.
        $notification = $this->seedNotification($this->patient, ['message' => 'uuid round trip']);

        $this->assertSame($this->patient->id, $notification->fresh()->notifiable_id);
        $this->assertSame($this->patient->getMorphClass(), $notification->fresh()->notifiable_type);
        $this->assertTrue(
            (bool) preg_match('/^[0-9a-f-]{36}$/i', (string) $notification->fresh()->notifiable_id),
            'notifiable_id did not survive the round trip as a UUID.'
        );

        $this->mobileGetJson($this->patient, '/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notification->id);
    }

    /**
     * Regression guard for the second defect found while writing these tests:
     * AppServiceProvider registers Relation::morphMap(['patient' => Patient::class]),
     * so Laravel writes `notifiable_type = 'patient'`. scopedQuery() compared
     * against Patient::class ('App\Models\Patient'), which matched nothing —
     * every patient notification was invisible to the mobile API while both
     * endpoints still returned a cheerful 200 with an empty list.
     */
    public function test_scoped_query_matches_the_patient_morph_alias_not_the_fqcn(): void
    {
        $morphClass = $this->patient->getMorphClass();

        $this->assertNotSame(
            Patient::class,
            $morphClass,
            'Patient is expected to be aliased in the morph map; update this guard if that changes.'
        );

        $this->seedNotification($this->patient, ['message' => 'written under the morph alias']);

        $this->assertSame(
            $morphClass,
            DB::table('notifications')->value('notifiable_type'),
            'Laravel should persist the morph alias for Patient notifiables.'
        );

        // The endpoints must find it despite the alias/FQCN mismatch.
        $this->mobileGetJson($this->patient, '/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'written under the morph alias');

        $this->mobileGetJson($this->patient, '/api/mobile/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 1]);
    }

    /**
     * The same guard, exercised through Laravel's own notification pipeline
     * (Notifiable::notify -> DatabaseChannel) rather than a hand-built row —
     * this is the write path that threw before the column was widened.
     */
    public function test_a_notification_dispatched_to_a_uuid_patient_is_readable_over_the_api(): void
    {
        $this->patient->notify(new HealthIdExpiryNotification(
            healthId: $this->patient->health_id,
            name: 'Nadia Notify',
            expiresAt: '2026-12-31',
            daysLeft: 25,
        ));

        $this->assertDatabaseHas('notifications', [
            // AppServiceProvider registers a morph map, so Laravel writes the
            // alias ('patient'), not the FQCN — the controller must match this.
            'notifiable_type' => $this->patient->getMorphClass(),
            'notifiable_id'   => $this->patient->id,
        ]);

        $this->mobileGetJson($this->patient, '/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.type', 'health_id_expiry_warning')
            ->assertJsonPath('data.0.category', 'health')
            ->assertJsonPath('data.0.severity', 'high')
            ->assertJsonPath('data.0.read', false);
    }
}
