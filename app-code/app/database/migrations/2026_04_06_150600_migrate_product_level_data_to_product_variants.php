<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    /**
     * @throws Throwable
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $product_to_variant_id_map = [];

            DB::table('products')
                ->orderBy('id')
                ->chunkById(500, function ($products) use (&$product_to_variant_id_map): void {
                    foreach ($products as $product) {
                        $variant_id = DB::table('product_variants')->insertGetId([
                            'product_id'     => (int) $product->id,
                            'is_default'     => true,
                            'is_active'      => (bool) $product->is_active,
                            'quantity'       => (int) $product->quantity,
                            'minimum'        => max(1, (int) $product->minimum),
                            'price'          => (float) $product->price,
                            'image'          => $product->image,
                            'date_available' => $product->date_available,
                            'sort_order'     => 0,
                            'created_at'     => $product->created_at,
                            'updated_at'     => $product->updated_at,
                        ]);

                        DB::table('products')
                            ->where('id', (int) $product->id)
                            ->update(['default_variant_id' => $variant_id]);

                        $product_to_variant_id_map[(int) $product->id] = (int) $variant_id;
                    }
                });

            if ($product_to_variant_id_map === []) {
                return;
            }

            collect($product_to_variant_id_map)
                ->chunk(250)
                ->each(function ($variant_map_chunk): void {
                    $variant_map = $variant_map_chunk->all();
                    $product_ids = array_keys($variant_map);

                    $images = DB::table('product_images')
                        ->whereIn('product_id', $product_ids)
                        ->orderBy('id')
                        ->get();

                    if ($images->isNotEmpty()) {
                        $payload = $images
                            ->map(function ($image_row) use ($variant_map): array {
                                return [
                                    'product_variant_id' => (int) $variant_map[(int) $image_row->product_id],
                                    'image'              => (string) $image_row->image,
                                    'is_primary'         => false,
                                    'sort_order'         => (int) $image_row->sort_order,
                                    'created_at'         => $image_row->created_at,
                                    'updated_at'         => $image_row->updated_at,
                                ];
                            })
                            ->all();

                        DB::table('product_variant_images')->insert($payload);
                    }

                    $discounts = DB::table('product_discounts')
                        ->whereIn('product_id', $product_ids)
                        ->orderBy('id')
                        ->get();

                    if ($discounts->isNotEmpty()) {
                        $payload = $discounts
                            ->map(function ($discount_row) use ($variant_map): array {
                                return [
                                    'product_variant_id' => (int) $variant_map[(int) $discount_row->product_id],
                                    'user_group_id'      => $discount_row->user_group_id,
                                    'quantity'           => $discount_row->quantity,
                                    'priority'           => $discount_row->priority,
                                    'price'              => $discount_row->price,
                                    'date_start'         => $discount_row->date_start,
                                    'date_end'           => $discount_row->date_end,
                                    'created_at'         => $discount_row->created_at,
                                    'updated_at'         => $discount_row->updated_at,
                                ];
                            })
                            ->all();

                        DB::table('product_variant_discounts')->insert($payload);
                    }

                    $attributes = DB::table('product_to_attributes')
                        ->whereIn('product_id', $product_ids)
                        ->orderBy('id')
                        ->get();

                    if ($attributes->isNotEmpty()) {
                        $payload = $attributes
                            ->map(function ($attribute_row) use ($variant_map): array {
                                return [
                                    'product_variant_id' => (int) $variant_map[(int) $attribute_row->product_id],
                                    'attribute_id'       => $attribute_row->attribute_id,
                                    'language_id'        => $attribute_row->language_id,
                                    'value_string'       => $attribute_row->text,
                                    'created_at'         => $attribute_row->created_at,
                                    'updated_at'         => $attribute_row->updated_at,
                                ];
                            })
                            ->all();

                        DB::table('product_variant_attribute_values')->insert($payload);
                    }
                });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('product_variant_attribute_values')->delete();
            DB::table('product_variant_discounts')->delete();
            DB::table('product_variant_images')->delete();
            DB::table('product_variants')->delete();

            DB::table('products')->update(['default_variant_id' => null]);
        });
    }
};
