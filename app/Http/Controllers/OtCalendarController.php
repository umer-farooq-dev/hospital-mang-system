<?php

namespace App\Http\Controllers;

use App\Models\IpdOperation;
use App\Models\MasterListItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;

/** Calendar of all operating theatres. */
class OtCalendarController extends AppBaseController
{
    public function index()
    {
        return view('ot_calendar.index', [
            'rooms' => MasterListItem::options('ot_room'),
            'statuses' => MasterListItem::options('operation_status'),
            'excluded' => IpdOperation::excludedStatuses(),
        ]);
    }

    public function getEvents(Request $request)
    {
        $from = Carbon::parse($request->input('start', now()->startOfMonth()));
        $to = Carbon::parse($request->input('end', now()->endOfMonth()));
        $excluded = IpdOperation::excludedStatuses();
        $rooms = MasterListItem::labels('ot_room');
        $palette = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#14B8A6', '#EC4899', '#6366F1'];

        $operations = IpdOperation::with('operations', 'ipd_patient_department.patient.patientUser', 'surgeon.doctorUser')
            ->where(fn ($q) => $q->whereBetween('scheduled_start', [$from, $to])
                ->orWhere(fn ($q) => $q->whereNull('scheduled_start')->whereBetween('operation_date', [$from->toDateString(), $to->toDateString()])))
            ->when($request->filled('room'), fn ($q) => $q->where('ot_room', $request->integer('room')))
            ->get();

        return response()->json($operations->map(function (IpdOperation $op) use ($excluded, $rooms, $palette) {
            $start = $op->scheduled_start ?? Carbon::parse($op->operation_date);
            $freed = in_array((int) $op->status, $excluded, true);

            return [
                'id' => $op->id,
                'title' => implode(' · ', array_filter([$rooms[$op->ot_room] ?? null, $op->ipd_patient_department?->patient?->patientUser?->full_name,
                    $op->operations?->name, $op->surgeon?->doctorUser?->full_name])),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $op->scheduled_end?->format('Y-m-d\TH:i:s'),
                'allDay' => $op->scheduled_start === null,
                'url' => route('operation.sheet', $op->id),
                'color' => $freed ? '#9CA3AF' : $palette[((int) $op->ot_room) % count($palette)],
            ];
        })->values());
    }

    public function update(Request $request)
    {
        $codes = array_map('intval', (array) $request->input('excluded_statuses', []));
        Setting::updateOrCreate(['key' => 'ot_excluded_statuses'], ['value' => implode(',', $codes)]);
        Flash::success(__('messages.ot.settings_saved'));

        return redirect()->route('ot-calendar.index');
    }
}
