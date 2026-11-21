<?php

namespace App\Exports;

use App\Repositories\PatientRegistrationReportRepository;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PatientRegistrationExport implements FromView, WithTitle, ShouldAutoSize
{
    public function __construct(private PatientRegistrationReportRepository $repository, private array $filters)
    {
    }

    public function view(): View
    {
        return view('patient_registrations.table', [
            'patients' => $this->repository->patients($this->filters)->get(),
        ]);
    }

    public function title(): string
    {
        return __('messages.reception_report.title');
    }
}
