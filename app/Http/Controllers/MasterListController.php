<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterListItemRequest;
use App\Models\MasterListItem;
use App\Repositories\MasterListRepository;
use Exception;
use Illuminate\Http\Request;

class MasterListController extends AppBaseController
{
    /** @var MasterListRepository */
    private $masterListRepository;

    public function __construct(MasterListRepository $masterListRepo)
    {
        $this->masterListRepository = $masterListRepo;
    }

    public function index(Request $request)
    {
        $types = array_keys(MasterListItem::TYPES);
        $type = in_array($request->get('type'), $types, true) ? $request->get('type') : $types[0];

        return view('master_lists.index', compact('types', 'type'));
    }

    public function store(MasterListItemRequest $request)
    {
        $this->masterListRepository->store($request->validated());

        return $this->sendSuccess(__('messages.master_list.item').' '.__('messages.common.saved_successfully'));
    }

    public function edit(MasterListItem $masterListItem)
    {
        return $this->sendResponse($masterListItem, 'Master list item retrieved successfully.');
    }

    public function update(MasterListItem $masterListItem, MasterListItemRequest $request)
    {
        $this->masterListRepository->updateItem($masterListItem, $request->validated());

        return $this->sendSuccess(__('messages.master_list.item').' '.__('messages.common.updated_successfully'));
    }

    public function destroy(MasterListItem $masterListItem)
    {
        try {
            $this->masterListRepository->delete($masterListItem->id);

            return $this->sendSuccess(__('messages.master_list.item').' '.__('messages.common.deleted_successfully'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage(), 422);
        }
    }
}
