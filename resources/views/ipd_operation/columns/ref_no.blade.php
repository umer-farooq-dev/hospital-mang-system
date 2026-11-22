@if ($row->ref_no === null)
    {{ __('messages.common.n/a') }}
@else
    <a href="{{ route('operation.sheet', $row->id) }}" class="badge bg-light-info text-decoration-none" title="{{ __('messages.ot.sheet') }}">
        <div>{{ $row->ref_no }}</div>
    </a>
@endif
