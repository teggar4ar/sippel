@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }

    $isLoginPage = $livewire instanceof \Filament\Pages\Auth\Login || $livewire instanceof \App\Filament\Pages\Auth\Login;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    @if ($isLoginPage)
        {{-- Custom Login Layout with inline styles to override Filament defaults --}}
        <style>
            .login-layout { min-height: 100vh; display: flex; flex-direction: column; }
            .login-branding { display: none; }
            .login-form-section { flex: 1; display: flex; flex-direction: column; }
            .login-mobile-header { background: linear-gradient(to right, #334155, #475569); padding: 1.5rem 1rem; }
            .login-form-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f9fafb; }
            .login-card { background: white; border-radius: 1rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); padding: 1.5rem; width: 100%; max-width: 24rem; }
            .login-footer { text-align: center; padding: 1rem; font-size: 0.75rem; color: #6b7280; }

            @media (min-width: 1024px) {
                .login-layout { flex-direction: row; }
                .login-branding { display: flex; width: 50%; background: linear-gradient(to bottom right, #1e293b, #334155, #0f172a); position: relative; overflow: hidden; }
                .login-mobile-header { display: none; }
                .login-form-section { min-height: auto; }
                .login-form-container { padding: 2rem; }
                .login-card { padding: 2rem; }
            }

            @media (min-width: 1280px) {
                .login-branding { width: 55%; }
            }

            .dark .login-form-container { background: #111827; }
            .dark .login-card { background: #1f2937; box-shadow: none; border: 1px solid #374151; }
            .dark .login-footer { color: #9ca3af; }
        </style>

        <div class="login-layout">
            {{-- Left side - Branding (desktop only) --}}
            <div class="login-branding">
                {{-- Grid pattern --}}
                <div style="position: absolute; inset: 0; opacity: 0.1;">
                    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)"/>
                    </svg>
                </div>

                {{-- Content --}}
                <div style="position: relative; z-index: 10; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 100%; padding: 3rem;">
                    <div style="max-width: 24rem; text-align: center;">
                        {{-- Logo --}}
                        <div style="width: 5rem; height: 5rem; margin: 0 auto 1.5rem; background: rgba(255,255,255,0.1); border-radius: 1rem; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.2);">
                            <svg style="width: 2.5rem; height: 2.5rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>

                        <h1 style="font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">SIPPEL</h1>
                        <p style="font-size: 1rem; color: #cbd5e1; margin-bottom: 2rem;">
                            Sistem Informasi Pencatatan<br>Aktivitas Pembelajaran
                        </p>

                        {{-- Features --}}
                        <div style="text-align: left;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; color: #cbd5e1; margin-bottom: 1rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: rgba(20,184,166,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 1rem; height: 1rem; color: #2dd4bf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span style="font-size: 0.875rem;">Catat dan Pantau aktivitas pembelajaran harian</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; color: #cbd5e1; margin-bottom: 1rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: rgba(59,130,246,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 1rem; height: 1rem; color: #60a5fa;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <span style="font-size: 0.875rem;">Laporan nilai dan kehadiran siswa</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; color: #cbd5e1;">
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: rgba(245,158,11,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg style="width: 1rem; height: 1rem; color: #fbbf24;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <span style="font-size: 0.875rem;">Akses untuk Admin, Guru, dan Siswa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right side - Login Form --}}
            <div class="login-form-section">
                {{-- Mobile header --}}
                <div class="login-mobile-header">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: rgba(255,255,255,0.1); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.2);">
                            <svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div style="text-align: left;">
                            <h1 style="font-size: 1.125rem; font-weight: 700; color: white; margin: 0;">SIPPEL</h1>
                            <p style="font-size: 0.75rem; color: #cbd5e1; margin: 0;">Sistem Informasi Pencatatan Aktivitas Pembelajaran</p>
                        </div>
                    </div>
                </div>

                {{-- Form container --}}
                <div class="login-form-container">
                    <div style="width: 100%; max-width: 24rem;">
                        <div class="login-card">
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white m-0">Masuk ke Akun</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Silakan masukkan kredensial Anda</p>
                            </div>
                            {{ $slot }}
                        </div>
                        <p class="login-footer">© {{ date('Y') }} SIPPEL. Hak cipta dilindungi.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Default Simple Layout for other pages --}}
        <div class="fi-simple-layout">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

            @if (($hasTopbar ?? true) && filament()->auth()->check())
                <div class="fi-simple-layout-header">
                    @if (filament()->hasDatabaseNotifications())
                        @livewire(Filament\Livewire\DatabaseNotifications::class, [
                            'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                            'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                        ])
                    @endif

                    @if (filament()->hasUserMenu())
                        @livewire(Filament\Livewire\SimpleUserMenu::class)
                    @endif
                </div>
            @endif

            <div class="fi-simple-main-ctn">
                <main
                    @class([
                        'fi-simple-main',
                        ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                    ])
                >
                    {{ $slot }}
                </main>
            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
        </div>
    @endif
</x-filament-panels::layout.base>
