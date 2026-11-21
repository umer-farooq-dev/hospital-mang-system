<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReceptionDeskRepository
{
    const SEARCH_LIMIT = 15;

    const RECENT_LIMIT = 8;

    public function search(string $term): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        // The hospital ID card QR holds a link ending with the patient number.
        if (filter_var($term, FILTER_VALIDATE_URL)) {
            $term = basename(parse_url($term, PHP_URL_PATH) ?: $term);
        }

        $idNumber = Patient::normalizeIdNumber($term);
        $digits = preg_replace('/\D/', '', $term);
        $words = preg_split('/\s+/', $term);

        return Patient::with('patientUser.media')
            ->where(function (Builder $q) use ($term, $idNumber, $digits, $words) {
                $q->where('patient_unique_id', $term)
                    ->orWhere('id_document_number', $idNumber)
                    ->orWhereHas('patientUser', function (Builder $u) use ($words, $digits) {
                        $u->where(function (Builder $name) use ($words) {
                            // Every word must appear in the first or last name, in any order.
                            foreach ($words as $word) {
                                $name->where(fn ($w) => $w->where('first_name', 'like', '%'.$word.'%')
                                    ->orWhere('last_name', 'like', '%'.$word.'%'));
                            }
                        });
                        if (strlen($digits) >= 4) {
                            $u->orWhere('phone', 'like', '%'.$digits.'%');
                        }
                    });
            })
            ->latest('id')
            ->limit(self::SEARCH_LIMIT)
            ->get();
    }

    public function todayAppointments(): Collection
    {
        return Appointment::with(['patient.patientUser', 'doctor.doctorUser', 'doctor.department'])
            ->whereDate('opd_date', today())
            ->whereHas('patient')
            ->orderBy('opd_date')
            ->get();
    }

    public function recentPatients(): Collection
    {
        return Patient::with('patientUser.media')->whereHas('patientUser')->latest('id')->limit(self::RECENT_LIMIT)->get();
    }
}
