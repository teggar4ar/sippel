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
     * Register JavaScript to guard against unauthorized access via bfcache.
     * This handles the browser back button loading cached pages.
     */
    private function registerRoleGuardScript(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                // Only run inactivity timer for authenticated users
                if (! Auth::check()) {
                    return '';
                }

                return Blade::render(<<<'HTML_WRAP'
                    <script>
                        (function() {
                            // Inactivity Timer for Admin Panel
                            const IDLE_MINUTES = 30;
                            const WARNING_MINUTES = 2;
                            const LOGOUT_URL = '/app/logout';
                            const IDLE_TIMEOUT = IDLE_MINUTES * 60 * 1000;
                            const WARNING_TIMEOUT = WARNING_MINUTES * 60 * 1000;
                
                            let idleTimer = null;
                            let warningTimer = null;
                            let countdownInterval = null;
                            let warningModal = null;
                            let isWarningShown = false;
                
                            function resetTimers() {
                                clearTimeout(idleTimer);
                                clearTimeout(warningTimer);
                                clearInterval(countdownInterval);
                                dismissWarning();
                
                                idleTimer = setTimeout(() => {
                                    showWarning();
                                }, IDLE_TIMEOUT - WARNING_TIMEOUT);
                            }
                
                            function showWarning() {
                                if (isWarningShown) return;
                                isWarningShown = true;
                
                                let remainingSeconds = WARNING_TIMEOUT / 1000;
                
                                // Create modal with inline styles to ensure visibility
                                warningModal = document.createElement('div');
                                warningModal.id = 'inactivity-warning-modal';
                                warningModal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);';
                                warningModal.innerHTML = `
                                    <div style="background:white;border-radius:12px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);padding:24px;max-width:400px;margin:16px;">
                                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                                            <div style="width:48px;height:48px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                                                <svg style="width:24px;height:24px;color:#d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 style="font-size:18px;font-weight:600;color:#111827;margin:0;">Sesi Akan Berakhir</h3>
                                                <p style="font-size:14px;color:#6b7280;margin:0;">Tidak ada aktivitas terdeteksi</p>
                                            </div>
                                        </div>
                                        <p style="color:#4b5563;margin-bottom:16px;">
                                            Anda akan logout otomatis dalam <span id="admin-countdown-timer" style="font-weight:bold;color:#d97706;">${Math.floor(remainingSeconds / 60)}:${String(remainingSeconds % 60).padStart(2, '0')}</span>
                                        </p>
                                        <div style="display:flex;gap:12px;">
                                            <button id="admin-stay-logged-in" style="flex:1;padding:8px 16px;background:#2563eb;color:white;border:none;border-radius:8px;font-weight:500;cursor:pointer;">
                                                Tetap Login
                                            </button>
                                            <button id="admin-logout-now" style="flex:1;padding:8px 16px;background:#e5e7eb;color:#374151;border:none;border-radius:8px;font-weight:500;cursor:pointer;">
                                                Logout Sekarang
                                            </button>
                                        </div>
                                    </div>
                                `;
                
                                document.body.appendChild(warningModal);
                
                                // Event listeners
                                document.getElementById('admin-stay-logged-in').addEventListener('click', () => {
                                    isWarningShown = false;
                                    resetTimers();
                                });
                
                                document.getElementById('admin-logout-now').addEventListener('click', () => {
                                    performLogout();
                                });
                
                                // Countdown
                                countdownInterval = setInterval(() => {
                                    remainingSeconds--;
                                    const timerEl = document.getElementById('admin-countdown-timer');
                                    if (timerEl) {
                                        const mins = Math.floor(remainingSeconds / 60);
                                        const secs = remainingSeconds % 60;
                                        timerEl.textContent = mins + ':' + String(secs).padStart(2, '0');
                                    }
                                    if (remainingSeconds <= 0) {
                                        clearInterval(countdownInterval);
                                        performLogout();
                                    }
                                }, 1000);
                
                                // Auto logout timer
                                warningTimer = setTimeout(() => {
                                    performLogout();
                                }, WARNING_TIMEOUT);
                            }
                
                            function dismissWarning() {
                                if (warningModal && warningModal.parentNode) {
                                    warningModal.parentNode.removeChild(warningModal);
                                    warningModal = null;
                                }
                                isWarningShown = false;
                            }
                
                            function performLogout() {
                                clearTimeout(idleTimer);
                                clearTimeout(warningTimer);
                                clearInterval(countdownInterval);
                
                                // Create and submit logout form with CSRF token
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = LOGOUT_URL;
                
                                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                                    || document.querySelector('input[name="_token"]')?.value
                                    || '';
                
                                form.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '">';
                                document.body.appendChild(form);
                                form.submit();
                            }
                
                            // Activity events - only reset if warning is not shown
                            const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
                            events.forEach(event => {
                                document.addEventListener(event, function() {
                                    if (!isWarningShown) {
                                        resetTimers();
                                    }
                                }, { passive: true });
                            });
                
                            // Start timers
                            resetTimers();
                
                            // Check session validity via API call (for bfcache handling)
                            async function checkSession() {
                                // Don't check session if warning modal is showing
                                if (isWarningShown) return;
                
                                try {
                                    const response = await fetch('/app/api/check-role', {
                                        method: 'GET',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    });
                
                                    if (!response.ok) {
                                        window.location.replace('/app/login');
                                        return;
                                    }
                
                                    const data = await response.json();
                                    if (!data.authorized) {
                                        window.location.replace('/app/login');
                                    }
                                } catch (e) {
                                    // On error, don't redirect - might be network issue
                                    console.warn('Session check failed:', e);
                                }
                            }
                
                            // Handle page show event (triggered when page is loaded from bfcache)
                            window.addEventListener('pageshow', function(event) {
                                if (event.persisted && !isWarningShown) {
                                    checkSession();
                                }
                            });
                        })();
                    </script>
                HTML_WRAP);
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
