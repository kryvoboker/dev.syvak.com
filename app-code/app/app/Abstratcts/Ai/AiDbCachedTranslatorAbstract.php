<?php

declare(strict_types=1);

namespace App\Abstratcts\Ai;

use App\Models\Catalogs\Products\ProductAttributeTextHash;
use App\Models\Catalogs\Products\ProductDescriptionHash;
use App\Models\Catalogs\Products\ProductNameHash;
use App\Services\Ai\OpenAiTranslatorService;
use App\Supports\Services\Ai\AiPromptHasherService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class AiDbCachedTranslatorAbstract
{
    private ?ProductNameHash $product_name_hash;

    private ?ProductDescriptionHash $product_description_hash;

    private ?ProductAttributeTextHash $product_attribute_text_hash;

    public function __construct(
        protected OpenAiTranslatorService $ai,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function translate(string $prompt): string
    {
        $normalized = AiPromptHasherService::normalize($prompt);
        $hash       = AiPromptHasherService::hash($normalized);

        $cached = $this->findCached($hash);

        if ($cached !== null) {
            return $cached;
        }

        $translated = $this->ai->translate($prompt);

        DB::transaction(function () use ($hash, $prompt, $translated) {
            // re-check in case of race
            $cached_again = $this->findCached($hash);

            if ($cached_again === null) {
                $this->storeTranslation($hash, $prompt, $translated);
            }
        });

        return $translated;
    }

    abstract protected function findCached(string $hash): ?string;

    abstract protected function storeTranslation(string $hash, string $prompt, string $translated_text): Model;

    public function getProductNameHash(): ?ProductNameHash
    {
        return $this->product_name_hash;
    }

    /**
     * @return $this
     */
    public function setProductNameHash(?ProductNameHash $product_name_hash): AiDbCachedTranslatorAbstract
    {
        $this->product_name_hash = $product_name_hash;

        return $this;
    }

    public function getProductDescriptionHash(): ?ProductDescriptionHash
    {
        return $this->product_description_hash;
    }

    /**
     * @return $this
     */
    public function setProductDescriptionHash(?ProductDescriptionHash $product_description_hash): AiDbCachedTranslatorAbstract
    {
        $this->product_description_hash = $product_description_hash;

        return $this;
    }

    public function getProductAttributeTextHash(): ?ProductAttributeTextHash
    {
        return $this->product_attribute_text_hash;
    }

    /**
     * @return $this
     */
    public function setProductAttributeTextHash(?ProductAttributeTextHash $product_attribute_text_hash): AiDbCachedTranslatorAbstract
    {
        $this->product_attribute_text_hash = $product_attribute_text_hash;

        return $this;
    }
}
