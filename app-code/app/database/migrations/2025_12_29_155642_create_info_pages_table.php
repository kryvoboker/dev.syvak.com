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
        Schema::create('info_pages', function (Blueprint $table) {
            $table->id();

            $table->json('positions')->nullable()->comment('Where show links, in Header or Footer or Both');
            $table->smallInteger('sort_order')->nullable(false)->default(1);
            $table->boolean('is_active')->nullable(false)->default(false);
            $table->boolean('is_noindex')->nullable(false)->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_pages');
    }
};
