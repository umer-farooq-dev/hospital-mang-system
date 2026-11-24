document.addEventListener('DOMContentLoaded', loadWebContentData)

function loadWebContentData() {
    // if(!$('.doctor-name-filter').length) {
    //     return
    // }
    //
    // if(!$('.datepicker').length) {
    //     return
    // }

    $('.doctor-name-filter').selectize();
    $.datepicker.setDefaults($.datepicker.regional[$('.userCurrentLanguage').val()])
    $('.datepicker').datepicker(
        {
            minDate: new Date(),
            isRTL: false,
            dateFormat: 'mm/dd/yy',
        });
}
