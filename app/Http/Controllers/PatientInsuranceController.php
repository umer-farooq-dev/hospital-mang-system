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
        $policy = $patient->insurances()->create($this->input($request));
        $this->saveCard($request, $policy);

        return $this->listResponse($patient, __('messages.patient_insurance.policy').' '.__('messages.common.saved_successfully'));
    }

    public function update(PatientInsuranceRequest $request, PatientInsurance $patientInsurance)
    {
        $patientInsurance->update($this->input($request));
        $this->saveCard($request, $patientInsurance);

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
        return array_merge(\Illuminate\Support\Arr::except($request->validated(), ['card_photo']), ['is_active' => $request->boolean('is_active')]);
    }

    /** The card has no barcode: its photo is kept in the patient's documents and linked to the policy. */
    private function saveCard(PatientInsuranceRequest $request, PatientInsurance $policy): void
    {
        if (! $request->hasFile('card_photo')) {
            return;
        }
        $typeId = \App\Models\Patient::registrationSettings()['id_scan_save_as']
            ?: \App\Models\DocumentType::firstOrCreate(['name' => __('messages.patient_insurance.card_photo')])->id;
        $document = \App\Models\Document::create(['title' => __('messages.patient_insurance.card_title', ['payer' => $policy->insurance->name]),
            'document_type_id' => $typeId, 'patient_id' => $policy->patient_id, 'uploaded_by' => auth()->id()]);
        $document->addMedia($request->file('card_photo'))->toMediaCollection(\App\Models\Document::PATH, config('app.media_disc'));
        $policy->update(['card_document_id' => $document->id]);
    }

    private function listResponse(Patient $patient, string $message)
    {
        $html = view('patients.insurances.list', ['policies' => $patient->insurances()->with('insurance')->get(), 'patient' => $patient])->render();

        return $this->sendResponse(['html' => $html], $message);
    }
}
