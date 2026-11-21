<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Insurances screen becomes the list of payers: insurers, partner (employer) companies
     * and mutuals. Each payer has its own coverage % and discount, set by the admin. A patient
     * holds one or more policies/memberships with these payers, in a priority order.
     */
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->unsignedInteger('payer_type')->default(1)->after('name');
            $table->decimal('coverage_percent', 5, 2)->nullable()->after('payer_type');
            $table->string('contact_person', 160)->nullable()->after('remark');
            $table->string('phone', 30)->nullable()->after('contact_person');
            $table->string('email', 160)->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('billing_option', 20)->default('per_visit')->after('address');
            $table->unsignedSmallInteger('credit_days')->nullable()->after('billing_option');
            // A partner company has no insurance number, service tax or hospital rate.
            $table->string('insurance_no')->nullable()->change();
            $table->double('service_tax')->default(0)->change();
            $table->double('hospital_rate')->default(0)->change();
            $table->double('total')->default(0)->change();
        });

        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id');
            $table->unsignedInteger('insurance_id');
            $table->string('plan_name', 160)->nullable();
            $table->string('policy_number', 100)->nullable();
            $table->string('member_number', 100)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->decimal('annual_limit', 15, 2)->nullable();
            $table->decimal('visit_limit', 15, 2)->nullable();
            $table->unsignedTinyInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('insurance_id')->references('id')->on('insurances')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['patient_id', 'priority']);
            $table->index('policy_number');
            $table->index('member_number');
        });

        $now = now();
        $types = [
            [1, 'Insurance company', 'messages.insurance.payer_types.insurer'],
            [2, 'Partner company', 'messages.insurance.payer_types.company'],
            [3, 'Mutual / public scheme', 'messages.insurance.payer_types.mutual'],
        ];
        foreach ($types as $i => [$code, $name, $langKey]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'payer_type', 'code' => $code, 'name' => $name, 'lang_key' => $langKey,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_insurances');
        DB::table('master_list_items')->where('type', 'payer_type')->delete();
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn(['payer_type', 'coverage_percent', 'contact_person', 'phone', 'email', 'address', 'billing_option', 'credit_days']);
        });
    }
};
