<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\CategoryPageSettingsWikiPage;
use App\Filament\Resources\PageSettings\Category\CategoryPageSettingResource;
use App\Filament\Resources\PageSettings\Category\Pages\EditCategoryPageSettings;
use Filament\Actions\Action;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class CategoryPageSettingsWikiPageTest extends TestCase
{
    public function test_resource_does_not_register_wiki_page_anymore(): void
    {
        $pages = CategoryPageSettingResource::getPages();

        $this->assertArrayNotHasKey('wiki', $pages);
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

    public function test_standalone_wiki_page_is_discoverable_in_wiki_group(): void
    {
        $this->assertStringContainsString('/wiki/page-settings-categories', CategoryPageSettingsWikiPage::getUrl());
        $this->assertSame((string) AdminNavigationGroupEnum::PageSettings->getLabel(), CategoryPageSettingsWikiPage::getNavigationParentItem());
    }

    public function test_shared_wiki_view_exists_and_contains_main_blocks(): void
    {
        $view_path = resource_path('views/filament/pages/wiki/page.blade.php');

        $this->assertFileExists($view_path);

        $view_content = File::get($view_path);

        $this->assertStringContainsString('admin/wiki.common.examples_title', $view_content);
        $this->assertStringContainsString('getTableHeadings', $view_content);
        $this->assertStringContainsString('getWikiSections', $view_content);
    }
}
