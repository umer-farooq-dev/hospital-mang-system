<?php

namespace App\Http\Controllers;

use App\Models\ExamOrder;
use App\Models\ExamOrderItem;
use App\Models\Patient;
use App\Models\Setting;

/** Patient portal: the patient's own released results, when the admin allows it. */
class PatientResultsController extends AppBaseController
{
    public function index()
    {
        $patient = $this->patient();

        return view('patient_results.index', ['examItems' => ExamOrderItem::workFor($patient->id)->where('status', 'released')->values()]);
    }

    public function show(ExamOrder $order)
    {
        abort_unless($order->patient_id === $this->patient()->id, 404);
        $order->load('patient.patientUser', 'doctor.doctorUser', 'items.results');

        return view('lab_worklist.report', [
            'order' => $order,
            'items' => $order->items->where('status', 'released'),
            'settings' => getSettingValue(),
        ]);
    }

    private function patient(): Patient
    {
        abort_unless(Setting::where('key', 'portal_show_results')->value('value') === '1', 404);
        $user = getLoggedInUser();
        $patient = $user && $user->owner_type === Patient::class ? Patient::find($user->owner_id) : null;
        abort_unless($patient, 404);

        return $patient;
    }
}
