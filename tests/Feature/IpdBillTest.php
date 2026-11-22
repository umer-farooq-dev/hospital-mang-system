<?php

namespace Tests\Feature;

use App\Repositories\IpdBillRepository;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IpdBillTest extends TestCase
{
    public function test_bed_units_per_day_night_or_hour(): void
    {
        $in = Carbon::parse('2026-11-20 22:00');
        $out = Carbon::parse('2026-11-23 09:00'); // 2 days 11 hours, 3 midnights

        $this->assertSame(3, IpdBillRepository::bedUnits($in, $out, 'day'), 'a started day counts');
        $this->assertSame(3, IpdBillRepository::bedUnits($in, $out, 'night'));
        $this->assertSame(59, IpdBillRepository::bedUnits($in, $out, 'hour'));
        $this->assertSame(1, IpdBillRepository::bedUnits($in, $in->copy()->addMinutes(30), 'night'), 'at least one unit');
        $this->assertSame(1, IpdBillRepository::bedUnits($in, $in->copy()->subHour(), 'day'), 'end before start');
    }

    public function test_net_payable_taxes_the_charges_and_deducts_cover_payments_and_advances(): void
    {
        // 3 bed days at 10 000 + 20 000 of charges; 10% tax; insurer covers 40 000; 5 000 paid; 2 000 advance.
        $net = IpdBillRepository::netPayable(['charges' => 20000, 'bed' => 30000, 'other' => 1000, 'discount_percent' => 0,
            'tax_percent' => 10, 'cover' => 40000, 'payments' => 5000, 'advances' => 2000]);

        $this->assertSame(9000.0, $net, '50 000 + 1 000 + 5 000 tax - 40 000 - 5 000 - 2 000');
    }
}
