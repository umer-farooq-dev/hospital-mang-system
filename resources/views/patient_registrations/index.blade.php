@extends('layouts.app')
@section('title')
    {{ __('messages.reception_report.title') }}
@endsection
@section('content')
    @php
        $query = [
            'from' => $filters['from']->toDateString(),
            'to' => $filters['to']->toDateString(),
            'gender' => $filters['gender'],
            'id_document_type' => $filters['id_document_type'],
        ];
        $visitTotals = [
            'visits' => $summary['visits']->sum('visits'),
            'new' => $summary['visits']->sum('new'),
            'returning' => $summary['visits']->sum('returning'),
        ];
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')

            <div class="card mb-7">
                <div class="card-body">
                    <form method="GET" action="{{ route('patient-registrations.index') }}" class="row g-4 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.from') }}</label>
                            <input type="date" name="from" class="form-control" value="{{ $query['from'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.to') }}</label>
                            <input type="date" name="to" class="form-control" value="{{ $query['to'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.user.gender') }}</label>
                            <select name="gender" class="form-select">
                                <option value="">{{ __('messages.filter.All') }}</option>
                                <option value="male" {{ $filters['gender'] === 'male' ? 'selected' : '' }}>{{ __('messages.user.male') }}</option>
                                <option value="female" {{ $filters['gender'] === 'female' ? 'selected' : '' }}>{{ __('messages.user.female') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception.id_document_type') }}</label>
                            <select name="id_document_type" class="form-select">
                                <option value="">{{ __('messages.filter.All') }}</option>
                                @foreach ($idTypes as $code => $label)
                                    <option value="{{ $code }}" {{ $filters['id_document_type'] === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex flex-wrap gap-2 justify-content-md-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>{{ __('messages.reception_report.apply') }}</button>
                            <a href="{{ route('patient-registrations.index') }}" class="btn btn-secondary">{{ __('messages.common.reset') }}</a>
                            @canroute('patient-registrations.export')
                            <a href="{{ route('patient-registrations.export', $query) }}" class="btn btn-light-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
                            @endcanroute
                            @canroute('patient-registrations.pdf')
                            <a href="{{ route('patient-registrations.pdf', $query) }}" class="btn btn-light-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                            @endcanroute
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['total', 'fa-users', 'primary'],
                    ['male', 'fa-mars', 'info'],
                    ['female', 'fa-venus', 'danger'],
                    ['with_id', 'fa-id-card', 'success'],
                ] as [$key, $icon, $color])
                    <div class="col-xl-3 col-md-6 mb-7">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <span class="bg-light-{{ $color }} text-{{ $color }} rounded p-4 me-4"><i class="fas {{ $icon }} fs-2"></i></span>
                                <div>
                                    <div class="fs-1 fw-bold">{{ $summary[$key] }}</div>
                                    <div class="text-muted">{{ __('messages.reception_report.' . $key) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-xl-7 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">{{ __('messages.reception_report.visits_by_department') }}</h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed mb-0">
                                    <thead>
                                        <tr class="text-gray-600 fw-bold">
                                            <th>{{ __('messages.reception_report.department') }}</th>
                                            <th class="text-end">{{ __('messages.reception_report.visits') }}</th>
                                            <th class="text-end">{{ __('messages.reception_report.new_patients') }}</th>
                                            <th class="text-end">{{ __('messages.reception_report.returning_patients') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($summary['visits'] as $department => $row)
                                            <tr>
                                                <td>{{ $department }}</td>
                                                <td class="text-end">{{ $row['visits'] }}</td>
                                                <td class="text-end">{{ $row['new'] }}</td>
                                                <td class="text-end">{{ $row['returning'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted">{{ __('messages.reception_report.no_visits') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                    @if ($summary['visits']->isNotEmpty())
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td>{{ __('messages.reception_report.total_row') }}</td>
                                                <td class="text-end">{{ $visitTotals['visits'] }}</td>
                                                <td class="text-end">{{ $visitTotals['new'] }}</td>
                                                <td class="text-end">{{ $visitTotals['returning'] }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">{{ __('messages.reception_report.by_day') }}</h3>
                        </div>
                        <div class="card-body pt-0">
                            @forelse ($summary['by_day'] as $day => $count)
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span>{{ \Carbon\Carbon::parse($day)->translatedFormat('D d/m/Y') }}</span>
                                    <span class="fw-bold">{{ $count }}</span>
                                </div>
                            @empty
                                <div class="text-muted py-3">{{ __('messages.reception_report.no_patients') }}</div>
                            @endforelse
                            @if ($summary['by_id_type']->isNotEmpty())
                                <h4 class="fs-6 fw-bold mt-6 mb-2">{{ __('messages.reception.id_document') }}</h4>
                                @foreach ($summary['by_id_type'] as $type => $count)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $type }}</span><span class="fw-bold">{{ $count }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">{{ __('messages.reception_report.registered_patients') }}</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        @include('patient_registrations.table', ['patients' => $patients])
                    </div>
                    <div class="mt-5">{{ $patients->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
