<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What a payer covers in one department and/or charge category. A rule with neither is the
 * payer's default for everything else: marking it "not covered" leaves only the listed
 * departments covered (an insurer that accepts some departments only).
 */
class CoverageRule extends Model
{
    protected $table = 'coverage_rules';

    protected $fillable = ['insurance_id', 'doctor_department_id', 'charge_category_id', 'coverage_percent', 'not_covered', 'needs_approval', 'max_amount'];

    protected $casts = [
        'insurance_id' => 'integer',
        'doctor_department_id' => 'integer',
        'charge_category_id' => 'integer',
        'coverage_percent' => 'float',
        'not_covered' => 'boolean',
        'needs_approval' => 'boolean',
        'max_amount' => 'float',
    ];

    public static function rules(): array
    {
        return [
            'doctor_department_id' => 'nullable|integer|exists:doctor_departments,id',
            'charge_category_id' => 'nullable|integer|exists:charge_categories,id',
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'max_amount' => 'nullable|numeric|min:0',
        ];
    }

    /** The most specific rule of a payer for a department and category, if any. */
    public static function match(int $insuranceId, ?int $departmentId, ?int $categoryId = null): ?self
    {
        return self::where('insurance_id', $insuranceId)
            ->where(fn ($q) => $q->whereNull('doctor_department_id')->orWhere('doctor_department_id', $departmentId))
            ->where(fn ($q) => $q->whereNull('charge_category_id')->orWhere('charge_category_id', $categoryId))
            ->get()
            ->sortByDesc(fn ($rule) => ($rule->doctor_department_id ? 2 : 0) + ($rule->charge_category_id ? 1 : 0))
            ->first();
    }
}
