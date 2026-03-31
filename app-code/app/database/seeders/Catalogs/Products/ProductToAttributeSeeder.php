<?php

declare(strict_types=1);

namespace Database\Seeders\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductToAttribute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductToAttributeSeeder extends Seeder
{
    use WithoutModelEvents;

    private const int PRODUCT_CHUNK_SIZE = 200;

    private const int UPSERT_CHUNK_SIZE = 1000;

    private const int STABLE_SEED = 20260323;

    /** @var array<int, string> */
    private const array ATTRIBUTE_POOL_TYPES = [
        5  => 'size',
        7  => 'color',
        8  => 'weight',
        9  => 'type',
        10 => 'material',
        11 => 'length',
        12 => 'print_type',
    ];
    private array $attributes_values_indexes        = [
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3
    ];
    private array $product_to_attribute_value_index = [];

    public function run(): void
    {
        /** @var Collection<Language> $active_languages */
        $active_languages = Language::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code']);

        /** @var Collection<Attribute> $active_attributes */
        $active_attributes = Attribute::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys(self::ATTRIBUTE_POOL_TYPES))
            ->orderBy('id')
            ->get(['id']);

        if ($active_languages->isEmpty()) {
            Log::channel('stack')->warning('[SEEDER:product_to_attributes] Active languages not found. Seeder skipped.');

            return;
        }

        if ($active_attributes->isEmpty()) {
            Log::channel('stack')->warning('[SEEDER:product_to_attributes] Supported active attributes not found. Seeder skipped.', [
                'expected_attribute_ids' => array_keys(self::ATTRIBUTE_POOL_TYPES),
            ]);

            return;
        }

        $attribute_value_pools = $this->buildAttributeValuePools(
            active_attributes: $active_attributes,
            active_languages : $active_languages,
        );

        $processed_products_count = 0;
        $upserted_rows_count      = 0;

