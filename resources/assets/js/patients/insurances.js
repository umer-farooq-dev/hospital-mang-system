// Patient file > Insurance tab: add, edit and delete the patient's policies.
const patientInsuranceFields = ['insurance_id', 'plan_name', 'policy_number', 'member_number', 'valid_from', 'valid_to',
    'coverage_percent', 'annual_limit', 'visit_limit', 'priority', 'notes'];

function openPatientInsuranceModal (url, policy) {
    let form = $('#patientInsuranceForm');
    let title = $('#patientInsuranceModalTitle');
    form[0].reset();
    $('#patientInsuranceErrorsBox').addClass('d-none').html('');
    form.data('url', url);
    title.text(policy ? title.data('edit') : title.data('new'));
    patientInsuranceFields.forEach(function (field) {
        let value = policy ? policy[field] : null;
        if (field === 'priority' && value === null) {
            value = $('#patientInsuranceList tbody tr').length + 1;
        }
        form.find('[name="' + field + '"]').val(value ?? '');
    });
    form.find('[name="insurance_id"]').trigger('change');
    form.find('.price-input').trigger('input');
    $('#patientInsuranceActive').prop('checked', policy ? !!policy.is_active : true);
    $('#patientInsuranceModal').modal('show');
}

listenClick('#addPatientInsurance', function () {
    openPatientInsuranceModal($('#patientInsuranceForm').data('store-url'), null);
});

listenClick('.edit-patient-insurance', function () {
    openPatientInsuranceModal($(this).data('url'), $(this).data('policy'));
});

listenSubmit('#patientInsuranceForm', function (event) {
    event.preventDefault();
    let button = $('#patientInsuranceSave').prop('disabled', true);
    $.ajax({
        url: $(this).data('url'),
        type: 'POST',
        data: $(this).serialize(),
        success: function (result) {
            $('#patientInsuranceList').html(result.data.html);
            $('#patientInsuranceModal').modal('hide');
            displaySuccessMessage(result.message);
        },
        error: function (result) {
            printErrorMessage('#patientInsuranceErrorsBox', result);
        },
        complete: function () {
            button.prop('disabled', false);
        },
    });
});

listenClick('.delete-patient-insurance', function () {
    let url = $(this).data('url');
    let label = $(this).data('label');
    swal({
        title: $('.deleteVariable').val(),
        text: $('.confirmVariable').val() + label + '?',
        icon: $('.sweetAlertIcon').val(),
        buttons: {
            confirm: $('.yesVariable').val() + ', ' + $('.deleteVariable').val(),
            cancel: $('.noVariable').val() + ', ' + $('.cancelVariable').val(),
        },
    }).then(function (confirmed) {
        if (!confirmed) {
            return;
        }
        $.ajax({
            url: url,
            type: 'DELETE',
            dataType: 'json',
            success: function (result) {
                $('#patientInsuranceList').html(result.data.html);
                displaySuccessMessage(result.message);
            },
            error: function (result) {
                manageAjaxErrors(result);
            },
        });
    });
});
