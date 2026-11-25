<?php

namespace App\Repositories;

use App\Models\CoverageRule;
use App\Models\Insurance;
use App\Models\InsuranceDisease;
use Arr;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Validator;

/**
 * Class InsuranceRepository
 *
 * @version February 22, 2020, 9:01 am UTC
 */
class InsuranceRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'service_tax',
        'insurance_no',
        'insurance_code',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return Insurance::class;
    }

    public function store($input)
    {
        $diseaseItemInputArray = Arr::only($input, ['disease_name', 'disease_charge']);

        $insurance = Insurance::create(Arr::except($input, ['disease_name', 'disease_charge', 'coverage', 'website_logo']));
        $this->saveLogo($insurance, $input);
        $this->syncCoverageRules($insurance, $input['coverage'] ?? []);

        $diseaseItemInput = $this->prepareInputForDiseaseItem($diseaseItemInputArray);

        foreach ($diseaseItemInput as $key => $data) {
            $data['insurance_id'] = $insurance->id;
            $validator = Validator::make($data, InsuranceDisease::$rules);

            if ($validator->fails()) {
                throw new UnprocessableEntityHttpException($validator->errors()->first());
            }
            $data['disease_charge'] = removeCommaFromNumbers($data['disease_charge']);
            $disease = InsuranceDisease::create($data);
        }

        return true;
    }

    private function saveLogo(Insurance $insurance, array $input): void
    {
        if (! empty($input['website_logo'])) {
            $insurance->clearMediaCollection(Insurance::LOGO_PATH);
            $insurance->addMedia($input['website_logo'])->toMediaCollection(Insurance::LOGO_PATH, config('app.media_disc'));
        }
    }

    /** Replace the payer's coverage rules with the rows of the form. */
    public function syncCoverageRules(Insurance $insurance, array $rows): void
    {
        $insurance->coverageRules()->delete();
        foreach ($rows as $row) {
            $row = array_map(fn ($v) => $v === '' ? null : $v, (array) $row);
            $validator = Validator::make($row, CoverageRule::rules(), [], [
                'coverage_percent' => __('messages.coverage.percent'), 'max_amount' => __('messages.coverage.max_amount'),
            ]);
            if ($validator->fails()) {
                throw new UnprocessableEntityHttpException($validator->errors()->first());
            }
            CoverageRule::create([
                'insurance_id' => $insurance->id,
                'doctor_department_id' => $row['doctor_department_id'] ?? null,
                'charge_category_id' => $row['charge_category_id'] ?? null,
                'coverage_percent' => $row['coverage_percent'] ?? null,
                'not_covered' => ! empty($row['not_covered']),
                'needs_approval' => ! empty($row['needs_approval']),
                'max_amount' => $row['max_amount'] ?? null,
            ]);
        }
    }

    public function prepareInputForDiseaseItem($input)
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ((array) $data as $index => $value) {
                $items[$index][$key] = $value;
            }
        }

        // Disease charges are optional (a partner company has none): skip blank rows.
        return array_filter($items, fn ($item) => filled($item['disease_name'] ?? null) || filled($item['disease_charge'] ?? null));
    }

    public function getDisease()
    {
        $disease = InsuranceDisease::all();

        return $disease;
    }

    public function getInsuranceDisease($insuranceId)
    {
        $diseases = InsuranceDisease::whereInsuranceId($insuranceId)->get();

        return $diseases;
    }

    public function update($insurance, $input)
    {
        $diseaseItemInputArray = Arr::only($input, ['disease_name', 'disease_charge']);

        $insurance->update(Arr::except($input, ['coverage', 'website_logo']));
        $this->saveLogo($insurance, $input);
        $this->syncCoverageRules($insurance, $input['coverage'] ?? []);

        $disease = InsuranceDisease::whereInsuranceId($insurance->id);
        $disease->delete();
        $diseaseItemInput = $this->prepareInputForDiseaseItem($diseaseItemInputArray);

        foreach ($diseaseItemInput as $key => $data) {
            $data['insurance_id'] = $insurance->id;
            $validator = Validator::make($data, InsuranceDisease::$rules);

            if ($validator->fails()) {
                throw new UnprocessableEntityHttpException($validator->errors()->first());
            }
            $data['disease_charge'] = removeCommaFromNumbers($data['disease_charge']);
            InsuranceDisease::create($data);
        }

        return true;
    }

    public function delete($insuranceId)
    {
        try {
            $insurance = Insurance::find($insuranceId);
            $insurance->delete();
            $insuranceDisease = InsuranceDisease::whereInsuranceId($insuranceId);
            $insuranceDisease->delete();

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
