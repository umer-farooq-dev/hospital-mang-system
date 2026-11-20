<?php

namespace App\Repositories;

use App\Listeners\RecordAuditLog;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Roles are rows of the departments table (App\Models\Department is the
 * spatie role model). Permissions come from config/access.php.
 */
class RoleRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return Department::class;
    }

    /**
     * Permissions to show on the Roles screen, ordered as in config/access.php.
     *
     * @return array<string, array<string, array<string, string>>> group => module => action => permission name
     */
    public function permissionMatrix(): array
    {
        $existing = Permission::where('guard_name', 'web')->pluck('name')->flip();
        $modules = config('access.modules');
        $matrix = [];

        foreach (config('access.groups') as $group) {
            foreach (array_keys($modules, $group, true) as $module) {
                foreach (config('access.actions') as $action) {
                    if (isset($existing[$module.'.'.$action])) {
                        $matrix[$group][$module][$action] = $module.'.'.$action;
                    }
                }
            }
        }

        return $matrix;
    }

    public function store(array $input): Department
    {
        return DB::transaction(function () use ($input) {
            $role = Department::create(['name' => $input['name'], 'is_active' => Department::ACTIVE]);
            $this->syncPermissions($role, $input['permissions'] ?? []);

            return $role;
        });
    }

    public function updateRole(Department $role, array $input): Department
    {
        return DB::transaction(function () use ($role, $input) {
            if (! $role->isBuiltIn()) {
                $role->update(['name' => $input['name']]);
            }
            if (! $role->isAdmin()) {
                $this->syncPermissions($role, $input['permissions'] ?? []);
            }

            return $role;
        });
    }

    public function delete($id)
    {
        $role = Department::findOrFail($id);

        if ($role->isBuiltIn()) {
            throw new UnprocessableEntityHttpException(__('messages.access.built_in_cannot_delete'));
        }
        if (DB::table(config('permission.table_names.model_has_roles'))->where('role_id', $role->id)->exists()) {
            throw new UnprocessableEntityHttpException(__('messages.access.role_has_users'));
        }

        DB::transaction(function () use ($role) {
            $this->syncPermissions($role, []);
            $role->delete();
        });

        return true;
    }

    private function syncPermissions(Department $role, array $names): void
    {
        // Only permissions shown on the Roles screen can be granted.
        $allowed = [];
        foreach ($this->permissionMatrix() as $modules) {
            foreach ($modules as $actions) {
                array_push($allowed, ...array_values($actions));
            }
        }
        $names = array_values(array_intersect($names, $allowed));

        $old = $role->permissions()->pluck('name')->all();
        $role->syncPermissions($names);
        $new = $role->permissions()->pluck('name')->all();

        $removed = array_values(array_diff($old, $new));
        $added = array_values(array_diff($new, $old));
        if ($removed || $added) {
            RecordAuditLog::write($role, 'updated', ['permissions_removed' => $removed], ['permissions_added' => $added]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
