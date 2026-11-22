<?php

namespace App\Http\Controllers;

use App\Models\ExamOrder;
use App\Models\ExamOrderItem;
use App\Models\ExamResult;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;

/** Lab and imaging worklist: collect the sample, enter results or the report, validate, release, print. */
class LabWorklistController extends AppBaseController
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'open');
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : now()->subDays(7);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : now();
        $search = trim((string) $request->input('search'));
        $items = ExamOrderItem::with('order.patient.patientUser', 'order.doctor.doctorUser')
            ->whereIn('testable_type', $type === 'all' ? ExamOrderItem::WORK_TYPES : [$type])
            ->when($status === 'open', fn ($q) => $q->where('status', '!=', 'released'))
            ->when(in_array($status, ExamOrderItem::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->whereHas('order', fn ($q) => $q->whereBetween('ordered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('sample_number', 'like', "%$search%")->orWhere('description', 'like', "%$search%")
                ->orWhereHas('order.patient.patientUser', fn ($u) => $u->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%$search%"))))
            ->orderBy('id')->paginate(50)->withQueryString();

        return view('lab_worklist.index', [
            'items' => $items, 'type' => $type, 'status' => $status, 'from' => $from, 'to' => $to, 'search' => $search,
            'requirePayment' => $this->setting('lab_require_payment', '1') === '1',
        ]);
    }

    public function collect(ExamOrderItem $item)
    {
        abort_unless(in_array($item->testable_type, ExamOrderItem::WORK_TYPES, true), 404);
        if ($this->setting('lab_require_payment', '1') === '1' && ! $item->isPaid()) {
            Flash::error(__('messages.lab.not_paid'));

            return redirect()->back();
        }
        if ($item->status === 'ordered') {
            $item->update(['status' => 'collected', 'collected_at' => now(), 'collected_by' => auth()->id(),
                'sample_number' => ExamOrderItem::sampleNumber($this->setting('lab_sample_prefix', 'S'), $item->id)]);
        }
        Flash::success(__('messages.lab.collected', ['number' => $item->sample_number]));

        return redirect()->to(url()->previous() . (str_contains(url()->previous(), '?') ? '&' : '?') . 'label=' . $item->id);
    }

    public function label(ExamOrderItem $item)
    {
        return view('lab_worklist.label', ['item' => $item->load('order.patient.patientUser')]);
    }

    public function show(ExamOrderItem $item)
    {
        $item->load('order.patient.patientUser', 'order.doctor.doctorUser', 'results');
        $rows = $item->results->isNotEmpty()
            ? $item->results->map(fn ($r) => $r->only(['pathology_parameter_id', 'label', 'unit', 'reference_range', 'value']))->all()
            : ($item->is_lab ? ExamOrderItem::parametersOf($item->testable()) : []);

        return view('lab_worklist.results', ['item' => $item, 'rows' => $rows]);
    }

    public function update(Request $request, ExamOrderItem $item)
    {
        abort_if(in_array($item->status, ['validated', 'released'], true), 403, __('messages.lab.locked'));
        $data = $request->validate([
            'rows' => 'nullable|array',
            'rows.*.label' => 'required_with:rows|string|max:191',
            'rows.*.value' => 'nullable|string|max:100',
            'rows.*.unit' => 'nullable|string|max:50',
            'rows.*.reference_range' => 'nullable|string|max:100',
            'rows.*.pathology_parameter_id' => 'nullable|integer',
            'rows.*.is_abnormal' => 'nullable',
            'result_note' => 'nullable|string|max:5000',
            'report_text' => 'nullable|string|max:50000',
        ]);
        DB::transaction(function () use ($item, $data) {
            $item->results()->delete();
            foreach (array_values($data['rows'] ?? []) as $i => $row) {
                if (trim((string) ($row['label'] ?? '')) === '') {
                    continue;
                }
                $auto = ExamResult::isOutside($row['value'] ?? null, $row['reference_range'] ?? null);
                ExamResult::create([
                    'exam_order_item_id' => $item->id, 'pathology_parameter_id' => $row['pathology_parameter_id'] ?? null,
                    'label' => $row['label'], 'value' => $row['value'] ?? null, 'unit' => $row['unit'] ?? null,
                    'reference_range' => $row['reference_range'] ?? null, 'sort_order' => $i,
                    'is_abnormal' => $auto ?? ! empty($row['is_abnormal']),
                ]);
            }
            $item->update(['result_note' => $data['result_note'] ?? null, 'report_text' => $data['report_text'] ?? null,
                'status' => 'in_progress', 'result_entered_at' => now(), 'result_entered_by' => auth()->id(),
                'collected_at' => $item->collected_at ?? now()]);
        });
        Flash::success(__('messages.lab.results_saved'));

        return redirect()->route('lab-worklist.show', $item->id);
    }

    public function validateResults(ExamOrderItem $item)
    {
        abort_unless($item->status === 'in_progress', 422, __('messages.lab.nothing_to_validate'));
        $changes = ['status' => 'validated', 'validated_at' => now(), 'validated_by' => auth()->id()];
        if ($this->setting('lab_auto_release', '0') === '1') {
            $changes += ['status' => 'released', 'released_at' => now(), 'released_by' => auth()->id()];
        }
        $item->update($changes);
        Flash::success(__('messages.lab.validated'));

        return redirect()->route('lab-worklist.show', $item->id);
    }

    public function releaseResults(ExamOrderItem $item)
    {
        abort_unless($item->status === 'validated', 422, __('messages.lab.validate_first'));
        $item->update(['status' => 'released', 'released_at' => now(), 'released_by' => auth()->id()]);
        Flash::success(__('messages.lab.released'));

        return redirect()->route('lab-worklist.show', $item->id);
    }

    /** Report of an exam request: every validated or released exam with its values. */
    public function report(ExamOrder $order)
    {
        $order->load('patient.patientUser', 'doctor.doctorUser', 'items.results');

        return view('lab_worklist.report', [
            'order' => $order,
            'items' => $order->items->filter(fn ($i) => in_array($i->status, ['validated', 'released'], true)),
            'settings' => getSettingValue(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate(['lab_sample_prefix' => ['required', 'string', 'max:6', 'regex:/^[A-Za-z0-9]+$/']]);
        foreach (['lab_sample_prefix' => strtoupper($request->input('lab_sample_prefix')),
            'lab_auto_release' => $request->boolean('lab_auto_release') ? '1' : '0',
            'lab_require_payment' => $request->boolean('lab_require_payment') ? '1' : '0'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Flash::success(__('messages.lab.settings_saved'));

        return redirect()->route('lab-worklist.index');
    }

    private function setting(string $key, string $default): string
    {
        return (string) (Setting::where('key', $key)->value('value') ?? $default);
    }
}
