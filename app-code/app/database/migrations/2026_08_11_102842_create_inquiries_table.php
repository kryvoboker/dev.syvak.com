<?php

declare(strict_types=1);

use App\Enums\Inquiries\InquiryStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('language_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->nullableMorphs('inquiryable');

            $table->string('type', 100);
            $table->string('status', 50)->default(InquiryStatusEnum::New->value);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->softDeletes()->comment('For soft delete');

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['email', 'created_at']);
            $table->index(['submitted_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
