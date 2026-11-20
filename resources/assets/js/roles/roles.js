'use strict';

listenClick('.deleteRoleBtn', function (event) {
    let roleId = $(event.currentTarget).attr('data-id');
    deleteItem($('#indexRoleUrl').val() + '/' + roleId, '', $('#Role').val());
});

listenChange('.role-select-all', function () {
    let checked = $(this).prop('checked');
    $('.role-permission:not(:disabled), .role-select-group, .role-select-row').prop('checked', checked);
});

listenChange('.role-select-group', function () {
    let group = $(this).attr('data-group');
    let checked = $(this).prop('checked');
    $('.role-permission[data-group="' + group + '"]:not(:disabled), .role-select-row[data-group="' + group + '"]').prop('checked', checked);
});

listenChange('.role-select-row', function () {
    let module = $(this).attr('data-module');
    $('.role-permission[data-module="' + module + '"]:not(:disabled)').prop('checked', $(this).prop('checked'));
});
