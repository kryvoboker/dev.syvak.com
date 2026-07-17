<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->comment('User who made the change')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('old_order_status_id')
                ->nullable()
                ->constrained('order_statuses', 'id')
                ->cascadeOnUpdate()
                ->noActionOnDelete();

            $table->foreignId('order_status_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->noActionOnDelete();

            $table->string('event')
                ->nullable(false)
                ->comment('For check the event that changed the order status');

            $table->json('json')
                ->nullable()
                ->comment('For save additional data');

            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_histories');
    }
};
