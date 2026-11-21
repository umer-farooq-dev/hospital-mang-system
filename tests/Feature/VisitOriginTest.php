<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Models\OpdPatientDepartment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VisitOriginTest extends TestCase
{
    public function test_origin_emergency_level_and_referrer_come_from_master_lists(): void
    {
        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedInteger('code');
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('value')->nullable();
            $t->timestamps();
        });
        Schema::create('opd_patient_departments', fn (Blueprint $t) => $t->id());
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        (require base_path('database/migrations/2026_11_22_110000_add_queue_cards_and_visit_origin.php'))->up();
        MasterListItem::create(['type' => 'referrer', 'code' => 1, 'name' => 'Dispensaire de quartier', 'is_active' => false]);
        MasterListItem::flushCache();

        app()->setLocale('fr');
        $this->assertSame([1 => 'Auto-orienté', 2 => 'Référé', 3 => 'Évacué', 4 => 'Transféré'], MasterListItem::options('patient_origin'));

        $rules = OpdPatientDepartment::visitListRules();
        $this->assertTrue(Validator::make(['patient_origin' => 3, 'emergency_level' => 2, 'referrer' => null], $rules)->passes());
        $this->assertTrue(Validator::make(['patient_origin' => 9], $rules)->fails(), 'code not in the list');
        $this->assertTrue(Validator::make(['referrer' => 1], $rules)->passes(), 'a deactivated referrer stays valid on old visits');
    }
}
