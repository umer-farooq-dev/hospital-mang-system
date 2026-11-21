@php
    $modules = App\Models\Module::cacheFor(now()->addDays())
        ->toBase()
        ->get();
@endphp
@canroute('enable-2fa')
<li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('2fa*') ? 'd-none' : '' }}">
    <a class="nav-link p-0 {{ Request::is('2fa*') ? 'active' : '' }}" href="{{ route('enable-2fa') }}">
        {{ __('Two-Factor Authentication') }}
    </a>
</li>
@endcanroute
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @canroute('dashboard')
    <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('dashboard*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            {{ __('messages.dashboard.dashboard') }}
        </a>
    </li>
    @endcanroute

    @canroute('addon.index')
    <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('add-on*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('add-on*') ? 'active' : '' }}" href="{{ route('addon.index') }}">
            {{ __('messages.addon.addon') }}
        </a>
    </li>
    @endcanroute


    @if (moduleExists('Holiday'))
        @canroute('annualHolidays.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('annual-holidays*', 'calendar-view*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('annual-holidays*', 'calendar-view*') ? 'active' : '' }}"
                href="{{ route('annualHolidays.index') }}">
                {{ __('holiday::messages.holidays') }}
            </a>
        </li>
        @endcanroute
    @endif

@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @canroute('smart-patient-cards.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('smart-patient-cards*', 'generate-patient-smart-cards*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('smart-patient-cards*') ? 'active' : '' }}"
            href="{{ route('smart-patient-cards.index') }}">
            {{ __('messages.patient_id_card.patient_id_card_template') }}
        </a>
    </li>
    @endcanroute
@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @canroute('generate-patient-smart-cards.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('smart-patient-cards*', 'generate-patient-smart-cards*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('generate-patient-smart-cards*') ? 'active' : '' }}"
            href="{{ route('generate-patient-smart-cards.index') }}">
            {{ __('messages.patient_id_card.generate_patient_id_card') }}
        </a>
    </li>
    @endcanroute
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @canroute('users.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('users*', 'admins*', 'accountants*', 'nurses*', 'lab-technicians*', 'receptionists*', 'pharmacists*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('users*') ? 'active' : '' }}" href="{{ route('users.index') }}">
            {{ __('messages.users') }}
        </a>
    </li>
    @endcanroute
