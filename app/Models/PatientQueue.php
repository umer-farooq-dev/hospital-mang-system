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

    /** A patient keeps the card while waiting and during the consultation. */
    const CARD_HELD_STATUSES = [Appointment::STATUS_IN_QUEUE, Appointment::STATUS_CHECK_IN];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /** Number of reusable queue cards at reception (Settings > Reception); 0 = no cards. */
    public static function cardCount(): int
    {
        return max(0, (int) Setting::where('key', 'queue_card_count')->value('value'));
    }

    /**
     * Put an appointment in today's queue and give it the next token number.
     * Numbers start again at 1 every day; an appointment already queued keeps its token.
     * With cards, the number is the next card after the last one given, going round,
     * skipping cards still held by a patient.
     */
    public static function enqueue(Appointment $appointment): self
    {
        return DB::transaction(function () use ($appointment) {
            $appointment->update(['is_completed' => Appointment::STATUS_IN_QUEUE]);

            $existing = self::where('appointment_id', $appointment->id)->whereDate('created_at', today())->first();
            if ($existing) {
                return $existing;
            }

            $today = self::whereDate('created_at', today())->lockForUpdate()->orderBy('id')->get(['id', 'no', 'appointment_id']);

            return self::create(['appointment_id' => $appointment->id, 'no' => self::nextNumber($today, self::cardCount())]);
        });
    }

    private static function nextNumber($today, int $cards): int
    {
        $highest = (int) $today->max('no');
        if ($cards === 0) {
            return $highest + 1;
        }

        $held = self::whereIn('id', $today->pluck('id'))
            ->whereHas('appointment', fn ($q) => $q->whereIn('is_completed', self::CARD_HELD_STATUSES))
            ->pluck('no')->all();
        $last = (int) ($today->last()->no ?? 0);
        for ($step = 1; $step <= $cards; $step++) {
            $no = ($last + $step - 1) % $cards + 1;
            if (! in_array($no, $held)) {
                return $no;
            }
        }

        // Every card is out: carry on above the card numbers so nobody is turned away.
        return max($cards, $highest) + 1;
    }
}
