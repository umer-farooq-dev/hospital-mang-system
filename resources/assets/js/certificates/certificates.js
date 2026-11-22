document.addEventListener('DOMContentLoaded', loadCertificatePages)

// Certificate form: fill the text from the chosen template; list: open the print of the new certificate.
function loadCertificatePages () {
    let page = document.getElementById('certificatesPage')
    if (page && page.dataset.printUrl) {
        window.open(page.dataset.printUrl, '_blank')
    }
    let form = $('#certificateForm')
    if (!form.length) {
        return
    }
    let fill = function () {
        if (!form.find('[name="certificate_template_id"]').val()) {
            return
        }
        $.ajax({
            url: form.data('previewUrl'),
            data: {
                template_id: form.find('[name="certificate_template_id"]').val(),
                patient_id: form.find('[name="patient_id"]').val(),
                doctor_id: form.find('[name="doctor_id"]').val(),
                ipd_patient_department_id: form.find('[name="ipd_patient_department_id"]').val(),
                start_date: form.find('[name="start_date"]').val(),
                end_date: form.find('[name="end_date"]').val(),
                notes: form.find('[name="notes"]').val(),
            },
            success: function (result) {
                $('#certificateBody').val(result.body)
            },
        })
    }
    form.find('[name="certificate_template_id"]').on('change', fill)
    $('#certificateFill').on('click', fill)
}
