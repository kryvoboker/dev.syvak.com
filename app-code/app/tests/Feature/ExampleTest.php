<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SetDefaultLocalePrefix;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutMiddleware([
            SetDefaultLocalePrefix::class,
        ]);

        $response = $this->get('/');

        $response->assertRedirect('/' . config('app.locale'));
    }
}
