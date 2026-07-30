<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->ulid('order_number')
                ->unique()
                ->nullable(false);

            $table->foreignId('order_status_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->noActionOnDelete();

            $table->string('order_status_name')->nullable(false);

            $table->string('order_type')
                ->nullable(false)
                ->comment('For check is order is FAST or REGULAR or OTHER');

            $table->text('comment')->nullable();
            $table->decimal('total', 15, 4)->nullable(false);

            $table->foreignId('language_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('language_code', 10)->nullable(false);

            $table->foreignId('currency_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('currency_code', 3)->nullable(false);
            $table->decimal('exchange_rate', 15, 8)->nullable(false);
            $table->string('accept_language')->nullable();
            $table->string('ip', 45)->nullable(false);
            $table->string('forwarded_ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('added_at')->nullable(false);
            $table->timestamp('deleted_at')
                ->nullable()
                ->comment('For soft delete');

            $table->timestamps();

            $table->index(['order_number', 'order_status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
