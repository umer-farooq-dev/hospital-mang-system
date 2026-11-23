// Exam request form: the patient's payers, then a live price and payer/patient split of the chosen exams.
function refreshExamQuote () {
    let form = $('#examOrderForm');
    if (!form.length) {
        return;
    }
    $.get(form.data('quote-url'), form.serialize(), function (result) {
        $('#examQuote').html(result.data.html);
    });
}

function loadExamPayers () {
    let select = $('#examPolicyId');
    let patientId = $('#examPatientId').val();
    select.find('option:not(:first)').remove();
    if (!patientId) {
        refreshExamQuote();
        return;
    }
    $.get(select.data('url'), { patient_id: patientId }, function (result) {
        result.data.forEach(function (policy) {
            select.append($('<option>', { value: policy.id, text: policy.label }).attr('data-price-list', policy.price_list_id || ''));
        });
        let preferred = result.data.find((p) => p.default);
        select.val(preferred ? String(preferred.id) : '').trigger('change');
    });
}

listenChange('#examPatientId', loadExamPayers);

listenChange('#examPolicyId', function () {
    let list = $(this).find('option:selected').data('price-list');
    if (list) {
        $('#examPriceListId').val(String(list)).trigger('change.select2');
    }
    refreshExamQuote();
});

listenChange('#examPriceListId, #examDoctorId, #examTests, #examVoucher', refreshExamQuote);

document.addEventListener('DOMContentLoaded', function () {
    if ($('#examPatientId').val()) {
        loadExamPayers();
    }
});
