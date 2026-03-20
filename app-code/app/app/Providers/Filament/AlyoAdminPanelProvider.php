<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Http\Middleware\LogFilamentErrors;
use App\Http\Middleware\SetDefaultLocalePrefix;
use App\Http\Middleware\User\SetCommonPreferences;
use App\Models\ApplicationSettings\Language;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
                    $languages = new Language()->getActiveLanguages();

                    return view('filament.hooks.language-switcher', [
                        'languages'      => $languages,
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
                AdminNavigationGroupEnum::Modules->getLabel(),
                AdminNavigationGroupEnum::Wiki->getLabel(),
                AdminNavigationGroupEnum::PageSettings->getLabel(),
                AdminNavigationGroupEnum::ApplicationSettings->getLabel(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
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
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetDefaultLocalePrefix::class,
                SetCommonPreferences::class,
                LogFilamentErrors::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
