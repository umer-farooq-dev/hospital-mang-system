{{-- Stock taken out or put in by a purchase or a pharmacy bill: $document --}}
@php
    $moves = \App\Models\StockMovement::with('batch', 'stockable')->forDocument($document)->orderBy('id')->get();
    $isBill = $document instanceof \App\Models\MedicineBill;
    $issued = $moves->sum('quantity') < 0;
@endphp
<div class="card mt-7">
    <div class="card-body">
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">{{ __('messages.stock.stock') }} · {{ $document->store->name ?? '—' }}
                @if (! $isBill && $document->supplier) · {{ $document->supplier->name }}@endif
                @if ($isBill)
                    <span class="badge {{ $issued ? 'bg-light-success text-success' : 'bg-light-warning text-warning' }} ms-2">{{ $issued ? __('messages.stock.dispensed') : __('messages.stock.not_dispensed') }}</span>
                @endif
            </h5>
            @if ($isBill && ! $issued && $document->saleMedicine()->exists())
                @canroute('medicine-bills.dispense')
                    {{ Form::open(['route' => ['medicine-bills.dispense', $document->id], 'method' => 'post', 'class' => 'mt-3 mt-md-0']) }}
                    <button type="submit" class="btn btn-primary">{{ __('messages.stock.dispense') }}</button>
                    {{ Form::close() }}
                @endcanroute
            @endif
        </div>
        @if ($isBill && ! $issued)
            <div class="form-text mb-3">{{ __('messages.stock.dispense_hint') }}</div>
        @endif
        @if ($moves->isNotEmpty())
            <table class="table table-row-dashed align-middle mb-0">
                <thead>
                    <tr class="text-muted fs-7 text-uppercase">
                        <th>{{ __('messages.stock.date') }}</th>
                        <th>{{ __('messages.stock.type') }}</th>
                        <th>{{ __('messages.medicine.medicine') }}</th>
                        <th>{{ __('messages.stock.batch') }}</th>
                        <th>{{ __('messages.stock.expiry') }}</th>
                        <th class="text-end">{{ __('messages.stock.quantity') }}</th>
                    </tr>
                </thead>
                @foreach ($moves as $move)
                    <tr>
                        <td>{{ $move->created_at->translatedFormat('d/m/Y H:i') }}</td>
                        <td>{{ __('messages.stock.types.' . $move->movement_type) }}</td>
                        <td>{{ $move->stockable->name ?? '' }}</td>
                        <td>{{ $move->batch->batch_no ?? '—' }}</td>
                        <td>{{ $move->batch?->expiry_date ? \Illuminate\Support\Carbon::parse($move->batch->expiry_date)->translatedFormat('d/m/Y') : '—' }}</td>
                        <td class="text-end {{ $move->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $move->quantity > 0 ? '+' : '' }}{{ $move->quantity }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>
