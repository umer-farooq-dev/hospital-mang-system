<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdvancedPaymentRequest;
use App\Http\Requests\UpdateAdvancedPaymentRequest;
use App\Models\AdvancedPayment;
use App\Repositories\AdvancedPaymentRepository;
use Flash;
use Illuminate\Support\Facades\Schema;

class AdvancedPaymentController extends AppBaseController
{
    /** @var AdvancedPaymentRepository */
    private $advancedPaymentRepository;

    public function __construct(AdvancedPaymentRepository $advancedPaymentRepo)
    {
        $this->advancedPaymentRepository = $advancedPaymentRepo;
    }

    public function index()
    {
        $patients = $this->advancedPaymentRepository->getPatients();

        return view('advanced_payments.index', compact('patients'));
    }

    public function store(CreateAdvancedPaymentRequest $request)
    {
        $input = $request->all();
        $input['amount'] = removeCommaFromNumbers($input['amount']);
        $cashier = app(\App\Repositories\CashierRepository::class);
        try {
            $cashier->assertCanCollect(\Auth::user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError($e->getMessage(), 422);
        }
        Schema::disableForeignKeyConstraints();
        $advancedPayment = $this->advancedPaymentRepository->create($input);
        Schema::enableForeignKeyConstraints();
        // An advance is paid in cash at the register (the screen has no payment method).
        $cashier->recordPayment(\Auth::user(), (int) $advancedPayment->patient_id, 'advance_payment', $advancedPayment->id,
            __('messages.cashier.advance_payment').' · '.$advancedPayment->receipt_no, (float) $advancedPayment->amount, \App\Repositories\CashierRepository::cashMethod());
        $this->advancedPaymentRepository->createNotification($input);

        return $this->sendSuccess(__('messages.advanced_payment.advanced_payment').' '.__('messages.common.saved_successfully'));
    }

    public function show(AdvancedPayment $advancedPayment)
    {
        $advancedPayment = $this->advancedPaymentRepository->find($advancedPayment->id);

        if (empty($advancedPayment)) {
            Flash::error(__('messages.advanced_payment.advanced_payment').' '.__('messages.common.not_found'));

            return redirect(route('advancedPayments.index'));
        }
        $patients = $this->advancedPaymentRepository->getPatients();

        return view('advanced_payments.show')->with(['advancedPayment' => $advancedPayment, 'patients' => $patients]);
    }

    public function edit(AdvancedPayment $advancedPayment)
    {
        return $this->sendResponse($advancedPayment, 'Advance Payment retrieved successfully.');
    }

    public function update(AdvancedPayment $advancedPayment, UpdateAdvancedPaymentRequest $request)
    {
        $input = $request->all();
        $input['amount'] = removeCommaFromNumbers($input['amount']);
        Schema::disableForeignKeyConstraints();
        $this->advancedPaymentRepository->update($input, $advancedPayment->id);
        Schema::enableForeignKeyConstraints();

        return $this->sendSuccess(__('messages.advanced_payment.advanced_payment').' '.__('messages.common.updated_successfully'));
    }

    public function destroy(AdvancedPayment $advancedPayment)
    {
        $advancedPayment->delete();

        return $this->sendSuccess(__('messages.advanced_payment.advanced_payment').' '.__('messages.common.deleted_successfully'));
    }
}
