@extends('layouts.app')
@section('title')
    {{ __('messages.reception_visit.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('reception-desk.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php($user = $patient->patientUser)
    <div class="container-fluid">
        @include('flash::message')
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        {{-- patient --}}
        <div class="card mb-5">
            <div class="card-body d-flex flex-wrap align-items-center gap-5 py-5">
                <div class="symbol symbol-60px symbol-circle">
                    <img src="{{ $user->image_url }}" alt="">
                </div>
                <div class="flex-grow-1">
                    <div class="fs-3 fw-bolder">{{ $user->full_name }}</div>
                    <div class="text-muted">
                        {{ __('messages.reception_visit.patient_id') }} {{ $patient->patient_unique_id }}
                        · {{ $user->gender == 1 ? __('messages.user.female') : __('messages.user.male') }}
                        @if ($user->dob) · {{ __('messages.reception.age_years', ['count' => \Carbon\Carbon::parse($user->dob)->age]) }} @endif
                        @if ($user->phone) · {{ $user->phone }} @endif
                    </div>
                </div>
                <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-sm btn-light">{{ __('messages.reception_desk.patient_file') }}</a>
            </div>
        </div>

        @if ($hasCounter && ! $session)
            <div class="alert alert-warning d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">{{ __('messages.reception_visit.session_closed') }}</div>
                <form method="POST" action="{{ route('cashier.open') }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    <input type="hidden" name="back" value="{{ url()->current() }}">
                    <input type="number" name="opening_float" min="0" step="1" class="form-control form-control-sm w-150px" placeholder="{{ __('messages.reception_visit.opening_float') }}">
                    <button class="btn btn-sm btn-warning text-nowrap">{{ __('messages.reception_visit.open_session') }}</button>
                </form>
            </div>
        @elseif (! $hasCounter)
            <div class="alert alert-info">{{ __('messages.reception_visit.no_counter') }}</div>
        @endif

        <form method="POST" action="{{ route('reception-desk.visit.store', $patient->id) }}" id="receptionVisitForm"
            data-quote-url="{{ route('reception-desk.visit.quote', $patient->id) }}">
            @csrf
            <div class="row g-5">
                <div class="col-xl-7">
                    {{-- 1. service --}}
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title"><span class="badge badge-circle badge-primary me-3">1</span>{{ __('messages.reception_visit.step_service') }}</h3></div>
                        <div class="card-body row g-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.reception_visit.department') }}</label>
                                <select id="visitDepartment" class="form-select" data-control="select2">
                                    <option value="">{{ __('messages.reception_visit.all_departments') }}</option>
                                    @foreach ($departments as $id => $title)
                                        <option value="{{ $id }}">{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.reception_visit.doctor') }}</label>
                                <select name="doctor_id" id="visitDoctor" class="form-select" data-control="select2">
                                    <option value="">{{ __('messages.reception_visit.choose_doctor') }}</option>
                                    @foreach ($doctors as $id => $doctor)
                                        <option value="{{ $id }}" data-department="{{ $doctor['department'] }}">{{ $doctor['name'] }}{{ $doctor['specialist'] ? ' · '.$doctor['specialist'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-check form-switch form-check-custom">
                                    <input class="form-check-input" type="checkbox" name="consultation" value="1" id="visitConsultation" checked>
                                    <span class="form-check-label fw-bold">{{ __('messages.reception_visit.consultation') }}</span>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('messages.reception_visit.exams') }}</label>
                                <select name="tests[]" id="visitTests" class="form-select" data-control="select2" multiple data-placeholder="{{ __('messages.reception_visit.choose_exams') }}">
                                    @foreach ($tests as $group => $options)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($options as $key => $name)
                                                <option value="{{ $key }}">{{ $name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 2. payer and discount --}}
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title"><span class="badge badge-circle badge-primary me-3">2</span>{{ __('messages.reception_visit.step_payer') }}</h3></div>
                        <div class="card-body row g-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.reception_visit.payer') }}</label>
                                <select name="patient_insurance_id" id="visitPayer" class="form-select">
                                    <option value="">{{ __('messages.reception_visit.patient_pays') }}</option>
                                    @foreach ($policies as $id => $label)
                                        <option value="{{ $id }}" @selected($defaultPolicy == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if (empty($policies))
                                    <div class="form-text">{{ __('messages.reception_visit.no_policy') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.reception_visit.voucher') }}</label>
                                <input type="text" name="voucher_number" maxlength="50" class="form-control">
                            </div>
                            @if (count($priceLists) > 1)
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('messages.reception_visit.price_list') }}</label>
                                    <select name="price_list_id" class="form-select">
                                        {{-- empty: the insurer's own list for an insured patient, the default list otherwise --}}
                                        <option value="">{{ __('messages.reception_visit.price_list_auto') }}</option>
                                        @foreach ($priceLists as $id => $label)
                                            <option value="{{ $id }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if ($maxDiscount > 0)
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('messages.reception_visit.discount') }} (%)</label>
                                    <input type="number" name="extra_discount" min="0" max="{{ $maxDiscount }}" step="0.5" class="form-control" placeholder="0">
                                    <div class="form-text">{{ __('messages.reception_visit.discount_max', ['max' => formatPercent($maxDiscount)]) }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 3. bill and payment --}}
                <div class="col-xl-5">
                    <div class="card position-sticky" style="top: 90px">
                        <div class="card-header"><h3 class="card-title"><span class="badge badge-circle badge-primary me-3">3</span>{{ __('messages.reception_visit.step_payment') }}</h3></div>
                        <div class="card-body">
                            <div id="visitWarnings"></div>
                            <table class="table table-row-dashed align-middle fs-6 mb-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>{{ __('messages.cashier.nature') }}</th>
                                        <th class="text-end">{{ __('messages.reception_visit.price') }}</th>
                                        <th class="text-end">{{ __('messages.reception_visit.payer_share') }}</th>
                                        <th class="text-end">{{ __('messages.reception_visit.patient_share') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="visitLines">
                                    <tr><td colspan="4" class="text-muted text-center py-5">{{ __('messages.reception_visit.empty') }}</td></tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between text-muted"><span>{{ __('messages.cashier.total_amount') }}</span><span id="visitTotalGross">—</span></div>
                            <div class="d-flex justify-content-between text-muted"><span>{{ __('messages.split.discount') }}</span><span id="visitTotalDiscount">—</span></div>
                            <div class="d-flex justify-content-between text-muted mb-3"><span>{{ __('messages.cashier.payer_pays') }}</span><span id="visitTotalPayer">—</span></div>
                            <div class="d-flex justify-content-between align-items-center bg-light-success rounded p-4 mb-5">
                                <span class="fw-bolder fs-4">{{ __('messages.cashier.patient_pays') }}</span>
                                <span class="fw-bolder fs-1 text-success" id="visitTotalPatient">—</span>
                            </div>

                            @if ($session)
                                <div class="row g-3 mb-5">
                                    <div class="col-6">
                                        <label class="form-label">{{ __('messages.cashier.payment_method') }}</label>
                                        <select name="payment_method" id="visitMethod" class="form-select">
                                            @foreach ($paymentMethods as $id => $label)
                                                <option value="{{ $id }}" @selected($cashMethod == $id)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">{{ __('messages.reception_visit.received') }}</label>
                                        <input type="number" name="amount_received" id="visitReceived" min="0" step="1" class="form-control">
                                    </div>
                                    <div class="col-12 text-end fs-5">{{ __('messages.cashier.change') }} : <b id="visitChange">—</b></div>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100" id="visitSubmit">
                                    <i class="fas fa-print me-2"></i>{{ __('messages.reception_visit.save_print') }}
                                </button>
                            @else
                                <input type="hidden" name="payment_method" value="{{ $cashMethod }}">
                                <button type="submit" class="btn btn-primary btn-lg w-100" id="visitSubmit">
                                    {{ __('messages.reception_visit.save_to_cashier') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('page_scripts')
    <script>
        $(function () {
            const form = $('#receptionVisitForm');
            const doctors = $('#visitDoctor');
            const allDoctors = doctors.find('option').clone();
            let due = 0;
            let timer = null;

            // department -> only its doctors
            $('#visitDepartment').on('change', function () {
                const department = $(this).val();
                const current = doctors.val();
                doctors.empty().append(allDoctors.filter((i, o) => !o.value || !department || String($(o).data('department')) === department).clone());
                doctors.val(doctors.find('option[value="' + current + '"]').length ? current : '').trigger('change');
            });

            const money = (value) => $('<span>').text(value).html();
            const refresh = () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const data = form.serializeArray().filter((f) => f.name !== '_token');
                    $.get(form.data('quote-url'), $.param(data)).done((res) => {
                        const r = res.data;
                        due = r.patient_due;
                        $('#visitLines').html(r.lines.length ? r.lines.map((l) =>
                            '<tr><td>' + money(l.description) + '</td>'
                            + '<td class="text-end text-nowrap">' + money(l.gross) + '</td>'
                            + '<td class="text-end text-nowrap">' + money(l.payer) + '</td>'
                            + '<td class="text-end text-nowrap fw-bold">' + money(l.patient) + '</td></tr>').join('')
                            : '<tr><td colspan="4" class="text-muted text-center py-5">{{ __('messages.reception_visit.empty') }}</td></tr>');
                        $('#visitTotalGross').text(r.totals.gross);
                        $('#visitTotalDiscount').text(r.totals.discount);
                        $('#visitTotalPayer').text(r.totals.payer);
                        $('#visitTotalPatient').text(r.totals.patient);
                        $('#visitWarnings').html(r.warnings.map((w) => '<div class="alert alert-warning py-2 mb-3">' + money(w) + '</div>').join(''));
                        $('#visitReceived').attr('placeholder', due);
                        updateChange();
                    }).fail((xhr) => {
                        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '';
                        $('#visitWarnings').html(msg ? '<div class="alert alert-danger py-2 mb-3">' + money(msg) + '</div>' : '');
                    });
                }, 250);
            };
            const updateChange = () => {
                const received = parseFloat($('#visitReceived').val());
                $('#visitChange').text(isNaN(received) ? '—' : Math.max(0, received - due).toLocaleString('fr-FR'));
            };

            form.on('change', 'select, input[type=checkbox]', refresh);
            form.on('input', 'input[name=extra_discount], input[name=voucher_number]', refresh);
            $('#visitReceived').on('input', updateChange);
            form.on('submit', () => $('#visitSubmit').prop('disabled', true));
            refresh();
        });
    </script>
@endsection
