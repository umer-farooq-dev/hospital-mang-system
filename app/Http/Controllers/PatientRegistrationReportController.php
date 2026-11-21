<?php

namespace App\Http\Controllers;

use App\Exports\PatientRegistrationExport;
use App\Models\MasterListItem;
use App\Repositories\PatientRegistrationReportRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PatientRegistrationReportController extends AppBaseController
{
    const PER_PAGE = 25;

    /** @var PatientRegistrationReportRepository */
    private $reportRepository;

    public function __construct(PatientRegistrationReportRepository $reportRepo)
    {
        $this->reportRepository = $reportRepo;
    }

    public function index(Request $request)
    {
        $filters = $this->reportRepository->filters($request->all());
        $summary = $this->reportRepository->summary($filters);
        $patients = $this->reportRepository->patients($filters)->paginate(self::PER_PAGE)->withQueryString();
        $idTypes = MasterListItem::options('id_document_type', $filters['id_document_type']);

        return view('patient_registrations.index', compact('filters', 'summary', 'patients', 'idTypes'));
    }

    public function export(Request $request)
    {
        $filters = $this->reportRepository->filters($request->all());

        return Excel::download(new PatientRegistrationExport($this->reportRepository, $filters),
            'patient-registrations-'.$filters['from']->format('Ymd').'-'.$filters['to']->format('Ymd').'.xlsx');
    }

    public function downloadPdf(Request $request)
    {
        $filters = $this->reportRepository->filters($request->all());
        $summary = $this->reportRepository->summary($filters);
        $patients = $this->reportRepository->patients($filters)->get();

        return Pdf::loadView('patient_registrations.pdf', compact('filters', 'summary', 'patients'))
            ->setPaper('a4', 'landscape')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('patient-registrations-'.$filters['from']->format('Ymd').'-'.$filters['to']->format('Ymd').'.pdf');
    }
}
