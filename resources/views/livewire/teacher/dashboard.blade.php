<div class="space-y-4">
    {{-- Welcome Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white">Halo, {{ explode(' ', auth()->user()->name ?? 'Guru')[0] }}!</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300 mt-0.5">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm cursor-pointer">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>

    {{-- Summary Widget — 4 metric cards with icons --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-1.5 sm:gap-3">
        {{-- Kelas Diampu --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg items-center justify-center">
                <flux:icon name="building-library" class="w-4.5 h-4.5 text-blue-500/70 dark:text-blue-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-blue-500/80 dark:text-blue-400/70 leading-tight">
                <span class="sm:hidden">Mapel</span>
                <span class="hidden sm:inline">Mata Pelajaran Diampu</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['kelas_diampu'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Mapel</span></p>
        </div>
        {{-- Total Siswa --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
                <flux:icon name="user-group" class="w-4.5 h-4.5 text-emerald-500/70 dark:text-emerald-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-500/80 dark:text-emerald-400/70 leading-tight">
                <span class="sm:hidden">Siswa</span>
                <span class="hidden sm:inline">Total Siswa</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['total_siswa'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Siswa</span></p>
        </div>
        {{-- Aktivitas Minggu Ini --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-violet-50 dark:bg-violet-900/30 rounded-lg items-center justify-center">
                <flux:icon name="clipboard-document-check" class="w-4.5 h-4.5 text-violet-500/70 dark:text-violet-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-violet-500/80 dark:text-violet-400/70 leading-tight">
                <span class="sm:hidden">Minggu Ini</span>
                <span class="hidden sm:inline">Aktivitas Minggu Ini</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['aktivitas_minggu_ini'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Aktivitas</span></p>
        </div>
        {{-- Rata-rata Kehadiran --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg items-center justify-center">
                <flux:icon name="hand-raised" class="w-4.5 h-4.5 text-amber-500/70 dark:text-amber-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-amber-500/80 dark:text-amber-400/70 leading-tight">
                <span class="sm:hidden">Kehadiran</span>
                <span class="hidden sm:inline">Rata-rata Kehadiran</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['rata_kehadiran'] }}<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">%</span></p>
        </div>
    </div>

    {{-- Quick Actions - Mobile compact buttons --}}
    <div class="flex gap-2 lg:hidden">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
           class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-slate-100 dark:bg-slate-900/90 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg">
            <flux:icon name="list-bullet" class="w-4 h-4" />
            Aktivitas
        </a>
        <a href="{{ route('teacher.laporan') }}" wire:navigate
           class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-slate-100 dark:bg-slate-900/90 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg">
            <flux:icon name="chart-bar" class="w-4 h-4" />
            Laporan
        </a>
    </div>

    @php
        $subjectCount    = $this->mySubjects->count();
        $partisipasiMap  = $this->partisipasiPerKelas->keyBy('mapel_id');
        $useSidebar      = true;
    @endphp

    {{-- ═══ Main content area ═══ --}}
    <div class="{{ $useSidebar ? 'grid grid-cols-1 lg:grid-cols-3 gap-4' : '' }}">

        {{-- Aktivitas Terkini --}}
        <div class="{{ $useSidebar ? 'lg:col-span-2' : '' }}">
            <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Aktivitas Terkini <span class="text-xs font-normal text-slate-400 dark:text-slate-500">(7 Hari Terakhir)</span></h2>
                    <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
                       class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors cursor-pointer">
                        Lihat Semua
                        <flux:icon name="arrow-right" class="w-3 h-3" />
                    </a>
                </div>

                @if($this->recentActivities->isNotEmpty())
                    {{-- Desktop Table --}}
                    <div class="hidden lg:block px-4 pb-2">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Tanggal</flux:table.column>
                                <flux:table.column>Kelas</flux:table.column>
                                <flux:table.column>Mata Pelajaran</flux:table.column>
                                <flux:table.column>Topik</flux:table.column>
                                <flux:table.column>Kehadiran</flux:table.column>
                                <flux:table.column>Partisipasi</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->recentActivities as $activity)
                                    @php
                                        $pc = match($activity['partisipasi']) {
                                            'Sangat Aktif' => 'green', 'Aktif' => 'blue',
                                            'Cukup' => 'amber', 'Pasif' => 'red', default => 'zinc',
                                        };
                                        $kh = $activity['kehadiran_pct'];
                                        $khColor      = $kh >= 80 ? 'text-emerald-600 dark:text-emerald-400'
                                            : ($kh >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                                        $khBarColor   = $kh >= 80 ? 'bg-emerald-500'
                                            : ($kh >= 60 ? 'bg-amber-500' : 'bg-red-500');
                                    @endphp
                                    <flux:table.row :key="$activity['id']">
                                        <flux:table.cell class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                                            <div class="text-xs tabular-nums">{{ $activity['tanggal'] }}</div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 tabular-nums">{{ $activity['waktu'] }} WIB</div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <span class="text-xs font-medium px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded">{{ $activity['kelas'] }}</span>
                                        </flux:table.cell>
                                        <flux:table.cell><span class="text-sm text-slate-700 dark:text-slate-300">{{ $activity['mapel'] }}</span></flux:table.cell>
                                        <flux:table.cell class="max-w-[11rem] w-15">
                                            <span class="block truncate text-sm text-slate-700 dark:text-slate-300" title="{{ $activity['topik'] }}">{{ $activity['topik'] }}</span>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex flex-col gap-1 min-w-[3.5rem]">
                                                <span class="text-xs tabular-nums font-semibold {{ $khColor }}">{{ $activity['kehadiran'] }}</span>
                                                <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $khBarColor }}" x-data="{ w: @js(min($kh, 100)) }" x-bind:style="`width: ${w}%`"></div>
                                                </div>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if($activity['partisipasi'] !== '-')
                                                <flux:badge color="{{ $pc }}" size="sm" inset="top bottom">{{ $activity['partisipasi'] }}</flux:badge>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    {{-- Mobile Cards --}}
                    <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-700/80">
                        @foreach($this->recentActivities as $activity)
                            @php
                                $khi  = $activity['kehadiran_pct'];
                                $khc  = $khi >= 80 ? 'text-emerald-600 dark:text-emerald-400'
                                    : ($khi >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                                $khbc = $khi >= 80 ? 'bg-emerald-500' : ($khi >= 60 ? 'bg-amber-500' : 'bg-red-500');
                                $pcm  = match($activity['partisipasi']) {
                                    'Sangat Aktif' => 'green', 'Aktif' => 'blue',
                                    'Cukup' => 'amber', 'Pasif' => 'red', default => 'zinc',
                                };
                            @endphp
                            <div class="p-3" wire:key="dash-mob-{{ $activity['id'] }}">
                                {{-- Baris 1: tanggal + kelas + mapel --}}
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="text-xs font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ $activity['tanggal'] }}</span>
                                    <span class="text-[10px] tabular-nums text-slate-400 dark:text-slate-500">{{ $activity['waktu'] }} WIB</span>
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded">{{ $activity['kelas'] }}</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $activity['mapel'] }}</span>
                                </div>
                                {{-- Baris 2: topik --}}
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100 leading-snug mb-2 truncate" title="{{ $activity['topik'] }}">{{ $activity['topik'] }}</p>
                                {{-- Baris 3: kehadiran + partisipasi --}}
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs tabular-nums font-semibold {{ $khc }}">
                                            <flux:icon name="users" class="w-3 h-3 inline -mt-0.5" /> {{ $activity['kehadiran'] }}
                                        </span>
                                        <div class="w-10 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $khbc }}" x-data="{ w: @js(min($khi, 100)) }" x-bind:style="`width: ${w}%`"></div>
                                        </div>
                                    </div>
                                    @if($activity['partisipasi'] !== '-')
                                        <flux:badge color="{{ $pcm }}" size="sm">{{ $activity['partisipasi'] }}</flux:badge>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                            <flux:icon name="clipboard-document-list" class="w-6 h-6 text-slate-400" />
                        </div>
                        <h3 class="font-medium text-slate-900 dark:text-white mb-1">Belum Ada Aktivitas</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-300 mb-4">Belum ada aktivitas dalam 7 hari terakhir</p>
                        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                            <flux:icon name="plus" class="w-4 h-4" />
                            Buat Aktivitas
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mata Pelajaran Teraktif — sidebar (desktop + mobile) --}}
        <div class="{{ $useSidebar ? 'lg:col-span-1' : '' }}">
            <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Mata Pelajaran Teraktif</h2>
                </div>
                <div class="grid gap-3 p-3">
                    @forelse($this->partisipasiPerKelas as $mapel)
                        @php
                            $pl = match(true) { $mapel['avg'] >= 3.5 => 'Sangat Aktif', $mapel['avg'] >= 2.5 => 'Aktif', $mapel['avg'] >= 1.5 => 'Cukup', default => 'Pasif' };
                            $bc = match($pl) { 'Sangat Aktif' => 'green', 'Aktif' => 'blue', 'Cukup' => 'amber', 'Pasif' => 'red' };
                            $kh = $mapel['kehadiran_pct'] ?? 0;
                        @endphp
                        <div class="border border-slate-200 dark:border-slate-700/90 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/60 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <flux:icon name="building-library" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $mapel['kelas'] }} ({{ $mapel['mapel'] }})
                                    </p>
                                    <div class="flex items-center gap-1 mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        <flux:icon name="user-group" class="w-3.5 h-3.5" />
                                        <span>{{ $mapel['siswa_count'] ?? 0 }} Siswa</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-3 mt-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                        <flux:icon name="users" class="w-3.5 h-3.5" />
                                        <span class="font-medium">Kehadiran</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $kh }}%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden mt-1.5">
                                        <div class="h-full bg-emerald-500 rounded-full" x-data="{ w: @js(min($kh, 100)) }" x-bind:style="`width: ${w}%`"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <flux:icon name="star" class="w-3.5 h-3.5 text-amber-500" />
                                    <span class="text-xs text-slate-600 dark:text-slate-300">Partisipasi</span>
                                    <flux:badge color="{{ $bc }}" size="sm">{{ $pl }}</flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="border border-dashed border-slate-200 dark:border-slate-700/90 rounded-lg p-3 text-center">
                            <flux:icon name="book-open" class="w-5 h-5 mx-auto text-slate-300 dark:text-slate-600 mb-1" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada mata pelajaran diampu.</p>
                        </div>
                    @endforelse
                </div>
            </div>

                @if($this->mySubjects->count() > 5)
                    <div class="text-center mt-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400">+{{ $this->mySubjects->count() - 5 }} kelas lainnya</span>
                    </div>
                @endif

            <div class="mt-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-4 pt-4 pb-2">
                        <p class="text-[11px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-widest">Panduan Indikator</p>
                        <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mt-0.5">Tingkat Partisipasi</h2>
                    </div>

                    <div class="px-4 pb-4 mt-2 space-y-0 divide-y divide-slate-100 dark:divide-slate-800">
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Sangat Aktif</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Proaktif, memimpin diskusi, sering bertanya.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400">Aktif</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Fokus, merespons pertanyaan dengan baik.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">Cukup</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Mendengarkan, namun jarang mengambil inisiatif.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-rose-600 dark:text-rose-400">Pasif</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kurang fokus, pasif, atau tidak merespons.</p>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
