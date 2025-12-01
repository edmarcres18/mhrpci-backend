<?php

namespace Tests\Feature;

use Tests\TestCase;

class TimezoneTest extends TestCase
{
    public function test_application_timezone_is_manila(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', \date_default_timezone_get());
    }
}

