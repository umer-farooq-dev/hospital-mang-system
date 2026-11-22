<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Operating theatre: room, schedule, status, team, pre-op checklist, K tariff and post-op notes. */
    public function up(): void
    {
        Schema::table('ipd_operation', function (Blueprint $table) {
            $table->unsignedInteger('ot_room')->nullable()->after('operation_date');
            $table->dateTime('scheduled_start')->nullable()->after('ot_room');
            $table->dateTime('scheduled_end')->nullable()->after('scheduled_start');
            $table->unsignedInteger('status')->nullable()->after('scheduled_end');
            $table->unsignedInteger('intervention_type')->nullable()->after('status');
            $table->unsignedBigInteger('assistant_doctor_id')->nullable()->after('doctor_id');
            $table->unsignedBigInteger('anaesthetist_doctor_id')->nullable()->after('assistant_doctor_id');
            $table->text('preop_checks')->nullable();
            $table->decimal('k_value', 14, 2)->nullable();
            $table->decimal('surgeon_fee', 14, 2)->default(0);
            $table->decimal('assistant_fee', 14, 2)->default(0);
            $table->decimal('anaesthetist_fee', 14, 2)->default(0);
            $table->index(['ot_room', 'scheduled_start']);
        });
        Schema::table('operations', function (Blueprint $table) {
            $table->decimal('k_surgeon', 8, 2)->nullable();
            $table->decimal('k_assistant', 8, 2)->nullable();
            $table->decimal('k_anaesthetist', 8, 2)->nullable();
        });
        Schema::table('price_lists', function (Blueprint $table) {
            $table->decimal('k_value', 14, 2)->nullable()->after('adjustment_percent');
        });
        Schema::create('operation_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_operation_id')->index();
            $table->dateTime('noted_at');
            $table->text('note');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        $now = now();
        $lists = [
            'ot_room' => [[1, 'Theatre 1', 'theatre_1']],
            'operation_status' => [[1, 'Scheduled', 'scheduled'], [2, 'In progress', 'in_progress'], [3, 'Done', 'done'],
                [4, 'Postponed', 'postponed'], [5, 'Cancelled', 'cancelled']],
            'intervention_type' => [[1, 'Scheduled', 'scheduled'], [2, 'Emergency', 'emergency']],
            'anaesthesia_type' => [[1, 'General (AG)', 'general'], [2, 'Regional (ALR)', 'regional'], [3, 'Local', 'local']],
            'preop_check' => [[1, 'Consent signed', 'consent'], [2, 'Fasting checked', 'fasting'], [3, 'Blood group / cross-match', 'blood'],
                [4, 'Pre-anaesthesia consultation done', 'anaesthesia_visit'], [5, 'Operation site marked', 'site'], [6, 'Allergies checked', 'allergies']],
        ];
        foreach ($lists as $type => $items) {
            foreach ($items as $i => [$code, $name, $key]) {
                DB::table('master_list_items')->insertOrIgnore([
                    'type' => $type, 'code' => $code, 'name' => $name, 'lang_key' => 'messages.ot.lists.' . $type . '.' . $key,
                    'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        // Cancelled operations free the room and are not billed (the admin can change this on the OT calendar).
        if (! DB::table('settings')->where('key', 'ot_excluded_statuses')->exists()) {
            DB::table('settings')->insert(['key' => 'ot_excluded_statuses', 'value' => '5', 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! Module::where('name', 'OT Calendar')->exists()) {
            Module::create(['name' => 'OT Calendar', 'is_active' => 1, 'route' => 'ot-calendar.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'OT Calendar')->delete();
        DB::table('settings')->where('key', 'ot_excluded_statuses')->delete();
        DB::table('master_list_items')->whereIn('type', ['ot_room', 'operation_status', 'intervention_type', 'anaesthesia_type', 'preop_check'])->delete();
        Schema::dropIfExists('operation_followups');
        Schema::table('price_lists', fn (Blueprint $table) => $table->dropColumn('k_value'));
        Schema::table('operations', fn (Blueprint $table) => $table->dropColumn(['k_surgeon', 'k_assistant', 'k_anaesthetist']));
        Schema::table('ipd_operation', function (Blueprint $table) {
            $table->dropIndex(['ot_room', 'scheduled_start']);
            $table->dropColumn(['ot_room', 'scheduled_start', 'scheduled_end', 'status', 'intervention_type', 'assistant_doctor_id',
                'anaesthetist_doctor_id', 'preop_checks', 'k_value', 'surgeon_fee', 'assistant_fee', 'anaesthetist_fee']);
        });
    }
};
