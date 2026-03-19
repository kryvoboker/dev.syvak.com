<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductFormValidationRulesTest extends TestCase
{
    public function test_attributes_language_field_uses_languages_exists_rule(): void
    {
        $product_form_file_path = base_path('app/Filament/Resources/Catalogs/Products/Products/Schemas/ProductForm.php');

        $product_form_content = File::get($product_form_file_path);

        $this->assertStringContainsString("Rule::exists('languages', 'id')", $product_form_content);
    }
}
