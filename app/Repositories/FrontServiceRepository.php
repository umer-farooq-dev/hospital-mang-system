<?php

namespace App\Repositories;

use App\Models\FrontService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FrontServiceRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'short_description',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return FrontService::class;
    }

    public function store($input)
    {
        try {
            $frontService = FrontService::create($input);
            $this->saveMedia($frontService, $input);

            return true;
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    private function saveMedia(FrontService $frontService, array $input): void
    {
        foreach (['icon' => FrontService::PATH, 'image' => FrontService::IMAGE_PATH] as $field => $collection) {
            if (! empty($input[$field])) {
                $frontService->clearMediaCollection($collection);
                $frontService->addMedia($input[$field])->toMediaCollection($collection, config('app.media_disc'));
            }
        }
    }

    public function updateFrontService($input, $frontServiceId)
    {
        try {
            unset($input['type']); // a service stays a service
            $frontService = $this->update($input, $frontServiceId);
            $this->saveMedia($frontService, $input);
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
