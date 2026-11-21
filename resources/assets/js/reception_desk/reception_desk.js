// Reception Desk: one search box for name, phone, patient number, ID number or a scanned
// hospital card. A USB/Bluetooth scanner types the card link and presses Enter.

let receptionSearchTimer = null;
let receptionSearchRequest = null;

listenKeyup("#receptionDeskSearch", function (e) {
    clearTimeout(receptionSearchTimer);
    let input = this;
    let delay = e.key === "Enter" ? 0 : 350;
    receptionSearchTimer = setTimeout(() => receptionDeskSearch($(input).val()), delay);
});

listenClick(".reception-desk-pick", function () {
    let value = $(this).data("search");
    $("#receptionDeskSearch").val(value);
    receptionDeskSearch(value);
    $("html, body").animate({ scrollTop: 0 }, 300);
});

function receptionDeskSearch(term) {
    term = String(term || "").trim();
    let results = $("#receptionDeskResults");
    if (term.length < 2) {
        results.empty();
        return;
    }
    if (receptionSearchRequest) {
        receptionSearchRequest.abort();
    }
    receptionSearchRequest = $.ajax({
        url: $("#receptionDeskSearch").data("url"),
        type: "GET",
        data: { search: term },
        success: function (result) {
            results.html(result.data);
        },
        error: function (result) {
            if (result.statusText !== "abort") {
                manageAjaxErrors(result);
            }
        },
    });
}
