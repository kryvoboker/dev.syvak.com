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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();

            $table->json('titles')->nullable(false);
            $table->json('meta_titles')->nullable();
            $table->json('meta_descriptions')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->string('contact_emails', 500)->nullable();
            $table->string('contact_phones', 500)->nullable();
            $table->json('socials')->nullable();
            $table->string('work_time')->nullable();
            $table->json('contact_addresses')->nullable();
            $table->string('coordinates')->nullable();
            $table->text('iframe_map')->nullable();

            $table->text('timezone')->default(config('app.timezone'))->nullable(false);
            $table->json('image_sizes')->nullable();
            $table->json('user_settings')->nullable();
            $table->json('ai_settings')->nullable();
            $table->json('system_settings')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
