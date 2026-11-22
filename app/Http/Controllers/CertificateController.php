<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Doctor;
use App\Models\IpdPatientDepartment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;

/** Medical certificates: issue from a template, print, list. */
class CertificateController extends AppBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $certificates = Certificate::with('patient.patientUser', 'doctor.doctorUser')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%$search%")->orWhere('title', 'like', "%$search%")
                ->orWhereHas('patient.patientUser', fn ($u) => $u->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%$search%"))))
            ->latest('id')->paginate(30)->withQueryString();

        return view('certificates.index', compact('certificates', 'search'));
    }

    public function create(Request $request)
    {
        return view('certificates.create', [
            'patients' => Patient::with('patientUser')->get()->where('patientUser.status', 1)
                ->mapWithKeys(fn ($p) => [$p->id => $p->patientUser->full_name . ' · ' . $p->patient_unique_id])->sort()->all(),
            'doctors' => Doctor::with('doctorUser')->get()->mapWithKeys(fn ($d) => [$d->id => $d->doctorUser?->full_name])->filter()->sort()->all(),
            'templates' => CertificateTemplate::active()->mapWithKeys(fn ($t) => [$t->id => $t->label])->all(),
            'patientId' => $request->integer('patient_id') ?: null,
            'ipdId' => $request->integer('ipd_id') ?: null,
            'doctorId' => getLoggedInUser()?->owner_type === Doctor::class ? getLoggedInUser()->owner_id : null,
        ]);
    }

    /** Text of the template with the chosen patient, doctor and dates filled in. */
    public function getPreview(Request $request)
    {
        $template = CertificateTemplate::findOrFail($request->integer('template_id'));

        return response()->json(['body' => CertificateTemplate::fillPlaceholders($template->text, $this->values($request))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'certificate_template_id' => 'required|integer|exists:certificate_templates,id',
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'ipd_patient_department_id' => 'nullable|integer|exists:ipd_patient_departments,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'body' => 'required|string|max:20000',
        ]);
        $template = CertificateTemplate::findOrFail($data['certificate_template_id']);
        $certificate = DB::transaction(fn () => Certificate::create($data + [
            'number' => Certificate::newNumber(), 'title' => $template->label, 'issued_at' => now(), 'created_by' => auth()->id(),
        ]));
        Flash::success(__('messages.certificate.saved', ['number' => $certificate->number]));

        return redirect()->route('certificates.index', ['print' => $certificate->id]);
    }

    public function show(Certificate $certificate)
    {
        return view('certificates.print', [
            'certificate' => $certificate->load('patient.patientUser', 'doctor.doctorUser'),
            'settings' => getSettingValue(),
        ]);
    }

    private function values(Request $request): array
    {
        $patient = Patient::with('patientUser')->find($request->integer('patient_id'));
        $user = $patient?->patientUser;
        $doctor = Doctor::with('doctorUser')->find($request->integer('doctor_id'));
        $ipd = IpdPatientDepartment::find($request->integer('ipd_patient_department_id'));
        $start = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $end = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : null;

        return [
            'hospital' => getAppName(),
            'patient_name' => $user?->full_name,
            'patient_id' => $patient?->patient_unique_id,
            'age' => $user?->dob ? Carbon::parse($user->dob)->age . ' ' . __('messages.discharge.years') : '',
            'gender' => $user ? ($user->gender == 1 ? __('messages.user.female') : __('messages.user.male')) : '',
            'dob' => $user?->dob ? Carbon::parse($user->dob)->format('d/m/Y') : '',
            'date' => now()->format('d/m/Y'),
            'doctor' => $doctor?->doctorUser?->full_name,
            'start_date' => $start?->format('d/m/Y'),
            'end_date' => $end?->format('d/m/Y'),
            'days' => $start && $end ? $start->diffInDays($end) + 1 : '',
            'ipd_number' => $ipd?->ipd_number,
            'admission_date' => $ipd ? Carbon::parse($ipd->admission_date)->format('d/m/Y') : '',
            'discharge_date' => $ipd?->discharge_date ? Carbon::parse($ipd->discharge_date)->format('d/m/Y') : '',
            'notes' => (string) $request->input('notes'),
        ];
    }
}
