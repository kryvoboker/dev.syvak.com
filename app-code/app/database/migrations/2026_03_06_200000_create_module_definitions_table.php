<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('module_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255)->nullable(false);
            $table->string('slug', 255)->unique()->nullable(false);
            $table->string('nwidart_name', 255)->unique()->nullable(false);
            $table->string('module_path', 1000)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true)->nullable(false);
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->boolean('is_enabled_in_filesystem')->default(true)->nullable(false);
            $table->unsignedInteger('sort_order')->default(0)->nullable(false);
            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'is_installed'], 'module_definitions_enabled_installed_idx');
            $table->index('sort_order', 'module_definitions_sort_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_definitions');
    }
};
