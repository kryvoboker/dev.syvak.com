<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('module_instances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_definition_id')
                ->constrained('module_definitions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name', 255)->nullable(false);
            $table->string('placement', 255)->nullable();
            $table->string('context_key', 255)->nullable();
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->unsignedInteger('sort_order')->default(0)->nullable(false);
            $table->json('settings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['module_definition_id', 'is_enabled'], 'module_instances_definition_enabled_idx');
            $table->index(['placement', 'context_key'], 'module_instances_placement_context_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_instances');
    }
};
