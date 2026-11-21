<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PatientQueue extends Model
{
    use HasFactory;

    protected $table = 'patient_queues';

    protected $fillable = [
        'no',
        'appointment_id',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Put an appointment in today's queue and give it the next token number.
     * Numbers start again at 1 every day; an appointment already queued keeps its token.
     */
    public static function enqueue(Appointment $appointment): self
    {
        return DB::transaction(function () use ($appointment) {
            $appointment->update(['is_completed' => Appointment::STATUS_IN_QUEUE]);

            $existing = self::where('appointment_id', $appointment->id)->whereDate('created_at', today())->first();
            if ($existing) {
                return $existing;
            }

            $last = self::whereDate('created_at', today())->lockForUpdate()->max('no');

            return self::create(['appointment_id' => $appointment->id, 'no' => ($last ?? 0) + 1]);
        });
    }
}
