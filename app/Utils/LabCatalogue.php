<?php

namespace App\Utils;

use Illuminate\Support\Facades\DB;

/** Adds the proposed lab and imaging catalogue (config/lab_catalogue.php); tests that already exist are left alone. */
class LabCatalogue
{
    public static function install(string $locale): array
    {
        $i = $locale === 'fr' ? 1 : 0;
        $negative = ['Negative' => $i ? 'Négatif' : 'Negative'];
        $now = now();
        $counts = ['lab' => 0, 'imaging' => 0];

        return DB::transaction(function () use ($i, $negative, $now, &$counts) {
            $chargeType = DB::table('master_list_items')->where('type', 'charge_type')->where('code', 1)->exists()
                ? 1 : (int) DB::table('master_list_items')->where('type', 'charge_type')->min('code');
            $chargeCategory = fn (string $name) => DB::table('charge_categories')->where('name', $name)->value('id')
                ?? DB::table('charge_categories')->insertGetId(['name' => $name, 'charge_type' => $chargeType, 'created_at' => $now, 'updated_at' => $now]);
            $findOrCreate = fn (string $table, string $column, string $value) => DB::table($table)->where($column, $value)->value('id')
                ?? DB::table($table)->insertGetId([$column => $value, 'created_at' => $now, 'updated_at' => $now]);

            $labCharges = $chargeCategory($i ? 'Laboratoire' : 'Laboratory');
            foreach (config('lab_catalogue.lab') as $group) {
                $categoryId = $findOrCreate('pathology_categories', 'name', $group['category'][$i]);
                foreach ($group['tests'] as $test) {
                    if (DB::table('pathology_tests')->where('test_name', $test['name'][$i])->exists()) {
                        continue;
                    }
                    $testId = DB::table('pathology_tests')->insertGetId(['test_name' => $test['name'][$i], 'short_name' => $test['short'],
                        'test_type' => $test['type'], 'category_id' => $categoryId, 'charge_category_id' => $labCharges, 'standard_charge' => 0,
                        'created_at' => $now, 'updated_at' => $now]);
                    foreach ($test['parameters'] as $parameter) {
                        $unitId = $findOrCreate('pathology_units', 'name', $parameter[2]);
                        $name = $parameter[$i];
                        $range = $negative[$parameter[3]] ?? $parameter[3];
                        // Same name, unit and range = same parameter (e.g. "Result / Negative" shared by serologies).
                        $parameterId = DB::table('pathology_parameters')->where('parameter_name', $name)->where('unit_id', $unitId)->where('reference_range', $range)->value('id')
                            ?? DB::table('pathology_parameters')->insertGetId(['parameter_name' => $name, 'unit_id' => $unitId,
                                'reference_range' => $range, 'created_at' => $now, 'updated_at' => $now]);
                        DB::table('pathology_parameter_items')->insert(['pathology_id' => $testId, 'parameter_id' => $parameterId,
                            'patient_result' => '', 'created_at' => $now, 'updated_at' => $now]);
                    }
                    $counts['lab']++;
                }
            }

            $imagingCharges = $chargeCategory($i ? 'Imagerie' : 'Imaging');
            foreach (config('lab_catalogue.imaging') as $group) {
                $categoryId = $findOrCreate('radiology_categories', 'name', $group['category'][$i]);
                foreach ($group['tests'] as [$names, $short, $modality, $templateKey]) {
                    if (DB::table('radiology_tests')->where('test_name', $names[$i])->exists()) {
                        continue;
                    }
                    DB::table('radiology_tests')->insert(['test_name' => $names[$i], 'short_name' => $short, 'test_type' => $short,
                        'category_id' => $categoryId, 'modality' => $modality,
                        'report_template_id' => $templateKey ? DB::table('imaging_report_templates')->where('lang_key', $templateKey)->value('id') : null,
                        'charge_category_id' => $imagingCharges, 'standard_charge' => 0, 'created_at' => $now, 'updated_at' => $now]);
                    $counts['imaging']++;
                }
            }

            return $counts;
        });
    }
}
