<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\ApplicationCurrenciesWikiPage;
use App\Filament\Pages\Wiki\ApplicationLanguagesWikiPage;
use App\Filament\Pages\Wiki\ApplicationSettingsWikiPage;
use App\Filament\Pages\Wiki\CatalogAttributesWikiPage;
use App\Filament\Pages\Wiki\CatalogCategoriesWikiPage;
use App\Filament\Pages\Wiki\CatalogProductsWikiPage;
use App\Filament\Pages\Wiki\CategoryPageSettingsWikiPage;
use App\Filament\Pages\Wiki\InfoPagesWikiPage;
use App\Filament\Pages\Wiki\ModulesWikiPage;
use App\Filament\Pages\Wiki\UserGroupsWikiPage;
use App\Filament\Pages\Wiki\UsersWikiPage;
use App\Filament\Resources\ApplicationSettings\AppSettings\Pages\EditAppSetting;
use App\Filament\Resources\ApplicationSettings\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Catalogs\Products\Products\Pages\ListProducts;
use App\Filament\Resources\Modules\ModuleDefinitions\Pages\ListModuleDefinitions;
use Filament\Actions\Action;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class AdminWikiNavigationPagesTest extends TestCase
{
    public function test_wiki_pages_are_in_wiki_navigation_group_and_have_stable_urls(): void
    {
        $wiki_pages = [
            CatalogProductsWikiPage::class => AdminNavigationGroupEnum::Catalog,
            CatalogCategoriesWikiPage::class => AdminNavigationGroupEnum::Catalog,
            CatalogAttributesWikiPage::class => AdminNavigationGroupEnum::Catalog,
            CategoryPageSettingsWikiPage::class => AdminNavigationGroupEnum::PageSettings,
            InfoPagesWikiPage::class => AdminNavigationGroupEnum::InfoPages,
            UsersWikiPage::class => AdminNavigationGroupEnum::Users,
            UserGroupsWikiPage::class => AdminNavigationGroupEnum::Users,
            ModulesWikiPage::class => AdminNavigationGroupEnum::Modules,
            ApplicationCurrenciesWikiPage::class => AdminNavigationGroupEnum::ApplicationSettings,
            ApplicationLanguagesWikiPage::class => AdminNavigationGroupEnum::ApplicationSettings,
            ApplicationSettingsWikiPage::class => AdminNavigationGroupEnum::ApplicationSettings,
        ];

        foreach ($wiki_pages as $wiki_page_class => $navigation_parent_group) {
            $this->assertSame(AdminNavigationGroupEnum::Wiki, $wiki_page_class::getNavigationGroup());
            $this->assertSame((string) $navigation_parent_group->getLabel(), $wiki_page_class::getNavigationParentItem());
            $this->assertStringContainsString('/wiki/', $wiki_page_class::getUrl());
        }
    }

    public function test_admin_pages_contain_open_wiki_header_action(): void
    {
        $pages = [
            ListProducts::class,
            ListCurrencies::class,
            ListModuleDefinitions::class,
            EditAppSetting::class,
        ];

        foreach ($pages as $page_class) {
            $page_instance = app($page_class);

            $method = new ReflectionMethod($page_class, 'getHeaderActions');
            $method->setAccessible(true);

            /** @var array<int, Action> $actions */
            $actions = $method->invoke($page_instance);

            $action_names = collect($actions)
                ->map(fn (Action $action): string => $action->getName())
                ->all();

            $this->assertContains('open_wiki', $action_names);
        }
    }

    public function test_shared_wiki_view_and_language_files_exist(): void
    {
        $wiki_view_path = resource_path('views/filament/pages/wiki/page.blade.php');

        $this->assertFileExists($wiki_view_path);

        $wiki_view_content = File::get($wiki_view_path);

        $this->assertStringContainsString('admin/wiki/wiki.common.examples_title', $wiki_view_content);
        $this->assertStringContainsString('getWikiSections', $wiki_view_content);

        $this->assertFileExists(lang_path('en/admin/wiki/wiki.php'));
        $this->assertFileExists(lang_path('uk/admin/wiki/wiki.php'));
    }
}
