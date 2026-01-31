<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIPPEL - Portal Guru' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-removebg.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-removebg.png') }}">

    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#1e293b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIPPEL Guru">
    <link rel="manifest" href="/manifest-teacher.json">
    <link rel="apple-touch-icon" href="/icons/teacher-icon-192.svg">

    @fluxAppearance
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 antialiased overflow-x-hidden"
      data-inactivity-timer
      data-idle-minutes="30"
      data-warning-minutes="2"
      data-logout-url="{{ route('filament.app.auth.logout') }}">
    <div class="flex min-h-screen w-full overflow-x-hidden">
        {{-- Desktop Sidebar - Slate/Blue professional theme --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-60 lg:fixed lg:inset-y-0 bg-slate-800 dark:bg-slate-900">
            {{-- Brand --}}
            <div class="flex items-center h-14 px-4 bg-slate-900 dark:bg-black/30">
                <a href="{{ route('teacher.dashboard') }}" wire:navigate class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <flux:icon name="academic-cap" class="w-5 h-5 text-white" />
                    </div>
                    <span class="text-base font-bold text-white">SIPPEL</span>
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('teacher.dashboard') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('teacher.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80 hover:text-white' }}">
                    <flux:icon name="squares-2x2" variant="outline" class="w-5 h-5" />
                    Dashboard
                </a>
                <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('teacher.aktivitas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80 hover:text-white' }}">
                    <flux:icon name="clipboard-document-list" variant="outline" class="w-5 h-5" />
                    Aktivitas
                </a>
                <a href="{{ route('teacher.laporan') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('teacher.laporan') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80 hover:text-white' }}">
                    <flux:icon name="chart-bar" variant="outline" class="w-5 h-5" />
                    Laporan
                </a>
            </nav>

            {{-- User info --}}
            <div class="px-3 py-3 border-t border-slate-700/80">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-white">{{ substr(auth()->user()->name ?? 'G', 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Guru' }}</p>
                        <p class="text-xs text-slate-300">Guru</p>
                    </div>
                </div>
                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="flex items-center gap-2 w-full mt-2 px-3 py-2 text-sm text-slate-300 hover:text-white hover:bg-slate-700/80 rounded-lg transition-colors cursor-pointer">
                    <flux:icon name="arrow-right-start-on-rectangle" variant="outline" class="w-4 h-4" />
                    Keluar
                </button>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-data="{ open: false }" class="lg:hidden">
            {{-- Backdrop --}}
            <div x-show="open" x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"></div>

            {{-- Mobile sidebar --}}
            <aside x-show="open" x-transition:enter="transition ease-out duration-200 transform"
                   x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition ease-in duration-200 transform"
                   x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                   class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 dark:bg-slate-900 shadow-xl">
                <div class="flex items-center justify-between h-14 px-4 bg-slate-900 dark:bg-black/30">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <flux:icon name="academic-cap" class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-base font-bold text-white">SIPPEL</span>
                    </div>
                    <button @click="open = false" class="p-1.5 text-slate-300 hover:text-white rounded-lg">
                        <flux:icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>
                <nav class="px-3 py-4 space-y-1">
                    <a href="{{ route('teacher.dashboard') }}" wire:navigate @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('teacher.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80' }}">
                        <flux:icon name="squares-2x2" variant="outline" class="w-5 h-5" />
                        Dashboard
                    </a>
                    <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('teacher.aktivitas.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80' }}">
                        <flux:icon name="clipboard-document-list" variant="outline" class="w-5 h-5" />
                        Aktivitas
                    </a>
                    <a href="{{ route('teacher.laporan') }}" wire:navigate @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('teacher.laporan') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700/80' }}">
                        <flux:icon name="chart-bar" variant="outline" class="w-5 h-5" />
                        Laporan
                    </a>
                </nav>
                <div class="absolute bottom-0 left-0 right-0 px-3 py-3 border-t border-slate-700/80">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-sm font-semibold text-white">{{ substr(auth()->user()->name ?? 'G', 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Guru' }}</p>
                            <p class="text-xs text-slate-300">Guru</p>
                        </div>
                    </div>
                    <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="flex items-center gap-2 w-full mt-2 px-3 py-2 text-sm text-slate-300 hover:text-white hover:bg-slate-700/80 rounded-lg">
                        <flux:icon name="arrow-right-start-on-rectangle" variant="outline" class="w-4 h-4" />
                        Keluar
                    </button>
                </div>
            </aside>

            {{-- Mobile header - Compact --}}
            <header class="fixed top-0 left-0 right-0 z-30 flex items-center justify-between h-14 px-3 bg-slate-800 dark:bg-slate-900/95 lg:hidden safe-area-inset">
                <div class="flex items-center gap-2">
                    <button @click="open = true" class="p-2 -ml-1 text-slate-300 hover:text-white rounded-lg">
                        <flux:icon name="bars-3" class="w-6 h-6" />
                    </button>
                    <div class="w-7 h-7 bg-blue-500 rounded-md flex items-center justify-center">
                        <flux:icon name="academic-cap" class="w-4 h-4 text-white" />
                    </div>
                    <span class="text-base font-semibold text-white">SIPPEL</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                       class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <flux:icon name="plus" class="w-5 h-5" />
                    </a>
                </div>
            </header>
        </div>

        {{-- Main content --}}
        <main class="flex-1 lg:pl-60 overflow-x-hidden">
            <div class="pt-16 lg:pt-4 pb-4 px-3 lg:px-6">
                <div class="max-w-5xl mx-auto w-full">
                    {{-- Flash messages --}}
                    @if (session('success'))
                        <div class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-200 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/50 text-red-700 dark:text-red-200 rounded-lg text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Page content --}}
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    {{-- Logout form --}}
    <form id="logout-form" action="{{ route('filament.app.auth.logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    @fluxScripts
    @livewireScripts

    {{-- PWA Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw-teacher.js')
                    .then((registration) => {
                        console.log('SW Teacher registered:', registration.scope);
                    })
                    .catch((error) => {
                        console.log('SW Teacher registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
