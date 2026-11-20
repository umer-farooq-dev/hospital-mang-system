<?php

namespace Tests\Feature;

use App\Utils\RouteAccess;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route-to-permission rules (no database: roles and permissions are passed in).
 */
class RouteAccessTest extends TestCase
{
    private function route(string $name): RoutingRoute
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "route $name exists");

        return $route;
    }

    private function routeFor(string $action): RoutingRoute
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->getActionName() === $action) {
                return $route;
            }
        }
        $this->fail("no route for $action");
    }

    public function test_routes_map_to_module_and_action(): void
    {
        $this->assertSame(['insurances', 'list'], RouteAccess::permission($this->route('insurances.index')));
        $this->assertSame(['insurances', 'create'], RouteAccess::permission($this->route('insurances.store')));
        $this->assertSame(['insurances', 'edit'], RouteAccess::permission($this->route('insurances.edit')));
        $this->assertSame(['insurances', 'delete'], RouteAccess::permission($this->route('insurances.destroy')));
        $this->assertSame(['insurances', 'export'], RouteAccess::permission($this->route('insurances.excel')));
        $this->assertSame(['roles', 'list'], RouteAccess::permission($this->route('roles.index')));
        $this->assertSame(['patients', 'support'], RouteAccess::permission($this->routeFor('App\Http\Controllers\PatientController@getBirthDate')));
        $this->assertSame(['complaint_responses', 'edit'], RouteAccess::permission($this->routeFor('App\Http\Controllers\ComplaintController@responseEdit')));
        $this->assertSame(['dashboard', 'view'], RouteAccess::permission($this->routeFor('App\Http\Controllers\HomeController@dashboardChart')));
    }

    public function test_permission_not_role_name_decides(): void
    {
        $index = $this->route('insurances.index'); // default access: Admin|Receptionist

        $this->assertTrue(RouteAccess::check($index, ['Receptionist'], ['insurances.list']));
        // Admin removed the permission from Receptionist: no access although the role is listed on the route.
        $this->assertFalse(RouteAccess::check($index, ['Receptionist'], []));
        // A custom role with the permission gets in.
        $this->assertTrue(RouteAccess::check($index, ['Cashier'], ['insurances.list']));
        $this->assertFalse(RouteAccess::check($index, ['Cashier'], ['insurances.view']));
    }

    public function test_support_routes_follow_default_roles_or_write_permissions(): void
    {
        $lookup = $this->routeFor('App\Http\Controllers\IpdChargeController@getChargeList');

        $this->assertTrue(RouteAccess::check($lookup, ['Nurse'], []), 'default role keeps form lookups');
        $this->assertFalse(RouteAccess::check($lookup, ['Patient'], ['ipd_charges.list']), 'viewing is not enough');
        $this->assertTrue(RouteAccess::check($lookup, ['Cashier'], ['ipd_charges.create']));
    }

    public function test_fixed_and_staff_wide_routes(): void
    {
        $patientHome = $this->routeFor('App\Http\Controllers\HomeController@patientDashboard');
        $this->assertTrue(RouteAccess::check($patientHome, ['Patient'], []));
        $this->assertFalse(RouteAccess::check($patientHome, ['Cashier'], ['dashboard.view']));

        // Open to every built-in role: needs no permission, also for custom roles.
        $noticeBoard = $this->route('employee.noticeboard');
        $this->assertTrue(RouteAccess::check($noticeBoard, ['Cashier'], []));
    }

    public function test_add_on_upload_is_not_open_to_every_user(): void
    {
        $upload = $this->route('addOn.extractZip');

        $this->assertFalse(RouteAccess::check($upload, ['Patient'], []));
        $this->assertFalse(RouteAccess::check($upload, ['Doctor'], []));
        $this->assertTrue(RouteAccess::check($upload, ['Admin'], ['add_ons.edit']));
    }

    public function test_every_access_route_is_in_the_catalogue(): void
    {
        $unmapped = [];
        foreach (Route::getRoutes() as $route) {
            if (RouteAccess::defaultRoles($route) !== null && RouteAccess::permission($route) === null) {
                $unmapped[] = $route->getActionName();
            }
        }

        $this->assertSame([], $unmapped, 'add these controllers to config/access.php');
    }
}
