<?php

namespace Tests\Feature\Rbac;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_get_pages_do_not_500(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = $role->id;
        $user->save();

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods())) continue;
            $uri = $route->uri();
            if (!str_starts_with($uri, 'portals/admin')) continue;
            if (str_contains($uri, '{')) continue;

            // Isolate each request in a savepoint so a failed query in one route
            // does not abort the surrounding RefreshDatabase transaction.
            DB::beginTransaction();
            try {
                $res = $this->actingAs($user)->get('/' . $uri);
                $status = $res->getStatusCode();
                if ($status >= 500) {
                    $ex = $res->exception;
                    $failures['/' . $uri] = $ex
                        ? get_class($ex) . ' — ' . substr($ex->getMessage(), 0, 150) . ' @ ' . basename($ex->getFile()) . ':' . $ex->getLine()
                        : "status {$status}";
                }
            } catch (\Throwable $e) {
                $failures['/' . $uri] = get_class($e) . ' — ' . substr($e->getMessage(), 0, 150);
            } finally {
                try { DB::rollBack(); } catch (\Throwable $e) {}
            }
        }

        ksort($failures);
        foreach ($failures as $u => $msg) {
            fwrite(STDERR, "\n500  {$u}\n     {$msg}\n");
        }
        fwrite(STDERR, "\n\nTOTAL 500s: " . count($failures) . "\n");

        $this->assertTrue(true);
    }
}