        Product::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(self::PRODUCT_CHUNK_SIZE, function (EloquentCollection $products_chunk) use (
                $active_languages,
                $active_attributes,
                $attribute_value_pools,
                &$processed_products_count,
                &$upserted_rows_count,
            ): void {
                $now_timestamp   = now(config('app.timezone'));
                $rows_for_upsert = [];

                foreach ($products_chunk as $product) {
                    foreach ($active_attributes as $attribute) {
                        foreach ($active_languages as $language) {
                            $rows_for_upsert[] = [
                                'product_id'   => (int)$product->id,
                                'attribute_id' => (int)$attribute->id,
                                'language_id'  => (int)$language->id,
                                'text'         => $this->resolveAttributeValue(
                                    attribute_value_pools: $attribute_value_pools,
                                    attribute_id         : (int)$attribute->id,
                                    language_id          : (int)$language->id,
                                    product_id           : (int)$product->id
                                ),
                                'created_at'   => $now_timestamp,
                                'updated_at'   => $now_timestamp,
                            ];
                        }
                    }
                }

                foreach (array_chunk($rows_for_upsert, self::UPSERT_CHUNK_SIZE) as $upsert_chunk) {
                    ProductToAttribute::query()->upsert(
                        values  : $upsert_chunk,
                        uniqueBy: ['product_id', 'attribute_id', 'language_id'],
                        update  : ['text', 'updated_at'],
                    );
                }

                $processed_products_count += $products_chunk->count();
                $upserted_rows_count      += count($rows_for_upsert);
            });
    }

    /**
     * @param EloquentCollection<int, Attribute> $active_attributes
     * @param EloquentCollection<int, Language>  $active_languages
     *
     * @return array<int, array<int, array<int, string>>>
     */
    private function buildAttributeValuePools(EloquentCollection $active_attributes, EloquentCollection $active_languages): array
    {
        $attribute_value_pools = [];

        foreach ($active_attributes as $attribute) {
            $pool_type = self::ATTRIBUTE_POOL_TYPES[(int)$attribute->id] ?? 'generic';

            foreach ($active_languages as $language) {
                $attribute_value_pools[(int)$attribute->id][(int)$language->id] = $this->resolveLocalizedValuesPool(
                    pool_type    : $pool_type,
                    language_code: (string)$language->code,
                );
            }
        }

        return $attribute_value_pools;
    }

    /**
     * @return array<int, string>
     */
    private function resolveLocalizedValuesPool(string $pool_type, string $language_code): array
    {
        $pools_by_type = [
            'size'       => [
                'uk'      => ['20x20 см', '30x30 см', '40x40 см', '50x50 см'],
                'en'      => ['20x20 cm', '30x30 cm', '40x40 cm', '50x50 cm'],
                'ru'      => ['20x20 см', '30x30 см', '40x40 см', '50x50 см'],
                'default' => ['20x20', '30x30', '40x40', '50x50'],
            ],
            'color'      => [
                'uk'      => ['Чорний', 'Білий', 'Синій', 'Червоний'],
                'en'      => ['Black', 'White', 'Blue', 'Red'],
                'ru'      => ['Черный', 'Белый', 'Синий', 'Красный'],
                'default' => ['Black', 'White', 'Blue', 'Red'],
            ],
            'weight'     => [
                'uk'      => ['100 г', '250 г', '500 г', '1 кг'],
                'en'      => ['100 g', '250 g', '500 g', '1 kg'],
                'ru'      => ['100 г', '250 г', '500 г', '1 кг'],
                'default' => ['100 g', '250 g', '500 g', '1 kg'],
            ],
            'type'       => [
                'uk'      => ['Класичний', 'Спортивний', 'Повсякденний', 'Преміум'],
                'en'      => ['Classic', 'Sport', 'Casual', 'Premium'],
                'ru'      => ['Классический', 'Спортивный', 'Повседневный', 'Премиум'],
                'default' => ['Classic', 'Sport', 'Casual', 'Premium'],
            ],
            'material'   => [
                'uk'      => ['Бавовна', 'Поліестер', 'Льон', 'Віскоза'],
                'en'      => ['Cotton', 'Polyester', 'Linen', 'Viscose'],
                'ru'      => ['Хлопок', 'Полиэстер', 'Лен', 'Вискоза'],
                'default' => ['Cotton', 'Polyester', 'Linen', 'Viscose'],
            ],
            'length'     => [
                'uk'      => ['30 см', '50 см', '70 см', '100 см'],
                'en'      => ['30 cm', '50 cm', '70 cm', '100 cm'],
                'ru'      => ['30 см', '50 см', '70 см', '100 см'],
                'default' => ['30 cm', '50 cm', '70 cm', '100 cm'],
            ],
            'print_type' => [
                'uk'      => ['Шовкотрафарет', 'Термодрук', 'Вишивка', 'Сублімація'],
                'en'      => ['Silkscreen', 'Heat transfer', 'Embroidery', 'Sublimation'],
                'ru'      => ['Шелкография', 'Термопечать', 'Вышивка', 'Сублимация'],
                'default' => ['Silkscreen', 'Heat transfer', 'Embroidery', 'Sublimation'],
            ],
            'generic'    => [
                'uk'      => ['Стандарт', 'Преміум', 'Комфорт', 'Базовий'],
                'en'      => ['Standard', 'Premium', 'Comfort', 'Basic'],
                'ru'      => ['Стандарт', 'Премиум', 'Комфорт', 'Базовый'],
                'default' => ['Standard', 'Premium', 'Comfort', 'Basic'],
            ],
        ];

        $pool = $pools_by_type[$pool_type] ?? $pools_by_type['generic'];

        return $pool[$language_code] ?? $pool['default'];
    }

    /**
     * @param array<int, array<int, array<int, string>>> $attribute_value_pools
     */
    private function resolveAttributeValue(
        array $attribute_value_pools,
        int   $attribute_id,
        int   $language_id,
        int   $product_id,
    ): string {
        $values_pool = $attribute_value_pools[$attribute_id][$language_id] ?? ['Standard'];

        $value_index = $this->product_to_attribute_value_index[$product_id] ?? null;

        if ($value_index === null) {
            shuffle($this->attributes_values_indexes);

            $value_index                                         = array_rand($this->attributes_values_indexes);
            $this->product_to_attribute_value_index[$product_id] = $value_index;
        }

        return $values_pool[$value_index] ?? $values_pool[0];
    }
}
