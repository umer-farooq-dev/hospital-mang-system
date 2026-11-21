<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\ReceptionDeskRepository;
use Illuminate\Http\Request;

class ReceptionDeskController extends AppBaseController
{
    /** @var ReceptionDeskRepository */
    private $receptionDeskRepository;

    public function __construct(ReceptionDeskRepository $receptionDeskRepo)
    {
        $this->receptionDeskRepository = $receptionDeskRepo;
    }

    public function index(Request $request)
    {
        $appointments = $this->receptionDeskRepository->todayAppointments();
        $recentPatients = $this->receptionDeskRepository->recentPatients();
        // Coming back from "New patient": show that patient ready for the next step.
        $selected = $request->filled('patient')
            ? Patient::with('patientUser.media')->whereKey($request->integer('patient'))->get()
            : collect();

        return view('reception_desk.index', compact('appointments', 'recentPatients', 'selected'));
    }

    /**
     * Search by name, phone, patient number, ID number, or a scanned hospital card.
     */
    public function getSearch(Request $request)
    {
        $patients = $this->receptionDeskRepository->search((string) $request->get('search'));

        return $this->sendResponse(view('reception_desk.patient_results', compact('patients'))->render(), '');
    }
}
