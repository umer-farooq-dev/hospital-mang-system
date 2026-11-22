<?php

namespace Tests\Feature;

use App\Models\ExamOrderItem;
use App\Models\ExamResult;
use App\Utils\RouteAccess;
use Tests\TestCase;

class LabWorklistTest extends TestCase
{
    public function test_values_outside_the_reference_range_are_flagged(): void
    {
        $this->assertTrue(ExamResult::isOutside('12.4', '4.5 - 11'));
        $this->assertFalse(ExamResult::isOutside('7,2', '4,5-11'), 'French decimal comma');
        $this->assertTrue(ExamResult::isOutside('3', '4.5–11'));
        $this->assertTrue(ExamResult::isOutside('6', '< 5'));
        $this->assertFalse(ExamResult::isOutside('5', '<= 5'));
        $this->assertTrue(ExamResult::isOutside('35', '≥ 40'));
        $this->assertNull(ExamResult::isOutside('positive', 'negative'), 'text results are flagged by hand');
        $this->assertNull(ExamResult::isOutside('8', ''));
    }

    public function test_sample_numbers_and_approve_action(): void
    {
        $this->assertSame('S000042', ExamOrderItem::sampleNumber('S', 42));
        $this->assertSame('LAB001234', ExamOrderItem::sampleNumber('LAB', 1234));
        $this->assertSame('approve', RouteAccess::action('validateResults', ['POST']));
        $this->assertSame('approve', RouteAccess::action('releaseResults', ['POST']));
        $this->assertSame('edit', RouteAccess::action('update', ['POST']));
    }
}
