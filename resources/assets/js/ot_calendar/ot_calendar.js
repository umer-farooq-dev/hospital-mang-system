document.addEventListener('DOMContentLoaded', loadOtCalendar)

// Calendar of all operating theatres (events come from ot-calendar/events).
function loadOtCalendar () {
    let el = document.getElementById('otCalendar')
    if (!el || typeof FullCalendar === 'undefined') {
        return
    }
    let calendar = new FullCalendar.Calendar(el, {
        themeSystem: 'bootstrap5',
        height: 750,
        locale: el.dataset.locale,
        headerToolbar: { left: 'title', center: 'prev,next today', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        buttonText: { today: el.dataset.today, month: el.dataset.month, week: el.dataset.week, day: el.dataset.day },
        initialView: 'timeGridWeek',
        nowIndicator: true,
        editable: false,
        dayMaxEvents: true,
        events: function (info, success, failure) {
            $.ajax({
                url: el.dataset.eventsUrl,
                data: { start: info.startStr, end: info.endStr, room: $('#otRoomFilter').val() },
                success: success,
                error: failure,
            })
        },
    })
    calendar.render()
    $('#otRoomFilter').on('change', function () {
        calendar.refetchEvents()
    })
}
