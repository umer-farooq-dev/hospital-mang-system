<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOpdPatientDepartmentRequest;
use App\Http\Requests\UpdateOpdPatientDepartmentRequest;
use App\Models\AddCustomFields;
use App\Models\Appointment;
use App\Models\PatientQueue;
use App\Models\DoctorOPDCharge;
use App\Models\OpdPatientDepartment;
use App\Repositories\OpdPatientDepartmentRepository;
use Flash;
use Illuminate\Http\Request;

class OpdPatientDepartmentController extends AppBaseController
{
    /** @var OpdPatientDepartmentRepository */
    private $opdPatientDepartmentRepository;

    public function __construct(OpdPatientDepartmentRepository $opdPatientDepartmentRepo)
    {
        $this->opdPatientDepartmentRepository = $opdPatientDepartmentRepo;
    }

    public function index()
    {
        return view('opd_patient_departments.index');
    }

    public function create(Request $request)
    {
        $data = $this->opdPatientDepartmentRepository->getAssociatedData();
        $data['revisit'] = ($request->get('revisit')) ? $request->get('revisit') : 0;
        $customField = AddCustomFields::where('module_name', AddCustomFields::OpdPatient)->get()->toArray();
        if ($data['revisit']) {
            $id = $data['revisit'];
            $data['last_visit'] = OpdPatientDepartment::find($id);
        } elseif ($request->filled('patient_id')) {
            // Opened from the Reception Desk: the patient (and an appointment being checked in) is known.
            $data['appointment'] = $request->filled('appointment_id')
                ? Appointment::where('patient_id', $request->integer('patient_id'))->find($request->integer('appointment_id'))
                : null;
            $data['last_visit'] = new OpdPatientDepartment([
                'patient_id' => $request->integer('patient_id'),
                'doctor_id' => $data['appointment']->doctor_id ?? null,
            ]);
        }

        return view('opd_patient_departments.create', compact('data', 'customField'));
    }

    public function store(CreateOpdPatientDepartmentRequest $request)
    {
        $input = $request->all();

        $input['standard_charge'] = removeCommaFromNumbers($input['standard_charge']);
        $this->opdPatientDepartmentRepository->store($input);
        $this->opdPatientDepartmentRepository->createNotification($input);
        $message = __('messages.opd_patient.opd_patient').' '.__('messages.common.saved_successfully');

        // Checking in an appointment puts it in today's patient queue.
        $appointment = ! empty($input['appointment_id'])
            ? Appointment::where('patient_id', $input['patient_id'])->where('is_completed', Appointment::STATUS_PENDING)->find($input['appointment_id'])
            : null;
        if ($appointment) {
            $message .= ' '.__('messages.reception_desk.queue_token', ['no' => PatientQueue::enqueue($appointment)->no]);
        }
        Flash::success($message);

        return redirect(afterSaveUrl(route('opd.patient.index')));
    }

    public function show(OpdPatientDepartment $opdPatientDepartment)
    {
        $doctors = $this->opdPatientDepartmentRepository->getDoctorsData();
        $medicineCategories = $this->opdPatientDepartmentRepository->getMedicinesCategoriesData();
        $medicineCategoriesList = $this->opdPatientDepartmentRepository->getMedicineCategoriesList();
        $doseDurationList = $this->opdPatientDepartmentRepository->getDoseDurationList();
        $doseIntervalList = $this->opdPatientDepartmentRepository->getDoseIntervalList();
        $mealList = $this->opdPatientDepartmentRepository->getMealList();

        return view('opd_patient_departments.show', compact('opdPatientDepartment', 'doctors', 'medicineCategories', 'medicineCategoriesList', 'doseDurationList', 'doseIntervalList', 'mealList'));
    }

    public function edit(OpdPatientDepartment $opdPatientDepartment)
    {
        $data = $this->opdPatientDepartmentRepository->getAssociatedData();
        $customField = AddCustomFields::where('module_name', AddCustomFields::OpdPatient)->get()->toArray();

        return view('opd_patient_departments.edit', compact('data', 'opdPatientDepartment','customField'));
    }

    public function update(OpdPatientDepartment $opdPatientDepartment, UpdateOpdPatientDepartmentRequest $request)
    {
        $input = $request->all();
        $this->opdPatientDepartmentRepository->updateOpdPatientDepartment($input, $opdPatientDepartment);
        Flash::success(__('messages.opd_patient.opd_patient').' '.__('messages.common.updated_successfully'));

        return redirect(route('opd.patient.index'));
    }

    public function destroy($id)
    {
        $opdPatientDepartment = OpdPatientDepartment::find($id);
        $opdPatientDepartment->delete();

        return $this->sendSuccess(__('messages.opd_patient.opd_patient').' '.__('messages.common.deleted_successfully'));
    }

    public function getPatientCasesList(Request $request)
    {
        $patientCases = $this->opdPatientDepartmentRepository->getPatientCases($request->get('id'));

        return $this->sendResponse($patientCases, 'Retrieved successfully');
    }

    public function getDoctorOPDCharge(Request $request)
    {
        $doctorOPDCharge = DoctorOPDCharge::whereDoctorId($request->get('id'))->get();

        return $this->sendResponse($doctorOPDCharge, 'Doctor OPD Charge retrieved successfully.');
    }
}
