@extends('layouts.app')
@section('title')
    {{ $item->description }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <div>
                <h1 class="mb-1">@yield('title') @include('lab_worklist.status', ['status' => $item->status])</h1>
                <div class="text-muted">{{ $item->order?->patient?->patientUser?->full_name }} · {{ $item->sample_number }} · {{ $item->order?->doctor?->doctorUser?->full_name }}</div>
            </div>
            <div class="d-flex gap-2">
                @if (in_array($item->status, ['validated', 'released'], true))
                    <a href="{{ route('exam-orders.report', $item->exam_order_id) }}" target="_blank" class="btn btn-success"><i class="fas fa-print me-1"></i>{{ __('messages.lab.report') }}</a>
                @endif
                <a href="{{ route('lab-worklist.index') }}" class="btn btn-outline-primary">{{ __('messages.lab.worklist') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php($locked = in_array($item->status, ['validated', 'released'], true) || ! canAccessRoute('lab-worklist.update'))
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                {{ Form::open(['route' => ['lab-worklist.update', $item->id], 'method' => 'post', 'id' => 'labResultForm']) }}
                @if ($item->is_lab)
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle" id="labRows">
                            <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th>{{ __('messages.lab.parameter') }}</th><th style="width:160px">{{ __('messages.lab.value') }}</th><th style="width:120px">{{ __('messages.nursing.unit') }}</th><th style="width:170px">{{ __('messages.lab.range') }}</th><th class="text-center" style="width:80px">{{ __('messages.lab.abnormal') }}</th></tr></thead>
                            <tbody>
                                @foreach (array_merge($rows, $locked ? [] : [['pathology_parameter_id' => null, 'label' => null, 'unit' => null, 'reference_range' => null, 'value' => null]]) as $i => $row)
                                    @php($flag = \App\Models\ExamResult::isOutside($row['value'] ?? null, $row['reference_range'] ?? null))
                                    <tr>
                                        <td><input type="hidden" name="rows[{{ $i }}][pathology_parameter_id]" value="{{ $row['pathology_parameter_id'] }}">
                                            <input type="text" name="rows[{{ $i }}][label]" value="{{ $row['label'] }}" class="form-control form-control-sm" maxlength="191" placeholder="{{ $row['label'] ? '' : __('messages.lab.add_line') }}" {{ $locked ? 'disabled' : '' }}></td>
                                        <td><input type="text" name="rows[{{ $i }}][value]" value="{{ $row['value'] }}" class="form-control form-control-sm {{ $flag ? 'border-danger text-danger fw-bold' : '' }}" maxlength="100" {{ $locked ? 'disabled' : '' }}></td>
                                        <td><input type="text" name="rows[{{ $i }}][unit]" value="{{ $row['unit'] }}" class="form-control form-control-sm" maxlength="50" {{ $locked ? 'disabled' : '' }}></td>
                                        <td><input type="text" name="rows[{{ $i }}][reference_range]" value="{{ $row['reference_range'] }}" class="form-control form-control-sm" maxlength="100" {{ $locked ? 'disabled' : '' }}></td>
                                        <td class="text-center">
                                            @if ($flag === null)
                                                <input type="checkbox" name="rows[{{ $i }}][is_abnormal]" value="1" class="form-check-input" {{ ! empty($item->results[$i]?->is_abnormal) ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }}>
                                            @elseif ($flag)
                                                <span class="text-danger fw-bold">H/L</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (empty($rows))
                        <div class="form-text mb-5">{{ __('messages.lab.no_parameters') }}</div>
                    @endif
                @else
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-end mb-2 gap-3">
                            {{ Form::label('report_text', __('messages.lab.imaging_report') . ':', ['class' => 'form-label mb-0']) }}
                            @if (! $locked && $templates->isNotEmpty())
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="imagingTemplate">
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}" data-body="{{ $template->text }}">{{ $template->label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-light-primary text-nowrap" id="imagingTemplateInsert">{{ __('messages.imaging.insert_template') }}</button>
                                </div>
                            @endif
                        </div>
                        {{ Form::textarea('report_text', $report, ['class' => 'form-control', 'rows' => 16, 'maxlength' => 50000, 'id' => 'imagingReport'] + ($locked ? ['disabled' => 'disabled'] : [])) }}
                    </div>
                @endif
                <div class="mb-5">
                    {{ Form::label('result_note', __('messages.lab.note') . ':', ['class' => 'form-label']) }}
                    {{ Form::textarea('result_note', $item->result_note, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 5000] + ($locked ? ['disabled' => 'disabled'] : [])) }}
                </div>
                @unless ($locked)
                    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endunless
                {{ Form::close() }}
            </div>
        </div>
        <div class="card mb-7">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">{{ __('messages.imaging.files') }}</h3>
                    @if ($pacsUrl)
                        <a href="{{ $pacsUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-info"><i class="fas fa-external-link-alt me-1"></i>{{ __('messages.imaging.open_pacs') }}</a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-4 mb-4">
                    @forelse ($files as $file)
                        <div class="border rounded p-2 text-center" style="width:140px">
                            <a href="{{ $file->getUrl() }}" target="_blank" class="text-decoration-none">
                                @if (str_starts_with((string) $file->mime_type, 'image/'))
                                    <img src="{{ $file->getUrl() }}" alt="" style="max-width:120px;max-height:90px">
                                @else
                                    <i class="fas fa-file-medical fs-1 text-primary"></i>
                                @endif
                                <div class="fs-8 text-truncate mt-1" title="{{ $file->file_name }}">{{ $file->file_name }}</div>
                            </a>
                            @unless ($locked)
                                {{ Form::open(['route' => ['lab-worklist.files.destroy', $item->id, $file->id], 'method' => 'post']) }}
                                <button class="btn btn-link btn-sm text-danger p-0">{{ __('messages.common.delete') }}</button>
                                {{ Form::close() }}
                            @endunless
                        </div>
                    @empty
                        <div class="text-muted">{{ __('messages.imaging.no_files') }}</div>
                    @endforelse
                </div>
                @unless ($locked)
                    {{ Form::open(['route' => ['lab-worklist.files.store', $item->id], 'method' => 'post', 'files' => true, 'class' => 'd-flex gap-3']) }}
                    <input type="file" name="files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.dcm,.dicom" required>
                    {{ Form::submit(__('messages.imaging.upload'), ['class' => 'btn btn-light-primary']) }}
                    {{ Form::close() }}
                    <div class="form-text">{{ __('messages.imaging.files_hint') }}</div>
                @endunless
            </div>
        </div>
        <div class="card">
            <div class="card-body d-flex flex-wrap gap-5 align-items-center">
                @foreach (['collected', 'result_entered', 'validated', 'released'] as $step)
                    @if ($item->{$step . '_at'})
                        <div><div class="text-muted fs-7">{{ __('messages.lab.steps.' . $step) }}</div>{{ $item->{$step . '_at'}->format('d/m/Y H:i') }} · {{ $item->stepUser($step)?->full_name }}</div>
                    @endif
                @endforeach
                <div class="ms-auto d-flex gap-2">
                    @if ($item->status === 'in_progress' && canAccessRoute('lab-worklist.validate'))
                        {{ Form::open(['route' => ['lab-worklist.validate', $item->id], 'method' => 'post']) }}<button class="btn btn-primary">{{ $item->is_lab ? __('messages.lab.validate') : __('messages.imaging.sign') }}</button>{{ Form::close() }}
                    @endif
                    @if ($item->status === 'validated' && canAccessRoute('lab-worklist.release'))
                        {{ Form::open(['route' => ['lab-worklist.release', $item->id], 'method' => 'post']) }}<button class="btn btn-success">{{ __('messages.lab.release') }}</button>{{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
