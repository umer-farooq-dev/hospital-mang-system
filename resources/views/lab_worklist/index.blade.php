@extends('layouts.app')
@section('title')
    {{ __('messages.lab.worklist') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    @php($canEdit = canAccessRoute('lab-worklist.collect'))
    <div class="container-fluid" id="labWorklist" data-label-url="{{ request('label') ? route('lab-worklist.label', request('label')) : '' }}">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                {{ Form::open(['route' => 'lab-worklist.index', 'method' => 'get', 'class' => 'row g-3 align-items-end mb-5']) }}
                <div class="col-md-2">{{ Form::label('type', __('messages.lab.type') . ':', ['class' => 'form-label']) }}
                    {{ Form::select('type', ['all' => __('messages.filter.All'), 'pathology_test' => __('messages.lab.lab'), 'radiology_test' => __('messages.lab.imaging')], $type, ['class' => 'form-select']) }}</div>
                <div class="col-md-2">{{ Form::label('status', __('messages.ot.status') . ':', ['class' => 'form-label']) }}
                    {{ Form::select('status', ['open' => __('messages.lab.open'), 'all' => __('messages.filter.All')] + collect(\App\Models\ExamOrderItem::STATUSES)->mapWithKeys(fn ($s) => [$s => __('messages.lab.statuses.' . $s)])->all(), $status, ['class' => 'form-select']) }}</div>
                <div class="col-md-2">{{ Form::label('from', __('messages.certificate.start_date') . ':', ['class' => 'form-label']) }}<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control"></div>
                <div class="col-md-2">{{ Form::label('to', __('messages.certificate.end_date') . ':', ['class' => 'form-label']) }}<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control"></div>
                <div class="col-md-3"><input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('messages.lab.search') }}"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fas fa-search"></i></button></div>
                {{ Form::close() }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.lab.sample') }}</th><th>{{ __('messages.cashier.patient') }}</th><th>{{ __('messages.exam_orders.exam') }}</th><th>{{ __('messages.ot.status') }}</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td class="text-nowrap">{{ $item->order?->ordered_at?->format('d/m/Y H:i') }}@if ($item->order?->ipd_patient_department_id)<div><span class="badge bg-light-info">IPD</span></div>@endif</td>
                                    <td class="fw-bold">{{ $item->sample_number }}</td>
                                    <td>{{ $item->order?->patient?->patientUser?->full_name }}<div class="text-muted fs-7">{{ $item->order?->doctor?->doctorUser?->full_name }}</div></td>
                                    <td>{{ $item->description }}<div class="text-muted fs-7">{{ $item->is_lab ? __('messages.lab.lab') : __('messages.lab.imaging') }}</div></td>
                                    <td>@include('lab_worklist.status', ['status' => $item->status])
                                        @if ($requirePayment && $item->status === 'ordered' && ! $item->isPaid())<div class="text-danger fs-7">{{ __('messages.lab.unpaid') }}</div>@endif</td>
                                    <td class="text-end text-nowrap">
                                        @if ($item->status === 'ordered' && $canEdit)
                                            {{ Form::open(['route' => ['lab-worklist.collect', $item->id], 'method' => 'post', 'class' => 'd-inline']) }}
                                            <button class="btn btn-sm btn-light-info">{{ $item->is_lab ? __('messages.lab.collect') : __('messages.lab.start') }}</button>
                                            {{ Form::close() }}
                                        @endif
                                        @if ($item->sample_number)
                                            <a href="{{ route('lab-worklist.label', $item->id) }}" target="_blank" class="btn btn-sm btn-light" title="{{ __('messages.lab.label') }}"><i class="fas fa-tag"></i></a>
                                        @endif
                                        <a href="{{ route('lab-worklist.show', $item->id) }}" class="btn btn-sm btn-light-primary">{{ __('messages.lab.results') }}</a>
                                        @if (in_array($item->status, ['validated', 'released'], true))
                                            <a href="{{ route('exam-orders.report', $item->exam_order_id) }}" target="_blank" class="btn btn-sm btn-light-success" title="{{ __('messages.lab.report') }}"><i class="fas fa-print"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('messages.lab.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $items->links() }}
            </div>
        </div>
        @php($importProfiles = \App\Models\LabImportProfile::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
        @if (canAccessRoute('lab-worklist.import'))
            <div class="card mb-7">
                <div class="card-body">
                    <h3 class="mb-2">{{ __('messages.lab_import.import') }}</h3>
                    <div class="form-text mb-4">{{ __('messages.lab_import.import_hint') }}
                        @canroute('lab-import-profiles.index') <a href="{{ route('lab-import-profiles.index') }}">{{ __('messages.lab_import.profiles') }}</a>@endcanroute</div>
                    @if ($importProfiles->isNotEmpty())
                        {{ Form::open(['route' => 'lab-worklist.import', 'method' => 'post', 'files' => true, 'class' => 'row g-3 align-items-end']) }}
                        <div class="col-md-4">{{ Form::select('profile_id', $importProfiles, null, ['class' => 'form-select', 'required']) }}</div>
                        <div class="col-md-5"><input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="form-control" required></div>
                        <div class="col-md-3">{{ Form::submit(__('messages.lab_import.import_button'), ['class' => 'btn btn-primary w-100']) }}</div>
                        {{ Form::close() }}
                    @else
                        <div class="text-muted">{{ __('messages.lab_import.no_profile') }}</div>
                    @endif
                </div>
            </div>
        @endif
        @if (canAccessRoute('lab-worklist.settings'))
            @php($labSettings = \App\Models\Setting::whereIn('key', ['lab_sample_prefix', 'lab_auto_release', 'lab_require_payment', 'pacs_viewer_url', 'portal_show_results'])->pluck('value', 'key'))
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-5">{{ __('messages.lab.settings') }}</h3>
                    {{ Form::open(['route' => 'lab-worklist.settings', 'method' => 'post', 'class' => 'row g-4 align-items-end']) }}
                    <div class="col-md-3">{{ Form::label('lab_sample_prefix', __('messages.lab.sample_prefix') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('lab_sample_prefix', $labSettings['lab_sample_prefix'] ?? 'S', ['class' => 'form-control', 'maxlength' => 6, 'required']) }}</div>
                    <div class="col-md-4"><label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="lab_require_payment" value="1" {{ ($labSettings['lab_require_payment'] ?? '1') === '1' ? 'checked' : '' }}><span class="form-check-label">{{ __('messages.lab.require_payment') }}</span></label></div>
                    <div class="col-md-3"><label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="lab_auto_release" value="1" {{ ($labSettings['lab_auto_release'] ?? '0') === '1' ? 'checked' : '' }}><span class="form-check-label">{{ __('messages.lab.auto_release') }}</span></label></div>
                    <div class="col-md-2">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary w-100']) }}</div>
                    <div class="col-md-12"><label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="portal_show_results" value="1" {{ ($labSettings['portal_show_results'] ?? '0') === '1' ? 'checked' : '' }}><span class="form-check-label">{{ __('messages.exam_results.portal_setting') }}</span></label></div>
                    <div class="col-md-10">{{ Form::label('pacs_viewer_url', __('messages.imaging.pacs_url') . ':', ['class' => 'form-label']) }}
                        {{ Form::url('pacs_viewer_url', $labSettings['pacs_viewer_url'] ?? '', ['class' => 'form-control', 'maxlength' => 500, 'placeholder' => 'https://pacs.example/viewer?accession={sample_number}']) }}
                        <div class="form-text">{{ __('messages.imaging.pacs_hint') }}</div></div>
                    <div class="col-12 form-text">{{ __('messages.lab.settings_hint') }}
                        @canroute('imaging-report-templates.index') · <a href="{{ route('imaging-report-templates.index') }}">{{ __('messages.imaging.templates_title') }}</a>@endcanroute
                        @canroute('master-lists.index') · <a href="{{ route('master-lists.index', ['type' => 'imaging_modality']) }}">{{ __('messages.master_list.types.imaging_modality') }}</a>@endcanroute
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        @endif
    </div>
@endsection
