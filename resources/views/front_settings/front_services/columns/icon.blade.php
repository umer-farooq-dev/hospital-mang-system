@php($thumb = $row->icon_url ?: $row->image_url)
@if (!empty($thumb))
    <div class="d-flex align-items-center">
        <div class="image image-mini me-3">
            <a href="javascript:void(0)">
                <img src="{{ $thumb }}" alt="user" class="user-img rounded-circle object-contain">
            </a>
        </div>
    </div>
@else
    <div class="d-flex align-items-center">
        <div class="image image-circle image-mini me-3">
            <img src="{{ asset('web_front/images/services/medicine.png') }}" alt="user" class="user-img image rounded-circle object-contain">
        </div>
    </div>
@endif
