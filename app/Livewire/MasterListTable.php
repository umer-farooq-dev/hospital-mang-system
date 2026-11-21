<?php

namespace App\Livewire;

use App\Models\MasterListItem;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MasterListTable extends LivewireTableComponent
{
    protected $model = MasterListItem::class;

    public $showButtonOnHeader = true;

    public $paginationIsEnabled = true;

    public $buttonComponent = 'master_lists.add-button';

    public string $type = '';

    protected $listeners = ['refresh' => '$refresh', 'resetPage'];

    public function mount(string $type): void
    {
        $this->type = $type;
    }

    public function placeholder()
    {
        return view('livewire.skeleton_files.common_skeleton_af');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('sort_order', 'asc')
            ->setQueryStringStatus(false);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.master_list.name'), 'name')
                ->view('master_lists.templates.columns.name')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.master_list.sort_order'), 'sort_order')
                ->sortable(),
            Column::make(__('messages.common.status'), 'is_active')
                ->view('master_lists.templates.columns.status')
                ->sortable(),
            Column::make(__('messages.common.action'), 'id')
                ->view('master_lists.action'),
        ];
    }

    public function builder(): Builder
    {
        return MasterListItem::query()->select('master_list_items.*')->where('type', $this->type);
    }
}
