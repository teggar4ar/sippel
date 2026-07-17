<div class="space-y-4">

    {{-- ── Welcome Header ─────────────────────────────────────────────────── --}}
    <x-ui.section-heading variant="teacher" title="Halo, {{ explode(' ', auth()->user()->name ?? 'Guru')[0] }}!"
        subtitle="{{ now()->translatedFormat('l, d F Y') }}">
        <x-slot:action>
            <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm cursor-pointer">
                <flux:icon name="plus" variant="outline" class="w-4 h-4" />
                Buat Aktivitas
            </a>
        </x-slot:action>
    </x-ui.section-heading>

    {{-- ── 4 Metric Cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <x-ui.metric-card label="Mata Pelajaran Diampu" short-label="Mapel" :value="$this->dashboardStats['kelas_diampu']" unit="Mapel"
            icon="building-library" accent="blue" />
        <x-ui.metric-card label="Total Siswa" short-label="Siswa" :value="$this->dashboardStats['total_siswa']" unit="Siswa" icon="user-group"
            accent="emerald" />
        <x-ui.metric-card label="Aktivitas Minggu Ini" short-label="Minggu Ini" :value="$this->dashboardStats['aktivitas_minggu_ini']" unit="Aktivitas"
            icon="clipboard-document-check" accent="violet" />
        <x-ui.metric-card label="Rata-rata Kehadiran" short-label="Kehadiran" :value="$this->dashboardStats['rata_kehadiran']" unit="%"
            icon="hand-raised" accent="amber" />
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MAIN 2-COLUMN LAYOUT: Charts (kiri) + Sidebar (kanan)                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- ── KOLOM KIRI: Filter + 3 Chart ──────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Filter Bar --}}
            <x-ui.card variant="teacher" flush>
                <div class="px-4 py-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        {{-- Dropdowns kiri --}}
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="flex-1 min-w-0">
                                <flux:select wire:model.live="kelasId" size="sm" placeholder="Semua Kelas">
                                    <flux:select.option value="">Semua Kelas</flux:select.option>
                                    @foreach ($this->kelasList as $kelas)
                                        <flux:select.option value="{{ $kelas['id'] }}">Kelas {{ $kelas['label'] }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="flex-1 min-w-0">
                                <flux:select wire:model.live="mapelId" size="sm" placeholder="Semua Mapel">
                                    <flux:select.option value="">Semua Mapel</flux:select.option>
                                    @foreach ($this->mapelList as $mapel)
                                        <flux:select.option value="{{ $mapel['id'] }}">{{ $mapel['label'] }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>

                        {{-- Button group kanan --}}
                        <x-ui.segmented class="self-start sm:self-auto w-full sm:w-auto">
                            @foreach (['semester' => 'Semester ini', 'bulan' => 'Bulan ini', 'minggu' => 'Minggu ini'] as $value => $label)
                                <button wire:click="$set('rentangWaktu', '{{ $value }}')" type="button"
                                    class="flex-1 sm:flex-none px-3 py-1.5 text-xs font-medium transition-colors border-r border-slate-200 dark:border-slate-700 last:border-r-0
                                            {{ $rentangWaktu === $value
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </x-ui.segmented>
                    </div>
                </div>
            </x-ui.card>

            @include('livewire.teacher.partials.dashboard-charts')
        </div>

        {{-- ── KOLOM KANAN: Sidebar 2 Card ───────────────────────────────── --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Sidebar Card 1: Mata Pelajaran Teraktif --}}
            <x-ui.card variant="teacher" title="Mata Pelajaran Diampu" flush>
                <div class="grid gap-3 p-3">
                    @forelse($this->keaktifanPerKelas as $mapel)
                        @php
                            $pl = match (true) {
                                $mapel['avg'] >= 3.5 => 'Sangat Aktif',
                                $mapel['avg'] >= 2.5 => 'Aktif',
                                $mapel['avg'] >= 1.5 => 'Cukup',
                                default => 'Pasif',
                            };
                            $bc = match ($pl) {
                                'Sangat Aktif' => 'green',
                                'Aktif' => 'blue',
                                'Cukup' => 'amber',
                                'Pasif' => 'red',
                            };
                            $kh = $mapel['kehadiran_pct'] ?? 0;
                            $ta = $mapel['total_aktivitas'] ?? 0;
                        @endphp
                        <div class="border border-slate-200 dark:border-slate-700/90 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 bg-blue-100 dark:bg-blue-900/60 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <flux:icon name="building-library"
                                        class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $mapel['kelas'] }} — {{ $mapel['mapel'] }}
                                    </p>
                                    <div
                                        class="flex items-center gap-1 mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        <flux:icon name="user-group" class="w-3.5 h-3.5" />
                                        <span>{{ $mapel['siswa_count'] ?? 0 }} Siswa</span>
                                    </div>
                                </div>
                                <flux:badge size="sm" class="ml-auto shrink-0">{{ $ta }} Aktivitas
                                </flux:badge>
                            </div>
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Kehadiran</span>
                                    <span
                                        class="font-semibold text-slate-800 dark:text-white">{{ $kh }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                                        x-data="{ w: @js(min($kh, 100)) }" x-bind:style="`width: ${w}%`"></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Keaktifan</span>
                                    <flux:badge color="{{ $bc }}" size="sm">{{ $pl }}
                                    </flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="border border-dashed border-slate-200 dark:border-slate-700/90 rounded-lg p-4 text-center">
                            <flux:icon name="book-open"
                                class="w-5 h-5 mx-auto text-slate-300 dark:text-slate-600 mb-1" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada mata pelajaran diampu.</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            {{-- Sidebar Card 2: Panduan Indikator Keaktifan --}}
            <x-ui.card variant="teacher" title="Indikator Keaktifan" subtitle="Panduan" flush>
                <div class="px-4 py-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ([['color' => 'bg-emerald-500', 'label' => 'Sangat Aktif', 'textColor' => 'text-emerald-600 dark:text-emerald-400', 'desc' => 'Proaktif, memimpin diskusi, sering bertanya.'], ['color' => 'bg-blue-500', 'label' => 'Aktif', 'textColor' => 'text-blue-600 dark:text-blue-400', 'desc' => 'Fokus, merespons pertanyaan dengan baik.'], ['color' => 'bg-amber-400', 'label' => 'Cukup', 'textColor' => 'text-amber-600 dark:text-amber-400', 'desc' => 'Mendengarkan, jarang mengambil inisiatif.'], ['color' => 'bg-rose-500', 'label' => 'Pasif', 'textColor' => 'text-rose-600 dark:text-rose-400', 'desc' => 'Kurang fokus, pasif, atau tidak merespons.']] as $item)
                        <div class="flex items-start gap-3 py-2.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }} shrink-0 mt-0.5"></span>
                            <div>
                                <span
                                    class="text-xs font-semibold {{ $item['textColor'] }}">{{ $item['label'] }}</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                    {{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
