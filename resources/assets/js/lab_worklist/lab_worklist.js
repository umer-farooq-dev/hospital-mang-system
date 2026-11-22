document.addEventListener('DOMContentLoaded', function () {
    // After a sample is collected, open its label for printing.
    let page = document.getElementById('labWorklist')
    if (page && page.dataset.labelUrl) {
        window.open(page.dataset.labelUrl, '_blank')
    }

    // Imaging report: insert the chosen template (appended when the report already has text).
    $('#imagingTemplateInsert').on('click', function () {
        let body = $('#imagingTemplate option:selected').data('body') || ''
        let report = $('#imagingReport')
        report.val(report.val().trim() === '' ? body : report.val().trimEnd() + '\n\n' + body).trigger('focus')
    })
})
