<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientInsuranceRequest;
use App\Models\Patient;
use App\Models\PatientInsurance;

/**
 * Insurance policies and partner-company memberships on the patient file (Insurance tab).
 * Each action answers with the refreshed list so the tab updates without a reload.
 */
class PatientInsuranceController extends AppBaseController
{
    public function store(PatientInsuranceRequest $request, Patient $patient)
    {
        $patient->insurances()->create($this->input($request));

        return $this->listResponse($patient, __('messages.patient_insurance.policy').' '.__('messages.common.saved_successfully'));
    }

    public function update(PatientInsuranceRequest $request, PatientInsurance $patientInsurance)
    {
        $patientInsurance->update($this->input($request));

        return $this->listResponse($patientInsurance->patient, __('messages.patient_insurance.policy').' '.__('messages.common.updated_successfully'));
    }

    public function destroy(PatientInsurance $patientInsurance)
    {
        $patient = $patientInsurance->patient;
        $patientInsurance->delete();

        return $this->listResponse($patient, __('messages.patient_insurance.policy').' '.__('messages.common.deleted_successfully'));
    }

    private function input(PatientInsuranceRequest $request): array
    {
        return array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);
    }

    private function listResponse(Patient $patient, string $message)
    {
        $html = view('patients.insurances.list', ['policies' => $patient->insurances()->with('insurance')->get(), 'patient' => $patient])->render();

        return $this->sendResponse(['html' => $html], $message);
    }
}
