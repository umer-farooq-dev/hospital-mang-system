<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\AppBaseController;
use App\Models\AddCustomFields;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\FrontService;
use App\Models\FrontSetting;
use App\Models\HospitalSchedule;
use App\Models\NoticeBoard;
use App\Models\Nurse;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Repositories\AppointmentRepository;
use App\Repositories\AdvancedPaymentRepository;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use App\Models\Vaccination;
use App\Models\PatientCase;
use App\Models\PatientAdmission;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\AdvancedPayment;
use App\Models\Document;
use App\Models\VaccinatedPatients;
use App\Models\PatientIdCardTemplate;
class WebController extends AppBaseController
{
    /**  @var AppointmentRepository */
    private $appointmentRepository;


    /** @var PatientRepository */
    private $patientRepository;


    public function __construct(AppointmentRepository $appointmentRepository, PatientRepository $patientRepo)
    {
        $this->appointmentRepository = $appointmentRepository;
        $this->patientRepository = $patientRepo;
    }

    public function index()
    {
        $totalbeds = Bed::count();
        $totalDoctorNurses = Doctor::count() + Nurse::count();
        $totalPatient = Patient::count();
        $doctorsDepartments = DoctorDepartment::take(6)->toBase()->get();
        $doctorAppointments = Doctor::withCount('appointments')->with('department', 'doctorUser')
            ->whereHas('doctorUser', function (Builder $query) {
                return $query->where('status', 1);
            })
            ->distinct()->take(6)->orderByDesc('appointments_count')->get();
        $todayNotice = NoticeBoard::whereDate('created_at', Carbon::today()->toDateTimeString())->latest()->first();
        $testimonials = Testimonial::with('media')->get();
        $doctors = Doctor::with('doctorUser')->whereHas('doctorUser', fn (Builder $q) => $q->where('status', 1))->get();
        $frontSetting = frontSetting();
        $sections = FrontSetting::homeSections($frontSetting);
        $frontServices = FrontService::with('media')->ofType(FrontService::SERVICE)->take(8)->get();
        $cases = FrontService::with('media')->ofType(FrontService::CASE_STUDY)->take(3)->get();
        $posts = FrontService::with('media')->ofType(FrontService::POST)->take(6)->get();
        $departments = DoctorDepartment::orderBy('title')->pluck('title', 'id');
        $partners = \App\Models\Insurance::websitePartners();

        return view('web.home.index',
            compact('doctorsDepartments', 'doctors', 'todayNotice', 'testimonials', 'totalbeds',
                'totalDoctorNurses', 'totalPatient', 'doctorAppointments', 'frontSetting', 'frontServices',
                'sections', 'cases', 'posts', 'departments', 'partners'));
    }

    public function demo()
    {
        return \view('web.demo.index');
    }

    public function modulesOfHms()
    {
        return \view('web.modules_of_hms.index');
    }

    public function changeLanguage(Request $request)
    {
        // $defaultLanguage = Setting::where('key', 'default_lang')->first()->value;
        // if($defaultLanguage){
        //     Session::put('languageName', $defaultLanguage);
        // }
        Session::put('languageName', $request->input('languageName'));

        return redirect()->back();

    }

    public function aboutUs()
    {
        $frontSetting = FrontSetting::whereType(FrontSetting::ABOUT_US)->pluck('value', 'key')->toArray();
        $totalbeds = Bed::count();
        $totalDoctorNurses = Doctor::count() + Nurse::count();
        $totalPatient = Patient::count();
        $testimonials = Testimonial::with('media')->get();
        $doctors = Doctor::withCount(['appointments', 'patients'])->with('department',
            'doctorUser')->whereHas('doctorUser',
                function (Builder $query) {
                    $query->where('status', 1);
                })->distinct()->take(4)->orderByDesc('appointments_count')->get();

        return view('web.home.about_us',
            compact('frontSetting', 'totalbeds', 'totalDoctorNurses', 'totalPatient', 'testimonials', 'doctors'));
    }

    public function appointmentFromOther(Request $request)
    {
        $data = $request->all();

        return redirect()->route('appointment')->with(['data' => $data]);
    }

    public function appointment()
    {
        $departments = $this->appointmentRepository->getDoctorDepartments();
        $doctors = $this->appointmentRepository->getDoctorLists();
        $customField = AddCustomFields::where('module_name', AddCustomFields::Appointment)->get()->toArray();

        return view('web.home.appointment', compact('departments', 'doctors','customField'));
    }

    public function services($id = null)
    {
        return $this->content(FrontService::SERVICE, $id);
    }

