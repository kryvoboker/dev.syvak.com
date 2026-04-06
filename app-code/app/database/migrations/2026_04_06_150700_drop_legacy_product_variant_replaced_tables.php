<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_discounts');
        Schema::dropIfExists('product_to_attributes');
        Schema::dropIfExists('product_attribute_text_hashes');
    }

    public function down(): void
    {
        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('image', 3000)->nullable(false);
            $table->smallInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('product_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_group_id')
                ->nullable()
                ->constrained('user_groups')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->dateTime('date_start')->nullable(false);
            $table->dateTime('date_end')->nullable(false);
            $table->timestamps();
            $table->unique(['product_id', 'user_group_id']);
        });

        Schema::create('product_to_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('attribute_id')
                ->nullable()
                ->constrained('attributes')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('text', 3000)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_id', 'language_id']);
        });

        Schema::create('product_attribute_text_hashes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('attribute_id')
                ->constrained('attributes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->char('hash', 64)->nullable(false)->index();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_id', 'hash'], 'product_name_attribute_text_hash_unique');
        });
    }
};
