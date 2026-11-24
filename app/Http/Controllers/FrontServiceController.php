<?php

namespace App\Http\Controllers;

use App\Http\Requests\FrontServiceRequest;
use App\Http\Requests\UpdateFrontServiceRequest;
use App\Models\FrontService;
use App\Repositories\FrontServiceRepository;

class FrontServiceController extends AppBaseController
{
    /** @var FrontServiceRepository */
    private $frontServiceRepository;

    public function __construct(FrontServiceRepository $frontServiceRepository)
    {
        $this->frontServiceRepository = $frontServiceRepository;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $type = array_key_exists((int) $request->type, FrontService::TYPES) ? (int) $request->type : FrontService::SERVICE;

        return view('front_settings.front_services.index', compact('type'));
    }

    public function store(FrontServiceRequest $request)
    {
        try {
            $input = $request->all();
            $this->frontServiceRepository->store($input);

            return $this->sendSuccess(__('messages.front_service').' '.__('messages.common.saved_successfully'));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function edit($id)
    {
        $frontService = FrontService::find($id);

        return $this->sendResponse($frontService, 'FrontService retrieved successfully.');
    }

    public function update($id, UpdateFrontServiceRequest $request)
    {
        try {
            $this->frontServiceRepository->updateFrontService($request->all(), $id);

            return $this->sendSuccess(__('messages.front_service').' '.__('messages.common.updated_successfully'));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $frontService = FrontService::find($id);
            $frontService->delete(); // media library removes icon and image with the model

            return $this->sendSuccess(__('messages.front_service').' '.__('messages.common.deleted_successfully'));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
