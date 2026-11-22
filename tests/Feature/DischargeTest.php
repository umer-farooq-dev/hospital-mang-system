<?php

namespace Tests\Feature;

use App\Models\IpdPatientDepartment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DischargeTest extends TestCase
{
    public function test_discharge_rules_follow_the_admin_settings(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('currency_settings', function (Blueprint $t) {
            $t->id();
            $t->string('currency_name');
            $t->string('currency_code');
            $t->string('currency_icon');
            $t->timestamps();
        });
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        \Illuminate\Support\Facades\DB::table('currency_settings')->insert(['currency_name' => 'CFA franc', 'currency_code' => 'xof', 'currency_icon' => 'FCFA']);
        \Illuminate\Support\Facades\DB::table('settings')->insert(['key' => 'current_currency', 'value' => 'xof']);
        $checks = [1 => 'Summary given', 2 => 'Bill settled'];

        $this->assertSame([], IpdPatientDepartment::dischargeBlockers($checks, [], 5000, false, false), 'both rules off');
        $this->assertSame([], IpdPatientDepartment::dischargeBlockers($checks, [1, 2], 0, true, true), 'checklist done and nothing due');

        $reasons = IpdPatientDepartment::dischargeBlockers($checks, [1], 0, true, true);
        $this->assertCount(1, $reasons);
        $this->assertStringContainsString('Bill settled', $reasons[0]);

        $this->assertCount(1, IpdPatientDepartment::dischargeBlockers($checks, [], 1500, false, true), 'balance due');
        $this->assertSame([], IpdPatientDepartment::dischargeBlockers($checks, [], 0.004, false, true), 'rounding is not a balance');
        $this->assertCount(2, IpdPatientDepartment::dischargeBlockers($checks, [], 1500, true, true));
    }
}
