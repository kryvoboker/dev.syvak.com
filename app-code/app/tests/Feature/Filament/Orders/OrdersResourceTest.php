<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Models\Marketing\PromoCode;
use App\Models\Marketing\PromoCodeDiscount;
use App\Models\Marketing\PromoCodeUsage;
use App\Models\Orders\OrderCustomers;
use App\Models\Orders\OrderProducts;
use App\Models\Orders\OrderPromoCodeProducts;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderShippings;
use App\Models\Orders\OrderStatusDescriptions;
use App\Models\Orders\OrderStatuses;
use App\Models\Orders\OrderTotals;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\User;
use App\Services\Marketing\PromoCodeService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrdersResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        Filament::setCurrentPanel(Filament::getPanel('alyo-admin'));
    }

    public function test_admin_can_view_orders_in_the_orders_table(): void
    {
        $admin = $this->createAdmin(['ViewAny:Orders']);
        $order = $this->createOrder();

        $this->actingAs($admin);

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->assertTableColumnStateSet('order_number', $order->order_number, $order)
            ->assertTableColumnStateSet('total', (string) $order->total, $order);
    }

    public function test_orders_table_displays_the_no_call_preference(): void
    {
        $admin = $this->createAdmin(['ViewAny:Orders']);
        $order = $this->createOrder(true);

        $this->actingAs($admin);

        Livewire::test(ListOrders::class)
            ->assertTableColumnStateSet('customer.no_call', true, $order);
    }

    public function test_admin_can_open_order_edit_page_with_current_order_data(): void
    {
        $admin = $this->createAdmin(['View:Orders', 'Update:Orders']);
        $order = $this->createOrder();

        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertFormSet([
                'id' => $order->getKey(),
                'order_number' => $order->order_number,
                'comment' => $order->comment,
                'customer.first_name' => 'Ada',
                'customer.last_name' => 'Lovelace',
                'customer.no_call' => false,
                'shipping.code' => 'pickup_store',
            ]);
    }

    public function test_admin_can_edit_order_comment_from_the_edit_page(): void
    {
        $admin = $this->createAdmin(['View:Orders', 'Update:Orders']);
        $order = $this->createOrder();

        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->fillForm(['comment' => 'Updated by admin'])
            ->assertFormSet(['comment' => 'Updated by admin'])
            ->assertActionVisible('save');
    }

    public function test_admin_can_update_the_customer_callback_preference(): void
    {
        $admin = $this->createAdmin(['View:Orders', 'Update:Orders']);
        $order = $this->createOrder();

        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->fillForm(['customer.no_call' => true])
            ->call('save');

        $this->assertDatabaseHas('order_customers', [
            'order_id' => $order->getKey(),
            'no_call' => true,
        ]);
    }

    public function test_admin_can_view_the_promo_code_snapshot_on_the_order(): void
    {
        $admin = $this->createAdmin(['View:Orders', 'Update:Orders']);
        $order = $this->createOrder();
        $promo_code = PromoCode::query()->create([
            'name' => 'Spring sale',
            'code' => 'SPRING10',
            'normalized_code' => 'spring10',
            'promo_type' => 'super',
            'discount_type' => 'fixed',
            'is_active' => true,
        ]);
        PromoCodeDiscount::query()->create([
            'promo_code_id' => $promo_code->getKey(),
            'currency_id' => $order->currency_id,
            'value' => 25,
        ]);
        $promo_code_usage = PromoCodeUsage::query()->create([
            'promo_code_id' => $promo_code->getKey(),
            'order_id' => $order->getKey(),
            'discount_type' => 'fixed',
            'promo_type' => 'super',
            'used_at' => now(),
        ]);
        OrderPromoCodeProducts::query()->create([
            'order_id' => $order->getKey(),
            'promo_code_usage_id' => $promo_code_usage->getKey(),
            'order_product_id' => $order->products()->firstOrFail()->getKey(),
            'is_eligible' => true,
            'discount_amount' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->assertFormSet([
                'promo_code.id' => $promo_code->getKey(),
                'promo_code.code' => 'SPRING10',
                'promo_code.promo_type' => 'super',
                'promo_code.discount_type' => 'fixed',
                'promo_code.discount_value' => '25.0000',
            ]);
    }

    public function test_promo_code_consumption_persists_discount_and_promo_types(): void
    {
        $order = $this->createOrder();
        $promo_code = PromoCode::query()->create([
            'name' => 'Spring sale',
            'code' => 'SPRING10',
            'normalized_code' => 'spring10',
            'promo_type' => 'super',
            'discount_type' => 'fixed',
            'is_active' => true,
        ]);

        app(PromoCodeService::class)->consume($promo_code, $order);

        $this->assertDatabaseHas('promo_code_usages', [
            'order_id' => $order->getKey(),
            'promo_code_id' => $promo_code->getKey(),
            'discount_type' => 'fixed',
            'promo_type' => 'super',
        ]);
    }

    /**
     * @param array<int, string> $permissions
     */
    private function createAdmin(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'orders-admin-' . uniqid(),
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permission_name) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permission_name,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $role->givePermissionTo(Permission::query()->firstOrCreate([
            'name' => 'ViewAny:Orders',
            'guard_name' => 'web',
        ]));

        $admin = User::query()->create([
            'name' => 'Orders Admin',
            'email' => 'orders-admin-' . uniqid() . '@example.com',
            'password' => app(HashManager::class)->make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    private function createOrder(bool $no_call = false): Orders
    {
        $language = Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);
        $currency = Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $order_status = OrderStatuses::query()->create([
            'code' => 'processing',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);
        OrderStatusDescriptions::query()->create([
            'order_status_id' => $order_status->getKey(),
            'language_id' => $language->getKey(),
            'name' => 'Processing',
        ]);
        PaymentStatuses::query()->create([
            'code' => 'pending',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $order = Orders::query()->create([
            'order_number' => '01JORDERTEST0000000000000001',
            'order_status_id' => $order_status->getKey(),
            'order_status_name' => 'Processing',
            'order_type' => 'regular',
            'comment' => 'Initial comment',
            'total' => 50,
            'language_id' => $language->getKey(),
            'language_code' => 'en',
            'currency_id' => $currency->getKey(),
            'currency_code' => 'USD',
            'exchange_rate' => 1,
            'ip' => '127.0.0.1',
            'added_at' => now(),
        ]);

        OrderCustomers::query()->create([
            'order_id' => $order->getKey(),
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'telephone' => '+380501234567',
            'no_call' => $no_call,
        ]);
        OrderShippings::query()->create([
            'order_id' => $order->getKey(),
            'method' => 'Pickup store',
            'code' => 'pickup_store',
        ]);
        OrderProducts::query()->create([
            'order_id' => $order->getKey(),
            'is_default_variant' => true,
            'name' => 'Garden Guardian T-Shirt',
            'quantity' => 1,
            'unit_price' => 50,
            'discount' => 0,
            'line_total' => 50,
        ]);
        OrderTotals::query()->create([
            'order_id' => $order->getKey(),
            'total_type' => 'total',
            'name' => 'Total',
            'value' => 50,
            'sort_order' => 1,
        ]);

        $order->load(['status.descriptions', 'customer', 'shipping', 'products', 'totals']);

        return $order;
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('user_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
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
        Schema::create('order_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
        Schema::create('order_status_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_status_id');
            $table->foreignId('language_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('payment_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
        Schema::create('payment_status_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_status_id');
            $table->foreignId('language_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('order_status_id');
            $table->string('order_status_name');
            $table->string('order_type');
            $table->text('comment')->nullable();
            $table->decimal('total', 15, 4);
            $table->foreignId('language_id')->nullable();
            $table->string('language_code');
            $table->foreignId('currency_id')->nullable();
            $table->string('currency_code');
            $table->decimal('exchange_rate', 15, 8);
            $table->string('accept_language')->nullable();
            $table->string('ip');
            $table->string('forwarded_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('added_at');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('user_group_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('telephone');
            $table->boolean('no_call')->default(false);
            $table->timestamps();
        });
        Schema::create('order_shippings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique();
            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_cost_enabled')->default(true);
            $table->string('city')->nullable();
            $table->string('city_id')->nullable();
            $table->string('address')->nullable();
            $table->string('delivery_point')->nullable();
            $table->string('delivery_point_id')->nullable();
            $table->string('postcode')->nullable();
            $table->json('provider_data')->nullable();
            $table->timestamps();
        });
        Schema::create('order_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('product_variant_id')->nullable();
            $table->boolean('is_default_variant');
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('sku')->nullable();
            $table->string('ean')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('discount', 15, 4)->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
        Schema::create('order_totals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('total_type');
            $table->string('name');
            $table->decimal('value', 15, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->foreignId('payment_status_id');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('failure_reason')->nullable();
            $table->json('provider_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('old_order_status_id')->nullable();
            $table->foreignId('order_status_id');
            $table->string('event');
            $table->json('json')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('promo_code_product', function (Blueprint $table): void {
            $table->foreignId('promo_code_id');
            $table->foreignId('product_id');
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('promo_code_category', function (Blueprint $table): void {
            $table->foreignId('promo_code_id');
            $table->foreignId('category_id');
        });
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('normalized_code');
            $table->string('promo_type')->default('regular');
            $table->string('discount_type')->default('percentage');
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
        Schema::create('promo_code_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id');
            $table->foreignId('order_id');
            $table->string('discount_type');
            $table->string('promo_type');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('user_group_id')->nullable();
            $table->string('consumer_key')->nullable();
            $table->dateTime('used_at');
            $table->timestamps();
        });
        Schema::create('order_promo_code_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('promo_code_usage_id');
            $table->foreignId('order_product_id');
            $table->foreignId('product_id')->nullable();
            $table->boolean('is_eligible')->default(false);
            $table->string('override', 20)->nullable();
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('nwidart_name')->unique();
            $table->string('module_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
