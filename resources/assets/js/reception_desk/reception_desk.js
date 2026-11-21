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

// Scan the hospital patient card (QR) with the webcam; the result goes into the search.
let deskScanner = null;

listenClick("#receptionScanCard", function () {
    let modal = $("#receptionScanModal").appendTo("body");
    let errorBox = modal.find(".webcam-error").addClass("d-none");
    modal.modal("show");

    if (typeof Html5Qrcode === "undefined" || !navigator.mediaDevices) {
        errorBox.text(errorBox.data("https")).removeClass("d-none");
        return;
    }
    deskScanner = new Html5Qrcode("receptionQrReader");
    deskScanner
        .start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            function (text) {
                stopDeskScanner();
                modal.modal("hide");
                // The card holds a link ending with the patient number.
                let value = /^https?:\/\//i.test(text) ? text.replace(/\/+$/, "").split("/").pop() : text;
                $("#receptionDeskSearch").val(value);
                receptionDeskSearch(value);
            },
            function () {}
        )
        .catch(function () {
            deskScanner = null;
            errorBox.text(errorBox.data("denied")).removeClass("d-none");
        });
});

function stopDeskScanner() {
    if (deskScanner) {
        let scanner = deskScanner;
        deskScanner = null;
        scanner.stop().then(() => scanner.clear()).catch(() => {});
    }
}

listenHiddenBsModal("#receptionScanModal", stopDeskScanner);
