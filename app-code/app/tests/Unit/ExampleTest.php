<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        self::assertIsString(config('app.name'));
    }
}
