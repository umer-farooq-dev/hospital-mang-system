document.addEventListener('DOMContentLoaded', initIcd10Selects)

// ICD-10 code search (select2 + ajax) in the diagnosis forms.
function initIcd10Selects () {
    $('.icd10-select').each(function () {
        let select = $(this)
        if (select.data('select2')) {
            return
        }
        select.select2({
            dropdownParent: select.closest('.modal').length ? select.closest('.modal') : $(document.body),
            placeholder: select.data('placeholder'),
            allowClear: true,
            minimumInputLength: 1,
            width: '100%',
            ajax: { url: select.data('url'), dataType: 'json', delay: 250, data: params => ({ q: params.term }) },
        })
    })
    $(document).on('hidden.bs.modal', '.modal', function () {
        $(this).find('.icd10-select').val(null).trigger('change')
    })
}

// Fill the ICD-10 fields of an edit form from the diagnosis returned by the server.
window.setIcd10Fields = function (form, data) {
    let select = $(form).find('.icd10-select')
    select.empty()
    if (data.icd10_code) {
        select.append(new Option(data.icd10_label || data.icd10_code, data.icd10_code, true, true))
    }
    select.trigger('change')
    $(form).find('[name="diagnosis_status"]').val(data.diagnosis_status === null || data.diagnosis_status === undefined ? '' : String(data.diagnosis_status))
}
