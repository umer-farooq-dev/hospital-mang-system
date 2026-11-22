<?php

namespace Tests\Feature;

use App\Models\ExamResult;
use Tests\TestCase;

class LabCatalogueTest extends TestCase
{
    public function test_catalogue_is_complete_and_its_ranges_are_readable(): void
    {
        $lab = config('lab_catalogue.lab');
        $tests = collect($lab)->flatMap(fn ($g) => $g['tests']);
        $this->assertGreaterThanOrEqual(20, $tests->count());
        foreach ($tests as $test) {
            $this->assertCount(2, $test['name'], 'name in English and French');
            $this->assertNotEmpty($test['parameters']);
            foreach ($test['parameters'] as $parameter) {
                $this->assertCount(4, $parameter);
                $this->assertNotSame('', $parameter[2], 'a unit is required by the parameters table');
            }
        }
        // numeric ranges must be understood by the abnormal flag
        $this->assertTrue(ExamResult::isOutside('9.5', '12 - 17'));
        $this->assertTrue(ExamResult::isOutside('2.4', '< 2.00'));
        $this->assertFalse(ExamResult::isOutside('0.95', '0.70 - 1.10'));
        $this->assertCount(8, collect(config('lab_catalogue.imaging'))->flatMap(fn ($g) => $g['tests']));
    }
}
