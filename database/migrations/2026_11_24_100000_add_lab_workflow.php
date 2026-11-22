<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Lab and imaging work on each requested exam: sample, results, validation, release. */
    public function up(): void
    {
        Schema::table('exam_order_items', function (Blueprint $table) {
            $table->string('status', 20)->default('ordered')->after('patient_amount')->index();
            $table->string('sample_number', 30)->nullable()->unique()->after('status');
            $table->dateTime('collected_at')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->dateTime('result_entered_at')->nullable();
            $table->unsignedBigInteger('result_entered_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->text('result_note')->nullable();
            $table->longText('report_text')->nullable();
        });
        // Values are copied with the parameter's name, unit and range so later changes to the test do not alter old reports.
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_order_item_id')->index();
            $table->unsignedBigInteger('pathology_parameter_id')->nullable();
            $table->string('label', 191);
            $table->string('value', 100)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('reference_range', 100)->nullable();
            $table->boolean('is_abnormal')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        $now = now();
        foreach (['lab_sample_prefix' => 'S', 'lab_auto_release' => '0', 'lab_require_payment' => '1'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (! Module::where('name', 'Lab Worklist')->exists()) {
            Module::create(['name' => 'Lab Worklist', 'is_active' => 1, 'route' => 'lab-worklist.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Lab Worklist')->delete();
        DB::table('settings')->whereIn('key', ['lab_sample_prefix', 'lab_auto_release', 'lab_require_payment'])->delete();
        Schema::dropIfExists('exam_results');
        Schema::table('exam_order_items', fn (Blueprint $table) => $table->dropColumn(['status', 'sample_number', 'collected_at', 'collected_by',
            'result_entered_at', 'result_entered_by', 'validated_at', 'validated_by', 'released_at', 'released_by', 'result_note', 'report_text']));
    }
};
