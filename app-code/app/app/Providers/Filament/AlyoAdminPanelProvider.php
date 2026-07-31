<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\ApplicationCurrenciesWikiPage;
use App\Filament\Pages\Wiki\CatalogProductsWikiPage;
use App\Filament\Pages\Wiki\CategoryPageSettingsWikiPage;
use App\Filament\Pages\Wiki\InfoPagesWikiPage;
use App\Filament\Pages\Wiki\ModulesWikiPage;
use App\Filament\Pages\Wiki\UsersWikiPage;
use App\Http\Middleware\LogFilamentErrors;
use App\Http\Middleware\SetDefaultLocalePrefix;
use App\Models\ApplicationSettings\Language;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AlyoAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('alyo-admin')
            ->path('{locale?}/alyo-admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/assets/filament/alyo-admin/theme.css')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                function (): View {
                    $languages = (new Language())->getActiveLanguages();

                    return view('filament.hooks.language-switcher', [
                        'languages' => $languages,
                        'current_locale' => app()->getLocale(),
                    ]);
                },
            )
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationSort(99)
                    ->navigationGroup(AdminNavigationGroupEnum::Users),
            ])
            // Show group menu list if user visited page from group
            ->collapsibleNavigationGroups()
            // Show group menu list if user visited page from group
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                AdminNavigationGroupEnum::Catalog->getLabel(),
                AdminNavigationGroupEnum::InfoPages->getLabel(),
                AdminNavigationGroupEnum::Users->getLabel(),
                AdminNavigationGroupEnum::Marketing->getLabel(),
                AdminNavigationGroupEnum::Orders->getLabel(),
                AdminNavigationGroupEnum::Modules->getLabel(),
                AdminNavigationGroupEnum::Wiki->getLabel(),
                AdminNavigationGroupEnum::PageSettings->getLabel(),
                AdminNavigationGroupEnum::ApplicationSettings->getLabel(),
            ])
            ->navigationItems([
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::Catalog->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::Squares2x2)
                    ->url(fn (): string => CatalogProductsWikiPage::getUrl()),
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::InfoPages->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::DocumentText)
                    ->url(fn (): string => InfoPagesWikiPage::getUrl()),
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::Users->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::Users)
                    ->url(fn (): string => UsersWikiPage::getUrl()),
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::Modules->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::SquaresPlus)
                    ->url(fn (): string => ModulesWikiPage::getUrl()),
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::PageSettings->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::AdjustmentsHorizontal)
                    ->url(fn (): string => CategoryPageSettingsWikiPage::getUrl()),
                NavigationItem::make(fn (): string => AdminNavigationGroupEnum::ApplicationSettings->getLabel())
                    ->group(AdminNavigationGroupEnum::Wiki)
                    ->icon(Heroicon::Cog6Tooth)
                    ->url(fn (): string => ApplicationCurrenciesWikiPage::getUrl()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverPages(in: base_path('Modules/NovaPoshta/app/Filament/Pages'), for: 'Modules\NovaPoshta\Filament\Pages')
            ->discoverPages(in: base_path('Modules/UkrPoshta/app/Filament/Pages'), for: 'Modules\UkrPoshta\Filament\Pages')
            ->discoverPages(in: base_path('Modules/Pickup/app/Filament/Pages'), for: 'Modules\Pickup\Filament\Pages')
            ->discoverPages(in: base_path('Modules/PaymentUponDelivery/app/Filament/Pages'), for: 'Modules\PaymentUponDelivery\Filament\Pages')
            ->discoverPages(in: base_path('Modules/BankTransfer/app/Filament/Pages'), for: 'Modules\BankTransfer\Filament\Pages')
            ->discoverPages(in: base_path('Modules/WayForPay/app/Filament/Pages'), for: 'Modules\WayForPay\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetDefaultLocalePrefix::class,
                LogFilamentErrors::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
