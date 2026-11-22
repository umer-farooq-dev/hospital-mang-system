<?php

namespace App\Utils;

use App\Models\Department;
use App\Models\User;
use Illuminate\Routing\Route;

/**
 * Decides whether a user may open a route, using the permissions the admin sets
 * on the Roles screen. See config/access.php for how routes map to permissions.
 */
class RouteAccess
{
    const PATIENT = 'Patient';

    // Payment gateway redirects and callbacks are "support" routes.
    const PAYMENT_CALLBACK = '/stripe|razor|paystack|phonepe|flutterwave|paypal|onboard|paymentsuccess|paymentfail/i';

    /** @var array<int, array{roles: string[], permissions: string[]}> */
    private static array $users = [];

    public static function allows(User $user, Route $route): bool
    {
        $access = self::$users[$user->id] ??= [
            'roles' => $user->getRoleNames()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ];

        return self::check($route, $access['roles'], $access['permissions']);
    }

    /**
     * @param  string[]  $roles  role names of the user
     * @param  string[]  $permissions  permission names the user holds through those roles
     */
    public static function check(Route $route, array $roles, array $permissions): bool
    {
        $defaults = self::defaultRoles($route);
        if ($defaults === null) {
            return true; // not under the access middleware
        }

        if (self::isOpenToStaff($defaults) && (! in_array(self::PATIENT, $roles, true) || in_array(self::PATIENT, $defaults, true))) {
            return true;
        }

        $inDefaults = array_intersect($roles, $defaults) !== [];
        $permission = self::permission($route);
        if ($permission === null) {
            return $inDefaults; // controller not in config/access.php: keep the route's own role list
        }

        [$module, $action] = $permission;

        return match ($action) {
            'fixed' => $inDefaults,
            // Form lookups and payment callbacks serve whoever may change the module.
            'support' => $inDefaults || array_intersect(["$module.create", "$module.edit", "$module.delete"], $permissions) !== [],
            default => in_array($module.'.'.$action, $permissions, true),
        };
    }

    /**
     * Role names written on the route (access:Admin|Doctor), or null when the route
     * is not under the access middleware.
     */
    public static function defaultRoles(Route $route): ?array
    {
        // middleware() reads the route definition only; gatherMiddleware() would build the
        // controller (and run its constructor) for every menu item checked.
        foreach ($route->middleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'access:')) {
                return array_values(array_filter(explode('|', substr($middleware, 7))));
            }
        }

        return null;
    }

    /** True when every built-in staff role is in the list: such a route needs no permission. */
    public static function isOpenToStaff(array $defaults): bool
    {
        return array_diff(self::builtInRoles(), [self::PATIENT], $defaults) === [];
    }

    /** [module, action] for a controller route, or null when its controller is not in config/access.php. */
    public static function permission(Route $route): ?array
    {
        $uses = $route->getActionName();
        if (! str_contains($uses, '@')) {
            return null;
        }

        if ($override = config('access.methods')[$uses] ?? null) {
            return $override;
        }

        [$controller, $method] = explode('@', $uses);
        $module = config('access.controllers')[$controller] ?? null;

        return $module === null ? null : [$module, self::action($method, $route->methods())];
    }

    public static function action(string $method, array $verbs): string
    {
        $m = strtolower($method);

        return match (true) {
            preg_match(self::PAYMENT_CALLBACK, $m) === 1, str_starts_with($m, 'get') => 'support',
            $m === 'index' => 'list',
            str_contains($m, 'export') => 'export',
            str_starts_with($m, 'approve'), str_starts_with($m, 'validate'), str_starts_with($m, 'release') => 'approve',
            str_starts_with($m, 'download') => 'download',
            str_starts_with($m, 'create'), str_starts_with($m, 'store'),
            str_ends_with($m, 'create'), str_ends_with($m, 'store') => 'create',
            $m === 'destroy', $m === 'delete' => 'delete',
            str_starts_with($m, 'edit'), str_starts_with($m, 'update'), str_contains($m, 'activedeactive') => 'edit',
            in_array('GET', $verbs, true) => 'view',
            default => 'edit',
        };
    }

    /** @return string[] */
    public static function builtInRoles(): array
    {
        return array_values(array_diff(Department::ROLE, ['All']));
    }
}
