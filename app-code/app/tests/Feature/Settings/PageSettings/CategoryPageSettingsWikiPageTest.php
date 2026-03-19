<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Resources\PageSettings\Category\CategoryPageSettingResource;
use App\Filament\Resources\PageSettings\Category\Pages\CategoryPageSettingsWiki;
use App\Filament\Resources\PageSettings\Category\Pages\EditCategoryPageSettings;
use Filament\Actions\Action;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class CategoryPageSettingsWikiPageTest extends TestCase
{
    public function test_resource_registers_wiki_page(): void
    {
        $pages = CategoryPageSettingResource::getPages();

        $this->assertArrayHasKey('wiki', $pages);
        $this->assertSame(CategoryPageSettingsWiki::class, $pages['wiki']->getPage());
    }

    public function test_edit_page_contains_open_wiki_header_action(): void
    {
        $page_instance = app(EditCategoryPageSettings::class);

        $method = new ReflectionMethod(EditCategoryPageSettings::class, 'getHeaderActions');
        $method->setAccessible(true);

        /** @var array<int, Action> $actions */
        $actions = $method->invoke($page_instance);

        $action_names = collect($actions)
            ->map(fn (Action $action): string => $action->getName())
            ->all();

        $this->assertContains('open_wiki', $action_names);
    }

    public function test_wiki_view_exists_and_contains_main_blocks(): void
    {
        $view_path = resource_path('views/filament/resources/page-settings/category/pages/category-page-settings-wiki.blade.php');

        $this->assertFileExists($view_path);

        $view_content = File::get($view_path);

        $this->assertStringContainsString('wiki.intro_title', $view_content);
        $this->assertStringContainsString('wiki.table.field', $view_content);
        $this->assertStringContainsString('getWikiSections', $view_content);
    }
}
