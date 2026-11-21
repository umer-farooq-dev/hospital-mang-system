{{-- Take the patient's photo with the webcam (Settings > Reception > Webcam). JS: assets/js/patients/registration.js --}}
<div id="webcamPhotoModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('messages.reception.take_photo') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="alert alert-danger d-none webcam-error" data-https="{{ __('messages.reception.camera_https') }}"
                    data-denied="{{ __('messages.reception.camera_denied') }}"></div>
                <video class="w-100 rounded bg-dark webcam-video" autoplay playsinline muted></video>
                <canvas class="d-none webcam-canvas"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary webcam-capture">
                    <i class="fas fa-camera me-1"></i>{{ __('messages.reception.capture') }}
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
