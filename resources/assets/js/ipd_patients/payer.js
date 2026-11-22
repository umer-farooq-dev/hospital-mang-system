// IPD admission form: the patient's valid policies; the payer brings its price list.
listenChange('#ipdPatientId, #editIpdPatientId', function () {
    let select = $('#ipdPolicyId');
    if (!select.length) {
        return;
    }
    select.find('option:not(:first)').remove();
    if (!$(this).val()) {
        return;
    }
    $.get(select.data('url'), { patient_id: $(this).val() }, function (result) {
        result.data.forEach(function (policy) {
            select.append($('<option>', { value: policy.id, text: policy.label }).attr('data-price-list', policy.price_list_id || ''));
        });
        select.trigger('change.select2');
    });
});

listenChange('#ipdPolicyId', function () {
    let list = $(this).find('option:selected').data('price-list');
    if (list) {
        $('#ipdPriceListId').val(String(list)).trigger('change.select2');
    }
});
