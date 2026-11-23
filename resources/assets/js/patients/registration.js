// Patient registration helpers shared by the patient forms and the quick "new patient" modals:
// live age from the date of birth, and the duplicate-patient warning (Settings > Reception).

document.addEventListener("DOMContentLoaded", loadPatientRegistration);

function loadPatientRegistration() {
    $("#addPatientForm .patientBirthDate").flatpickr({
        maxDate: new Date(),
        locale: $(".userCurrentLanguage").val(),
    });
    $(".patientBirthDate").each(function () {
        showPatientAge(this);
    });
}

function showPatientAge(input) {
    let target = $(input).closest(".form-group").find(".patient-age");
    if (!target.length) {
        return;
    }
    let value = $(input).val();
    let dob = value ? new Date(value) : null;
    if (!dob || isNaN(dob.getTime())) {
        target.text("");
        return;
    }
    let today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    let birthdayPassed =
        today.getMonth() > dob.getMonth() ||
        (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());
    if (!birthdayPassed) {
        age--;
    }
    target.text(String(target.data("template")).replace(":count", Math.max(age, 0)));
}

listenChange(".patientBirthDate", function () {
    showPatientAge(this);
});

const duplicateFields =
    "[name=first_name], [name=last_name], [name=dob], [name=phone], [name=id_document_number]";
let duplicateTimer = null;

listenChange(duplicateFields, function () {
    let form = $(this).closest("form");
    let box = form.find(".patient-duplicate-box");
    if (!box.length) {
        return;
    }
    clearTimeout(duplicateTimer);
    duplicateTimer = setTimeout(() => checkPatientDuplicates(form, box), 300);
});

function checkPatientDuplicates(form, box) {
    let data = { patient_id: box.data("patient-id") };
    ["first_name", "last_name", "dob", "phone", "prefix_code", "id_document_number"].forEach((name) => {
        data[name] = form.find("[name=" + name + "]").val() || "";
    });

    $.ajax({
        url: box.data("url"),
        type: "GET",
        data: data,
        success: function (result) {
            let list = box.find(".patient-duplicate-list").empty();
            (result.data || []).forEach(function (patient) {
                let item = $("<li>");
                $("<a>", { href: patient.url, target: "_blank" }).text(patient.name).appendTo(item);
                item.append(document.createTextNode(
                    " (" + patient.patient_unique_id + (patient.phone ? ", " + patient.phone : "") + ")"
                ));
                list.append(item);
            });
            box.toggleClass("d-none", list.children().length === 0);
        },
        // The warning is a help only; saving still runs the server-side check.
        error: function () {},
    });
}

// Patient photo from the webcam: the captured image goes into the form's normal
// profile picture input, so saving works exactly like an uploaded file.
let webcamStream = null;
let webcamForm = null;

listenClick(".webcam-photo-open", function () {
    webcamForm = $(this).closest("form");
    let modal = $("#webcamPhotoModal").appendTo("body");
    let errorBox = modal.find(".webcam-error").addClass("d-none");
    modal.modal("show");

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        errorBox.text(errorBox.data("https")).removeClass("d-none");
        return;
    }
    navigator.mediaDevices
        .getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 } }, audio: false })
        .then(function (stream) {
            webcamStream = stream;
            modal.find(".webcam-video")[0].srcObject = stream;
        })
        .catch(function () {
            errorBox.text(errorBox.data("denied")).removeClass("d-none");
        });
});

function stopWebcam() {
    if (webcamStream) {
        webcamStream.getTracks().forEach((track) => track.stop());
        webcamStream = null;
    }
}

listenHiddenBsModal("#webcamPhotoModal", stopWebcam);

listenClick(".webcam-capture", function () {
    let modal = $("#webcamPhotoModal");
    let video = modal.find(".webcam-video")[0];
    if (!webcamStream || !video.videoWidth || !webcamForm) {
        return;
    }
    let canvas = modal.find(".webcam-canvas")[0];
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);
    canvas.toBlob(
        function (blob) {
            let transfer = new DataTransfer();
            transfer.items.add(new File([blob], "webcam-photo.jpg", { type: "image/jpeg" }));
            webcamForm.find("input[name=image]")[0].files = transfer.files;
            webcamForm.find("input[name=avatar_remove]").val("");
            webcamForm.find(".previewImage").css("background-image", "url(" + canvas.toDataURL("image/jpeg") + ")");
            modal.modal("hide");
        },
        "image/jpeg",
        0.9
    );
});

// Card reader (Settings > Reception): the webcam reads the three MRZ lines on the back of the ID card
// (parseMrz in mrz.js checks every check digit), fills the form, and keeps the image for the documents.
// The OCR engine is served from public/vendor/tesseract and loaded only when the reader is opened.
let idScan = { stream: null, form: null, worker: null, running: false };

