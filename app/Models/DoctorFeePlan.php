<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** How one doctor is paid: a share of each act (price lists), per patient, per exam, by the hour or a fixed monthly amount. */
class DoctorFeePlan extends Model
{
    const PAY_TYPES = ['share', 'per_patient', 'per_exam', 'hourly', 'monthly'];

    protected $fillable = ['doctor_id', 'pay_type', 'rate', 'withholding_exempt'];

    protected $casts = ['rate' => 'float', 'withholding_exempt' => 'boolean'];

    public static function forDoctor(int $doctorId): self
    {
        return self::firstWhere('doctor_id', $doctorId) ?? new self(['doctor_id' => $doctorId, 'pay_type' => 'share', 'rate' => 0, 'withholding_exempt' => false]);
    }
}
