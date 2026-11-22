document.addEventListener('DOMContentLoaded', function () {
    // After a sample is collected, open its label for printing.
    let page = document.getElementById('labWorklist')
    if (page && page.dataset.labelUrl) {
        window.open(page.dataset.labelUrl, '_blank')
    }
})
