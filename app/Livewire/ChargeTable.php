<?php

namespace App\Livewire;

use App\Models\Charge;
use App\Models\MasterListItem;
use App\Models\ChargeCategory;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Livewire\Attributes\Lazy;

#[Lazy]
class ChargeTable extends LivewireTableComponent
{
    public $showButtonOnHeader = true;

    public $showFilterOnHeader = true;

    public $paginationIsEnabled = true;

    public $buttonComponent = 'charges.templates.button.add-button';

    public $FilterComponent = ['charges.templates.button.filter-button', []];

    public function mount(): void
    {
        $this->FilterComponent[1] = MasterListItem::filterOptions('charge_type');
    }

    public $statusFilter;

    protected $model = Charge::class;

    protected $listeners = ['refresh' => '$refresh', 'changeFilter', 'resetPage'];

    // public function resetPage($pageName = 'page')
    // {
    //     $rowsPropertyData = $this->getRows()->toArray();
    //     $prevPageNum = $rowsPropertyData['current_page'] - 1;
    //     $prevPageNum = $prevPageNum > 0 ? $prevPageNum : 1;
    //     $pageNum = count($rowsPropertyData['data']) > 0 ? $rowsPropertyData['current_page'] : $prevPageNum;

    //     $this->setPage($pageNum, $pageName);
    // }

    public function placeholder()
    {
        return view('livewire.skeleton_files.common_skeleton_af');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('created_at', 'desc')
            ->setQueryStringStatus(false);
        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->isField('code') || $column->isField('name') || $column->isField('standard_charge')) {
                return [
                    'class' => 'p-5',
                ];
            }

            return [];
        });
        $this->setThAttributes(function (Column $column) {
            if ($column->isField('standard_charge')) {
                return [
                    'class' => 'd-flex '.(getCurrentLoginUserLanguageName() == 'ar' ? 'justify-content-start' : 'justify-content-end'),
                    'style' => (getCurrentLoginUserLanguageName() == 'ar' ? '' : 'padding-right: 7rem !important'),
                ];
            }

            return [];
        });
    }

    public function changeFilter($statusFilter)
    {
        $this->resetPage($this->getComputedPageName());
        $this->statusFilter = $statusFilter;
        $this->setBuilder($this->builder());
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.charge.code'), 'code')
                ->sortable()->searchable(),
            Column::make(__('messages.charge.charge_category'), 'chargeCategory.name')
                ->view('charges.templates.column.charge_category')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.charge_category.charge_type'), 'charge_type')
                ->view('charges.templates.column.charge_type')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.charge.standard_charge'), 'standard_charge')
                ->view('charges.templates.column.standard_charge')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.common.action'), 'id')
                ->view('charges.action'),
            Column::make('created_at')->sortable()->hideIf(1),
        ];
    }

    /**
     * @return Builder|Charge|\Illuminate\Database\Query\Builder
     */
    public function builder(): Builder
    {
        $query = Charge::whereHas('chargeCategory')->with('chargeCategory')->select('charges.*');

        // 0 = all charge types; any other value is a charge type code from Master Lists.
        $query->when(! empty($this->statusFilter), function (Builder $q) {
            $q->where('charges.charge_type', $this->statusFilter);
        });

        return $query;
    }
}
