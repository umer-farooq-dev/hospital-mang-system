{{-- Lab and imaging exams with their status; values only once released. Params: $examItems, $requestUrl (optional). --}}
<div class="card">
    <div class="card-body">
        @if (! empty($requestUrl) && canAccessRoute('exam-orders.create'))
            <div class="d-flex justify-content-end mb-5">
                <a href="{{ $requestUrl }}" class="btn btn-primary">{{ __('messages.exam_results.request') }}</a>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table table-row-dashed align-middle">
                <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.exam_orders.exam') }}</th><th>{{ __('messages.ot.status') }}</th><th>{{ __('messages.lab.results') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($examItems as $exam)
                        <tr>
                            <td class="text-nowrap">{{ $exam->order?->ordered_at?->format('d/m/Y H:i') }}<div class="text-muted fs-7">{{ $exam->order?->doctor?->doctorUser?->full_name }}</div></td>
                            <td>{{ $exam->description }}<div class="text-muted fs-7">{{ $exam->is_lab ? __('messages.lab.lab') : __('messages.lab.imaging') }} {{ $exam->sample_number ? '· ' . $exam->sample_number : '' }}</div></td>
                            <td>@include('lab_worklist.status', ['status' => $exam->status])</td>
                            <td class="fs-7">
                                @if ($exam->status === 'released')
                                    @foreach ($exam->results as $result)
                                        <div class="{{ $result->is_abnormal ? 'text-danger fw-bold' : '' }}">{{ $result->label }}: {{ $result->value }} {{ $result->unit }}</div>
                                    @endforeach
                                    @if ($exam->report_text)<div>{{ \Illuminate\Support\Str::limit($exam->report_text, 160) }}</div>@endif
                                @else
                                    <span class="text-muted">{{ __('messages.exam_results.pending') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($exam->status === 'released' && canAccessRoute('exam-orders.report'))
                                    <a href="{{ route('exam-orders.report', $exam->exam_order_id) }}" target="_blank" class="btn btn-sm btn-light-success"><i class="fas fa-print"></i></a>
                                @endif
                                @if (canAccessRoute('lab-worklist.show'))
                                    <a href="{{ route('lab-worklist.show', $exam->id) }}" class="btn btn-sm btn-light-primary">{{ __('messages.lab.results') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">{{ __('messages.exam_results.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
