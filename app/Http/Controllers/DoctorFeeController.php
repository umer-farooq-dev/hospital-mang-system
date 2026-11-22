<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorFeePlan;
use App\Models\DoctorStatement;
use App\Models\MasterListItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Doctors' pay plans and revenue statements. */
class DoctorFeeController extends AppBaseController
{
    private function doctors(): array
    {
        return Doctor::with('doctorUser')->get()->mapWithKeys(fn ($d) => [$d->id => $d->doctorUser->full_name ?? '#'.$d->id])->sort()->all();
    }

    public function index(Request $request)
    {
        $status = in_array($request->input('status'), DoctorStatement::STATUSES, true) ? $request->input('status') : null;
        $statements = DoctorStatement::with('doctor.doctorUser')
            ->when($request->integer('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($status, fn ($q) => $q->where('status', $status))->orderByDesc('id')->paginate(30)->withQueryString();

        return view('doctor_fees.index', ['statements' => $statements, 'doctors' => $this->doctors(), 'status' => $status]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from',
            'hours' => 'nullable|numeric|min:0|max:10000', 'other' => 'nullable|numeric|min:0', 'note' => 'nullable|string|max:255',
        ]);
        try {
            $statement = DoctorStatement::open(Doctor::findOrFail($data['doctor_id']), Carbon::parse($data['from']), Carbon::parse($data['to']),
                isset($data['hours']) ? (float) $data['hours'] : null, (float) ($data['other'] ?? 0), $data['note'] ?? null, getLoggedInUserId());
        } catch (HttpException $e) {
            Flash::error($e->getMessage());

            return back()->withInput();
        }
        Flash::success(__('messages.doctor_fees.made', ['number' => $statement->number]));

        return redirect()->route('doctor-fees.show', $statement->id);
    }

    public function show(DoctorStatement $doctorStatement)
    {
        $doctorStatement->load('doctor.doctorUser', 'lines');

        return view('doctor_fees.show', ['statement' => $doctorStatement, 'plan' => DoctorFeePlan::forDoctor($doctorStatement->doctor_id)]);
    }

    public function updateStatement(DoctorStatement $doctorStatement)
    {
        return $this->step($doctorStatement, fn () => $doctorStatement->recompute());
    }

    public function approveStatement(DoctorStatement $doctorStatement)
    {
        return $this->step($doctorStatement, fn () => $doctorStatement->validateStatement(getLoggedInUserId()));
    }

    public function storePayment(Request $request, DoctorStatement $doctorStatement)
    {
        $data = $request->validate([
            'paid_on' => 'required|date|before_or_equal:today',
            'payment_method' => ['nullable', 'integer', Rule::in(array_keys(MasterListItem::options('payment_method')))],
            'payment_reference' => 'nullable|string|max:100',
        ]);

        return $this->step($doctorStatement, fn () => $doctorStatement->markPaid($data['paid_on'], $data['payment_method'] ?? null, $data['payment_reference'] ?? null));
    }

    public function cancel(DoctorStatement $doctorStatement)
    {
        return $this->step($doctorStatement, fn () => $doctorStatement->cancel());
    }

    public function exportStatement(DoctorStatement $doctorStatement)
    {
        $doctorStatement->load('doctor.doctorUser', 'lines');

        return response()->streamDownload(function () use ($doctorStatement) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('messages.stock.date'), __('messages.doctor_fees.kind'), __('messages.doctor_fees.detail'), __('messages.doctor_fees.base'), '%', __('messages.doctor_fees.amount')], ';');
            foreach ($doctorStatement->lines as $line) {
                fputcsv($out, [$line->line_date?->format('d/m/Y'), __('messages.doctor_fees.kinds.'.$line->kind), $line->description,
                    number_format($line->base_amount, 2, ',', ''), $line->percent !== null ? number_format($line->percent, 2, ',', '') : '', number_format($line->amount, 2, ',', '')], ';');
            }
            fputcsv($out, ['', '', __('messages.doctor_fees.net'), '', '', number_format($doctorStatement->net, 2, ',', '')], ';');
            fclose($out);
        }, $doctorStatement->number.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Pay plan of each doctor and the withholding taxes. */
    public function plans()
    {
        return view('doctor_fees.plans', [
            'doctors' => $this->doctors(),
            'plans' => DoctorFeePlan::all()->keyBy('doctor_id'),
            'withholdings' => DoctorStatement::withholdings(),
            'prefix' => Setting::where('key', 'doctor_statement_prefix')->value('value') ?: 'HD',
        ]);
    }

    public function updatePlans(Request $request)
    {
        $request->validate([
            'plans' => 'nullable|array',
            'plans.*.pay_type' => ['required', Rule::in(DoctorFeePlan::PAY_TYPES)],
            'plans.*.rate' => 'nullable|numeric|min:0',
            'withholdings' => 'nullable|array',
            'withholdings.*.name' => 'nullable|string|max:60',
            'withholdings.*.percent' => 'nullable|numeric|min:0|max:100',
            'doctor_statement_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
        ]);
        foreach ((array) $request->input('plans', []) as $doctorId => $row) {
            if (Doctor::whereKey($doctorId)->exists()) {
                DoctorFeePlan::updateOrCreate(['doctor_id' => (int) $doctorId], ['pay_type' => $row['pay_type'], 'rate' => (float) ($row['rate'] ?? 0),
                    'withholding_exempt' => ! empty($row['withholding_exempt'])]);
            }
        }
        $withholdings = collect((array) $request->input('withholdings', []))
            ->map(fn ($r) => ['name' => trim((string) ($r['name'] ?? '')), 'percent' => (float) ($r['percent'] ?? 0)])
            ->filter(fn ($r) => $r['name'] !== '' && $r['percent'] > 0)->values()->all();
        Setting::updateOrCreate(['key' => 'doctor_withholdings'], ['value' => json_encode($withholdings)]);
        Setting::updateOrCreate(['key' => 'doctor_statement_prefix'], ['value' => $request->input('doctor_statement_prefix')]);
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('doctor-fees.plans');
    }

    private function step(DoctorStatement $statement, callable $action)
    {
        try {
            $action();
            Flash::success(__('messages.stock_transfer.done'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('doctor-fees.show', $statement->id);
    }
}
