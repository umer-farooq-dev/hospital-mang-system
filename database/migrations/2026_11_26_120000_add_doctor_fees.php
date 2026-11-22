<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Doctor fees: how each doctor is paid, and statements per period with withholding (old "Revenus des médecins"). */
    public function up(): void
    {
        Schema::create('doctor_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->unique();
            $table->string('pay_type', 20)->default('share');
            $table->decimal('rate', 14, 2)->default(0);
            $table->boolean('withholding_exempt')->default(false);
            $table->timestamps();
        });
        Schema::create('doctor_statements', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('pay_type', 20);
            $table->decimal('hours', 8, 2)->nullable();
            $table->decimal('gross', 15, 2)->default(0);
            $table->decimal('withholding', 15, 2)->default(0);
            $table->decimal('net', 15, 2)->default(0);
            $table->string('status', 20)->index();
            $table->date('paid_on')->nullable();
            $table->unsignedInteger('payment_method')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_statement_id')->index();
            $table->string('kind', 20);
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('line_date')->nullable();
            $table->string('description', 255);
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('percent', 6, 2)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
        $now = now();
        foreach (['doctor_withholdings' => '[]', 'doctor_statement_prefix' => 'HD'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (! Module::where('name', 'Doctor Fees')->exists()) {
            Module::create(['name' => 'Doctor Fees', 'is_active' => 1, 'route' => 'doctor-fees.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Doctor Fees')->delete();
        DB::table('settings')->whereIn('key', ['doctor_withholdings', 'doctor_statement_prefix'])->delete();
        Schema::dropIfExists('doctor_statement_lines');
        Schema::dropIfExists('doctor_statements');
        Schema::dropIfExists('doctor_fee_plans');
    }
};
