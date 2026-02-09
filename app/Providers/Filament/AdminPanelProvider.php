<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Register JavaScript to handle bfcache and prevent unauthorized access
        $this->registerRoleGuardScript();

        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->spa()
            ->profile()
            // ->multiFactorAuthentication(
            //     AppAuthentication::make()
            //         ->recoverable(),
            // )
            ->sidebarCollapsibleOnDesktop()
            //            ->topNavigation()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->favicon('/favicon-removebg.png')
            ->navigationGroups([
                'Master Data',
                'Manajemen',
                'Pembelajaran',
                'Laporan',
                'Dashboard',
                'Data Saya',
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets(
                $this->getConditionalWidgets()
            )
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
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureUserIsAdmin::class,
            ])->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL + K',
                Platform::Mac => '⌘ + K',
                default => null,
            });
    }

    /**
     * Register JavaScript for inactivity timer and bfcache guard.
     * Injects configuration via data attributes for the shared inactivity-timer.js module.
     */
    private function registerRoleGuardScript(): void
    {
        // Inject the inactivity timer config into the page
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function (): string {
                // Only run for authenticated users
                if (! Auth::check()) {
                    return '';
                }

                // Get timeout values from config (falls back to defaults)
                $idleMinutes = (int) env('SESSION_TIMEOUT_ADMIN', 15);
                $warningMinutes = (int) env('SESSION_WARNING_MINUTES', 2);

                return <<<HTML
                    <div id="admin-inactivity-config"
                         data-inactivity-timer
                         data-idle-minutes="{$idleMinutes}"
                         data-warning-minutes="{$warningMinutes}"
                         data-logout-url="/app/logout"
                         data-session-check-url="/app/api/check-role"
                         style="display:none;"></div>
                HTML;
            },
        );

        // Load the inactivity timer script
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                if (! Auth::check()) {
                    return '';
                }

                // Use Vite to load the module
                return Blade::render('@vite(\'resources/js/inactivity-timer.js\')');
            },
        );
    }

    /**
     * Get widgets conditionally based on user role.
     * Only admin users can see AccountWidget and FilamentInfoWidget.
     */
    private function getConditionalWidgets(): array
    {
        $user = Auth::user();

        // Only show default widgets to admin users
        if ($user instanceof User && $user->hasRole('admin')) {
            return [
                AccountWidget::class,
                FilamentInfoWidget::class,
            ];
        }

        return [];
    }
}
