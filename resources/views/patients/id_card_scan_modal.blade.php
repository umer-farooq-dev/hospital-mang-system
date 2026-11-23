{{-- Card reader (Settings > Reception): the webcam reads the MRZ on the back of the ID card. JS: assets/js/patients/registration.js --}}
@if (\App\Models\Patient::registrationSettings()['id_scan'])
<div id="idCardScanModal" class="modal fade" tabindex="-1" aria-hidden="true" data-base="{{ asset('vendor/tesseract') }}"
    data-id-type="{{ \App\Models\Patient::registrationSettings()['id_scan_document_type'] }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('messages.reception.scan_id_card') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="alert alert-danger d-none id-scan-error" data-https="{{ __('messages.reception.camera_https') }}"
                    data-denied="{{ __('messages.reception.camera_denied') }}" data-engine="{{ __('messages.reception.id_scan_engine') }}"></div>
                <p class="text-muted">{{ __('messages.reception.id_scan_help') }}</p>
                <div class="position-relative d-inline-block w-100">
                    <video class="w-100 rounded bg-dark id-scan-video" autoplay playsinline muted></video>
                    <div class="position-absolute border border-3 border-success rounded" style="left: 8%; right: 8%; bottom: 10%; height: 30%; pointer-events: none;"></div>
                </div>
                <canvas class="d-none id-scan-canvas"></canvas>
                <div class="mt-3 fw-bold id-scan-status" data-loading="{{ __('messages.reception.id_scan_loading') }}"
                    data-reading="{{ __('messages.reception.id_scan_reading') }}"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
<div class="d-none id-scan-messages" data-done="{{ __('messages.reception.id_scan_done') }}" data-expired="{{ __('messages.reception.id_scan_expired') }}"></div>
@endif
