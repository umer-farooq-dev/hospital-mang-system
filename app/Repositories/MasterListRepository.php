<?php

namespace App\Repositories;

use App\Models\MasterListItem;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MasterListRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return MasterListItem::class;
    }

    public function store(array $input): MasterListItem
    {
        return MasterListItem::create([
            'type' => $input['type'],
            'code' => MasterListItem::nextCode($input['type']),
            'name' => $input['name'],
            // Without an order, a new item goes to the end of the list.
            'sort_order' => isset($input['sort_order']) && $input['sort_order'] !== ''
                ? $input['sort_order']
                : (int) MasterListItem::where('type', $input['type'])->max('sort_order') + 1,
            'is_active' => ! empty($input['is_active']),
        ]);
    }

    public function updateItem(MasterListItem $item, array $input): MasterListItem
    {
        $data = [
            'name' => $input['name'],
            'sort_order' => $input['sort_order'] ?? 0,
            'is_active' => ! empty($input['is_active']),
        ];
        // A renamed seeded item shows the new name instead of its original translation.
        if ($input['name'] !== $item->name) {
            $data['lang_key'] = null;
        }
        $item->update($data);

        return $item;
    }

    public function delete($id)
    {
        $item = MasterListItem::findOrFail($id);
        if ($item->isInUse()) {
            throw new UnprocessableEntityHttpException(__('messages.master_list.in_use'));
        }

        return $item->delete();
    }
}
