<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\Marketing\PromoCode;
use App\Services\Marketing\PromoCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromoCodeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('format_locale');
            $table->unsignedSmallInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('normalized_code')->unique();
            $table->string('promo_type')->default('regular');
            $table->string('discount_type')->default('percentage');
            $table->string('discount_base_mode')->default('include_discounted_products_at_rrp');
            $table->decimal('minimum_order_amount', 15, 4)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('promo_code_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id');
            $table->foreignId('currency_id');
            $table->decimal('value', 15, 4);
            $table->timestamps();
        });
        Schema::create('promo_code_error_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id');
            $table->foreignId('language_id');
            $table->text('expired_message')->nullable();
            $table->text('minimum_order_message')->nullable();
            $table->text('usage_limit_message')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('user_groups', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('promo_code_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('promo_code_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('promo_code_user_group', function (Blueprint $table): void {
            $table->unsignedBigInteger('promo_code_id');
            $table->unsignedBigInteger('user_group_id');
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('promo_code_product', function (Blueprint $table): void {
            $table->unsignedBigInteger('promo_code_id');
            $table->unsignedBigInteger('product_id');
        });
        Schema::create('promo_code_category', function (Blueprint $table): void {
            $table->unsignedBigInteger('promo_code_id');
            $table->unsignedBigInteger('category_id');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('promo_code_error_translations');
        Schema::dropIfExists('promo_code_user_group');
        Schema::dropIfExists('promo_code_user');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('users');
        Schema::dropIfExists('promo_code_category');
        Schema::dropIfExists('promo_code_product');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('promo_code_discounts');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('currencies');

        parent::tearDown();
    }

    public function testPromoCodeNormalizationSupportsUnicodeAndCaseInsensitiveMatching(): void
    {
        $this->assertSame('знижка 🎁', PromoCode::normalizeCode('  ЗНИЖКА 🎁  '));
    }

    public function testPercentageDiscountIsAppliedToTheFullTotalsBase(): void
    {
        config()->set('app.currency.current_currency_code', 'USD');
        config()->set('app.currency.current_exchange_rate', 1);

        $currency = Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'format_locale' => 'en_US',
            'is_active' => true,
            'is_default' => true,
            'exchange_rate' => 1,
            'decimal_places' => 2,
        ]);
        $promo_code = PromoCode::query()->create([
            'name' => 'Welcome',
            'code' => 'WELCOME 🎁',
            'normalized_code' => PromoCode::normalizeCode('WELCOME 🎁'),
            'discount_type' => PromoCodeDiscountTypeEnum::Percentage,
            'is_active' => true,
        ]);
        $promo_code->discounts()->create(['currency_id' => $currency->getKey(), 'value' => 10]);

        $totals = app(PromoCodeService::class)->applyToTotals(
            totals_data: [
                'lines' => [],
                'items_subtotal' => 90,
                'grand_total' => 100,
                'currency_code' => 'USD',
                'exchange_rate' => 1,
            ],
            cart_items: [[
                'product_id' => 1,
                'line_total' => 90,
                'rrc_line_total' => 90,
                'is_discounted' => false,
            ]],
            code: 'welcome 🎁',
        );

        $this->assertSame(-10.0, $totals['lines'][0]['amount']);
        $this->assertSame(10.0, $totals['promo_code']['discount_amount']);
    }

    public function testExpiredPromoCodeUsesTheCurrentLanguageCustomError(): void
    {
        $language = \App\Models\ApplicationSettings\Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);
        $promo_code = PromoCode::query()->create([
            'name' => 'Expired',
            'code' => 'EXPIRED',
            'normalized_code' => 'expired',
            'discount_type' => PromoCodeDiscountTypeEnum::Percentage,
            'is_active' => true,
            'ends_at' => now()->subMinute(),
        ]);
        $promo_code->errorTranslations()->create([
            'language_id' => $language->getKey(),
            'expired_message' => 'Custom expired message',
        ]);

        $totals = app(PromoCodeService::class)->applyToTotals(
            totals_data: ['lines' => [], 'grand_total' => 100, 'currency_code' => 'USD', 'exchange_rate' => 1],
            cart_items: [],
            code: 'expired',
            locale: 'en',
        );

        $this->assertFalse($totals['promo_code']['is_valid']);
        $this->assertSame('Custom expired message', $totals['promo_code']['message']);
    }
}
