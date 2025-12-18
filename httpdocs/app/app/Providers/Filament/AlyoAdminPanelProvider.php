<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\LogFilamentErrors;
use App\Http\Middleware\SetDefaultLocalePrefix;
use App\Http\Middleware\User\SetCommonPreferences;
use App\Models\Settings\Language;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->viteTheme('resources/assets/filament/alyo-admin/theme.css')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                function (): View {
                    $languages = new Language()->getActiveLanguages();

                    return view('filament.hooks.language-switcher', [
                        'languages'      => $languages,
                        'currentLocale'  => app()->getLocale(),
                    ]);
                },
            )
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(__('admin/default.menu.item_users')),
            ])
            // Show group menu list if user visited page from group
            ->collapsibleNavigationGroups()
            // Show group menu list if user visited page from group
            ->sidebarCollapsibleOnDesktop(true)
            ->navigationGroups([
                __('admin/default.menu.item_catalog'),
                __('admin/default.menu.item_users'),
                __('admin/default.menu.item_settings'),
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
                SetCommonPreferences::class,
                SetDefaultLocalePrefix::class,
                LogFilamentErrors::class
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
