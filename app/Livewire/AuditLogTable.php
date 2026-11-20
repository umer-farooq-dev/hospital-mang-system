<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AuditLogTable extends LivewireTableComponent
{
    protected $model = AuditLog::class;

    public $paginationIsEnabled = true;

    protected $listeners = ['refresh' => '$refresh', 'resetPage'];

    public function placeholder()
    {
        return view('livewire.skeleton_files.common_skeleton_af');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('created_at', 'desc')
            ->setQueryStringStatus(false);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.audit_log.date_time'), 'created_at')
                ->view('audit_logs.templates.columns.date')
                ->sortable(),
            Column::make(__('messages.audit_log.user'), 'user.first_name')
                ->view('audit_logs.templates.columns.user')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.audit_log.event'), 'event')
                ->view('audit_logs.templates.columns.event')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.audit_log.module'), 'auditable_type')
                ->view('audit_logs.templates.columns.module')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.audit_log.record_id'), 'auditable_id')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.audit_log.ip_address'), 'ip_address')
                ->searchable(),
            Column::make(__('messages.common.action'), 'id')
                ->view('audit_logs.action'),
        ];
    }

    public function builder(): Builder
    {
        return AuditLog::with('user')->select('audit_logs.*');
    }
}
