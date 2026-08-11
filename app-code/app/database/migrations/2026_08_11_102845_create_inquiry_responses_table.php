<?php

declare(strict_types=1);

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inquiry_responses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('inquiry_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('admin_user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->string('admin_name', 255);
            $table->string('subject')->nullable();
            $table->longText('body_html');
            $table->string('recipient_email')->nullable();
            $table->string('delivery_status', 50)
                ->default(InquiryResponseDeliveryStatusEnum::NotSent->value);
            $table->text('delivery_error')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['inquiry_id', 'created_at']);
            $table->index(['delivery_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_responses');
    }
};
