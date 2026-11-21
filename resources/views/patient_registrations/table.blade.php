{{-- Registered patients list: used by the screen, the PDF and the Excel export. --}}
<table class="table align-middle table-row-dashed mb-0" {!! ($print ?? false) ? 'border="1" cellspacing="0" cellpadding="4" width="100%"' : '' !!}>
    <thead>
        <tr class="text-gray-600 fw-bold">
            <th>{{ __('messages.reception_report.patient_number') }}</th>
            <th>{{ __('messages.advanced_payment.patient') }}</th>
            <th>{{ __('messages.user.gender') }}</th>
            <th>{{ __('messages.reception_report.age') }}</th>
            <th>{{ __('messages.user.phone') }}</th>
            <th>{{ __('messages.reception.id_document') }}</th>
            <th>{{ __('messages.reception_report.registered_at') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($patients as $patient)
            <tr>
                <td>{{ $patient->patient_unique_id }}</td>
                <td>{{ $patient->patientUser->full_name }}</td>
                <td>{{ $patient->patientUser->gender == 1 ? __('messages.user.female') : __('messages.user.male') }}</td>
                <td>{{ $patient->patientUser->dob ? \Carbon\Carbon::parse($patient->patientUser->dob)->age : '' }}</td>
                <td>{{ $patient->patientUser->phone }}</td>
                <td>{{ $patient->id_document_number ? $patient->id_document_type_name . ' ' . $patient->id_document_number : '' }}</td>
                <td>{{ $patient->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-muted">{{ __('messages.reception_report.no_patients') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
