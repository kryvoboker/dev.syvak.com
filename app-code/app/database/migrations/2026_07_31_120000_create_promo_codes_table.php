<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            $table->string('normalized_code', 255)->unique();
            $table->string('promo_type', 32)->default('regular');
            $table->string('discount_type', 32)->default('percentage');
            $table->string('discount_base_mode', 64)->default('include_discounted_products_at_rrp');
            $table->unsignedInteger('global_usage_limit')->nullable();
            $table->unsignedInteger('all_users_usage_limit')->nullable();
            $table->string('user_limit_mode', 32)->nullable();
            $table->unsignedInteger('user_usage_limit')->nullable();
            $table->unsignedInteger('all_groups_usage_limit')->nullable();
            $table->string('group_limit_mode', 32)->nullable();
            $table->unsignedInteger('group_usage_limit')->nullable();
            $table->decimal('minimum_order_amount', 15, 4)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
