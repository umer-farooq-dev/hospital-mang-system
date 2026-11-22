<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // The audit listener remembers whether audit_logs exists; each test builds its own tables.
    protected function tearDown(): void
    {
        (new \ReflectionProperty(\App\Listeners\RecordAuditLog::class, 'tableExists'))->setValue(null, null);
        parent::tearDown();
    }
}
