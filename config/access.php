<?php

use App\Http\Controllers;

/*
|--------------------------------------------------------------------------
| Access control catalogue (Settings > Roles and Permissions)
|--------------------------------------------------------------------------
|
| Every controller behind the "access" route middleware belongs to one module,
| and a permission is "<module>.<action>". Which role holds which permission is
| set by the admin on the Roles screen and stored in the database.
|
| The role list written on a route (access:Admin|Doctor) is only the default:
| `php artisan permissions:sync` grants it once, when that permission is first
| created. A route whose default list contains every staff role is open to all
| staff (and to patients too when Patient is listed) and needs no permission.
|
| How a method maps to an action is decided in App\Utils\RouteAccess::action().
| Add an entry to "methods" only when that rule would file a method wrongly.
|
*/

return [

    // Columns on the Roles screen, in this order.
    'actions' => ['list', 'view', 'create', 'edit', 'delete', 'export', 'download'],

    // Groups on the Roles screen, in this order.
    'groups' => ['dashboard', 'patients', 'appointments', 'opd', 'ipd', 'doctors', 'staff', 'billing', 'finance', 'charges', 'services', 'pharmacy', 'laboratory', 'radiology', 'diagnosis', 'beds', 'blood_bank', 'inventory', 'reports', 'documents', 'front_office', 'communication', 'live_consultation', 'vaccination', 'front_cms', 'self_service', 'patient_portal', 'settings'],

    // Module => group. Modules appear on the Roles screen in this order.
    'modules' => [
        // dashboard
        'dashboard' => 'dashboard',

        // patients
        'reception_desk' => 'patients',
        'patients' => 'patients',
        'patient_registrations' => 'patients',
        'patient_insurances' => 'patients',
        'patient_cases' => 'patients',
        'case_handlers' => 'patients',
        'patient_admissions' => 'patients',
        'patient_id_cards' => 'patients',
        'patient_id_card_templates' => 'patients',
        'odontograms' => 'patients',

        // appointments
        'appointments' => 'appointments',
        'appointment_calendar' => 'appointments',
        'appointment_transactions' => 'appointments',
        'patient_queues' => 'appointments',

        // opd
        'opd_patients' => 'opd',
        'opd_diagnoses' => 'opd',
        'opd_prescriptions' => 'opd',
        'opd_timelines' => 'opd',

        // ipd
        'ipd_patients' => 'ipd',
        'ipd_diagnoses' => 'ipd',
        'ipd_consultant_registers' => 'ipd',
        'ipd_charges' => 'ipd',
        'ipd_prescriptions' => 'ipd',
        'ipd_operations' => 'ipd',
        'ipd_timelines' => 'ipd',
        'ipd_payments' => 'ipd',
        'ipd_bills' => 'ipd',

        // doctors
        'doctors' => 'doctors',
        'doctor_departments' => 'doctors',
        'schedules' => 'doctors',
        'doctor_holidays' => 'doctors',
        'lunch_breaks' => 'doctors',
        'prescriptions' => 'doctors',

        // staff
        'users' => 'staff',
        'admins' => 'staff',
        'nurses' => 'staff',
        'receptionists' => 'staff',
        'pharmacists' => 'staff',
        'accountants' => 'staff',
        'lab_technicians' => 'staff',
        'departments' => 'staff',

        // billing
        'cashier' => 'billing',
        'cash_counters' => 'billing',
        'receipts' => 'billing',
        'cash_journal' => 'billing',
        'exam_orders' => 'laboratory',
        'accounts' => 'billing',
        'invoices' => 'billing',
        'bills' => 'billing',
        'payments' => 'billing',
        'payment_reports' => 'billing',
        'advanced_payments' => 'billing',
        'manual_bill_payments' => 'billing',
        'employee_payrolls' => 'billing',

        // finance
        'incomes' => 'finance',
        'expenses' => 'finance',

        // charges
        'charge_categories' => 'charges',
        'charges' => 'charges',
        'doctor_opd_charges' => 'charges',
        'price_lists' => 'charges',

        // services
        'insurances' => 'services',
        'packages' => 'services',
        'services' => 'services',
        'ambulances' => 'services',
        'ambulance_calls' => 'services',

        // pharmacy
        'medicines' => 'pharmacy',
        'medicine_categories' => 'pharmacy',
        'medicine_brands' => 'pharmacy',
        'purchase_medicines' => 'pharmacy',
        'medicine_bills' => 'pharmacy',

        // laboratory
        'pathology_categories' => 'laboratory',
        'pathology_tests' => 'laboratory',
        'pathology_units' => 'laboratory',
        'pathology_parameters' => 'laboratory',

        // radiology
        'radiology_categories' => 'radiology',
        'radiology_tests' => 'radiology',

        // diagnosis
        'diagnosis_categories' => 'diagnosis',
        'diagnosis_tests' => 'diagnosis',

        // beds
        'bed_types' => 'beds',
        'beds' => 'beds',
        'bed_assigns' => 'beds',

        // blood_bank
        'blood_banks' => 'blood_bank',
        'blood_donors' => 'blood_bank',
        'blood_donations' => 'blood_bank',
        'blood_issues' => 'blood_bank',

        // inventory
        'item_categories' => 'inventory',
        'items' => 'inventory',
        'item_stocks' => 'inventory',
        'issued_items' => 'inventory',

        // reports
        'birth_reports' => 'reports',
        'death_reports' => 'reports',
        'investigation_reports' => 'reports',
        'operation_reports' => 'reports',

        // documents
        'documents' => 'documents',
        'document_types' => 'documents',

        // front_office
        'enquiries' => 'front_office',
        'call_logs' => 'front_office',
        'visitors' => 'front_office',
        'postals' => 'front_office',
        'complaints' => 'front_office',
        'complaint_responses' => 'front_office',

        // communication
        'notice_boards' => 'communication',
        'sms' => 'communication',
        'mails' => 'communication',
        'notifications' => 'communication',

        // live_consultation
        'live_consultations' => 'live_consultation',
        'live_meetings' => 'live_consultation',
        'google_meet' => 'live_consultation',

        // vaccination
        'vaccinated_patients' => 'vaccination',
        'vaccinations' => 'vaccination',

        // front_cms
        'front_settings' => 'front_cms',
        'front_services' => 'front_cms',
        'testimonials' => 'front_cms',

        // self_service
        'my_notice_board' => 'self_service',
        'my_payrolls' => 'self_service',
        'doctor_directory' => 'self_service',
        'pharmacist_prescriptions' => 'self_service',
        'my_bills' => 'self_service',
        'my_invoices' => 'self_service',
        'my_admissions' => 'self_service',
        'my_diagnosis_tests' => 'self_service',

        // patient_portal
        'my_cases' => 'patient_portal',
        'my_prescriptions' => 'patient_portal',
        'my_ipd' => 'patient_portal',
        'my_opd' => 'patient_portal',
        'my_vaccinations' => 'patient_portal',

        // settings
        'settings' => 'settings',
        'roles' => 'settings',
        'master_lists' => 'settings',
        'audit_logs' => 'settings',
        'email_templates' => 'settings',
        'hospital_schedules' => 'settings',
        'currency_settings' => 'settings',
        'payment_gateways' => 'settings',
        'custom_fields' => 'settings',
        'operation_categories' => 'settings',
        'operations' => 'settings',
        'add_ons' => 'settings',
        'system_logs' => 'settings',
    ],

    // Controller => module.
    'controllers' => [
        Controllers\HomeController::class => 'dashboard',
        Controllers\PatientController::class => 'patients',
        Controllers\ReceptionDeskController::class => 'reception_desk',
        Controllers\PatientRegistrationReportController::class => 'patient_registrations',
        Controllers\PatientInsuranceController::class => 'patient_insurances',
        Controllers\PatientCaseController::class => 'patient_cases',
        Controllers\CaseHandlerController::class => 'case_handlers',
        Controllers\PatientAdmissionController::class => 'patient_admissions',
        Controllers\GeneratePatientIdCardController::class => 'patient_id_cards',
        Controllers\PatientIdCardTemplateController::class => 'patient_id_card_templates',
        Controllers\OdontogramController::class => 'odontograms',
        Controllers\AppointmentController::class => 'appointments',
        Controllers\AppointmentCalendarController::class => 'appointment_calendar',
        Controllers\AppointmentTransactionController::class => 'appointment_transactions',
        Controllers\PatientQueueController::class => 'patient_queues',
        Controllers\OpdPatientDepartmentController::class => 'opd_patients',
        Controllers\OpdDiagnosisController::class => 'opd_diagnoses',
        Controllers\OpdPrescriptionController::class => 'opd_prescriptions',
        Controllers\OpdTimelineController::class => 'opd_timelines',
        Controllers\IpdPatientDepartmentController::class => 'ipd_patients',
        Controllers\IpdDiagnosisController::class => 'ipd_diagnoses',
        Controllers\IpdConsultantRegisterController::class => 'ipd_consultant_registers',
        Controllers\IpdChargeController::class => 'ipd_charges',
        Controllers\IpdPrescriptionController::class => 'ipd_prescriptions',
        Controllers\IpdOperationController::class => 'ipd_operations',
        Controllers\IpdTimelineController::class => 'ipd_timelines',
        Controllers\IpdPaymentController::class => 'ipd_payments',
        Controllers\IpdBillController::class => 'ipd_bills',
        Controllers\DoctorController::class => 'doctors',
        Controllers\DoctorDepartmentController::class => 'doctor_departments',
        Controllers\ScheduleController::class => 'schedules',
        Controllers\DoctorHolidayController::class => 'doctor_holidays',
        Controllers\LunchBreakController::class => 'lunch_breaks',
        Controllers\PrescriptionController::class => 'prescriptions',
        Controllers\UserController::class => 'users',
        Controllers\adminController::class => 'admins',
        Controllers\NurseController::class => 'nurses',
        Controllers\ReceptionistController::class => 'receptionists',
        Controllers\PharmacistController::class => 'pharmacists',
        Controllers\AccountantController::class => 'accountants',
        Controllers\LabTechnicianController::class => 'lab_technicians',
        Controllers\DepartmentController::class => 'departments',
        Controllers\AccountController::class => 'accounts',
        Controllers\InvoiceController::class => 'invoices',
        Controllers\BillController::class => 'bills',
        Controllers\PaymentController::class => 'payments',
        Controllers\PaymentReportController::class => 'payment_reports',
        Controllers\AdvancedPaymentController::class => 'advanced_payments',
        Controllers\ManualBillPaymentController::class => 'manual_bill_payments',
        Controllers\EmployeePayrollController::class => 'employee_payrolls',
        Controllers\IncomeController::class => 'incomes',
        Controllers\ExpenseController::class => 'expenses',
        Controllers\ChargeCategoryController::class => 'charge_categories',
        Controllers\ChargeController::class => 'charges',
        Controllers\DoctorOPDChargeController::class => 'doctor_opd_charges',
        Controllers\PriceListController::class => 'price_lists',
        Controllers\CashierController::class => 'cashier',
        Controllers\CashCounterController::class => 'cash_counters',
        Controllers\ReceiptController::class => 'receipts',
        Controllers\CashJournalController::class => 'cash_journal',
        Controllers\ExamOrderController::class => 'exam_orders',
        Controllers\InsuranceController::class => 'insurances',
        Controllers\PackageController::class => 'packages',
        Controllers\ServiceController::class => 'services',
        Controllers\AmbulanceController::class => 'ambulances',
        Controllers\AmbulanceCallController::class => 'ambulance_calls',
        Controllers\MedicineController::class => 'medicines',
        Controllers\CategoryController::class => 'medicine_categories',
        Controllers\BrandController::class => 'medicine_brands',
        Controllers\PurchaseMedicineController::class => 'purchase_medicines',
        Controllers\MedicineBillController::class => 'medicine_bills',
        Controllers\PathologyCategoryController::class => 'pathology_categories',
        Controllers\PathologyTestController::class => 'pathology_tests',
        Controllers\PathologyUnitController::class => 'pathology_units',
        Controllers\PathologyParameterController::class => 'pathology_parameters',
        Controllers\RadiologyCategoryController::class => 'radiology_categories',
        Controllers\RadiologyTestController::class => 'radiology_tests',
        Controllers\DiagnosisCategoryController::class => 'diagnosis_categories',
        Controllers\PatientDiagnosisTestController::class => 'diagnosis_tests',
        Controllers\BedTypeController::class => 'bed_types',
        Controllers\BedController::class => 'beds',
        Controllers\BedAssignController::class => 'bed_assigns',
        Controllers\BloodBankController::class => 'blood_banks',
        Controllers\BloodDonorController::class => 'blood_donors',
        Controllers\BloodDonationController::class => 'blood_donations',
        Controllers\BloodIssueController::class => 'blood_issues',
        Controllers\ItemCategoryController::class => 'item_categories',
        Controllers\ItemController::class => 'items',
        Controllers\ItemStockController::class => 'item_stocks',
        Controllers\IssuedItemController::class => 'issued_items',
        Controllers\BirthReportController::class => 'birth_reports',
        Controllers\DeathReportController::class => 'death_reports',
        Controllers\InvestigationReportController::class => 'investigation_reports',
        Controllers\OperationReportController::class => 'operation_reports',
        Controllers\DocumentController::class => 'documents',
        Controllers\DocumentTypeController::class => 'document_types',
        Controllers\EnquiryController::class => 'enquiries',
        Controllers\CallLogController::class => 'call_logs',
        Controllers\VisitorController::class => 'visitors',
        Controllers\PostalController::class => 'postals',
        Controllers\ComplaintController::class => 'complaints',
        Controllers\NoticeBoardController::class => 'notice_boards',
        Controllers\SmsController::class => 'sms',
        Controllers\MailController::class => 'mails',
        Controllers\NotificationController::class => 'notifications',
        Controllers\LiveConsultationController::class => 'live_consultations',
        Controllers\LiveMeetingController::class => 'live_meetings',
        Controllers\GoogleMeetLiveConsultationController::class => 'google_meet',
        Controllers\VaccinatedPatientController::class => 'vaccinated_patients',
        Controllers\VaccinationController::class => 'vaccinations',
        Controllers\FrontSettingController::class => 'front_settings',
        Controllers\FrontServiceController::class => 'front_services',
        Controllers\TestimonialController::class => 'testimonials',
        Controllers\Employee\NoticeBoardController::class => 'my_notice_board',
        Controllers\Employee\PayrollController::class => 'my_payrolls',
        Controllers\Employee\DoctorController::class => 'doctor_directory',
        Controllers\Employee\PrescriptionController::class => 'pharmacist_prescriptions',
        Controllers\Employee\BillController::class => 'my_bills',
        Controllers\Employee\InvoiceController::class => 'my_invoices',
        Controllers\Employee\PatientAdmissionController::class => 'my_admissions',
        Controllers\Employee\PatientDiagnosisTestController::class => 'my_diagnosis_tests',
        Controllers\Patient\PatientCaseController::class => 'my_cases',
        Controllers\Patient\PrescriptionController::class => 'my_prescriptions',
        Controllers\Patient\IpdPatientDepartmentController::class => 'my_ipd',
        Controllers\Patient\OpdPatientDepartmentController::class => 'my_opd',
        Controllers\Patient\VaccinatedController::class => 'my_vaccinations',
        Controllers\SettingController::class => 'settings',
        Controllers\RoleController::class => 'roles',
        Controllers\MasterListController::class => 'master_lists',
        Controllers\AuditLogController::class => 'audit_logs',
        Controllers\EmailTemplateController::class => 'email_templates',
        Controllers\HospitalScheduleController::class => 'hospital_schedules',
        Controllers\CurrencySettingController::class => 'currency_settings',
        Controllers\PaymentGatewayController::class => 'payment_gateways',
        Controllers\AddCustomFieldsController::class => 'custom_fields',
        Controllers\OperationCategoryController::class => 'operation_categories',
        Controllers\OperationController::class => 'operations',
        Controllers\AddOnController::class => 'add_ons',
        \Rap2hpoutre\LaravelLogViewer\LogViewerController::class => 'system_logs',
    ],

    // "Controller@method" => [module, action] for methods the naming rules would misfile.
    // "support": form lookups and payment callbacks; allowed for the route's default roles
    //            and for any role that may create, edit or delete in the module.
    // "fixed":   allowed for the route's default roles only; not shown on the Roles screen.
    'methods' => [
        // Searching is what the desk is for: anyone who can open it can search.
        Controllers\ReceptionDeskController::class.'@getSearch' => ['reception_desk', 'list'],
        // The tariff grid is part of the price lists screen.
        Controllers\PriceListController::class.'@tariff' => ['price_lists', 'list'],
        // Working the register is the cashier's job: opening, collecting and closing go together; prints are reading.
        Controllers\CashierController::class.'@openSession' => ['cashier', 'create'],
        Controllers\CashierController::class.'@collect' => ['cashier', 'create'],
        Controllers\CashierController::class.'@closeSession' => ['cashier', 'create'],
        Controllers\CashierController::class.'@printReceipt' => ['cashier', 'list'],
        Controllers\CashierController::class.'@printSession' => ['cashier', 'list'],
        // Undoing money is a supervisor's action (delete); correcting the method is an edit.
        Controllers\ReceiptController::class.'@void' => ['receipts', 'delete'],
        Controllers\ReceiptController::class.'@refund' => ['receipts', 'delete'],
        Controllers\ReceiptController::class.'@changeMethod' => ['receipts', 'edit'],
        Controllers\CashJournalController::class.'@print' => ['cash_journal', 'list'],

        // Staff answer a complaint; only the patient edits it.
        Controllers\ComplaintController::class.'@responseEdit' => ['complaint_responses', 'edit'],
        Controllers\ComplaintController::class.'@updateStatusResponse' => ['complaint_responses', 'edit'],

        // Patient portal home, not the hospital dashboard.
        Controllers\HomeController::class.'@patientDashboard' => ['dashboard', 'fixed'],
        // Chart data for the dashboard page; a POST that only reads.
        Controllers\HomeController::class.'@dashboardChart' => ['dashboard', 'view'],

        // GET routes that change data.
        Controllers\PatientQueueController::class.'@changeStatus' => ['patient_queues', 'edit'],
        Controllers\PatientQueueController::class.'@checkOutIn' => ['patient_queues', 'edit'],
        Controllers\IssuedItemController::class.'@returnIssuedItem' => ['issued_items', 'edit'],
        Controllers\LiveConsultationController::class.'@getChangeStatus' => ['live_consultations', 'edit'],
        Controllers\GoogleMeetLiveConsultationController::class.'@disconnectGoogleCalendar' => ['google_meet', 'edit'],
        Controllers\GoogleMeetLiveConsultationController::class.'@syncGoogleCalendarList' => ['google_meet', 'edit'],

        // Data feeds of list and detail pages (named like lookups).
        Controllers\AppointmentCalendarController::class.'@calendarList' => ['appointment_calendar', 'list'],
        Controllers\AppointmentCalendarController::class.'@getAppointmentDetails' => ['appointment_calendar', 'view'],
        Controllers\PatientQueueController::class.'@refresh' => ['patient_queues', 'list'],
        Controllers\LiveConsultationController::class.'@getLiveStatus' => ['live_consultations', 'view'],

        // Form lookups whose names do not start with "get", or that serve another module's form.
        Controllers\AmbulanceController::class.'@isAvailableAmbulance' => ['ambulances', 'support'],
        Controllers\HospitalScheduleController::class.'@checkRecord' => ['hospital_schedules', 'support'],
        Controllers\MedicineController::class.'@checkUseOfMedicine' => ['medicines', 'support'],
        Controllers\ScheduleController::class.'@doctorScheduleList' => ['appointments', 'support'],
        Controllers\OperationCategoryController::class.'@getOperationName' => ['ipd_operations', 'support'],
    ],

];
