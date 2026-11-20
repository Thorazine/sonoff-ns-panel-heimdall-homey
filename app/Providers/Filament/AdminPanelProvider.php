<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\InitialAdminSetup;
use App\Filament\Widgets\HeimdallOverviewWidget;
use App\Filament\Widgets\LatestActivityLogsWidget;
use App\Http\Middleware\EnforceAdminIdleTimeout;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Heimdall Beheer')
            ->defaultThemeMode(ThemeMode::Dark)
            ->login(AdminLogin::class)
            ->routes(function (): void {
                Route::get('/setup', InitialAdminSetup::class)->name('auth.setup');
            })
            ->colors([
                'primary' => Color::Teal,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                function (): HtmlString {
                    if (! Filament::auth()->check()) {
                        return new HtmlString('');
                    }

                    $timeoutMs = (EnforceAdminIdleTimeout::TIMEOUT_SECONDS + 1) * 1000;

                    return new HtmlString(<<<HTML
                        <script>
                            (() => {
                                window.addEventListener('pageshow', (event) => {
                                    if (event.persisted) {
                                        window.location.reload();
                                    }
                                });

                                let idleTimer = null;
                                const lockAdmin = () => {
                                    window.location.reload();
                                };
                                const resetIdleTimer = () => {
                                    if (idleTimer) clearTimeout(idleTimer);
                                    idleTimer = setTimeout(lockAdmin, {$timeoutMs});
                                };

                                ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((evt) => {
                                    window.addEventListener(evt, resetIdleTimer, { passive: true });
                                });

                                resetIdleTimer();
                            })();
                        </script>
                    HTML);
                }
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                HeimdallOverviewWidget::class,
                LatestActivityLogsWidget::class,
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
            ])
            ->authMiddleware([
                EnforceAdminIdleTimeout::class,
                Authenticate::class,
            ], isPersistent: true);
    }
}
