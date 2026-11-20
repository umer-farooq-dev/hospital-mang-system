<?php

namespace App\Livewire;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RoleTable extends LivewireTableComponent
{
    protected $model = Department::class;

    public $showButtonOnHeader = true;

    public $paginationIsEnabled = true;

    public $buttonComponent = 'roles.add-button';

    protected $listeners = ['refresh' => '$refresh', 'resetPage'];

    public function placeholder()
    {
        return view('livewire.skeleton_files.common_skeleton_af');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('id', 'asc')
            ->setQueryStringStatus(false);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.access.role'), 'name')
                ->view('roles.templates.columns.name')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.access.users'))
                ->label(fn ($row) => $row->users_count),
            Column::make(__('messages.access.permissions'))
                ->label(fn ($row) => $row->permissions_count),
            Column::make(__('messages.common.action'), 'id')
                ->view('roles.action'),
        ];
    }

    public function builder(): Builder
    {
        $users = DB::table(config('permission.table_names.model_has_roles'))
            ->selectRaw('count(*)')
            ->whereColumn('role_id', 'departments.id');

        return Department::query()
            ->select('departments.*')
            ->selectSub($users, 'users_count')
            ->withCount('permissions');
    }
}
