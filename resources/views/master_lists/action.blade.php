<div class="d-flex align-items-center">
    @canroute('master-lists.update')
        <a href="javascript:void(0)" title="{{ __('messages.common.edit') }}" data-id="{{ $row->id }}"
           class="editMasterListItemBtn btn px-1 text-primary fs-3 ps-0">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
    @endcanroute
    @canroute('master-lists.destroy')
        <a href="javascript:void(0)" title="{{ __('messages.common.delete') }}" data-id="{{ $row->id }}"
           class="deleteMasterListItemBtn btn px-1 text-danger fs-3 pe-0 {{ getCurrentLoginUserLanguageName() == 'ar' ? 'me-2' : '' }}"
           wire:key="{{ $row->id }}">
            <i class="fa-solid fa-trash"></i>
        </a>
    @endcanroute
</div>
