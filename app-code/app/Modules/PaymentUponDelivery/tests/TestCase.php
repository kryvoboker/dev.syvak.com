<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createTables();
        $this->app['request']->setLaravelSession($this->app['session']->driver());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('languages');

        parent::tearDown();
    }

    protected function createTables(): void
    {
        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('languages');

        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('nwidart_name');
            $table->text('module_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('settings_schema')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        \App\Models\ApplicationSettings\Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    protected function enablePaymentUponDeliveryModule(): void
    {
        \App\Models\Modules\ModuleDefinition::query()->create([
            'name' => 'PaymentUponDelivery',
            'slug' => 'payment-upon-delivery',
            'nwidart_name' => 'PaymentUponDelivery',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'meta' => [],
        ]);
    }
}
