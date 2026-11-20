<?php

namespace App\Http\Middleware;

use App\Utils\RouteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "access:Admin|Doctor".
 *
 * The role list is the route's default access, granted once by
 * `php artisan permissions:sync`. The actual check uses the permissions the
 * admin set on the Roles screen (App\Utils\RouteAccess).
 */
class CheckAccess
{
    public function handle(Request $request, Closure $next, string ...$defaultRoles): Response
    {
        $user = $request->user();

        if (! $user || ! RouteAccess::allows($user, $request->route())) {
            abort(403, __('messages.access.no_permission'));
        }

        return $next($request);
    }
}
