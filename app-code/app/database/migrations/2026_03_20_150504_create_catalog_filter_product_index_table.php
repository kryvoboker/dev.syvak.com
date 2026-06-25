<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_filter_product_index', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_filter_set_id')
                ->constrained(indexName: 'catalog_filter_product_index_cf_s_id_foreign')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('index_version')->nullable(false);
            $table->string('context_type', 40)->nullable(false);

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('catalog_filter_group_id')
                ->constrained(indexName: 'catalog_filter_product_index_c_f_g_id_foreign')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('catalog_filter_value_id')
                ->nullable()
                ->constrained(indexName: 'catalog_filter_product_index_c_f_v_id_foreign')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->decimal('base_price', 15, 4)->nullable();
            $table->decimal('discount_price', 15, 4)->nullable();
            $table->decimal('effective_price', 15, 4)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('is_in_stock')->default(false)->nullable(false);
            $table->boolean('is_active_product')->default(true)->nullable(false);
            $table->timestamp('indexed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['catalog_filter_set_id', 'index_version', 'category_id', 'catalog_filter_group_id', 'catalog_filter_value_id'],
                'catalog_filter_product_index_lookup_idx',
            );
            $table->index(
                ['catalog_filter_set_id', 'index_version', 'category_id', 'effective_price'],
                'catalog_filter_product_index_price_idx',
            );
            $table->index(
                ['catalog_filter_set_id', 'index_version', 'category_id', 'stock_quantity'],
                'catalog_filter_product_index_stock_idx',
            );
            $table->index(
                ['catalog_filter_set_id', 'index_version', 'product_id'],
                'catalog_filter_product_index_product_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_product_index');
    }
};
