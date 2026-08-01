<?php

declare(strict_types=1);

namespace Modules\WayForPay\Tests;

use App\Models\ApplicationSettings\Currency;
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
        config()->set('app.currency.current_currency_code', 'UAH');
        $this->createTables();
        Currency::query()->create([
            'code' => 'UAH',
            'name' => 'Ukrainian Hryvnia',
            'format_locale' => 'uk_UA',
            'symbol_right' => '₴',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->app['request']->setLaravelSession($this->app['session']->driver());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('carts');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('global_configs');

        parent::tearDown();
    }

    protected function createTables(): void
    {
        Schema::dropIfExists('carts');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('global_configs');

        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('cart_mode')->default('regular');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedInteger('quantity');
            $table->text('chosen_attributes')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('format_locale')->nullable();
            $table->string('symbol_left')->nullable();
            $table->string('symbol_right')->nullable();
            $table->unsignedInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
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
    }

    protected function createActiveLanguages(): void
    {
        \App\Models\ApplicationSettings\Language::query()->create([
            'code' => 'uk',
            'name' => 'Українська',
            'is_active' => true,
            'is_default' => true,
        ]);
        \App\Models\ApplicationSettings\Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    protected function enableWayForPayModule(): void
    {
        \App\Models\Modules\ModuleDefinition::query()->create([
            'name' => 'WayForPay',
            'slug' => 'wayforpay',
            'nwidart_name' => 'WayForPay',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'meta' => [],
        ]);
    }
}
