<?php

namespace App\Repositories;

use App\Models\MasterListItem;
use App\Models\OpdPatientDepartment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Patients registered in a period, and the OPD visits of that period split into
 * new patients (registered in the period) and returning patients.
 */
class PatientRegistrationReportRepository
{
    const GENDERS = ['male' => 0, 'female' => 1];

    /**
     * Clean filters from the request; the default period is the current month.
     */
    public function filters(array $input): array
    {
        $date = function ($value, Carbon $default) {
            try {
                return $value ? Carbon::parse($value) : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        };
        $from = $date($input['from'] ?? null, now()->startOfMonth())->startOfDay();
        $to = $date($input['to'] ?? null, now())->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'from' => $from,
            'to' => $to,
            'gender' => array_key_exists($input['gender'] ?? '', self::GENDERS) ? $input['gender'] : '',
            'id_document_type' => array_key_exists((int) ($input['id_document_type'] ?? 0), MasterListItem::labels('id_document_type'))
                ? (int) $input['id_document_type'] : null,
        ];
    }

    public function patients(array $filters): Builder
    {
        return Patient::with('patientUser')
            ->whereHas('patientUser', function (Builder $u) use ($filters) {
                if ($filters['gender'] !== '') {
                    $u->where('gender', self::GENDERS[$filters['gender']]);
                }
            })
            ->whereBetween('patients.created_at', [$filters['from'], $filters['to']])
            ->when($filters['id_document_type'], fn ($q, $type) => $q->where('id_document_type', $type))
            ->orderByDesc('patients.created_at');
    }

    public function summary(array $filters): array
    {
        $patients = $this->patients($filters)->get(['patients.id', 'patients.user_id', 'patients.created_at', 'patients.id_document_type', 'patients.id_document_number']);

        $byDay = $patients->groupBy(fn ($p) => $p->created_at->toDateString())->map->count()->sortKeys();
        $byIdType = $patients->groupBy(fn ($p) => $p->id_document_type ? $p->id_document_type_name : __('messages.reception_report.no_id'))
            ->map->count()->sortDesc();

        return [
            'total' => $patients->count(),
            'male' => $patients->where('patientUser.gender', 0)->count(),
            'female' => $patients->where('patientUser.gender', 1)->count(),
            'with_id' => $patients->whereNotNull('id_document_number')->count(),
            'by_day' => $byDay,
            'by_id_type' => $byIdType,
            'visits' => $this->visits($filters),
        ];
    }

    /**
     * OPD visits in the period by doctor department, new against returning patients.
     */
    public function visits(array $filters): Collection
    {
        $visits = OpdPatientDepartment::with(['doctor.department', 'patient.patientUser'])
            ->whereBetween('appointment_date', [$filters['from'], $filters['to']])
            ->whereHas('patient.patientUser', function (Builder $u) use ($filters) {
                if ($filters['gender'] !== '') {
                    $u->where('gender', self::GENDERS[$filters['gender']]);
                }
            })
            ->get();

        return $visits->groupBy(fn ($visit) => $visit->doctor->department->title ?? __('messages.common.n/a'))
            ->map(function (Collection $rows) use ($filters) {
                $isNew = fn ($visit) => $visit->patient->created_at->between($filters['from'], $filters['to']);

                return [
                    'visits' => $rows->count(),
                    'patients' => $rows->pluck('patient_id')->unique()->count(),
                    'new' => $rows->filter($isNew)->pluck('patient_id')->unique()->count(),
                    'returning' => $rows->reject($isNew)->pluck('patient_id')->unique()->count(),
                ];
            })
            ->sortKeys();
    }
}
