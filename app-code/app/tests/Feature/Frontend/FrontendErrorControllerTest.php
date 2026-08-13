<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class FrontendErrorControllerTest extends TestCase
{
    public function test_it_logs_a_critical_frontend_error_to_the_stack_channel(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturnSelf();
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Critical frontend error.'
                    && $context['type'] === 'window_error'
                    && $context['message'] === 'Carousel initialization failed.'
                    && $context['url'] === 'https://dev.syvak.com/en';
            });

        $response = $this->postJson(route('frontend.errors.store'), [
            'type' => 'window_error',
            'message' => 'Carousel initialization failed.',
            'stack' => 'Error: Carousel initialization failed.',
            'url' => 'https://dev.syvak.com/en',
            'filename' => 'https://dev.syvak.com/assets/app.js',
            'line' => 42,
            'column' => 7,
            'user_agent' => 'Test browser',
            'page_type' => 'home',
        ]);

        $response->assertAccepted()->assertJson(['success' => true]);
    }

    public function test_it_rejects_an_invalid_frontend_error_payload(): void
    {
        $response = $this->postJson(route('frontend.errors.store'), [
            'type' => 'debug_message',
            'message' => str_repeat('x', 2001),
            'url' => 'not-a-url',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['type', 'message', 'url']);
    }
}