function loadOcrEngine(base) {
    if (window.Tesseract) {
        return Promise.resolve();
    }
    return new Promise(function (resolve, reject) {
        let script = document.createElement("script");
        script.src = base + "/tesseract.min.js";
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

listenClick(".id-card-scan-open", function () {
    idScan.form = $(this).closest("form");
    let modal = $("#idCardScanModal").appendTo("body");
    let errorBox = modal.find(".id-scan-error").addClass("d-none");
    let status = modal.find(".id-scan-status");
    modal.modal("show");
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        errorBox.text(errorBox.data("https")).removeClass("d-none");
        return;
    }
    status.text(status.data("loading"));
    let base = modal.data("base");
    navigator.mediaDevices
        .getUserMedia({ video: { width: { ideal: 1920 }, height: { ideal: 1080 }, facingMode: "environment" }, audio: false })
        .then(function (stream) {
            idScan.stream = stream;
            modal.find(".id-scan-video")[0].srcObject = stream;
            return loadOcrEngine(base);
        })
        .then(function () {
            if (idScan.worker) {
                return idScan.worker;
            }
            return Tesseract.createWorker("eng", 1, {
                workerPath: base + "/worker.min.js",
                corePath: base,
                langPath: base,
                gzip: true,
            }).then(function (worker) {
                idScan.worker = worker;
                return worker.setParameters({
                    tessedit_char_whitelist: "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<",
                    tessedit_pageseg_mode: "6",
                }).then(() => worker);
            });
        })
        .then(function () {
            if (!idScan.stream) {
                return;
            }
            status.text(status.data("reading"));
            idScan.running = true;
            readCardLoop(modal);
        })
        .catch(function (error) {
            let message = error && error.name === "NotAllowedError" ? errorBox.data("denied") : errorBox.data("engine");
            errorBox.text(message).removeClass("d-none");
            status.text("");
        });
});

// Reads the band inside the green frame again and again until a frame passes every check digit.
function readCardLoop(modal) {
    if (!idScan.running || !idScan.stream) {
        return;
    }
    let video = modal.find(".id-scan-video")[0];
    if (!video.videoWidth) {
        setTimeout(() => readCardLoop(modal), 300);
        return;
    }
    let canvas = modal.find(".id-scan-canvas")[0];
    let band = { x: video.videoWidth * 0.08, y: video.videoHeight * 0.6, w: video.videoWidth * 0.84, h: video.videoHeight * 0.3 };
    let scale = Math.min(2, 1600 / band.w);
    canvas.width = band.w * scale;
    canvas.height = band.h * scale;
    let context = canvas.getContext("2d");
    context.filter = "grayscale(1) contrast(1.6)";
    context.drawImage(video, band.x, band.y, band.w, band.h, 0, 0, canvas.width, canvas.height);
    idScan.worker
        .recognize(canvas)
        .then(function (result) {
            let card = window.parseMrz(result.data.text);
            if (card) {
                fillFromCard(modal, video, card);
            } else {
                setTimeout(() => readCardLoop(modal), 200);
            }
        })
        .catch(() => setTimeout(() => readCardLoop(modal), 1000));
}

function titleCase(text) {
    return text.toLowerCase().replace(/(^|[\s-])\S/g, (c) => c.toUpperCase());
}

function fillFromCard(modal, video, card) {
    idScan.running = false;
    let form = idScan.form;
    let set = (name, value) => value && form.find("[name=" + name + "]").val(value).trigger("change");
    set("last_name", card.lastName);
    set("first_name", titleCase(card.firstNames));
    set("id_document_number", card.documentNumber);
    let idType = modal.data("id-type");
    if (idType) {
        set("id_document_type", String(idType));
    }
    if (card.sex) {
        form.find("[name=gender][value=" + (card.sex === "F" ? 1 : 0) + "]").prop("checked", true).trigger("change");
    }
    if (card.birthDate) {
        let dob = form.find("[name=dob]")[0];
        if (dob && dob._flatpickr) {
            dob._flatpickr.setDate(card.birthDate, true);
        } else {
            set("dob", card.birthDate);
        }
    }
    // Keep a picture of the whole card for the patient's documents.
    let canvas = modal.find(".id-scan-canvas")[0];
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    let context = canvas.getContext("2d");
    context.filter = "none";
    context.drawImage(video, 0, 0);
    canvas.toBlob(
        function (blob) {
            let input = form.find(".id-card-image")[0];
            if (input && blob) {
                let transfer = new DataTransfer();
                transfer.items.add(new File([blob], "id-card.jpg", { type: "image/jpeg" }));
                input.files = transfer.files;
            }
        },
        "image/jpeg",
        0.85
    );
    let messages = $(".id-scan-messages");
    let expired = card.expiryDate && card.expiryDate < new Date().toISOString().slice(0, 10);
    modal.modal("hide");
    displaySuccessMessage(messages.data("done"));
    if (expired) {
        displayErrorMessage(String(messages.data("expired")).replace(":date", card.expiryDate.split("-").reverse().join("/")));
    }
}

listenHiddenBsModal("#idCardScanModal", function () {
    idScan.running = false;
    if (idScan.stream) {
        idScan.stream.getTracks().forEach((track) => track.stop());
        idScan.stream = null;
    }
});
