<div class="space-y-4">
    {{-- Welcome header - Compact --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white">Halo, {{ explode(' ', auth()->user()->name ?? 'Guru')[0] }}!</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300 mt-0.5">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>

    {{-- Stats cards - Compact grid --}}
    <div class="grid grid-cols-3 gap-2 lg:gap-4">
        <div class="p-3 lg:p-4 bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90">
            <div class="flex items-center gap-2 lg:gap-3">
                <div class="w-9 h-9 lg:w-10 lg:h-10 bg-blue-100 dark:bg-blue-900/80 rounded-lg flex items-center justify-center flex-shrink-0">
                    <flux:icon name="clipboard-document-check" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-lg lg:text-2xl font-bold text-slate-900 dark:text-white">{{ $this->dashboardStats['aktivitas_bulan_ini'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-300 truncate">Aktivitas</p>
                </div>
            </div>
        </div>
        <div class="p-3 lg:p-4 bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90">
            <div class="flex items-center gap-2 lg:gap-3">
                <div class="w-9 h-9 lg:w-10 lg:h-10 bg-emerald-100 dark:bg-emerald-900/80 rounded-lg flex items-center justify-center flex-shrink-0">
                    <flux:icon name="user-group" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-lg lg:text-2xl font-bold text-slate-900 dark:text-white">{{ $this->dashboardStats['rata_kehadiran'] }}%</p>
                    <p class="text-xs text-slate-500 dark:text-slate-300 truncate">Kehadiran</p>
                </div>
            </div>
        </div>
        <div class="p-3 lg:p-4 bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90">
            <div class="flex items-center gap-2 lg:gap-3">
                <div class="w-9 h-9 lg:w-10 lg:h-10 bg-amber-100 dark:bg-amber-900/80 rounded-lg flex items-center justify-center flex-shrink-0">
                    <flux:icon name="book-open" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-lg lg:text-2xl font-bold text-slate-900 dark:text-white">{{ $this->dashboardStats['total_mapel'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-300 truncate">Mapel</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions - Mobile compact buttons --}}
    <div class="flex gap-2 lg:hidden">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
           class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-slate-100 dark:bg-slate-900/90 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors hover:bg-slate-200 dark:hover:bg-slate-800">
            <flux:icon name="list-bullet" class="w-4 h-4" />
            Aktivitas
        </a>
        <a href="{{ route('teacher.laporan') }}" wire:navigate
           class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-slate-100 dark:bg-slate-900/90 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors hover:bg-slate-200 dark:hover:bg-slate-800">
            <flux:icon name="chart-bar" class="w-4 h-4" />
            Laporan
        </a>
    </div>

    {{-- My Classes - Compact cards --}}
    @if($this->mySubjects->isNotEmpty())
    <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
            <h2 class="font-semibold text-slate-900 dark:text-white">Kelas Saya</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700/80">
            @foreach($this->mySubjects->take(5) as $mapel)
                <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-white">{{ $mapel->kelas->tingkat_kelas }}{{ $mapel->kelas->grup_kelas }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ $mapel->nama_mapel }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-300">{{ $mapel->kelas->siswa_count ?? 0 }} siswa</p>
                        </div>
                    </div>
                    <a href="{{ route('teacher.aktivitas.create', ['mapel' => $mapel->id]) }}" wire:navigate
                       class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/40 rounded-lg transition-colors flex-shrink-0">
                        <flux:icon name="plus-circle" class="w-5 h-5" />
                    </a>
                </div>
            @endforeach
        </div>
        @if($this->mySubjects->count() > 5)
        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/80 text-center">
            <span class="text-xs text-slate-500 dark:text-slate-300">+{{ $this->mySubjects->count() - 5 }} kelas lainnya</span>
        </div>
        @endif
    </div>
    @endif

    {{-- Participation Progress - Compact --}}
    @if($this->partisipasiPerKelas->isNotEmpty())
    <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
            <h2 class="font-semibold text-slate-900 dark:text-white">Partisipasi Siswa</h2>
        </div>
        <div class="p-4 space-y-3">
            @foreach($this->partisipasiPerKelas->take(4) as $data)
                <div>
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="text-slate-600 dark:text-slate-300 truncate mr-2">{{ $data['kelas'] }} · {{ $data['mapel'] }}</span>
                        <span class="font-semibold text-slate-900 dark:text-white flex-shrink-0">{{ $data['avg'] }}/5</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700/80 rounded-full h-2">
                        <div
                            class="h-2 rounded-full transition-all duration-300 {{ $data['avg'] >= 4 ? 'bg-emerald-500' : ($data['avg'] >= 3 ? 'bg-blue-500' : ($data['avg'] >= 2 ? 'bg-amber-500' : 'bg-red-500')) }}"
                            style="width: {{ ($data['avg'] / 5) * 100 }}%">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @else
    {{-- Empty state --}}
    <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 p-6 text-center">
        <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
            <flux:icon name="clipboard-document-list" class="w-6 h-6 text-slate-400" />
        </div>
        <h3 class="font-medium text-slate-900 dark:text-white mb-1">Belum Ada Aktivitas</h3>
        <p class="text-sm text-slate-500 dark:text-slate-300 mb-4">Mulai catat aktivitas pembelajaran pertama Anda</p>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>
    @endif
</div>
