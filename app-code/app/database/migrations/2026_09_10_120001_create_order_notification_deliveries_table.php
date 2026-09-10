<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('order_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_notification_event_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('provider_reference')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['order_notification_event_id', 'channel']);
            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_deliveries');
    }
};
