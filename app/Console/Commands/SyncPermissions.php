<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Utils\RouteAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Create missing route permissions and grant each one to the default roles written on its routes';

    public function handle(): int
    {
        $defaults = [];
        $unmapped = [];

        foreach (Route::getRoutes() as $route) {
            $roles = RouteAccess::defaultRoles($route);
            if ($roles === null || RouteAccess::isOpenToStaff($roles)) {
                continue;
            }

            $permission = RouteAccess::permission($route);
            if ($permission === null) {
                $unmapped[$route->getActionName()] = true;

                continue;
            }

            [$module, $action] = $permission;
            if (in_array($action, ['fixed', 'support'], true)) {
                continue;
            }

            $name = $module.'.'.$action;
            $defaults[$name] = array_unique(array_merge($defaults[$name] ?? [], $roles));
        }

        // Only permissions that do not exist yet get their defaults: the admin's later
        // choices on the Roles screen are never overwritten.
        $missing = array_diff_key($defaults, Permission::pluck('name')->flip()->all());
        $created = count($missing);

        // Bulk inserts in one transaction: Permission::create() reloads the whole
        // permission cache on every row, which takes minutes for hundreds of rows.
        DB::transaction(function () use ($missing) {
            $now = now();
            $rows = array_map(fn ($name) => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now], array_keys($missing));
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table(config('permission.table_names.permissions'))->insert($chunk);
            }

            $permissionIds = Permission::whereIn('name', array_keys($missing))->pluck('id', 'name');
            $roleIds = Department::pluck('id', 'name');
            $grants = [];
            foreach ($missing as $name => $roles) {
                foreach ($roles as $role) {
                    if (isset($roleIds[$role])) {
                        $grants[] = ['permission_id' => $permissionIds[$name], 'role_id' => $roleIds[$role]];
                    }
                }
            }
            foreach (array_chunk($grants, 500) as $chunk) {
                DB::table(config('permission.table_names.role_has_permissions'))->insert($chunk);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Permissions created: {$created} (already present: ".(count($defaults) - $created).').');
        foreach (array_keys($unmapped) as $action) {
            $this->warn("Not in config/access.php, uses its route role list: {$action}");
        }

        return self::SUCCESS;
    }
}
