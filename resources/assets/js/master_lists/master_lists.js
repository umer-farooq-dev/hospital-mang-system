'use strict';

listenSubmit('#addMasterListItemForm', function (event) {
    event.preventDefault();
    let loadingButton = jQuery(this).find('#masterListItemSave');
    loadingButton.button('loading');
    newRecord({
        'formSelector': $(this),
        'url': $('#indexMasterListCreateUrl').val(),
        'type': 'POST',
    }, loadingButton, '#add_master_list_item_modal');
});

listenClick('.editMasterListItemBtn', function (event) {
    if ($('.ajaxCallIsRunning').val()) {
        return;
    }
    ajaxCallInProgress();
    let id = $(event.currentTarget).attr('data-id');
    $.ajax({
        url: $('#indexMasterListUrl').val() + '/' + id + '/edit',
        type: 'GET',
        success: function (result) {
            if (result.success) {
                $('#masterListItemId').val(result.data.id);
                $('#editMasterListItemName').val(result.data.name);
                $('#editMasterListItemSort').val(result.data.sort_order);
                $('#editMasterListItemActive').prop('checked', result.data.is_active);
                $('#edit_master_list_item_modal').modal('show');
                ajaxCallCompleted();
            }
        },
        error: function (result) {
            manageAjaxErrors(result);
        },
    });
});

listenSubmit('#editMasterListItemForm', function (event) {
    event.preventDefault();
    let loadingButton = jQuery(this).find('#editMasterListItemSave');
    loadingButton.button('loading');
    editRecordWithForm({
        'formSelector': $(this),
        'url': $('#indexMasterListUrl').val() + '/' + $('#masterListItemId').val(),
        'type': 'PUT',
    }, loadingButton, '#edit_master_list_item_modal');
});

listenClick('.deleteMasterListItemBtn', function (event) {
    let id = $(event.currentTarget).attr('data-id');
    deleteItem($('#indexMasterListUrl').val() + '/' + id, '', $('#masterListItemLabel').val());
});

listenHiddenBsModal('#add_master_list_item_modal', function () {
    resetModalForm('#addMasterListItemForm', '#masterListItemErrorsBox');
});
