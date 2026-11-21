<div class="d-flex align-items-center mt-2">
    @if ($row->payment_mode_name)
        <span class="badge bg-light-{{ $row->payment_mode == 1 ? 'primary' : 'success' }}">{{ $row->payment_mode_name }}</span>
    @endif
</div>