    public function cases($id = null)
    {
        return $this->content(FrontService::CASE_STUDY, $id);
    }

    public function news($id = null)
    {
        return $this->content(FrontService::POST, $id);
    }

    /** One page for the three kinds of website content: the list, or one item with the others beside it. */
    private function content(int $type, $id)
    {
        $item = $id ? FrontService::with('media')->where('type', $type)->findOrFail($id) : null;
        $items = FrontService::with('media')->ofType($type)->paginate(9);
        $others = $item ? FrontService::ofType($type)->whereKeyNot($item->id)->take(8)->get() : collect();

        return view('web.home.services', ['frontServices' => $items, 'item' => $item, 'others' => $others, 'type' => $type]);
    }

    public function doctors()
    {
        $doctors = Doctor::withCount(['appointments', 'patients'])->with('department',
            'doctorUser')->whereHas('doctorUser',
                function (Builder $query) {
                    $query->where('status', 1);
                })->distinct()->orderByDesc('appointments_count')->paginate(8);

        return view('web.home.doctors', compact('doctors'));
    }

    public function termsOfService()
    {
        $frontSetting = FrontSetting::whereType(FrontSetting::HOME_PAGE)->pluck('value', 'key')->toArray();

        return view('web.home.terms-of-service', compact('frontSetting'));
    }

    public function privacyPolicy()
    {
        $frontSetting = FrontSetting::whereType(FrontSetting::HOME_PAGE)->pluck('value', 'key')->toArray();

        return view('web.home.privacy-policy', compact('frontSetting'));
    }

    public function workingHours()
    {
        $hospitalSchedules = HospitalSchedule::all()->sortBy('day_of_week');
        $weekDay = HospitalSchedule::WEEKDAY_FULL_NAME;
        $doctors = Doctor::with('doctorUser')->get();

        return view('web.home.working-hours', compact('hospitalSchedules', 'weekDay', 'doctors'));
    }

    public function testimonials()
    {
        $testimonials = Testimonial::with('media')->get();

        return view('web.home.testimonials', compact('testimonials'));
    }

    public function setLanguage(Request $request)
    {
        // $defaultLanguage = Setting::where('key', 'default_lang')->first()->value;
        // if($defaultLanguage){
        //     Session::put('languageName', $defaultLanguage);
        // }
        Session::put('languageName', $request['languageName']);
        App::setLocale(session('languageName'));

        return redirect()->back();
    }

    public function showQrCodePatient($uniqueId)
    {
        $data = [];
        $patient = Patient::with(['patientUser','address','idCardTemplate'])->where('patient_unique_id', $uniqueId)->first();
        $data = $this->patientRepository->getPatientAssociatedData($patient->id);
        $advancedPaymentRepo = App::make(AdvancedPaymentRepository::class);
        $patients = $advancedPaymentRepo->getPatients();
        $user = Auth::user();
        if (!empty($user) && $user->hasRole('Doctor')) {
            $vaccinationPatients = getPatientsList($user->owner_id);
        } else {
            $vaccinationPatients = Patient::getActivePatientNames();
        }
        $vaccinations = Vaccination::toBase()->pluck('name', 'id')->toArray();
        natcasesort($vaccinations);
        $data['patientCases'] = PatientCase::with('doctor')->where('patient_id', $patient->id)->get();
        $data['patientAdmissions'] = PatientAdmission::with('patient.patientUser','doctor.doctorUser', 'package', 'insurance')
            ->where('patient_id', $patient->id)->get();
        $data['appointments'] = Appointment::with('doctor.doctorUser','doctor.department')->where('patient_id', $patient->id)->get();
        $data['bills'] = Bill::where('patient_id', $patient->id)->get();
        $data['invoices'] = Invoice::where('patient_id', $patient->id)->get();
        $data['advancePayments'] = AdvancedPayment::where('patient_id',  $patient->id)->get();
        $data['documents'] = Document::with('documentType')->where('patient_id', $patient->id)->get();
        $data['vaccinations'] = VaccinatedPatients::with('vaccination')->where('patient_id', $patient->id)->get();

        return view('web.home.qr_code_patient', compact('data', 'vaccinations', 'vaccinationPatients'));
    }

    public function doctorDetails($id){
        $doctorDetails = Doctor::with(['doctorUser', 'department', 'schedules'])->whereHas('doctorUser', fn (Builder $q) => $q->where('status', 1))->findOrFail($id);
        $doctorSchedule = $doctorDetails->schedules;
        return view('web.home.doctor-details',compact('doctorDetails','doctorSchedule'));
    }
}
