// OPD visit form: payer, price list and the payer/patient split (the server recalculates on save).
function opdSplitForm () {
    return $('#opdSplitSummary').closest('form');
}

function refreshOpdQuote (reprice) {
    let summary = $('#opdSplitSummary');
    let form = opdSplitForm();
    if (!summary.length || !form.find('[name="doctor_id"]').val()) {
        return;
    }
    let charge = form.find('[name="standard_charge"]');
    $.ajax({
        url: summary.data('url'),
        type: 'GET',
        data: {
            reprice: reprice ? 1 : 0,
            visit_id: summary.data('visit'),
            patient_id: form.find('[name="patient_id"]').val(),
            doctor_id: form.find('[name="doctor_id"]').val(),
            patient_insurance_id: $('#opdPolicyId').val(),
            price_list_id: $('#opdPriceListId').val(),
            voucher_number: $('#opdVoucherNumber').val(),
            appointment_date: form.find('[name="appointment_date"]').val(),
            standard_charge: removeCommas(charge.val() || '0'),
        },
        success: function (result) {
            if (reprice) {
                charge.val(result.data.standard_charge).trigger('input');
            }
            summary.html(result.data.html);
        },
    });
}

function loadOpdPayers () {
    let select = $('#opdPolicyId');
    let patientId = opdSplitForm().find('[name="patient_id"]').val();
    if (!select.length) {
        return;
    }
    let current = select.val();
    select.find('option:not(:first)').remove();
    if (!patientId) {
        refreshOpdQuote(false);
        return;
    }
    $.get(select.data('url'), { patient_id: patientId }, function (result) {
        result.data.forEach(function (policy) {
            select.append($('<option>', { value: policy.id, text: policy.label }).attr('data-price-list', policy.price_list_id || ''));
        });
        let preferred = result.data.find((p) => p.default);
        if (result.data.some((p) => String(p.id) === current)) {
            select.val(current).trigger('change.select2');
            refreshOpdQuote(false);
        } else {
            // Settings > Reception may choose the patient's main policy first; its change brings the price list.
            select.val(preferred ? String(preferred.id) : '').trigger('change');
        }
    });
}

listenChange('#opdPatientId, #editOpdPatientId', loadOpdPayers);

listenChange('#opdPolicyId', function () {
    // The payer brings its price list; the cashier can still pick another one.
    let list = $(this).find('option:selected').data('price-list');
    if (list) {
        $('#opdPriceListId').val(String(list)).trigger('change.select2');
    }
    refreshOpdQuote(true);
});

listenChange('#opdPriceListId', function () {
    refreshOpdQuote(true);
});

listenChange('#opdVoucherNumber', function () {
    refreshOpdQuote(false);
});

listenKeyup('#opdStandardCharge, #editOpdStandardCharge', function () {
    clearTimeout(window.opdQuoteTimer);
    window.opdQuoteTimer = setTimeout(() => refreshOpdQuote(false), 400);
});

window.refreshOpdQuote = refreshOpdQuote;
