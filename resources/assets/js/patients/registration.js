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
