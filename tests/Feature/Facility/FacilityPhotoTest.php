<?php

namespace Tests\Feature\Facility;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\CareMap\FacilityPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The facility photo / logo on the two settings pages
 * (portals.facility.settings.photo, portals.pharmacy.settings.photo).
 *
 * The file is re-encoded by GD and kept on the private `local` disk under
 * `care_facilities.logo_path`; the page shows it through a signed link
 * (`facility.photo`). A replacement deletes the previous file only after the
 * new one is written; every upload leaves one facility_update_audits row with
 * requires_review = false. Anyone without `service_catalogue` is refused with
 * 403 and an unlinked tenant with 404, exactly like every other write here.
 */
class FacilityPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Storage::fake('local');
    }

    /** @return array{0: Facility, 1: User} */
    private function tenant(string $roleName, string $type = 'hospital', string $name = 'Hôpital Laquintinie'): array
    {
        $facility = Facility::factory()->create(['type' => $type, 'name' => $name]);
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $user];
    }

    private function listing(Facility $facility, string $type = 'hospital'): CareFacility
    {
        return CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $facility->name, 'facility_type' => $type,
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Boulevard de la Liberté', 'phone_primary' => 'N/A',
        ]);
    }

    private function as(User $user, Facility $facility)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /** A real PNG of the given size, so GD (not just finfo) accepts it. */
    private function png(int $w, int $h, string $name = 'logo.png'): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 15, 76, 129));
        ob_start();
        imagepng($img);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        $tmp = tempnam(sys_get_temp_dir(), 'ph');
        file_put_contents($tmp, $bytes);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    public function test_a_facility_admin_uploads_a_photo_which_is_stored_privately_shown_signed_and_audited(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility);

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee(__('facility_settings.section_photo'))
            ->assertSee(__('facility_settings.photo_none'))
            ->assertSee(route('portals.facility.settings.photo'));

        $this->as($admin, $facility)
            ->post(route('portals.facility.settings.photo'), ['photo' => $this->png(640, 480)])
            ->assertRedirect(route('portals.facility.settings.index'))
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertNotNull($listing->logo_path);
        $this->assertStringStartsWith(FacilityPhotoService::DIRECTORY . '/' . $listing->id . '/', $listing->logo_path);
        Storage::disk('local')->assertExists($listing->logo_path);

        // What is stored is a fresh JPEG, never the upload itself.
        $info = getimagesizefromstring((string) Storage::disk('local')->get($listing->logo_path));
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        $this->assertSame([640, 480], [$info[0], $info[1]]);

        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'actor_id' => $admin->id, 'actor_type' => 'facility',
            'field_changed' => 'logo_path', 'new_value' => $listing->logo_path, 'requires_review' => false,
        ]);

        // The page now shows it through a signed link, and the link serves the bytes.
        $url = FacilityPhotoService::urlFor($listing);
        $this->assertNotNull($url);
        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()->assertSee('/facility/photo/' . $listing->id, false);

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('facility.photo', ['listing' => $listing->id]))->assertForbidden();
    }

    public function test_a_replacement_removes_the_previous_file_only_after_the_new_one_exists(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility);

        $this->as($admin, $facility)->post(route('portals.facility.settings.photo'), ['photo' => $this->png(300, 300)]);
        $first = $listing->refresh()->logo_path;

        $this->as($admin, $facility)->post(route('portals.facility.settings.photo'), ['photo' => $this->png(2400, 1200)])
            ->assertSessionHasNoErrors();
        $second = $listing->refresh()->logo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);

        // Fitted within 1024 on the longest edge, aspect kept.
        $info = getimagesizefromstring((string) Storage::disk('local')->get($second));
        $this->assertSame([1024, 512], [$info[0], $info[1]]);
        $this->assertSame(2, \DB::table('facility_update_audits')->where('facility_id', $listing->id)->where('field_changed', 'logo_path')->count());
    }

    public function test_a_wrong_type_is_rejected(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility);

        $tmp = tempnam(sys_get_temp_dir(), 'ph');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $svg = new UploadedFile($tmp, 'logo.svg', 'image/svg+xml', null, true);

        $this->as($admin, $facility)->from(route('portals.facility.settings.index'))
            ->post(route('portals.facility.settings.photo'), ['photo' => $svg])
            ->assertSessionHasErrors('photo');

        // A PNG header on a PDF-shaped body: finfo says image, GD cannot decode it.
        $tmp2 = tempnam(sys_get_temp_dir(), 'ph');
        file_put_contents($tmp2, "\x89PNG\r\n\x1a\n" . str_repeat('x', 4000));
        $fake = new UploadedFile($tmp2, 'logo.png', 'image/png', null, true);
        $this->as($admin, $facility)->from(route('portals.facility.settings.index'))
            ->post(route('portals.facility.settings.photo'), ['photo' => $fake])
            ->assertSessionHasErrors('photo');

        $this->assertNull($listing->refresh()->logo_path);
        $this->assertSame(0, \DB::table('facility_update_audits')->where('facility_id', $listing->id)->count());
    }

    public function test_too_large_and_too_small_pictures_are_rejected(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility);

        $big = UploadedFile::fake()->image('big.jpg', 400, 400)->size(FacilityPhotoService::MAX_KB + 1);
        $this->as($admin, $facility)->from(route('portals.facility.settings.index'))
            ->post(route('portals.facility.settings.photo'), ['photo' => $big])
            ->assertSessionHasErrors('photo');

        $this->as($admin, $facility)->from(route('portals.facility.settings.index'))
            ->post(route('portals.facility.settings.photo'), ['photo' => $this->png(199, 400)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($listing->refresh()->logo_path);
    }

    public function test_a_pharmacist_is_refused_with_403_and_a_pharmacy_manager_uploads_on_the_pharmacy_page(): void
    {
        [$facility, $pharmacist] = $this->tenant('pharmacist', 'pharmacy', 'Pharmacie du Marché');
        $listing = $this->listing($facility, 'pharmacy');

        $this->as($pharmacist, $facility)
            ->post(route('portals.pharmacy.settings.photo'), ['photo' => $this->png(300, 300)])
            ->assertForbidden();
        $this->as($pharmacist, $facility)
            ->post(route('portals.facility.settings.photo'), ['photo' => $this->png(300, 300)])
            ->assertForbidden();
        $this->assertNull($listing->refresh()->logo_path);

        [$facility2, $manager] = $this->tenant('pharmacy_manager', 'pharmacy', 'Pharmacie Bonanjo');
        $listing2 = $this->listing($facility2, 'pharmacy');

        $this->as($manager, $facility2)
            ->post(route('portals.pharmacy.settings.photo'), ['photo' => $this->png(300, 300)])
            ->assertRedirect(route('portals.pharmacy.settings.index'))
            ->assertSessionHasNoErrors();
        $this->assertNotNull($listing2->refresh()->logo_path);
        $this->as($manager, $facility2)->get(route('portals.pharmacy.settings.index'))
            ->assertOk()->assertSee(__('pharmacy_settings.section_photo'))->assertSee('/facility/photo/' . $listing2->id, false);
    }

    public function test_an_unlinked_tenant_is_refused_with_404(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');

        $this->as($admin, $facility)
            ->post(route('portals.facility.settings.photo'), ['photo' => $this->png(300, 300)])
            ->assertNotFound();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