@endif
@if (Auth::user()->hasRole('Admin|Doctor|Receptionist|Nurse|Lab Technician') || hasCustomRole())
    @module('IPD Patients', $modules)
        @canroute('ipd.patient.index')
        <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('ipds*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('ipds*') ? 'active' : '' }}" href="{{ route('ipd.patient.index') }}">
                {{ __('messages.ipd_patients') }}
            </a>
        </li>
        @endcanroute
    @endmodule
    @module('OPD Patients', $modules)
        @canroute('opd.patient.index')
        <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('opds*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('opds*') ? 'active' : '' }} " href="{{ route('opd.patient.index') }}">
                {{ __('messages.opd_patients') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Vaccinated Patients', $modules)
        @canroute('vaccinated-patients.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('vaccinated-patients*', 'vaccinations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('vaccinated-patients*') ? 'active' : '' }}"
                href="{{ route('vaccinated-patients.index') }}">
                {{ __('messages.vaccinated_patients') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Vaccinations', $modules)
        @canroute('vaccinations.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('vaccinated-patients*', 'vaccinations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('vaccinations*') ? 'active' : '' }}"
                href="{{ route('vaccinations.index') }}">
                {{ __('messages.vaccinations') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    {{-- @module('Accounts',$modules) --}}
    @canroute('admins.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('admins*', 'users*', 'accountants*', 'nurses*', 'lab-technicians*', 'receptionists*', 'pharmacists*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('admins*') ? 'active' : '' }}" href="{{ route('admins.index') }}">
            {{ __('messages.admin') }}
        </a>
    </li>
    @endcanroute
    {{-- @endmodule --}}
@endif
@if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
    @module('Accounts', $modules)
        @canroute('accounts.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('accounts*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
                {{ __('messages.account.account') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
    @module('Employee Payrolls', $modules)
        @canroute('employee-payrolls.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('employee-payrolls*') ? 'active' : '' }}"
                href="{{ route('employee-payrolls.index') }}">
                {{ __('messages.employee_payrolls') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
    @module('Invoices', $modules)
        @canroute('invoices.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('invoices*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                {{ __('messages.invoices') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
    @module('Payments', $modules)
        @canroute('payments.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('payments*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                {{ __('messages.payments') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Payment Reports', $modules)
        @canroute('payment.reports')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('payment-reports*') ? 'active' : '' }}"
                href="{{ route('payment.reports') }}">
                {{ __('messages.payment.payment_reports') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Advance Payments', $modules)
        @canroute('advanced-payments.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('advanced-payments*') ? 'active' : '' }}"
                href="{{ route('advanced-payments.index') }}">
                {{ __('messages.advanced_payments') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Accountant|Receptionist') || hasCustomRole())
    @module('Bills', $modules)
        @canroute('bills.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('manual-billing-payments*', 'accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('bills*') ? 'active' : '' }}" href="{{ route('bills.index') }}">
                {{ __('messages.bills') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @canroute('manual-billing-payments.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('accounts*', 'employee-payrolls*', 'invoices*', 'payments*', 'payment-reports*', 'advanced-payments*', 'bills*', 'manual-billing-payments*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('manual-billing-payments*') ? 'active' : '' }}"
            href="{{ route('manual-billing-payments.index') }}">
            {{ __('messages.bill.manual_bill') }}
        </a>
    </li>
    @endcanroute
@endif
@if (Auth::user()->hasRole('Admin|Nurse|Doctor') || hasCustomRole())
    @module('Bed Assigns', $modules)
        @canroute('bed-status')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('bed-types*', 'beds*', 'bed-assigns*', 'bulk-beds', 'bed-status') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('bed-status*') ? 'active' : '' }}" href="{{ route('bed-status') }}">
                {{ __('messages.bed_status.bed_status') }}
            </a>
        </li>
        @endcanroute
    @endmodule
    @module('Bed Assigns', $modules)
        @canroute('bed-assigns.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('bed-types*', 'beds*', 'bed-assigns*', 'bulk-beds', 'bed-status') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('bed-assigns*') ? 'active' : '' }}" href="{{ route('bed-assigns.index') }}">
                {{ __('messages.bed_assigns') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Nurse') || hasCustomRole())
    @module('Beds', $modules)
        @canroute('beds.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('bed-types*', 'beds*', 'bed-assigns*', 'bulk-beds', 'bed-status') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('beds*') || Request::is('bulk-beds') ? 'active' : '' }}"
                href="{{ route('beds.index') }}">
                {{ __('messages.beds') }}
            </a>
        </li>
        @endcanroute
    @endmodule
    @module('Bed Types', $modules)
        @canroute('bed-types.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('bed-types*', 'beds*', 'bed-assigns*', 'bulk-beds', 'bed-status') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('bed-types*') ? 'active' : '' }}" href="{{ route('bed-types.index') }}">
                {{ __('messages.bed_types') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif

@if (Auth::user()->hasRole('Admin|Lab Technician') || hasCustomRole())
    @module('Blood Banks', $modules)
        @canroute('blood-banks.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('blood-banks*', 'blood-donors*', 'blood-donations*', 'blood-issues*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('blood-banks*') ? 'active' : '' }}" href="{{ route('blood-banks.index') }}">
                {{ __('messages.blood_banks') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Lab Technician') || hasCustomRole())
    @module('Blood Donors', $modules)
        @canroute('blood-donors.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('blood-banks*', 'blood-donors*', 'blood-donations*', 'blood-issues*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('blood-donors*') ? 'active' : '' }}"
                href="{{ route('blood-donors.index') }}">
                {{ __('messages.blood_donors') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Lab Technician') || hasCustomRole())
    @module('Blood Donations', $modules)
        @canroute('blood-donations.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('blood-banks*', 'blood-donors*', 'blood-donations*', 'blood-issues*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('blood-donations*') ? 'active' : '' }}"
                href="{{ route('blood-donations.index') }}">
                {{ __('messages.blood_donations') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Lab Technician') || hasCustomRole())
    @module('Blood Issues', $modules)
        @canroute('blood-issues.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('blood-banks*', 'blood-donors*', 'blood-donations*', 'blood-issues*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('blood-issues*') ? 'active' : '' }}"
                href="{{ route('blood-issues.index') }}">
                {{ __('messages.blood_issues') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Doctor') || hasCustomRole())
    @module('Patients', $modules)
        @canroute('patients.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patients*', 'patient-cases*', 'case-handlers*', 'patient-admissions*', 'patient-registrations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('patients*') ? 'active' : '' }}" href="{{ route('patients.index') }}">
                {{ __('messages.patients') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @module('Patient Registrations', $modules)
        @canroute('patient-registrations.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patients*', 'patient-cases*', 'case-handlers*', 'patient-admissions*', 'patient-registrations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('patient-registrations*') ? 'active' : '' }}"
                href="{{ route('patient-registrations.index') }}">
                {{ __('messages.reception_report.tab') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Case Manager') || hasCustomRole())
    @module('Cases', $modules)
        @canroute('patient-cases.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patients*', 'patient-cases*', 'case-handlers*', 'patient-admissions*', 'patient-registrations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('patient-cases*') ? 'active' : '' }}"
                href="{{ route('patient-cases.index') }}">
                {{ __('messages.cases') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @module('Case Handlers', $modules)
        @canroute('case-handlers.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ !Request::is('patients*', 'patient-cases*', 'case-handlers*', 'patient-admissions*', 'patient-registrations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('case-handlers*') ? 'active' : '' }}"
                href="{{ route('case-handlers.index') }}">
                {{ __('messages.case_handlers') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Doctor|Case Manager') || hasCustomRole())
    @module('Patient Admissions', $modules)
        @canroute('patient-admissions.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patients*', 'patient-cases*', 'case-handlers*', 'patient-admissions*', 'patient-registrations*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('patient-admissions**') ? 'active' : '' }}"
                href="{{ route('patient-admissions.index') }}">
                {{ __('messages.patient_admissions') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@role('Case Manager|Pharmacist|Lab Technician')
    @module('Doctors', $modules)
        <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/doctor*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('employee/doctor*') ? 'active' : '' }}"
                href="{{ url('employee/doctor') }}">
                {{ __('messages.doctors') }}
            </a>
        </li>
    @endmodule
@endrole
@role('Pharmacist|Nurse')
    @module('Prescriptions', $modules)
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/prescriptions*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('employee/prescriptions*') ? 'active' : '' }}"
                href="{{ url('employee/prescriptions') }}">
                {{ __('messages.prescriptions') }}
            </a>
        </li>
    @endmodule
@endrole
@if (Auth::user()->hasRole('Admin|Doctor|Patient') || hasCustomRole())
    @module('Documents', $modules)
        @canroute('documents.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('documents*', 'document-types*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('documents*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                {{ __('messages.documents') }}
            </a>
        </li>
        @endcanroute
    @endmodule
    {{-- odontogram --}}
    @canroute('odontogram.index')
    <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('odontogram*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('odontogram*') ? 'active' : '' }}"
            href="{{ route('odontogram.index') }}">
            {{ __('Odontogram') }}
        </a>
    </li>
    @endcanroute

@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Document Types', $modules)
        @canroute('document-types.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('documents*', 'document-types*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('document-types*') ? 'active' : '' }}"
                href="{{ route('document-types.index') }}">
                {{ __('messages.document_types') }}
            </a>
        </li>
        @endcanroute
    @endmodule

    
    @canroute('email-template.index')
    <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('email-template*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('email-template*') ? 'active' : '' }}"
            href="{{ route('email-template.index') }}">
            {{ __('messages.email_template.email_template') }}
        </a>
    </li>
    @endcanroute
@endif

@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @module('Insurances', $modules)
        @canroute('insurances.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('insurances*', 'packages*', 'services*', 'ambulances*', 'ambulance-calls*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('insurances*') ? 'active' : '' }}"
                href="{{ route('insurances.index') }}">
                {{ __('messages.insurances') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    @module('Packages', $modules)
        @canroute('packages.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('insurances*', 'packages*', 'services*', 'ambulances*', 'ambulance-calls*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('packages*') ? 'active' : '' }}" href="{{ route('packages.index') }}">
                {{ __('messages.packages') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Accountant') || hasCustomRole())
    @module('Services', $modules)
        @canroute('services.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('insurances*', 'packages*', 'services*', 'ambulances*', 'ambulance-calls*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('services*') ? 'active' : '' }}" href="{{ route('services.index') }}">
                {{ __('messages.services') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Case Manager') || hasCustomRole())
    @module('Ambulances', $modules)
        @canroute('ambulances.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('insurances*', 'packages*', 'services*', 'ambulances*', 'ambulance-calls*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('ambulances*') ? 'active' : '' }}"
                href="{{ route('ambulances.index') }}">
                {{ __('messages.ambulances') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist|Case Manager') || hasCustomRole())
    @module('Ambulances Calls', $modules)
        @canroute('ambulance-calls.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('insurances*', 'packages*', 'services*', 'ambulances*', 'ambulance-calls*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('ambulance-calls*') ? 'active' : '' }}"
                href="{{ route('ambulance-calls.index') }}">
                {{ __('messages.ambulance_calls') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
    {{-- <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0"> --}}
    @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
        @module('Doctors', $modules)
            @canroute('doctors.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*', 'doctor-departments*', 'schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('doctors*') ? 'active' : '' }}" href="{{ route('doctors.index') }}">
                    {{ __('messages.doctors') }}
                </a>
            </li>
            @endcanroute
        @endmodule
    @endif
    @if (Auth::user()->hasRole('Admin') || hasCustomRole())
        @module('Doctor Departments', $modules)
            @canroute('doctor-departments.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*', 'doctor-departments*', 'schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('doctor-departments*') ? 'active' : '' }}"
                    href="{{ route('doctor-departments.index') }}">
                    <span class="menu-title" style="white-space: nowrap">{{ __('messages.doctor_departments') }}
                </a>
            </li>
            @endcanroute
        @endmodule
    @endif
    @if (Auth::user()->hasRole('Admin') || hasCustomRole())
        @module('Schedules', $modules)
            @canroute('schedules.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*', 'doctor-departments*', 'schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('schedules*') ? 'active' : '' }}" href="{{ route('schedules.index') }}">
                    {{ __('messages.schedules') }}
                </a>
            </li>
            @endcanroute
        @endmodule
    @endif
    @if (Auth::user()->hasRole('Admin|Nurse') || hasCustomRole())
        @module('Prescriptions', $modules)
            @canroute('prescriptions.index')
            <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('prescriptions*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('prescriptions*') ? 'active' : '' }}"
                    href="{{ route('prescriptions.index') }}">
                    {{ __('messages.prescriptions') }}
                </a>
            </li>
            @endcanroute
        @endmodule
    @endif
@endif
@if (Auth::user()->hasRole('Admin|Doctor') || hasCustomRole())
    @canroute('holidays.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*', 'doctor-departments*', 'schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('holidays*') ? 'active' : '' }}"
            href="{{ route('holidays.index') }}">{{ __('messages.holiday.doctor_holiday') }}</a>
    </li>
    @endcanroute
    @canroute('breaks.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*', 'doctor-departments*', 'schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('breaks*') ? 'active' : '' }}"
            href="{{ route('breaks.index') }}">{{ __('messages.lunch_break.lunch_breaks') }}</a>
    </li>
    @endcanroute
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Accountants', $modules)
        @canroute('accountants.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('accountants*', 'admins*', 'users*', 'nurses*', 'lab-technicians*', 'receptionists*', 'pharmacists*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('accountants*') ? 'active' : '' }}"
                href="{{ route('accountants.index') }}">
                {{ __('messages.accountants') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Nurses', $modules)
        @canroute('nurses.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('nurses*', 'admins*', 'users*', 'accountants*', 'lab-technicians*', 'receptionists*', 'pharmacists*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('nurses*') ? 'active' : '' }}" href="{{ route('nurses.index') }}">
                {{ __('messages.nurses') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
{{-- <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ (!Request::is('receptionists*')) ? 'd-none' : '' }}" --}}
{{-- > --}}
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Receptionists', $modules)
        @canroute('receptionists.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('receptionists*', 'users*', 'admins*', 'accountants*', 'nurses*', 'lab-technicians*', 'pharmacists*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('receptionists*') ? 'active' : '' }}"
                href="{{ route('receptionists.index') }}">
                {{ __('messages.receptionists') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Lab Technicians', $modules)
        @canroute('lab-technicians.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('lab-technicians*', 'admins*', 'users*', 'accountants*', 'nurses*', 'receptionists*', 'pharmacists*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('lab-technicians*') ? 'active' : '' }}"
                href="{{ route('lab-technicians.index') }}">
                {{ __('messages.lab_technicians') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin') || hasCustomRole())
    @module('Pharmacists', $modules)
        @canroute('pharmacists.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('pharmacists*', 'users*', 'admins*', 'accountants*', 'nurses*', 'lab-technicians*', 'receptionists*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('pharmacists*') ? 'active' : '' }}"
                href="{{ route('pharmacists.index') }}">
                {{ __('messages.pharmacists') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@role('Nurse')
    @module('Doctors', $modules)
        @canroute('doctors.index')
        <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('doctors*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('doctors*') ? 'active' : '' }}" href="{{ route('doctors.index') }}">
                {{ __('messages.doctors') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endrole
@role('Nurse')
    @module('Prescriptions', $modules)
        @canroute('prescriptions.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('prescriptions*', 'prescription-medicine-show*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('prescriptions*', 'prescription-medicine-show*') ? 'active' : '' }}"
                href="{{ route('prescriptions.index') }}">{{ __('messages.prescriptions') }}</a>
        </li>
        @endcanroute
    @endmodule
@endrole
@module('Appointments', $modules)
    @canroute('appointments.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('appointments*', 'appointment-calendars', 'appointments-transaction*', 'patient-queue*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('appointments*') && !Request::is('appointments-transaction*') ? 'active' : '' }}"
            href="{{ route('appointments.index') }}">
            {{ __('messages.appointments') }}
        </a>
    </li>
    @endcanroute
    @canroute('appointments-transaction.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('appointments*', 'appointment-calendars', 'appointments-transaction*', 'patient-queue*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('appointments-transaction*') ? 'active' : '' }}"
            href="{{ route('appointments-transaction.index') }}">
            {{ __('messages.common.appointment_transaction') }}
        </a>
    </li>
    @endcanroute
    @canroute('patient.queue.index')
    <li
        class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('appointments*', 'appointment-calendars', 'appointments-transaction*', 'patient-queue*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('patient-queue*') ? 'active' : '' }}"
            href="{{ route('patient.queue.index') }}">
            Patient Queue
        </a>
    </li>
    @endcanroute
@endmodule
@if (Auth::user()->hasRole('Admin|Doctor|Nurse|Patient') || hasCustomRole())
    @module('Birth Reports', $modules)
        @canroute('birth-reports.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('birth-reports*', 'death-reports*', 'investigation-reports*', 'operation-reports*', 'employee/patient-diagnosis-test*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('birth-reports*') ? 'active' : '' }}"
                href="{{ route('birth-reports.index') }}">
                {{ __('messages.birth_reports') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Doctor|Nurse|Patient') || hasCustomRole())
    @module('Death Reports', $modules)
        @canroute('death-reports.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('birth-reports*', 'death-reports*', 'investigation-reports*', 'operation-reports*', 'employee/patient-diagnosis-test*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('death-reports*') ? 'active' : '' }}"
                href="{{ route('death-reports.index') }}">
                {{ __('messages.death_reports') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Doctor|Nurse|Patient') || hasCustomRole())
    @module('Investigation Reports', $modules)
        @canroute('investigation-reports.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('birth-reports*', 'death-reports*', 'investigation-reports*', 'operation-reports*', 'employee/patient-diagnosis-test*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('investigation-reports*') ? 'active' : '' }}"
                href="{{ route('investigation-reports.index') }}">
                {{ __('messages.investigation_reports') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Doctor|Nurse|Patient') || hasCustomRole())
    @module('Operation Reports', $modules)
        @canroute('operation-reports.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('birth-reports*', 'death-reports*', 'investigation-reports*', 'operation-reports*', 'employee/patient-diagnosis-test*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('operation-reports*') ? 'active' : '' }}"
                href="{{ route('operation-reports.index') }}">
                {{ __('messages.operation_reports') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician') || hasCustomRole())
    @module('Medicine Categories', $modules)
        @canroute('categories.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('categories*') ? 'active' : '' }}"
                href="{{ route('categories.index') }}">
                {{ __('messages.medicine_categories') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician') || hasCustomRole())
    @module('Medicine Brands', $modules)
        @canroute('brands.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('brands*') ? 'active' : '' }}" href="{{ route('brands.index') }}">
                {{ __('messages.medicine_brands') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician|Nurse') || hasCustomRole())
    @module('Medicines', $modules)
        @canroute('medicines.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('medicines*') ? 'active' : '' }}" href="{{ route('medicines.index') }}">
                {{ __('messages.medicines') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif
@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician|Nurse') || hasCustomRole())
    @module('Medicines', $modules)
        @canroute('medicine-purchase.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('medicine-purchase*') ? 'active' : '' }}"
                href="{{ route('medicine-purchase.index') }}">
                {{ __('messages.purchase_medicine.purchase_medicine') }}
            </a>
        </li>
        @endcanroute
    @endmodule
@endif

@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician') || hasCustomRole())
    @module('Medicines', $modules)
        @canroute('used-medicine.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('used-medicine*') ? 'active' : '' }}"
                href="{{ route('used-medicine.index') }}">{{ __('messages.used_medicine.used_medicine') }}</a>
        </li>
        @endcanroute
    @endmodule
@endif

@if (Auth::user()->hasRole('Admin|Pharmacist|Lab Technician') || hasCustomRole())
    @module('Medicines', $modules)
        @canroute('medicine-bills.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('categories*', 'brands*', 'medicines*', 'medicine-purchase*', 'used-medicine*', 'medicine-bills*') ? 'd-none' : '' }}">
            <a class="nav-link p-0 {{ Request::is('medicine-bills*') ? 'active' : '' }}"
                href="{{ route('medicine-bills.index') }}">
                {{ __('messages.medicine_bills.medicine_bill') }}
            </a>
        </li>
        @endcanroute
        @endmodule @endif

        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Radiology Categories', $modules)
                @canroute('radiology.category.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ !Request::is('radiology-categories*', 'radiology-tests*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('radiology-categories*') ? 'active' : '' }}"
                        href="{{ route('radiology.category.index') }}">
                        {{ __('messages.radiology_category.radiology_categories') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist|Pharmacist|Lab Technician') || hasCustomRole())
            @module('Radiology Tests', $modules)
                @canroute('radiology.test.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('radiology-categories*', 'radiology-tests*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('radiology-tests*') ? 'active' : '' }}"
                        href="{{ route('radiology.test.index') }}">
                        {{ __('messages.radiology_tests') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Pathology Categories', $modules)
                @canroute('pathology.category.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('pathology-categories*', 'pathology-tests*', 'pathology-units*', 'pathology-parameters*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('pathology-categories*') ? 'active' : '' }}"
                        href="{{ route('pathology.category.index') }}">
                        {{ __('messages.pathology_category.pathology_categories') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
            @module('Pathology Units', $modules)
                @canroute('pathology.unit.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('pathology-categories*', 'pathology-tests*', 'pathology-units*', 'pathology-parameters*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('pathology-units*') ? 'active' : '' }}"
                        href="{{ route('pathology.unit.index') }}">
                        {{ __('messages.new_change.pathology_units') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
            @module('Pathology Parameters', $modules)
                @canroute('pathology.parameter.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('pathology-categories*', 'pathology-tests*', 'pathology-units*', 'pathology-parameters*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('pathology-parameters*') ? 'active' : '' }}"
                        href="{{ route('pathology.parameter.index') }}">
                        {{ __('messages.new_change.pathology_parameters') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist|Pharmacist|Lab Technician') || hasCustomRole())
            @module('Pathology Tests', $modules)
                @canroute('pathology.test.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('pathology-categories*', 'pathology-tests*', 'pathology-units*', 'pathology-parameters*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('pathology-tests*') ? 'active' : '' }}"
                        href="{{ route('pathology.test.index') }}">
                        {{ __('messages.pathology_tests') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Doctor|Receptionist|Lab Technician') || hasCustomRole())
            @module('Diagnosis Categories', $modules)
                @canroute('diagnosis.category.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('diagnosis-categories*', 'patient-diagnosis-test*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('diagnosis-categories*') ? 'active' : '' }}"
                        href="{{ route('diagnosis.category.index') }}">
                        {{ __('messages.diagnosis_category.diagnosis_categories') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Doctor|Receptionist|Lab Technician|Nurse') || hasCustomRole())
            @module('Diagnosis Tests', $modules)
                @canroute('patient.diagnosis.test.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('diagnosis-categories*', 'patient-diagnosis-test*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient-diagnosis-test*') ? 'active' : '' }}"
                        href="{{ route('patient.diagnosis.test.index') }}">
                        {{ __('messages.patient_diagnosis_test.diagnosis_test') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Doctor|Accountant|Case Manager|Receptionist|Pharmacist') || hasCustomRole())
            @module('SMS', $modules)
                @canroute('sms.index')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('sms*', 'mail*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('sms*') ? 'active' : '' }}" href="{{ route('sms.index') }}">
                        {{ __('messages.sms.sms') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Case Manager|Receptionist') || hasCustomRole())
            @module('Mail', $modules)
                @canroute('mail')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('sms*', 'mail*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('mail*') ? 'active' : '' }}" href="{{ route('mail') }}">
                        {{ __('messages.mail') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif

        @if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
            @module('Income', $modules)
                @canroute('incomes.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ !Request::is('incomes*', 'expenses*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('incomes*') ? 'active' : '' }}" href="{{ route('incomes.index') }}">
                        {{ __('messages.income') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Accountant') || hasCustomRole())
            @module('Expense', $modules)
                @canroute('expenses.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('incomes*', 'expenses*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('expenses*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                        {{ __('messages.expenses') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @module('Items Categories', $modules)
                @canroute('item-categories.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('item-categories*', 'items*', 'item-stocks*', 'issued-items*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('item-categories*') ? 'active' : '' }}"
                        href="{{ route('item-categories.index') }}">
                        {{ __('messages.items_categories') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @module('Items', $modules)
                @canroute('items.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('item-categories*', 'items*', 'item-stocks*', 'issued-items*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('items*') ? 'active' : '' }}" href="{{ route('items.index') }}">
                        {{ __('messages.items') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @module('Item Stocks', $modules)
                @canroute('item.stock.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('item-categories*', 'items*', 'item-stocks*', 'issued-items*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('item-stocks*') ? 'active' : '' }}"
                        href="{{ route('item.stock.index') }}">
                        {{ __('messages.items_stocks') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @module('Issued Items', $modules)
                @canroute('issued.item.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('item-categories*', 'items*', 'item-stocks*', 'issued-items*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('issued-items*') ? 'active' : '' }}"
                        href="{{ route('issued.item.index') }}">
                        {{ __('messages.issued_items') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Charge Categories', $modules)
                @canroute('charge-categories.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('charge-categories*', 'charges*', 'doctor-opd-charges*', 'price-lists*', 'tariffs*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('charge-categories*') ? 'active' : '' }}"
                        href="{{ route('charge-categories.index') }}">
                        {{ __('messages.charge_categories') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Charges', $modules)
                @canroute('charges.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('charge-categories*', 'charges*', 'doctor-opd-charges*', 'price-lists*', 'tariffs*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('charges*') ? 'active' : '' }}" href="{{ route('charges.index') }}">
                        {{ __('messages.charges') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Doctor OPD Charges', $modules)
                @canroute('doctor-opd-charges.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('charge-categories*', 'charges*', 'doctor-opd-charges*', 'price-lists*', 'tariffs*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('doctor-opd-charges*') ? 'active' : '' }}"
                        href="{{ route('doctor-opd-charges.index') }}">
                        {{ __('messages.doctor_opd_charges') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @module('Price Lists', $modules)
            @canroute('price-lists.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('charge-categories*', 'charges*', 'doctor-opd-charges*', 'price-lists*', 'tariffs*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('price-lists*', 'tariffs*') ? 'active' : '' }}" href="{{ route('price-lists.index') }}">
                    {{ __('messages.price_list.price_lists') }}
                </a>
            </li>
            @endcanroute
        @endmodule
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Call Logs', $modules)
                @canroute('call_logs.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('call-logs*', 'visitor*', 'receives*', 'dispatches*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('call-logs*') ? 'active' : '' }}" href="{{ route('call_logs.index') }}">
                        {{ __('messages.call_logs') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Visitors', $modules)
                @canroute('visitors.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('call-logs*', 'visitor*', 'receives*', 'dispatches*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('visitors*') ? 'active' : '' }}" href="{{ route('visitors.index') }}">
                        {{ __('messages.visitors') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Postal Receive', $modules)
                @canroute('receives.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('call-logs*', 'visitor*', 'receives*', 'dispatches*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('receives*') ? 'active' : '' }}" href="{{ route('receives.index') }}">
                        {{ __('messages.postal_receive') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Postal Dispatch', $modules)
                @canroute('dispatches.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('call-logs*', 'visitor*', 'receives*', 'dispatches*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('dispatches*') ? 'active' : '' }}"
                        href="{{ route('dispatches.index') }}">
                        {{ __('messages.postal_dispatch') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Doctor|Patient') || hasCustomRole())
            @module('Live Consultations', $modules)
                @canroute('live.consultation.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('live-consultation*', 'live-meeting*', 'google-meet*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('live-consultation*') ? 'active' : '' }}"
                        href="{{ route('live.consultation.index') }}">
                        {{ __('messages.live_consultations') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @if (Auth::user()->hasRole('Admin|Doctor|Accountant|Case Manager|Receptionist|Pharmacist|Lab Technician|Nurse') || hasCustomRole())
            @module('Live Meetings', $modules)
                @canroute('live.meeting.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ !Request::is('live-consultation*', 'live-meeting*', 'google-meet*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('live-meeting*') ? 'active' : '' }}"
                        href="{{ route('live.meeting.index') }}">
                        {{ __('messages.live_meetings') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @php($sectionName = Request::get('section') === null && !Request::is('hospital-schedules') && !Request::is('currency-settings') && !Request::is('operation-categories') && !Request::is('operations') && !Request::is('queue-theme-configuration') && !Request::is('payment-gateways') && !Request::is('add-custom-fields') ? 'general' : Request::get('section'))
        @canroute('settings.edit')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
            <a class="nav-link p-0  {{ isset($sectionName) && $sectionName == 'general' ? 'active' : '' }}"
                href="{{ route('settings.edit', ['section' => 'general']) }}">
                {{ __('messages.general') }}
            </a>
        </li>
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
            <a class="nav-link p-0  {{ isset($sectionName) && $sectionName == 'reception' ? 'active' : '' }}"
                href="{{ route('settings.edit', ['section' => 'reception']) }}">
                {{ __('messages.reception.reception') }}
            </a>
        </li>
        @endcanroute
        @canroute('hospital-schedules.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
            <a class="nav-link p-0 {{ Request::is('hospital-schedules*') ? 'active' : '' }}"
                href="{{ route('hospital-schedules.index') }}">
                {{ __('messages.hospital_schedule') }}
            </a>
        </li>
        @endcanroute
        @canroute('settings.edit')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }} ">
            <a class="nav-link p-0 {{ isset($sectionName) && $sectionName == 'sidebar-setting' ? 'active' : '' }}"
                href="{{ route('settings.edit', ['section' => 'sidebar-setting']) }}">
                {{ __('messages.sidebar_setting') }}
            </a>
        </li>
        @endcanroute
        @canroute('currency-settings.index')
        <li
            class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0  {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }} ">
            <a class="nav-link p-0 {{ Request::is('currency-settings*') ? 'active' : '' }}"
                href="{{ route('currency-settings.index') }}">
                {{ __('messages.currency_setting') }}
            </a>
        </li>
        @endcanroute
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @canroute('front.settings.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*','complaints*') ? 'd-none' : '' }}">
                <a class="nav-link p-0  {{ Request::is('front-settings*') ? 'active' : '' }}"
                    href="{{ route('front.settings.index') }}">
                    {{ __('messages.cms') }}
                </a>
            </li>
            @endcanroute
            @canroute('front.cms.services.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*','complaints*') ? 'd-none' : '' }}">
                <a class="nav-link p-0  {{ Request::is('front-cms-services*') ? 'active' : '' }}"
                    href="{{ route('front.cms.services.index') }}">
                    {{ __('messages.front_cms_services') }}
                </a>
            </li>
            @endcanroute
            @module('Notice Boards', $modules)
                @canroute('noticeboard')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*','complaints*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('notice-boards*') ? 'active' : '' }}" href="{{ url('notice-boards') }}">
                        {{ __('messages.notice_boards') }}
                    </a>
                </li>
                @endcanroute
            @endmodule

            @module('Testimonial', $modules)
                @canroute('testimonials.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*','complaints*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('testimonials*') ? 'active' : '' }}"
                        href="{{ route('testimonials.index') }}">
                        {{ __('messages.testimonials') }}
                    </a>
                </li>
                @endcanroute
            @endmodule

            @canroute('complaints.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*','complaints*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('complaints*') ? 'active' : '' }}"
                    href="{{ route('complaints.index') }}">
                    {{ __('messages.complaint') }}
                </a>
            </li>
            @endcanroute
        @endif
        
        @role('Receptionist')
            @module('Testimonial', $modules)
                @canroute('testimonials.index')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('front-settings*', 'notice-boards*', 'testimonials*', 'front-cms-services*', 'terms-and-conditions*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('testimonials*') ? 'active' : '' }}"
                        href="{{ route('testimonials.index') }}">
                        {{ __('messages.testimonials') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
            @canroute('complaints.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('complaints*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('complaints*') ? 'active' : '' }}"
                    href="{{ route('complaints.index') }}">
                    {{ __('messages.complaint') }}
                </a>
            </li>
            @endcanroute
        @endrole

        {{-- <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ (!Request::is('enquiries*','enquiry*')) ? 'd-none' : '' }}" --}}
        {{-- > --}}
        @if (Auth::user()->hasRole('Admin|Receptionist') || hasCustomRole())
            @module('Enquires', $modules)
                @canroute('enquiries')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('enquiries*', 'enquiry*') ? '' : 'd-none' }}">
                    <a class="nav-link p-0  {{ Request::is('enquiries*') || Request::is('enquiry*') ? 'active' : '' }}"
                        href="{{ route('enquiries') }}">
                        {{ __('messages.enquiries') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endif
        @role('Doctor')
            @module('Doctors', $modules)
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/doctor*', 'doctors*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/doctor*') ? 'active' : '' }}"
                        href="{{ url('employee/doctor') }}">
                        {{ __('messages.doctors') }}
                    </a>
                </li>
            @endmodule
            @module('Schedules', $modules)
                @canroute('schedules.edit')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('schedules*', 'holidays*', 'breaks*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('schedules*') ? 'active' : '' }}"
                        href="{{ route('schedules.edit', getDoctorSchedule()) }}">
                        {{ __('messages.schedules') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
            @module('Prescriptions', $modules)
                @canroute('prescriptions.index')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('prescriptions*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('prescriptions*') ? 'active' : '' }}"
                        href="{{ route('prescriptions.index') }}">
                        {{ __('messages.prescriptions') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endrole
        @role('Doctor|Accountant|Case Manager|Receptionist|Pharmacist|Lab Technician|Nurse|Patient')
            @module('Notice Boards', $modules)
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/notice-board*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/notice-board*') ? 'active' : '' }}"
                        href="{{ url('employee/notice-board') }}">
                        {{ __('messages.notice_boards') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @role('Doctor|Accountant|Case Manager|Receptionist|Pharmacist|Lab Technician|Nurse')
            @module('My Payrolls', $modules)
                @canroute('payroll')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/payroll*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/payroll*') ? 'active' : '' }}" href="{{ route('payroll') }}">
                        {{ __('messages.my_payrolls') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endrole
        @role('Patient')
            @canroute('patient.dashboard')
            <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient-dashboard*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('patient-dashboard*') ? 'active' : '' }}"
                    href="{{ route('patient.dashboard') }}">
                    {{ __('messages.dashboard.dashboard') }}
                </a>
            </li>
            @endcanroute

            @module('Patient Cases', $modules)
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient/my-cases*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient/my-cases*') ? 'active' : '' }}"
                        href="{{ url('patient/my-cases') }}">
                        {{ __('messages.patients_cases') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @role('Patient')
            @canroute('complaints.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('complaints*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('complaints*') ? 'active' : '' }}"
                    href="{{ route('complaints.index') }}">
                    {{ __('messages.complaint') }}
                </a>
            </li>
            @endcanroute
        @endrole
        @role('Patient')
            @canroute('patient.smart.card.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient-smart-card*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('patient-smart-card*') ? 'active' : '' }}"
                    href="{{ route('patient.smart.card.index') }}">
                    {{ __('messages.patient_id_card.patient_id_card') }}
                </a>
            </li>
            @endcanroute
        @endrole
        @role('Patient')
            @module('Patient Admissions', $modules)
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/patient-admissions*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/patient-admissions*') ? 'active' : '' }}"
                        href="{{ url('employee/patient-admissions') }}">
                        {{ __('messages.patient_admissions') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @role('Patient')
            @module('Prescriptions', $modules)
                @canroute('prescriptions.list')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient/my-prescriptions*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient/my-prescriptions*') ? 'active' : '' }}"
                        href="{{ route('prescriptions.list') }}">
                        {{ __('messages.prescriptions') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endrole
        @role('Patient')
            @module('Vaccinated Patients', $modules)
                @canroute('patient.vaccinated')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient/my-vaccinated*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient/my-vaccinated*') ? 'active' : '' }}"
                        href="{{ route('patient.vaccinated') }}">
                        {{ __('messages.vaccinated_patients') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endrole
        @role('Patient')
            @module('IPD Patients', $modules)
                @canroute('patient.ipd')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('patient/my-ipds*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient/my-ipds*') ? 'active' : '' }}"
                        href="{{ route('patient.ipd') }}">
                        {{ __('messages.ipd_patients') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
            @module('OPD Patients', $modules)
                @canroute('patient.opd')
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('opds*', 'patient/my-opds*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('patient/my-opds*') ? 'active' : '' }}"
                        href="{{ route('patient.opd') }}">
                        {{ __('messages.opd_patients') }}
                    </a>
                </li>
                @endcanroute
            @endmodule
        @endrole
        @role('Patient')
            @module('Diagnosis Tests', $modules)
                <li
                    class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('birth-reports*', 'death-reports*', 'investigation-reports*', 'operation-reports*', 'employee/patient-diagnosis-test*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/patient-diagnosis-test*') ? 'active' : '' }}"
                        href="{{ url('employee/patient-diagnosis-test') }}">
                        {{ __('messages.patient_diagnosis_test.diagnosis_test') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @role('Patient')
            @module('Invoices', $modules)
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/invoices*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/invoices*') ? 'active' : '' }}"
                        href="{{ url('employee/invoices') }}">
                        {{ __('messages.invoices') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @role('Patient')
            @module('Bills', $modules)
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('employee/bills*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('employee/bills*') ? 'active' : '' }}"
                        href="{{ url('employee/bills') }}">
                        {{ __('messages.bills') }}
                    </a>
                </li>
            @endmodule
        @endrole
        @module('Operation Categories', $modules)
            @canroute('operation.category.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
                <a class="nav-link p-0 {{ Request::is('operation-categories*') ? 'active' : '' }}"
                    href="{{ route('operation.category.index') }}">
                    {{ __('messages.operation_category.operation_categories') }}
                </a>
            </li>
            @endcanroute
        @endmodule

        @module('Operation', $modules)
            @canroute('operations.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
                <a class="nav-link p-0 {{ Request::is('operations*') ? 'active' : '' }}"
                    href="{{ route('operations.index') }}">
                    {{ __('messages.operations') }}
                </a>
            </li>
            @endcanroute
        @endmodule
        @if (Auth::user()->hasRole('Admin') || hasCustomRole())
            @canroute('payment-gateways.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
                <a class="nav-link p-0 {{ Request::is('payment-gateways*') ? 'active' : '' }}"
                    href="{{ route('payment-gateways.index') }}">
                    {{ __('messages.payment_gateways') }}
                </a>
            </li>
            @endcanroute
            @canroute('add-custom-fields.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
                <a class="nav-link p-0 {{ Request::is('add-custom-fields*') ? 'active' : '' }}"
                    href="{{ route('add-custom-fields.index') }}">
                    {{ __('messages.custom_field.custom_field') }}
                </a>
            </li>
            @endcanroute
            @canroute('queue-theme-view')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ Request::is('settings*', 'currency-settings*', 'hospital-schedules', 'operation-categories*', 'operations*', 'payment-gateways*', 'add-custom-fields*', 'queue-theme*') ? '' : 'd-none' }}">
                <a class="nav-link p-0 {{ Request::is('queue-theme*') ? 'active' : '' }}"
                    href="{{ route('queue-theme-view') }}">
                    {{ __('messages.queue.patient_queue_theme') }}
                </a>
            </li>
            @endcanroute
        @endif
        @role('Doctor')
            @canroute('googlemeet.consultations.index')
            <li
                class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('live-consultation*', 'live-meeting*', 'google-meet*') ? 'd-none' : '' }}">
                <a class="nav-link p-0 {{ Request::is('google-meet*') ? 'active' : '' }}"
                    href="{{ route('googlemeet.consultations.index') }}">
                    {{ __('messages.google_meet.connect_calendar') }}
                </a>
            </li>
            @endcanroute
        @endrole
        @if (Auth::user()->hasRole('Admin|Patient|Doctor') || hasCustomRole())
            {{-- <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('appointments*', 'appointment-calendars', 'appointments-transaction*') ? 'd-none' : '' }}">
        <a class="nav-link p-0 {{ Request::is('appointments-transaction*')  ? 'active' : '' }}"
            href="{{ route('appointments-transaction.index') }}">
            {{ __('messages.common.appointment_transaction') }}
        </a>
    </li> --}}
        @endif
        @role('Doctor|Accountant|Case Manager|Receptionist|Pharmacist|Lab Technician|Nurse|Patient')
            @if (moduleExists('Holiday'))
                @canroute('calendar.view')
                <li class="nav-item position-relative mx-xl-3 mb-3 mb-xl-0 {{ !Request::is('calendar-view*') ? 'd-none' : '' }}">
                    <a class="nav-link p-0 {{ Request::is('calendar-view*') ? 'active' : '' }}"
                        href="{{ route('calendar.view') }}">
                        {{ __('holiday::messages.holidays') }}
                    </a>
                </li>
                @endcanroute
            @endif
        @endrole
