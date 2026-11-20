<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Department;
use App\Repositories\RoleRepository;
use Exception;
use Flash;

class RoleController extends AppBaseController
{
    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(RoleRepository $roleRepo)
    {
        $this->roleRepository = $roleRepo;
    }

    public function index()
    {
        return view('roles.index');
    }

    public function create()
    {
        $matrix = $this->roleRepository->permissionMatrix();
        $rolePermissions = [];

        return view('roles.create', compact('matrix', 'rolePermissions'));
    }

    public function store(CreateRoleRequest $request)
    {
        $this->roleRepository->store($request->only('name', 'permissions'));

        Flash::success(__('messages.access.role').' '.__('messages.common.saved_successfully'));

        return redirect(route('roles.index'));
    }

    public function edit(Department $role)
    {
        $matrix = $this->roleRepository->permissionMatrix();
        $rolePermissions = $role->permissions()->pluck('name')->all();

        return view('roles.edit', compact('role', 'matrix', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Department $role)
    {
        $this->roleRepository->updateRole($role, $request->only('name', 'permissions'));

        Flash::success(__('messages.access.role').' '.__('messages.common.updated_successfully'));

        return redirect(route('roles.index'));
    }

    public function destroy(Department $role)
    {
        try {
            $this->roleRepository->delete($role->id);

            return $this->sendSuccess(__('messages.access.role').' '.__('messages.common.deleted_successfully'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }
    }
}
