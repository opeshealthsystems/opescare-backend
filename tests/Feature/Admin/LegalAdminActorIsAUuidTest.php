<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\MedicalId\LegalAdminController;
use App\Models\AccountClosureRequest;
use App\Models\LegalDocument;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Who the legal admin portal records as the actor.
 *
 * `account_closure_requests.reviewed_by`, `legal_documents.created_by` and
 * `legal_document_versions.published_by` are all `uuid` columns with a foreign
 * key onto `users.id`. The controller used to fill them from
 * `session('auth_email') ?: 'demo-admin'` — an email, and in practice always
 * the literal `demo-admin`, because nothing in this codebase ever writes
 * `auth_email` into the session. Postgres answers that with
 * `SQLSTATE[22P02] invalid input syntax for type uuid`, so approving a closure,
 * creating a legal document and publishing a version each threw every single
 * time they were called.
 *
 * These tests are about the TYPE and the FOREIGN KEY, not the workflow: a
 * string actor must never come back, and the id written must be an actual user.
 *
 * Note for whoever wires the routes: none of the LegalAdminController admin
 * methods is routed today (`route:list` shows only `public.legal` and
 * `public.legal.show`), so the controller is invoked directly here.
 */
class LegalAdminActorIsAUuidTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'legal_admin'],
            ['description' => 'Legal Document Admin', 'dashboard_profile_key' => null]
        );

        $user = User::create([
            'name'     => 'Legal Admin',
            'email'    => 'legal.admin@opescare.test',
            'password' => 'password',
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    private function request(array $payload): Request
    {
        $request = Request::create('/legal-admin', 'POST', $payload);
        $request->setLaravelSession(app('session.store'));

        return $request;
    }

    public function test_reviewing_a_closure_records_the_reviewing_user_id(): void
    {
        $admin   = $this->admin();
        $patient = Patient::create([
            'health_id'     => 'OC-TST-8500-0001-01',
            'first_name'    => 'Clotilde',
            'last_name'     => 'Closure',
            'sex'           => 'female',
            'date_of_birth' => '1988-07-07',
            'is_demo'       => false,
        ]);

        $closure = AccountClosureRequest::create([
            'patient_id' => $patient->id,
            'status'     => 'awaiting_review',
            'reason'     => 'Moving abroad.',
        ]);

        Auth::login($admin);

        app(LegalAdminController::class)->reviewClosure(
            $this->request(['action' => 'approve', 'review_note' => 'Verified by phone.']),
            $closure
        );

        $closure->refresh();

        $this->assertSame('approved', $closure->status);
        $this->assertSame($admin->id, $closure->reviewed_by);
        $this->assertTrue(Str::isUuid($closure->reviewed_by), 'reviewed_by is not a UUID.');
        $this->assertNotNull($closure->reviewed_at);

        // The FK must actually resolve — a well-formed UUID that matches no user
        // would satisfy the column type and still be a fabricated actor.
        $this->assertSame(
            $admin->email,
            User::whereKey($closure->reviewed_by)->value('email')
        );
    }

    public function test_rejecting_a_closure_records_the_reviewing_user_id(): void
    {
        $admin   = $this->admin();
        $patient = Patient::create([
            'health_id'     => 'OC-TST-8500-0002-01',
            'first_name'    => 'Rejane',
            'last_name'     => 'Refusal',
            'sex'           => 'female',
            'date_of_birth' => '1979-02-02',
            'is_demo'       => false,
        ]);

        $closure = AccountClosureRequest::create([
            'patient_id' => $patient->id,
            'status'     => 'awaiting_review',
        ]);

        Auth::login($admin);

        app(LegalAdminController::class)->reviewClosure(
            $this->request(['action' => 'reject']),
            $closure
        );

        $closure->refresh();

        $this->assertSame('rejected', $closure->status);
        $this->assertSame($admin->id, $closure->reviewed_by);
    }

    public function test_creating_a_legal_document_records_the_creating_user_id(): void
    {
        $admin = $this->admin();
        Auth::login($admin);

        app(LegalAdminController::class)->store($this->request([
            'slug'          => 'test-terms',
            'title'         => 'Test Terms',
            'document_type' => 'terms',
        ]));

        $document = LegalDocument::where('slug', 'test-terms')->sole();

        $this->assertSame($admin->id, $document->created_by);
        $this->assertTrue(Str::isUuid($document->created_by));
    }

    public function test_publishing_a_version_records_the_publishing_user_id(): void
    {
        $admin = $this->admin();
        Auth::login($admin);

        $document = LegalDocument::create([
            'slug'                => 'test-privacy',
            'title'               => 'Test Privacy Policy',
            'document_type'       => 'privacy',
            'language'            => 'en',
            'is_active'           => true,
            'requires_acceptance' => true,
        ]);

        app(LegalAdminController::class)->publishVersion(
            $this->request([
                'version'      => '1.0',
                'content_html' => str_repeat('This is the published policy text. ', 3),
            ]),
            $document
        );

        $version = $document->versions()->sole();

        $this->assertSame('1.0', $version->version);
        $this->assertSame($admin->id, $version->published_by);
        $this->assertTrue(Str::isUuid($version->published_by));
    }

    /**
     * Nobody signed in means nobody to attribute it to — and the columns are
     * nullable for exactly that. Null, not a placeholder string: a fabricated
     * actor in an audit column is worse than an absent one, and a string is a
     * 22P02 besides.
     */
    public function test_an_unattributed_review_writes_null_rather_than_a_placeholder(): void
    {
        $patient = Patient::create([
            'health_id'     => 'OC-TST-8500-0003-01',
            'first_name'    => 'Anonyme',
            'last_name'     => 'Unattributed',
            'sex'           => 'male',
            'date_of_birth' => '1970-01-01',
            'is_demo'       => false,
        ]);

        $closure = AccountClosureRequest::create([
            'patient_id' => $patient->id,
            'status'     => 'awaiting_review',
        ]);

        $this->assertGuest();

        app(LegalAdminController::class)->reviewClosure(
            $this->request(['action' => 'approve']),
            $closure
        );

        $closure->refresh();

        $this->assertSame('approved', $closure->status);
        $this->assertNull($closure->reviewed_by);
    }
}
