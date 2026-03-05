<?php

declare(strict_types=1);

namespace App\Supports\Services\Ai;

use App\Services\Ai\OpenAiTranslatorService;
use App\Supports\Services\Translations\Product\ProductAttributeTextAiTranslatorService;
use App\Supports\Services\Translations\Product\ProductDescriptionAiTranslatorService;
use App\Supports\Services\Translations\Product\ProductNameAiTranslatorService;
use Throwable;

final readonly class AiTranslationService
{
    public function __construct(
        private OpenAiTranslatorService $ai,
    ) {}

    /**
     * @throws Throwable
     */
    public function productName(int $product_id, string $prompt): string
    {
        return new ProductNameAiTranslatorService($this->ai, $product_id)->translate($prompt);
    }

    /**
     * @throws Throwable
     */
    public function productDescription(int $product_id, string $prompt): string
    {
        return new ProductDescriptionAiTranslatorService($this->ai, $product_id)->translate($prompt);
    }

    /**
     * @throws Throwable
     */
    public function productAttributeText(int $product_id, int $attribute_id, string $prompt): string
    {
        return new ProductAttributeTextAiTranslatorService($this->ai, $product_id, $attribute_id)->translate($prompt);
    }
}
