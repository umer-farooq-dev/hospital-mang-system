<div class="d-flex align-items-center mt-2">
    <a href="{{ getLoggedinPatient() ? url('employee/invoices'). '/' . $row->id : route('invoices.show',$row->id) }}"
       class="badge bg-light-info text-decoration-none">{{ $row->invoice_id }}</a>
    @if ($row->type === 'proforma')<span class="badge bg-light-warning text-warning ms-2">{{ __('messages.proforma.short') }}</span>@endif    
</div>

