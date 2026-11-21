<?php

namespace App\Http\Controllers;

use App\Repositories\PatientFlowRepository;
use Illuminate\Http\Request;

/**
 * Patient flow reports ("État de passage des patients") on screen and printed.
 */
class PatientFlowController extends AppBaseController
{
    /** @var PatientFlowRepository */
    private $flowRepository;

    public function __construct(PatientFlowRepository $flowRepo)
    {
        $this->flowRepository = $flowRepo;
    }

    private function data(Request $request): array
    {
        $filters = $this->flowRepository->filters($request->all());

        return ['filters' => $filters, 'report' => $this->flowRepository->report($filters), 'departments' => PatientFlowRepository::departments()];
    }

    public function index(Request $request)
    {
        return view('patient_flow.index', $this->data($request));
    }

    public function print(Request $request)
    {
        return view('patient_flow.print', $this->data($request) + ['settings' => getSettingValue()]);
    }
}
