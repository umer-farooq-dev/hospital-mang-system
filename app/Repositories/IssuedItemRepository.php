<?php

namespace App\Repositories;

use App\Models\Department;
use App\Models\IssuedItem;
use App\Models\ItemCategory;
use DB;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class IssuedItemRepository
 *
 * @version August 27, 2020, 2:15 pm UTC
 */
class IssuedItemRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'department_id',
        'user_id',
        'issued_by',
        'issued_date',
        'return_date',
        'item_category_id',
        'item_id',
        'quantity',
        'description',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return IssuedItem::class;
    }

    public function getAssociatedData()
    {
        $data['userTypes'] = Department::all()->pluck('name', 'id')->toArray();
        $data['itemCategories'] = ItemCategory::all()->pluck('name', 'id')->toArray();
        natcasesort($data['userTypes']);
        natcasesort($data['itemCategories']);

        return $data;
    }

    public function store($input)
    {
        try {
            DB::beginTransaction();

            $issuedItem = IssuedItem::create($input);
            \App\Utils\Stock::issue($issuedItem->item, (int) ($issuedItem->store_id ?: \App\Utils\Stock::inventoryStoreId()), (int) $issuedItem->quantity, $issuedItem, \App\Utils\Stock::ISSUE);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function destroyIssuedItemStock($issuedItem)
    {
        DB::transaction(function () use ($issuedItem) {
            \App\Utils\Stock::reverse($issuedItem, \App\Utils\Stock::RETURN); // nothing left to undo once returned
            $this->delete($issuedItem->id);
        });
    }

    public function returnIssuedItem($itemId)
    {
        $issuedItem = IssuedItem::whereId($itemId)->firstOrFail();
        DB::transaction(function () use ($issuedItem) {
            \App\Utils\Stock::reverse($issuedItem, \App\Utils\Stock::RETURN);
            $issuedItem->update(['return_date' => date('Y-m-d'), 'quantity' => 0, 'status' => IssuedItem::ITEM_RETURNED]);
        });

        return true;
    }
}
